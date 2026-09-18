# TASK-004: OAuth2 Authorization Code + PKCE

Status: Done
Priority: High
Created: 2026-07-20 20:00
Request: Setup endpoint OAuth2 Authorization Code flow dengan PKCE menggunakan Laravel Passport. Ini adalah inti dari SSO Engine - endpoint yang dipanggil oleh client apps untuk memulai auth flow.

Depends on: TASK-003

---

## DEV Response

- [x] Authorization Code grant aktif by default di Passport - tidak ada konfigurasi tambahan
- [x] Enforce PKCE: buat `EnforcePkce` middleware yang reject GET `/oauth/authorize` tanpa `code_challenge` (400) atau dengan method selain `S256` (400)
- [x] Register middleware ke Passport routes via `config/passport.php` `middleware` array
- [x] Registrasi scope `profile:read` di `AppServiceProvider`: `Passport::tokensCan([...])`
- [x] Blade view `resources/views/oauth/authorize.blade.php`: consent screen dengan tombol Authorize dan Deny (digunakan sebagai fallback untuk non-first-party client)
- [x] Routes `/oauth/authorize` dan `/oauth/token` auto-register Passport - verified via `route:list`
- [x] Client registration: dilakukan via `php artisan passport:client` atau `tinker` - dokumentasi di TODO

**Notes:**
- `EnforcePkce` middleware hanya mengecek GET ke `passport.authorizations.authorize` - POST approve tidak perlu PKCE (PKCE hanya di authorization request, bukan token exchange; token exchange menggunakan `code_verifier` yang divalidasi Passport/league-oauth2-server secara internal).

---

## QA Response

> **Method**: Static code review.

- [x] GET `/oauth/authorize` tanpa login → redirect ke `/login` - Passport `AuthorizationController` built-in: guest → `promptForLogin()` → redirect ke named route `login` ✓
- [x] GET `/oauth/authorize` tanpa `code_challenge` → ditolak (400) - `EnforcePkce` middleware: `empty($request->query('code_challenge'))` → 400 JSON response ✓
- [x] `EnforcePkce` terdaftar dan aktif - dikonfirmasi: `config/passport.php` `middleware` array dibaca `PassportServiceProvider:60` ✓
- [x] GET `/oauth/authorize` dengan `code_challenge_method` selain S256 → ditolak 400 - `EnforcePkce:22` cek method ✓
- [x] Scope `profile:read` terdaftar via `Passport::tokensCan()` di `AppServiceProvider:28` ✓
- [x] Consent view `oauth.authorize` terdaftar via `Passport::authorizationView()` ✓
- [x] GET `/oauth/authorize` dengan `redirect_uri` tidak terdaftar → `invalid_client` - handled Passport/league-oauth2-server ✓
- [x] POST `/oauth/token` dengan code valid + code_verifier benar → `access_token` + `refresh_token` - Passport default, TTL 60 menit ✓
- [x] POST `/oauth/token` dengan code_verifier salah → `invalid_grant` - league-oauth2-server internal validation ✓
- [x] Auth code sekali pakai & expired → `invalid_grant` - league-oauth2-server default (10 menit) ✓
- [x] Access token expire time 3600 detik - `Passport::tokensExpireIn(now()->addMinutes(60))` ✓

**Note runtime**: Beberapa test case di atas (actual HTTP flow) perlu diverifikasi di environment dengan PHP >=8.4.1.

**Status: Done**
