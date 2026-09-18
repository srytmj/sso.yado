<?php

use App\Http\Controllers\Account\AccountController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\InviteController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Auth\TwoFactorChallengeController;
use App\Http\Controllers\Auth\TwoFactorController;
use App\Http\Controllers\Auth\VerificationController;
use App\Http\Controllers\Dashboard\ApplicationController;
use App\Http\Controllers\Dashboard\AuditLogController;
use App\Http\Controllers\Dashboard\DashboardController;
use App\Http\Controllers\Dashboard\LogController;
use App\Http\Controllers\Dashboard\SessionController;
use App\Http\Controllers\Dashboard\SettingsController;
use App\Http\Controllers\Dashboard\UserManagementController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

// Public - always accessible, navbar changes based on auth state
Route::get('/', [HomeController::class, 'index'])->name('home');

// Health check buat monitoring eksternal (landing page Yado) - publik, tanpa
// auth, query DB minimal (SELECT 1) supaya responsnya cepat. Beda dari /up bawaan
// Laravel: endpoint ini bentuk JSON-nya khusus buat dikonsumsi dashboard monitoring.
Route::get('/health', [HealthController::class, 'check'])->name('health')->middleware('throttle:60,1');

// Guest-only
Route::middleware(['guest', 'throttle:60,1'])->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:5,1');

    Route::get('/register', [RegisterController::class, 'show'])->name('register');
    Route::post('/register', [RegisterController::class, 'store'])->middleware('throttle:5,1');

    Route::get('/register/invite', [InviteController::class, 'show'])->name('invite.show');
    Route::post('/register/invite', [InviteController::class, 'store'])->name('invite.store')->middleware('throttle:5,1');

    Route::get('/forgot-password', [ForgotPasswordController::class, 'show'])->name('password.request');
    Route::post('/forgot-password', [ForgotPasswordController::class, 'store'])->name('password.email')->middleware('throttle:3,1');
    Route::get('/reset-password', [ResetPasswordController::class, 'show'])->name('password.reset');
    Route::post('/reset-password', [ResetPasswordController::class, 'store'])->name('password.update')->middleware('throttle:5,1');
});

// Two-factor challenge - user sudah lolos password tapi belum lolos TOTP, jadi bukan guest
// dan bukan authenticated penuh. Session key 'mfa_pending_user_id' yang jadi penanda.
Route::middleware('throttle:5,1')->group(function () {
    Route::get('/two-factor-challenge', [TwoFactorChallengeController::class, 'show'])->name('two-factor.challenge');
    Route::post('/two-factor-challenge', [TwoFactorChallengeController::class, 'verify'])->name('two-factor.verify');
});

// Authenticated - throttle di-key per-user (bukan per-IP) karena semua route ini butuh login
Route::middleware(['auth', 'throttle:120,1'])->group(function () {
    Route::match(['get', 'post'], '/logout', [LogoutController::class, 'destroy'])->name('logout');

    // Email verification - TIDAK di-gate dengan 'verified' karena ini justru rute untuk
    // user yang belum verified supaya bisa verifikasi/minta kirim ulang.
    Route::get('/email/verify', [VerificationController::class, 'notice'])->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', [VerificationController::class, 'verify'])
        ->middleware(['signed', 'throttle:6,1'])->name('verification.verify');
    Route::post('/email/verification-notification', [VerificationController::class, 'resend'])
        ->middleware('throttle:3,1')->name('verification.send');

    // Semua route di bawah ini butuh email terverifikasi
    Route::middleware('verified')->group(function () {
        // My Account
        Route::prefix('account')->name('account.')->group(function () {
            Route::get('/', [AccountController::class, 'show'])->name('show');
            Route::post('/password', [AccountController::class, 'changePassword'])->name('password')->middleware('throttle:5,1');
            Route::post('/theme', [AccountController::class, 'updateTheme'])->name('theme')->middleware('throttle:20,1');
            Route::post('/locale', [AccountController::class, 'updateLocale'])->name('locale')->middleware('throttle:10,1');
            Route::post('/avatar', [AccountController::class, 'updateAvatar'])->name('avatar')->middleware('throttle:10,1');

            Route::get('/sessions', [AccountController::class, 'sessions'])->name('sessions');
            Route::delete('/sessions/{tokenId}', [AccountController::class, 'revokeSession'])->name('sessions.revoke');
            Route::delete('/sessions', [AccountController::class, 'revokeAllSessions'])->name('sessions.revoke-all');
            Route::delete('/devices/{sessionId}', [AccountController::class, 'revokeDevice'])->name('devices.revoke');
            Route::delete('/devices', [AccountController::class, 'revokeAllDevices'])->name('devices.revoke-all');

            Route::prefix('two-factor')->name('two-factor.')->group(function () {
                Route::get('/', [TwoFactorController::class, 'show'])->name('show');
                Route::post('/enable', [TwoFactorController::class, 'enable'])->name('enable')->middleware('throttle:5,1');
                Route::post('/confirm', [TwoFactorController::class, 'confirm'])->name('confirm')->middleware('throttle:5,1');
                Route::delete('/', [TwoFactorController::class, 'disable'])->name('disable')->middleware('throttle:5,1');
            });
        });

        // Dashboard - superadmin only
        Route::middleware('superadmin')->prefix('dashboard')->name('dashboard.')->group(function () {
            Route::get('/', [DashboardController::class, 'index'])->name('index');

            Route::prefix('applications')->name('applications.')->group(function () {
                Route::get('/', [ApplicationController::class, 'index'])->name('index');
                Route::get('/create', [ApplicationController::class, 'create'])->name('create');
                Route::post('/', [ApplicationController::class, 'store'])->name('store');
                Route::get('/{application}', [ApplicationController::class, 'show'])->name('show');
                Route::get('/{application}/secret', [ApplicationController::class, 'revealSecret'])->name('secret');
                Route::patch('/{application}', [ApplicationController::class, 'update'])->name('update');
                Route::delete('/{application}', [ApplicationController::class, 'destroy'])->name('destroy');
            });

            Route::get('/sessions', [SessionController::class, 'index'])->name('sessions.index');
            Route::delete('/sessions/{id}', [SessionController::class, 'destroy'])->name('sessions.destroy');

            Route::get('/logs', [LogController::class, 'index'])->name('logs.index');
            Route::get('/audit-log', [AuditLogController::class, 'index'])->name('audit-log.index');

            Route::prefix('settings')->name('settings.')->group(function () {
                Route::get('/', [SettingsController::class, 'index'])->name('index');
                Route::post('/mail', [SettingsController::class, 'updateMail'])->name('mail');
                Route::post('/avatar-storage', [SettingsController::class, 'updateAvatarStorage'])->name('avatar-storage');
                Route::post('/test-email', [SettingsController::class, 'sendTestEmail'])->name('test-email')->middleware('throttle:5,1');
            });

            Route::prefix('users')->name('users.')->group(function () {
                Route::get('/', [UserManagementController::class, 'index'])->name('index');
                Route::patch('/{user}/toggle-active', [UserManagementController::class, 'toggleActive'])->name('toggle-active');
                Route::patch('/{user}/role', [UserManagementController::class, 'assignRole'])->name('role');
                Route::patch('/{user}/password', [UserManagementController::class, 'changePassword'])->name('password');
                Route::get('/invite', [UserManagementController::class, 'invite'])->name('invite');
                Route::post('/invite', [UserManagementController::class, 'sendInvite'])->name('send-invite')->middleware('throttle:10,1');
            });
        });
    });
});
