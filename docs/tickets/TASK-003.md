# TASK-003: Register & Login Lokal (Blade)

Status: Done
Priority: High
Created: 2026-07-20 20:00
Request: Buat flow register dan login berbasis session menggunakan Blade. Ini adalah entry point user sebelum OAuth flow dimulai. Setelah login berhasil, user di-redirect ke intended URL (biasanya kembali ke `/oauth/authorize`).

Depends on: TASK-002

---

## DEV Response

**Register:**
- [x] `RegisterController`: method `show()` (GET /register) dan `store()` (POST /register)
- [x] `RegisterService`: assign role default "user", hash password via model cast, simpan user
- [x] Blade view `resources/views/auth/register.blade.php`: form name, username, email, password, password_confirmation
- [x] Validasi: name required, username unique/regex `[a-z0-9_]+`, email unique, password min 8 confirmed
- [x] Setelah register: auto-login dan redirect ke intended atau `/`

**Login:**
- [x] `LoginController`: method `show()` (GET /login) dan `store()` (POST /login)
- [x] `LoginService`: attempt auth, cek `is_active`, throw `ValidationException` jika gagal
- [x] Blade view `resources/views/auth/login.blade.php`: form email, password, remember me
- [x] Jika `is_active = false`: tolak login dengan pesan "Akun Anda tidak aktif."
- [x] Rate limiting: `throttle:5,1` pada POST /login
- [x] Setelah login: redirect ke `redirect()->intended('/')`
- [x] CSRF middleware aktif di semua route web (Laravel default)

**Logout:**
- [x] `LogoutController`: `destroy()` - `Auth::logout()`, invalidate session, regenerate CSRF token, redirect ke `/login`

**Routes (web.php):**
- [x] `GET /login`, `POST /login` (throttle:5,1)
- [x] `GET /register`, `POST /register`
- [x] `POST /logout` → `LogoutController@destroy` (middleware auth)
- [x] Middleware `guest` untuk login dan register

**Layout:**
- [x] `resources/views/layouts/auth.blade.php` - base layout dengan Tailwind CDN

---

## QA Response

> **Method**: Static code review.

- [x] Register dengan data valid → user tersimpan, session terbentuk, redirect ke `/` - `RegisterController::store()` memanggil `RegisterService::register()`, `Auth::login()`, `session()->regenerate()`, `redirect()->intended('/')` ✓
- [x] Register dengan email duplikat → validation error - `unique:users,email` rule di `RegisterController:26` ✓
- [x] Register dengan username yang sudah ada → validation error - `unique:users,username` rule di `RegisterController:25` ✓
- [x] Login dengan kredensial benar → session terbentuk, redirect ke intended URL - `LoginService::attempt()` + `redirect()->intended('/')` ✓
- [x] Login dengan password salah → error message, tidak ada session - `Auth::attempt()` fail → `ValidationException` di `LoginService:18` ✓
- [x] Login dengan user `is_active = false` → error "Akun Anda tidak aktif." - VERIFIED di `LoginService:26-30`: Auth::logout() dipanggil, ValidationException dengan pesan tepat ✓
- [x] POST /login lebih dari 5x gagal dalam 1 menit → HTTP 429 - `throttle:5,1` di `routes/web.php:10` ✓
- [x] CSRF token missing di form POST → HTTP 419 - Laravel default CSRF middleware aktif ✓
- [x] Akses `/login` saat sudah login → redirect - `middleware('guest')` di route group `routes/web.php:8` ✓
- [x] POST /logout → session hancur, redirect ke `/login` - `LogoutController::destroy()`: `Auth::logout()`, `invalidate()`, `regenerateToken()`, `redirect()->route('login')` ✓

**Additional checks:**
- [x] Controller thin - logic di LoginService & RegisterService, bukan Controller ✓
- [x] Tidak ada `dd()` atau debug code ✓
- [x] `POST /register` tidak punya throttle - SRS tidak mensyaratkan, aman untuk saat ini ✓

**Status: Done**
