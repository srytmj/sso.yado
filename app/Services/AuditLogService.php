<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request as RequestFacade;

class AuditLogService
{
    public const EVENTS = [
        'auth.login',
        'auth.login_failed',
        'auth.logout',
        'auth.registered',
        'auth.email_verified',
        'auth.password_reset_requested',
        'auth.password_reset_completed',
        'account.password_changed',
        'account.avatar_updated',
        'account.session_revoked',
        'account.device_revoked',
        'account.two_factor_enabled',
        'account.two_factor_disabled',
        'account.two_factor_challenge_failed',
        'dashboard.user_activated',
        'dashboard.user_deactivated',
        'dashboard.user_role_changed',
        'dashboard.user_invited',
        'dashboard.invite_accepted',
        'dashboard.session_revoked',
        'dashboard.application_created',
        'dashboard.application_updated',
        'dashboard.application_deleted',
        'dashboard.application_secret_revealed',
        'dashboard.user_password_changed',
        'settings.mail_updated',
        'settings.avatar_storage_updated',
    ];

    public function record(string $event, string $description, ?User $user = null, array $metadata = []): void
    {
        AuditLog::create([
            'user_id' => $user?->id ?? Auth::id(),
            'actor_id' => Auth::id(),
            'event' => $event,
            'description' => $description,
            'ip_address' => RequestFacade::ip(),
            'user_agent' => RequestFacade::userAgent(),
            'metadata' => $metadata,
            'created_at' => now(),
        ]);
    }

    public function paginate(?string $event, ?string $search, int $page, int $perPage = 25): LengthAwarePaginator
    {
        $query = AuditLog::query()->with(['user', 'actor'])->orderByDesc('created_at');

        if ($event) {
            $query->where('event', $event);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                    ->orWhere('ip_address', 'like', "%{$search}%");
            });
        }

        return $query->paginate($perPage, ['*'], 'page', $page);
    }
}
