<?php

namespace App\Services\Dashboard;

use App\Models\Role;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Pagination\LengthAwarePaginator;

class UserManagementService
{
    public function __construct(private readonly AuditLogService $auditLog) {}

    public function list(): LengthAwarePaginator
    {
        return User::with('role')->orderByDesc('created_at')->paginate(20);
    }

    public function toggleActive(User $user, User $actor): void
    {
        if ($user->id === $actor->id) {
            abort(422, 'Tidak bisa menonaktifkan akun sendiri.');
        }

        $user->update(['is_active' => ! $user->is_active]);

        $this->auditLog->record(
            $user->is_active ? 'dashboard.user_activated' : 'dashboard.user_deactivated',
            ($user->is_active ? 'Mengaktifkan' : 'Menonaktifkan') . " user \"{$user->email}\"",
            $user,
        );
    }

    public function assignRole(User $user, int $roleId): void
    {
        $role = Role::findOrFail($roleId);
        $user->update(['role_id' => $roleId]);

        $this->auditLog->record('dashboard.user_role_changed', "Role user \"{$user->email}\" diubah jadi \"{$role->name}\"", $user);
    }

    public function changeUserPassword(User $user, string $newPassword): void
    {
        $user->update(['password' => bcrypt($newPassword)]);

        $this->auditLog->record('dashboard.user_password_changed', "Password user \"{$user->email}\" diubah oleh superadmin", $user);
    }
}
