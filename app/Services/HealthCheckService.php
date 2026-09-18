<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class HealthCheckService
{
    /**
     * Cek kesehatan service ini buat monitoring eksternal (landing page yado).
     * Sengaja cuma query ringan (`SELECT 1`) — cukup buat pastikan koneksi DB hidup,
     * tanpa bikin endpoint ini jadi lambat/berat.
     *
     * @return array{status: string, service: string, timestamp?: string, error?: string}
     */
    public function check(): array
    {
        $service = Str::slug(config('app.name', 'sso-engine'));

        try {
            DB::select('select 1');

            return [
                'status' => 'ok',
                'service' => $service,
                'timestamp' => now()->toIso8601String(),
            ];
        } catch (Throwable $e) {
            return [
                'status' => 'error',
                'service' => $service,
                'error' => $e->getMessage(),
            ];
        }
    }
}
