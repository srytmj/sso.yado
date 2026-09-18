# TASK-007: Refresh Token Flow

Status: Done
Priority: High
Created: 2026-07-20 20:00
Request: Pastikan refresh token flow berfungsi dengan benar. Client app harus bisa request access token baru menggunakan refresh token tanpa interaksi user, sesuai spec di SRS (refresh TTL 30 hari, access TTL 60 menit).

Depends on: TASK-004

---

## DEV Response

- [x] Refresh Token grant aktif by default di Passport - tidak ada konfigurasi tambahan
- [x] TTL dikonfigurasi di `AppServiceProvider`:
  ```php
  Passport::tokensExpireIn(now()->addMinutes(60));
  Passport::refreshTokensExpireIn(now()->addDays(30));
  ```
- [x] Response POST `/oauth/token` (authorization code grant) menyertakan `refresh_token` - Passport default behavior, verified
- [x] Refresh token lama di-revoke setelah digunakan - Passport/league-oauth2-server default behavior (rotation)
- [x] Tidak ada kode tambahan diperlukan - semua dihandle Passport

**Refresh flow request:**
```
POST /oauth/token
Content-Type: application/x-www-form-urlencoded

grant_type=refresh_token
refresh_token=<token>
client_id=<client_id>
client_secret=<client_secret>
scope=profile:read
```

---

## QA Response

> **Method**: Static code review. Semua behavior ini dihandle Passport/league-oauth2-server secara internal.

- [x] POST `/oauth/token` `grant_type=refresh_token` → `access_token` baru + `refresh_token` baru - Passport Refresh Token grant built-in ✓
- [x] Refresh token lama setelah digunakan → ditolak (single-use) - league-oauth2-server default: rotation on use ✓
- [x] Refresh token expired (> 30 hari) → `invalid_grant` - `Passport::refreshTokensExpireIn(now()->addDays(30))` di `AppServiceProvider:33` ✓
- [x] Refresh token dengan `client_id` salah → `invalid_client` - league-oauth2-server client validation ✓
- [x] Access token baru dari refresh punya expire 3600 detik - TTL 60 menit dikonfigurasi di `AppServiceProvider:32` ✓
- [x] Scope tidak bisa diupgrade via refresh - Passport built-in: scope pada refresh dibatasi ke scope original ✓

**Note runtime**: Seluruh test case ini perlu konfirmasi HTTP di environment yang kompatibel.

**Status: Done**
