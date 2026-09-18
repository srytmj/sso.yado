<?php

namespace App\Providers;

use App\Services\Dashboard\SettingsService;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class SettingsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Ini jalan di SETIAP boot aplikasi, termasuk artisan command yang tidak
        // butuh DB sama sekali (key:generate, package:discover saat composer install
        // di dalam Docker build - belum ada koneksi DB sama sekali di titik itu).
        // Kalau DB belum siap/belum ada, diamkan saja dan biarkan config fallback ke .env.
        try {
            if (!Schema::hasTable('settings')) {
                return;
            }

            app(SettingsService::class)->applyToRuntimeConfig();
        } catch (\Throwable) {
            return;
        }
    }
}
