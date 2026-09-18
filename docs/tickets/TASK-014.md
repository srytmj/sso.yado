# TASK-014: Dashboard - Applications (OAuth Client Management)

Status: Done
Priority: Medium
Created: 2026-07-20 21:00
Request: Buat halaman manajemen OAuth client apps di dashboard superadmin. Superadmin bisa lihat semua registered client, tambah client baru, lihat client_id & client_secret, edit nama/redirect URI, dan hapus/revoke client - semua tanpa perlu tinker atau database GUI.

Depends on: TASK-013

---

## DEV Response

- [x] `GET /dashboard/applications` → `ApplicationController::index()` - `app/Http/Controllers/Dashboard/ApplicationController.php`
- [x] `GET /dashboard/applications/create` → `ApplicationController::create()`
- [x] `POST /dashboard/applications` → `ApplicationController::store()` - delegate ke `ApplicationService::create()`, redirect ke show dengan `->with('new_secret', $client->plainSecret)`
- [x] `GET /dashboard/applications/{id}` → `ApplicationController::show()`
- [x] `PATCH /dashboard/applications/{id}` → `ApplicationController::update()` - validasi `name required`, `redirect_uri url|max:1000`
- [x] `DELETE /dashboard/applications/{id}` → `ApplicationController::destroy()` - delegate ke `ApplicationService::delete()`
- [x] `ApplicationService` - `list()`: `Client::orderByDesc('created_at')->get()`, `create()`: `ClientRepository::create(userId: null, ...)`, `update()`: `$client->update(['name', 'redirect'])`, `delete()`: revoke semua token + refresh tokens client lalu `$clients->delete($client)` - `app/Services/Dashboard/ApplicationService.php`
- [x] Client secret flash via `session('new_secret')` - satu kali tampil saat create, warning kuning di view show
- [x] Views: `dashboard/applications/index.blade.php` (tabel), `create.blade.php` (form), `show.blade.php` (credentials + edit form)

---

## QA Response

> **Method**: Static code review. DEV Response belum di-update (checklist `[ ]`), tapi implementasi sudah ada.

- [x] GET `/dashboard/applications` → `ApplicationService::list()` return semua clients ✓
- [x] Tambah client: `ApplicationService::create()` pakai `ClientRepository::create(userId: null, ...)` → first-party client ✓
- [x] `store()` redirect ke show route dengan `->with('new_secret', $client->plainSecret)` - secret hanya ada di session flash (sekali tampil) ✓ (`ApplicationController:38`)
- [x] Refresh halaman show → flash `new_secret` sudah hilang → secret tidak tampil lagi ✓ (session flash one-time)
- [x] Edit: validasi `name required`, `redirect_uri url|max:1000` - URL tidak valid → validation error ✓ (`ApplicationController:31`)
- [x] Hapus: `ApplicationService::delete()` iterates `$client->tokens`, revoke + refreshToken, lalu `clients->delete($client)` ✓
- [x] Semua route /dashboard/applications dalam middleware group `auth` + `superadmin` ✓
- [x] Views: index, create, show exist ✓

**Status: Done**
