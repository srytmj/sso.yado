# TASK-015: Dashboard - Users Management & Invite via Email

Status: Done
Priority: Medium
Created: 2026-07-20 21:00
Request: Buat halaman manajemen user di dashboard superadmin. Superadmin bisa lihat semua user, aktifkan/nonaktifkan akun, assign role, dan invite user baru via email. Invite mengirimkan link satu kali pakai (expired 24 jam) untuk user set password dan complete profile.

Depends on: TASK-013

---

## DEV Response

**User List & Management:**
- [x] `GET /dashboard/users` → `UserManagementController::index()` - `UserManagementService::list()` paginate(20) with('role') - `app/Http/Controllers/Dashboard/UserManagementController.php`
- [x] `PATCH /dashboard/users/{id}/toggle-active` → `UserManagementController::toggleActive()` - safeguard self-deactivation di `UserManagementService::toggleActive()` (abort 422 jika actor = target)
- [x] `PATCH /dashboard/users/{id}/role` → `UserManagementController::assignRole()` - `Role::findOrFail($roleId)` + `$user->update(['role_id'])`
- [x] `UserManagementService` - `list()`, `toggleActive()`, `assignRole()` - `app/Services/Dashboard/UserManagementService.php`

**Invite Flow:**
- [x] Migration `user_invitations` - kolom: `id`, `email`, `token` (unique), `role_id` (FK), `used_at` (nullable), `expires_at`, `created_by` (FK users.id), `timestamps`
- [x] `GET /dashboard/users/invite` → `UserManagementController::invite()` - form email + select role
- [x] `POST /dashboard/users/invite` → `UserManagementController::sendInvite()` - validasi `email unique:users,email`, delegate ke `InviteUserAction`
- [x] `InviteUserAction::execute()` - generate token `Str::random(64)`, simpan ke `user_invitations` expires 24h, kirim `InvitationMail` - `app/Actions/Dashboard/InviteUserAction.php`
- [x] `InvitationMail` - Mailable dengan link `route('invite.show', ['token' => $token])` - `app/Mail/InvitationMail.php`
- [x] `GET /register/invite?token=xxx` → `InviteController::show()` - validasi token via `resolveInvitation()`: ada di DB + `used_at IS NULL` + `expires_at->isFuture()` - `app/Http/Controllers/Auth/InviteController.php`
- [x] `POST /register/invite` → `InviteController::store()` - buat user (email + role dari invitation), `used_at = now()`, auto-login via `Auth::login()`, redirect ke `/account`
- [x] `resources/views/auth/invite.blade.php` - form name, username, disabled email, password + confirm (toggle visibility), error callout jika token invalid, info callout dengan email undangan

---

## QA Response

> **Method**: Static code review. DEV Response belum di-update (checklist `[ ]`), tapi implementasi sudah ada.

- [x] GET `/dashboard/users` → `UserManagementService::list()` dengan paginate(20) + with('role') ✓
- [x] Toggle nonaktifkan → `toggleActive()` check self-deactivation (`user->id === actor->id` → abort 422) ✓ (`UserManagementService:17-19`)
- [x] Superadmin tidak bisa nonaktifkan diri sendiri → safeguard di `toggleActive()` ✓
- [x] Assign role → `assignRole()` pakai `Role::findOrFail($roleId)` + `update(['role_id'])` ✓
- [x] Invite: `InviteUserAction` buat invitation (token 64 char, expires 24h), kirim `InvitationMail` ✓
- [x] Validasi invite email: `unique:users,email` - tidak bisa invite email yang sudah terdaftar ✓ (`UserManagementController:55`)
- [x] GET `/register/invite?token=valid` → `InviteController::show()` memanggil `resolveInvitation()` → `isValid()` = `used_at IS NULL && expires_at->isFuture()` ✓
- [x] Token tidak ada → "Link undangan tidak valid." ✓ (`InviteController:74`)
- [x] Token sudah dipakai → "Link undangan sudah pernah digunakan." ✓ (`InviteController:84`)
- [x] Token expired → "Link undangan telah kedaluwarsa." ✓ (`InviteController:88`)
- [x] Submit valid → `User::create()` dengan email dari invitation, `role_id` dari invitation, `used_at = now()`, auto-login, redirect `/account` ✓
- [x] Invite link tidak bisa dipakai dua kali - `resolveInvitation()` check `isValid()` di `store()` juga ✓
- [x] Dashboard routes dalam `superadmin` middleware group ✓
- [x] Migration `user_invitations` dengan semua kolom yang diperlukan ✓
- [x] Views: dashboard/users/index.blade.php, dashboard/users/invite.blade.php, auth/invite.blade.php exist ✓

**Status: Done**
