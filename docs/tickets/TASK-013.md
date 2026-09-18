# TASK-013: Dashboard Superadmin - Gate & Layout

Status: Done
Priority: Medium
Created: 2026-07-20 21:00
Request: Setup fondasi dashboard superadmin - middleware gate akses role superadmin, layout dashboard, dan halaman index. Ini harus selesai sebelum TASK-014 dan TASK-015 bisa dikerjakan.

Depends on: TASK-002

---

## DEV Response

- [x] Role `superadmin` di `RoleSeeder` - `database/seeders/RoleSeeder.php:14`
- [x] `EnsureSuperadmin` middleware - cek `$request->user()->role?->slug === 'superadmin'` → abort(403) jika bukan - `app/Http/Middleware/EnsureSuperadmin.php`
- [x] Alias `superadmin` didaftarkan di `bootstrap/app.php:21`
- [x] Route group `/dashboard` dengan middleware `['auth', 'superadmin']` di `routes/web.php`
- [x] `DashboardController::index()` - pass `$stats` (users_active, users_total, clients count) + `$clients` list ke view - `app/Http/Controllers/Dashboard/DashboardController.php`
- [x] `resources/views/layouts/dashboard.blade.php` - sidebar fixed dengan nav (Overview, Applications, Users) + inline Heroicons SVG, mobile hamburger menu via Alpine.js, topbar mobile, sign out form
- [x] `resources/views/dashboard/index.blade.php` - stats grid 3 kolom + quick actions card

---

## QA Response

> **Method**: Static code review. DEV Response belum di-update (checklist `[ ]`), tapi implementasi sudah ada.

- [x] GET `/dashboard` tanpa login → redirect ke `/login` - route dalam `auth` + `superadmin` middleware group ✓
- [x] GET `/dashboard` dengan role `user`/`admin` → HTTP 403 - `EnsureSuperadmin`: `role->slug !== 'superadmin'` → abort(403) ✓ (`EnsureSuperadmin.php:15`)
- [x] Superadmin middleware alias terdaftar di `bootstrap/app.php:21` ✓
- [x] `superadmin` role ada di `RoleSeeder:14` ✓
- [x] `DashboardController::index()` passes `stats` (users_active, users_total, clients) dan `clients` list ke view ✓
- [x] View `dashboard/index.blade.php` exists ✓
- [x] Layout `dashboard.blade.php` exists (via views check) ✓
- [x] `EnsureSuperadmin` menggunakan optional chaining `role?->slug` - safe jika user belum punya role ✓

**Status: Done**
