<?php

use App\Http\Middleware\CheckUserActive;
use App\Http\Middleware\EnsureSuperadmin;
use App\Http\Middleware\HandleOAuthPrompt;
use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Inertia\Inertia;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');

        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            SetLocale::class,
            HandleOAuthPrompt::class,
        ]);

        $middleware->alias([
            'check.user.active' => CheckUserActive::class,
            'superadmin' => EnsureSuperadmin::class,
            'scope' => \Laravel\Passport\Http\Middleware\CheckTokenForAnyScope::class,
            'scopes' => \Laravel\Passport\Http\Middleware\CheckToken::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );

        $exceptions->respond(function (\Symfony\Component\HttpFoundation\Response $response, \Throwable $e, Request $request) {
            if ($request->is('api/*')) {
                return $response;
            }

            $status = $response->getStatusCode();

            if (! app()->environment(['local', 'testing']) && in_array($status, [403, 404, 405, 419, 429, 500, 503], true)) {
                return Inertia::render('Error', ['status' => $status])
                    ->toResponse($request)
                    ->setStatusCode($status);
            }

            return $response;
        });
    })->create();
