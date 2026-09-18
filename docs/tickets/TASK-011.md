# TASK-011: My Account - Info Akun & Ganti Password

Status: Done
Priority: Medium
Created: 2026-07-20 21:00
Request: Buat halaman self-service /account untuk user yang sudah login. Berisi info akun (name, username, email, avatar, role, status verifikasi) dan form ganti password. Ini adalah area personal user, bukan dashboard admin.

Depends on: TASK-003

---

## DEV Response

- [x] Route group `/account` di `routes/web.php` dengan middleware `auth` - `routes/web.php:41`
- [x] `GET /account` → `AccountController::show()` - pass `$user = $request->user()` ke view - `app/Http/Controllers/Account/AccountController.php`
- [x] `POST /account/password` → `AccountController::changePassword()` - validasi + delegate ke `ChangePasswordAction`
- [x] `ChangePasswordAction::execute()` - `Hash::check()` current password, cek tidak sama dengan new, `$user->update(['password' => $newPassword])` (model cast hashed) - `app/Actions/Account/ChangePasswordAction.php`
- [x] `resources/views/account/show.blade.php` - avatar initials (inisial dari `$user->name`), info grid (email, role badge, email verification badge, account status badge), form ganti password 3 field dengan toggle visibility (Alpine.js x-data)
- [x] Layout `resources/views/layouts/account.blade.php` - sidebar desktop + mobile horizontal tab nav (My Account / Active Sessions), sign out form

---

## QA Response

> **Method**: Static code review. DEV Response belum di-update (checklist `[ ]`), tapi implementasi sudah ada.

- [x] GET `/account` tanpa login → redirect ke `/login` - route ada di `auth` middleware group (`routes/web.php:41`) ✓
- [x] GET `/account` dengan login → tampil info user yang sedang login - `AccountController::show()` pass `$request->user()` ke view ✓
- [x] View `account/show.blade.php` exists ✓
- [x] Ganti password dengan `current_password` benar + `new_password` valid → update - `ChangePasswordAction::execute()`: `Hash::check()` lulus → `$user->update(['password' => $newPassword])` (model cast hash) ✓
- [x] Ganti password dengan `current_password` salah → ValidationException "Password saat ini tidak sesuai." ✓ (`ChangePasswordAction:13-17`)
- [x] `new_password` sama dengan `current_password` → ValidationException "Password baru tidak boleh sama..." ✓ (`ChangePasswordAction:19-23`)
- [x] `new_password` min:8 - validasi di `AccountController:28` ✓
- [x] Controller thin - delegate ke `ChangePasswordAction` ✓
- [x] Tidak ada dd() ✓

**Status: Done**
