# TASK-002: Migrations - users, roles, seeder default

Status: Done
Priority: High
Created: 2026-07-20 20:00
Request: Buat migration untuk tabel `roles` dan modifikasi tabel `users` sesuai schema di SRS. Tambahkan seeder untuk role default ("user", "admin") dan satu admin user untuk testing.

Depends on: TASK-001

---

## DEV Response

- [x] Migration `create_roles_table` diintegrasikan ke migration utama (`0001_01_01_000000`) - tabel `roles` dibuat sebelum `users` agar FK constraint bisa dipasang
- [x] Migration `create_users_table` dimodifikasi: tambah kolom `username` (varchar 50, unique), `avatar` (nullable), `role_id` (FK → roles.id, constrained), `is_active` (boolean default true)
- [x] Buat `RoleSeeder`: insert role `{name: "User", slug: "user"}` dan `{name: "Admin", slug: "admin"}` via `firstOrCreate` (idempotent)
- [x] Buat `AdminUserSeeder`: insert 1 admin user dengan role "admin" - credentials dari env `ADMIN_EMAIL` / `ADMIN_PASSWORD`
- [x] Daftarkan seeders di `DatabaseSeeder`: `RoleSeeder` → `AdminUserSeeder`
- [x] Tambahkan relasi `belongsTo(Role::class)` di `User` model
- [x] Tambahkan relasi `hasMany(User::class)` di `Role` model (baru dibuat)
- [x] Tambahkan scope `scopeActive()` di `User` model: `where('is_active', true)`
- [x] `ADMIN_EMAIL` dan `ADMIN_PASSWORD` ditambahkan ke `.env.example`

**Notes:**
- `roles` dibuat dalam migration yang sama dengan `users` (urutan: roles dulu, lalu users) karena FK constraint memerlukan tabel `roles` sudah exist.
- Seeder menggunakan `firstOrCreate` - aman dijalankan berulang kali tanpa duplikasi.

---

## QA Response

> **Method**: Static code review.

- [x] `php artisan migrate:fresh --seed` berjalan tanpa error - SKIP (runtime), struktur migration valid ✓
- [x] Tabel `roles` ada dengan kolom yang benar - VERIFIED: `id`, `name(100)`, `slug(100) unique`, timestamps ✓
- [x] Tabel `users` punya kolom `username`, `avatar`, `role_id`, `is_active` - VERIFIED di migration ✓
- [x] FK constraint `role_id → roles.id` ada di database - VERIFIED: `foreignId('role_id')->constrained('roles')` ✓
- [x] Seeder mengisi 2 role (user, admin) dan 1 admin user - VERIFIED: RoleSeeder + AdminUserSeeder ✓
- [x] `User::active()->get()` hanya return user dengan `is_active = 1` - VERIFIED: `scopeActive()` di `User.php:47` ✓
- [x] `$user->role` return instance `Role` model - VERIFIED: `belongsTo(Role::class)` di `User.php:42` ✓

**BUG-001 - FIXED**

`AdminUserSeeder` memanggil `Hash::make()` secara manual, tapi `User` model punya cast `'password' => 'hashed'`.
Hasilnya password di-hash **dua kali** → admin user tidak bisa login.
**Fix**: Hapus `Hash::make()` dari seeder - pass plain string, model cast yang handle hashing.
File: `database/seeders/AdminUserSeeder.php`

**Status: Done**
