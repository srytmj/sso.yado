<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MigrateToPgsql extends Command
{
    protected $signature = 'db:migrate-to-pgsql {--force : Lewati konfirmasi}';

    protected $description = 'Salin seluruh data dari koneksi MySQL lama (MYSQL_LEGACY_*) ke koneksi Postgres aktif (pgsql)';

    /**
     * Urutan wajib FK-safe: parent sebelum child.
     */
    private const TABLES = [
        'roles',
        'users',
        'oauth_clients',
        'oauth_access_tokens',
        'oauth_refresh_tokens',
        'oauth_auth_codes',
        'oauth_device_codes',
        'user_invitations',
        'settings',
        'audit_logs',
        'password_reset_tokens',
        'sessions',
    ];

    /**
     * Kolom boolean per tabel - MySQL PDO mengembalikan "0"/"1" string, kolom
     * boolean Postgres butuh true/false asli.
     */
    private const BOOLEAN_COLUMNS = [
        'users' => ['is_active'],
        'oauth_clients' => ['revoked'],
        'oauth_access_tokens' => ['revoked'],
        'oauth_refresh_tokens' => ['revoked'],
        'oauth_auth_codes' => ['revoked'],
        'oauth_device_codes' => ['revoked'],
    ];

    /**
     * Tabel dengan PK integer auto-increment yang sequence-nya perlu di-reset
     * setelah bulk insert (id eksplisit dilewatkan, bukan lewat nextval).
     */
    private const SERIAL_PK_TABLES = ['roles', 'users', 'user_invitations', 'settings', 'audit_logs'];

    public function handle(): int
    {
        if (config('database.default') !== 'pgsql') {
            $this->error('DB_CONNECTION aktif bukan pgsql. Set DB_CONNECTION=pgsql di .env dulu sebelum migrasi.');
            return self::FAILURE;
        }

        try {
            DB::connection('mysql')->getPdo();
        } catch (\Throwable $e) {
            $this->error('Tidak bisa konek ke koneksi mysql (legacy). Cek MYSQL_LEGACY_* di .env: '.$e->getMessage());
            return self::FAILURE;
        }

        $counts = [];
        foreach (self::TABLES as $table) {
            $counts[$table] = Schema::connection('mysql')->hasTable($table)
                ? DB::connection('mysql')->table($table)->count()
                : 0;
        }

        $this->table(['Tabel', 'Baris di MySQL lama'], collect($counts)->map(fn ($n, $t) => [$t, $n])->values());

        if (!$this->option('force') && !$this->confirm('Lanjutkan? Ini akan MENGHAPUS ISI tabel di atas pada database Postgres tujuan lalu menggantinya dengan data dari MySQL.')) {
            $this->warn('Dibatalkan.');
            return self::SUCCESS;
        }

        DB::connection('pgsql')->statement(
            'TRUNCATE TABLE '.implode(', ', self::TABLES).' RESTART IDENTITY CASCADE'
        );

        foreach (self::TABLES as $table) {
            if ($counts[$table] === 0) {
                $this->line("- {$table}: kosong, dilewati.");
                continue;
            }

            $migrated = 0;
            DB::connection('mysql')->table($table)->orderBy(
                Schema::connection('mysql')->getColumnListing($table)[0]
            )->chunk(500, function ($rows) use ($table, &$migrated) {
                $booleanColumns = self::BOOLEAN_COLUMNS[$table] ?? [];

                $prepared = $rows->map(function ($row) use ($booleanColumns) {
                    $row = (array) $row;
                    foreach ($booleanColumns as $column) {
                        if (array_key_exists($column, $row) && $row[$column] !== null) {
                            $row[$column] = (bool) $row[$column];
                        }
                    }
                    return $row;
                })->all();

                DB::connection('pgsql')->table($table)->insert($prepared);
                $migrated += count($prepared);
            });

            $this->line("- {$table}: {$migrated} baris dipindahkan.");

            if (in_array($table, self::SERIAL_PK_TABLES, true)) {
                DB::connection('pgsql')->statement(
                    "SELECT setval(pg_get_serial_sequence('{$table}', 'id'), COALESCE((SELECT MAX(id) FROM {$table}), 1), (SELECT MAX(id) FROM {$table}) IS NOT NULL)"
                );
            }
        }

        $this->info('Migrasi selesai. Verifikasi jumlah baris dan coba login sebelum menghapus database MySQL lama.');

        return self::SUCCESS;
    }
}
