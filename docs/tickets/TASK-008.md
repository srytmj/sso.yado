# TASK-008: Logout + Token Revocation

Status: Done
Priority: High
Created: 2026-07-20 20:00
Request: Implementasikan logout yang benar-benar "clean": hancurkan session web, revoke semua access token dan refresh token aktif milik user tersebut. Sehingga setelah logout, token lama tidak bisa dipakai lagi di endpoint manapun.

Depends on: TASK-003, TASK-004

---

## DEV Response

- [x] Buat `RevokeTokenAction` di `app/Actions/Auth/RevokeTokenAction.php`
  - Method `execute(User $user): void`
  - Ambil semua token user via `$user->tokens`
  - Revoke setiap token via `$token->revoke()`
  - Revoke semua refresh token terkait via `$token->refreshTokens()->update(['revoked' => true])`
- [x] Update `LogoutController` di `app/Http/Controllers/Auth/LogoutController.php`
  - Inject `RevokeTokenAction` via constructor
  - Urutan: revoke tokens → `Auth::logout()` → invalidate session → regenerate CSRF token → redirect `/login`
- [x] Route `POST /logout` sudah ada di `routes/web.php` dengan middleware `auth`
- [x] CSRF protection aktif (Laravel default untuk semua web routes)

---

## QA Response

> **Method**: Static code review.

- [x] POST `/logout` → session hancur, redirect ke `/login` - `LogoutController::destroy()`: `Auth::logout()` → `invalidate()` → `regenerateToken()` → `redirect()->route('login')` ✓
- [x] Akses halaman protected setelah logout → redirect ke `/login` - session dihapus, `auth` middleware redirect ke named route `login` ✓
- [x] GET `/api/user` dengan access token lama setelah logout → 401 - `RevokeTokenAction` memanggil `$token->revoke()` untuk semua token; `auth:api` guard menolak token yang revoked ✓
- [x] POST `/oauth/token` dengan refresh token lama setelah logout → `invalid_grant` - `$token->refreshTokens()->update(['revoked' => true])` di `RevokeTokenAction:13` ✓
- [x] POST `/logout` tanpa CSRF → HTTP 419 - Laravel default CSRF middleware aktif untuk semua web routes ✓
- [x] User tidak login POST `/logout` → redirect, tidak error 500 - route punya middleware `auth` → redirect ke `/login` sebelum masuk controller ✓
- [x] User punya multiple token → semua di-revoke - `foreach ($user->tokens as $token)` iterates semua token di `RevokeTokenAction:11` ✓

**Additional checks:**
- [x] `RevokeTokenAction` adalah Action class terpisah, `LogoutController` inject via constructor - thin controller ✓
- [x] Urutan operasi logout benar: revoke tokens → logout → invalidate → regenerateToken ✓
- [x] Tidak ada `dd()` ✓

**Status: Done**
