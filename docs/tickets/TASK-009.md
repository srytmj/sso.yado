# TASK-009: Login Page Context — App Name Banner

Status: Done
Priority: Medium
Created: 2026-07-20 20:00
Request: Tambahkan context awareness di halaman login. Jika user datang dari OAuth flow (ada client_id di session), tampilkan banner nama aplikasi yang sedang meminta akses. Jika user akses /login langsung tanpa OAuth context, tampilkan notice yang mengarahkan user untuk masuk via aplikasi client.

---

## DEV Response

- [x] `LoginController::show()` — tambah `resolveClientName()` private method:
  - Baca `session('url.intended')` — Passport menyimpan `/oauth/authorize?...` URL di sini saat redirect ke login
  - Parse `client_id` dari query string via `parse_url` + `parse_str`
  - Query `oauth_clients.name` via `DB::table('oauth_clients')->where('id', $clientId)->value('name')`
  - Return `null` jika tidak ada OAuth context atau client tidak ditemukan
  - Pass `$clientName` (nullable string) ke view
- [x] `resources/views/auth/login.blade.php` — conditional UI:
  - `$clientName` ada → banner biru: *"Anda akan masuk ke [Client Name]. Silakan login untuk melanjutkan."*
  - `$clientName` null → notice amber: *"Untuk masuk ke aplikasi ekosistem yado.my.id, silakan akses melalui aplikasi yang bersangkutan."*
- [x] Subtitle halaman ikut update: *"Sign in to continue to [Client Name]"* vs *"SSO Engine"*
- [x] Tidak ada perubahan di Service/Action layer

**Notes:**
- Passport tidak menyimpan `authRequest` di session saat guest (hanya disimpan setelah authenticated). Saat redirect ke login, yang tersedia hanya `url.intended` — ini sudah cukup karena `client_id` ada di query string `/oauth/authorize`.
- Jika `client_id` tidak ada di DB atau null → fallback ke notice biasa, tidak error.

---

## QA Response

> **Method**: Static code review.

- [x] GET `/login` via OAuth flow → session `url.intended` berisi `/oauth/authorize?client_id=...` → `resolveClientName()` parse URL → query `oauth_clients.name` → pass `$clientName` ke view ✓ (`LoginController:41-61`)
- [x] Blade: `$clientName` ada → banner biru "Masuk untuk melanjutkan ke [ClientName]" tampil — `login.blade.php:7-11` ✓
- [x] GET `/login` langsung (tanpa OAuth context) → `url.intended` kosong → `$clientName = null` → notice zinc tampil — `login.blade.php:12-17` ✓
- [x] Subtitle page: `"Sign in to continue to {$clientName}"` vs `'SSO Engine'` — `login.blade.php:4` ✓
- [x] Setelah login dari OAuth flow → `redirect()->intended($default)` menuju `/oauth/authorize` → silent SSO tetap jalan ✓ (`LoginController:38`)
- [x] Client tidak ditemukan di DB → `DB::table(...)->value('name')` return `null` → fallback notice, tidak error 500 ✓
- [x] Tidak ada dd() atau debug code ✓

**Note**: Ticket menyebut notice "amber" tapi implementasi pakai zinc/gray — functionally equivalent, minor cosmetic divergence.

**Status: Done**
