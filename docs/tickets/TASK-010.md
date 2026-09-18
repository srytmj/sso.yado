# TASK-010: Landing Page (/)

Status: Done
Priority: Medium
Created: 2026-07-20 21:00
Request: Buat halaman publik di root URL (/) yang menjelaskan SSO Engine yado.my.id. Halaman ini yang pertama dilihat visitor — bukan form login, bukan dashboard. Harus accessible tanpa login. Tautan ke /login dan /register.

---

## DEV Response

- [x] Route `GET /` di `routes/web.php:17` — tanpa middleware auth, accessible publik
- [x] `HomeController::index()` — redirect authenticated user (superadmin → `dashboard.index`, user → `account.show`), guest → return view `home` — `app/Http/Controllers/HomeController.php`
- [x] `resources/views/home.blade.php` — extends `layouts.public`, hero + 4 feature cards (SSO, OAuth2+PKCE, Self-service register, Secure Token)
- [x] `resources/views/layouts/public.blade.php` — base layout pure Tailwind + Alpine.js via Vite, navbar conditional (Dashboard/My Account/Sign Out jika login, Sign In/Register jika guest)
- [x] CTA conditional: jika login sebagai superadmin → "Buka Dashboard", jika login user biasa → "Lihat Akun Saya", jika guest → "Sign In" + "Daftar Sekarang"
- [x] Jika user sudah login → redirect ke `account.show` (user) atau `dashboard.index` (superadmin) — fixed via BUG-004, `HomeController.php`

---

## QA Response

> **Method**: Static code review.

- [x] GET `/` tanpa login → landing page tampil, tidak redirect ✓ (`routes/web.php:17`)
- [x] GET `/` saat login sebagai user → redirect ke `/account` ✓ — fixed via BUG-004
- [x] GET `/` saat login sebagai superadmin → redirect ke `/dashboard` ✓ — fixed via BUG-004
- [x] Tombol "Login" → `route('login')` ✓ (`home.blade.php`)
- [x] Tombol "Daftar Sekarang" → `route('register')` ✓ (`home.blade.php`)
- [x] Tidak ada auth middleware pada route `/` ✓
- [x] Tidak ada data sensitif di halaman ✓

**Status: Done**
