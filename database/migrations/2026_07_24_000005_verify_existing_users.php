<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Email verification baru wajib mulai sekarang - user yang sudah ada sebelum
     * fitur ini (dan karenanya belum pernah "diverifikasi") sudah dipercaya sejak
     * awal (dibuat via register/invite/seeder lama), jadi di-backfill terverifikasi
     * supaya tidak ke-lock out oleh middleware `verified` yang baru.
     */
    public function up(): void
    {
        DB::table('users')
            ->whereNull('email_verified_at')
            ->update(['email_verified_at' => now()]);
    }

    public function down(): void
    {
        // Tidak ada cara aman untuk membedakan mana yang genuinely verified vs
        // di-backfill migration ini - sengaja tidak revert apa pun.
    }
};
