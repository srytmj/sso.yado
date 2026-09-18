# TASK-017: Forgot Password via Email (Resend)

Status: Done
Priority: Medium
Created: 2026-07-20 21:30
Request: Implementasikan forgot password flow menggunakan Laravel built-in password reset + Resend sebagai email driver. User input email → dapat link reset via email → klik link → set password baru. Link expired 60 menit, satu kali pakai.

---

## DEV Response

**Setup Resend:**
- [x] `composer require resend/resend-laravel` - installed, package:discover DONE
- [x] `.env.example` diupdate: `MAIL_MAILER=resend`, `MAIL_FROM_ADDRESS=noreply@yado.my.id`, `MAIL_FROM_NAME="SSO Yado.my.id"`, `RESEND_API_KEY=re_xxx`
- [ ] Verifikasi domain `yado.my.id` di Resend dashboard (DNS record via Cloudflare) - runtime step, dilakukan di server

**Password Reset Flow (Laravel built-in):**
- [x] Migration `password_reset_tokens` sudah ada (Laravel default) - confirmed via `config/auth.php:103`
- [x] `ForgotPasswordController` - `show()` GET /forgot-password, `store()` POST /forgot-password via `Password::sendResetLink()` - `app/Http/Controllers/Auth/ForgotPasswordController.php`
- [x] `ResetPasswordController` - `show()` GET /reset-password?token&email, `store()` POST /reset-password via `Password::reset()` + event `PasswordReset` - `app/Http/Controllers/Auth/ResetPasswordController.php`
- [x] Routes di `routes/web.php` (dalam group `guest` middleware):
  - `GET /forgot-password` → `password.request`
  - `POST /forgot-password` → `password.email` (throttle:3,1)
  - `GET /reset-password` → `password.reset`
  - `POST /reset-password` → `password.update`
- [x] `resources/views/auth/forgot-password.blade.php` - form email + success callout via `session('status')`
- [x] `resources/views/auth/reset-password.blade.php` - form password + password_confirmation, token + email hidden
- [x] Link "Lupa password?" di `login.blade.php` → route `password.request`
- [x] `config/auth.php` `passwords.users.expire = 60` - sudah 60 (Laravel default)
- [x] Throttle `throttle:3,1` pada POST `/forgot-password`

**Notes:**
- `Password::reset()` di `ResetPasswordController::store()` menggunakan `$user->forceFill(['password' => $password])` - bypass mutator hashing agar tidak double-hash. Model cast `'password' => 'hashed'` tidak berlaku di `forceFill`, jadi password di-hash manual via `Hash` tidak diperlukan karena `Password::reset()` menerima raw string dan Laravel internal memanggil `$user->save()` setelah closure.
- Wait - koreksi: `forceFill` melewati fillable guard tapi TIDAK melewati model cast. Cast `'password' => 'hashed'` tetap berlaku saat `$user->save()`. Jadi tidak perlu `Hash::make()`. ✓
- `RESEND_API_KEY` harus ada di `.env` production sebelum forgot password bisa di-test runtime.

---

## QA Response

> **Method**: Static code review. Runtime items (email delivery) ditandai SKIP - perlu `RESEND_API_KEY` di server.

- [x] GET `/forgot-password` → `ForgotPasswordController::show()` return view `auth.forgot-password` ✓
- [x] POST `/forgot-password` email terdaftar → `Password::sendResetLink()` ✓; `ForgotPasswordController::store()` always `back()->with('status', ...)` - tidak reveal email exist/not ✓
- [x] POST `/forgot-password` email tidak terdaftar → pesan sukses sama (email enumeration prevented) ✓
- [x] POST `/forgot-password` throttle:3,1 - `routes/web.php:31` ✓
- [x] `ResetPasswordController::show()` pass token + email dari query string ke view ✓
- [x] `ResetPasswordController::store()` validasi: token required, email email, password min:8 confirmed ✓
- [x] Password reset: `$user->forceFill(['password' => $password])` - `forceFill` bypass fillable tapi bukan cast. Cast `hashed` tetap berlaku saat `$user->save()` → tidak double-hash ✓ (DEV note diverifikasi)
- [x] Setelah reset → redirect `route('login')` dengan `status` flash ✓ (`ResetPasswordController:42`)
- [x] Token single-use & expired 60 menit - Laravel `password_reset_tokens` default, `passwords.users.expire = 60` ✓
- [x] Link "Lupa password?" di `login.blade.php:52` → `route('password.request')` ✓
- [x] Routes dalam `guest` middleware group ✓
- [x] SKIP - email delivery (butuh RESEND_API_KEY + domain verification di Resend)

**Status: Done** (runtime email test pending server setup)
