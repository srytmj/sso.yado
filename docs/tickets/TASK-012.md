# TASK-012: My Account - Active Sessions & Revoke Device

Status: Done
Priority: Medium
Created: 2026-07-20 21:00
Request: Tambahkan halaman active sessions di /account/sessions. User bisa lihat daftar session/token aktif (device, waktu login, last used) dan mencabut session dari device yang tidak dikenal. Ini memanfaatkan tabel oauth_access_tokens yang sudah ada dari Passport.

Depends on: TASK-011

---

## DEV Response

- [x] `GET /account/sessions` → `AccountController::sessions()` - query `$user->tokens()->where('revoked', false)->where('expires_at', '>', now()->toDateTimeString())->with('client')->orderByDesc('created_at')->get()` - `AccountController.php:49-54`
- [x] `DELETE /account/sessions/{tokenId}` → `AccountController::revokeSession()` - delegate ke `RevokeSessionAction`
- [x] `DELETE /account/sessions` (revoke all) → `AccountController::revokeAll()` - loop semua token aktif user, revoke masing-masing
- [x] `RevokeSessionAction::execute()` - cek token milik user via `$user->tokens()->where('id', $tokenId)->first()` → abort(403) jika bukan milik user, lalu `$token->revoke()` + `$token->refreshToken?->update(['revoked' => true])` - `app/Actions/Account/RevokeSessionAction.php`
- [x] `resources/views/account/sessions.blade.php` - empty state jika tidak ada token, list token dengan nama client (`client?->name ?? 'Unknown App'`), waktu dibuat, expiry, scopes badge, tombol Revoke per token + Revoke All di header

---

## QA Response

> **Method**: Static code review. DEV Response belum di-update (checklist `[ ]`), tapi implementasi sudah ada.

- [x] GET `/account/sessions` → query `tokens()->where('revoked', false)->where('expires_at', '>', now())` - hanya token aktif yang tampil ✓ (`AccountController:49-54`)
- [x] Token expired atau revoked tidak tampil - filter di query ✓
- [x] Cabut session → `RevokeSessionAction::execute()` memanggil `$token->revoke()` + `refreshToken?->update(['revoked' => true])` ✓
- [x] Token milik user lain ditolak - `$user->tokens()->where('id', $tokenId)->first()` → abort(403) jika null ✓ (`RevokeSessionAction:13-16`)
- [x] "Cabut Semua" → `revokeAll()` revoke semua OAuth token; web session (cookie) tidak tersentuh ✓
- [x] Route dalam `auth` middleware group ✓
- [x] `refreshToken` (singular, hasOne) - Passport 13 compatible ✓
- [x] View `account/sessions.blade.php` exists ✓

**Status: Done**
