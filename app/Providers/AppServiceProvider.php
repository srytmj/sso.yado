<?php

namespace App\Providers;

use App\Models\OAuth\Client;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Passport;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        if (app()->environment('production')) {
            URL::forceScheme('https');
        }

        JsonResource::withoutWrapping();

        Passport::useClientModel(Client::class);

        Passport::tokensCan([
            'profile:read' => 'Read your profile information (name, username, email, avatar, role)',
        ]);

        Passport::tokensExpireIn(now()->addMinutes(60));
        Passport::refreshTokensExpireIn(now()->addDays(30));
        // Auth code TTL defaults to 10 minutes in league/oauth2-server

        Passport::authorizationView('oauth.authorize');

        // Rate limit semua endpoint /oauth/* (token exchange + authorize) per IP
        // untuk cegah DDoS/brute-force ke OAuth flow.
        RateLimiter::for('oauth', function ($request) {
            return Limit::perMinute(30)->by($request->ip());
        });

        // Rate limit login per email + IP (10 percobaan per menit) agar typo wajar tidak langsung terblokir
        RateLimiter::for('login', function ($request) {
            $email = (string) $request->input('email');

            return Limit::perMinute(10)->by($email.'|'.$request->ip());
        });
    }
}
