# TASK-016: Register - Full Name Opsional (Fallback ke Username)

Status: Done
Priority: Low
Created: 2026-07-20 21:30
Request: Ubah field "name" di form register menjadi opsional. Jika user tidak mengisi full name, sistem otomatis menggunakan username sebagai name. Perubahan kecil di validasi dan service layer.

Depends on: TASK-003

---

## DEV Response

- [x] `RegisterController`: ubah validasi `name` dari `required` → `nullable|string|max:255` - `app/Http/Controllers/Auth/RegisterController.php:24`
- [x] `RegisterService`: tambah logic `filled($data['name'] ?? null) ? $data['name'] : $data['username']` - `app/Services/Auth/RegisterService.php:16`
- [x] `resources/views/auth/register.blade.php`: label "Full Name" + badge `<span>` "opsional" (pure Tailwind), placeholder "Kosongkan untuk pakai username"
- [x] Sekalian: redirect post-register ke `/account` (user) atau `/dashboard` (superadmin), konsisten dengan post-login - `RegisterController:32-35`

---

## QA Response

> **Method**: Static code review.

- [x] Register dengan full name diisi → `filled($data['name'] ?? null)` true → simpan `$data['name']` ✓ (`RegisterService:16`)
- [x] Register dengan full name kosong (`name=''`) → `filled('')` false → fallback ke `$data['username']` ✓
- [x] Register dengan full name hanya spasi → `filled('   ')` false (Laravel `blank()` menganggap whitespace-only sebagai blank) → fallback ke username ✓
- [x] Validasi lain tidak terpengaruh - `nullable` hanya mengubah `name`, aturan username/email/password tidak berubah ✓
- [x] View: label "Full Name" + badge "opsional" + placeholder "Kosongkan untuk pakai username" ✓ (`register.blade.php:12-18`)
- [x] Redirect post-register: `route('account.show')` atau `route('dashboard.index')` untuk superadmin ✓ (`RegisterController:36-40`)

**Status: Done**
