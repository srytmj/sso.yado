# API Reference - SSO Engine (sso.yado.my.id)

Referensi teknis semua endpoint HTTP yang dipanggil programmatically (bukan halaman web portal SPA). Untuk panduan integrasi step-by-step, lihat [docs/INTEGRATION.md](INTEGRATION.md), [docs/AI_INTEGRATION.md](AI_INTEGRATION.md), atau [docs/AI_AGENT_GUIDE.md](AI_AGENT_GUIDE.md).

**Base URL**: `https://sso.yado.my.id` (production) - sesuaikan dengan `APP_URL` di environment kamu.

---

## Autentikasi

Ada dua model auth berbeda di project ini, jangan tertukar:

- **OAuth2 Bearer token** (`Authorization: Bearer <access_token>`) - dipakai client app buat memanggil `GET /api/user` setelah user selesai login lewat `/oauth/authorize`. Didapat lewat token exchange di `/oauth/token`.
- **Session cookie** (login web portal biasa) - dipakai untuk semua halaman Svelte SPA (`/login`, `/account/*`, `/dashboard/*`). Bukan cakupan dokumen ini karena dikelola oleh Inertia.js dan Laravel session cookie.

---

## Health Check

### `GET /health`

Publik, **tanpa autentikasi**, buat monitoring eksternal (landing page Yado). Query DB minimal (`SELECT 1`) supaya responsnya cepat - jangan expect ini memvalidasi seluruh dependency (mail, storage, dst.), cuma DB connectivity.

Rate limit: 60 request/menit per IP.

**Response sehat - `200 OK`**
```json
{
  "status": "ok",
  "service": "sso-engine",
  "timestamp": "2026-08-27T17:28:46+00:00"
}
```

**Response bermasalah - `503 Service Unavailable`**
```json
{
  "status": "error",
  "service": "sso-engine",
  "error": "SQLSTATE[HY000] [2002] Connection refused"
}
```

### `GET /up`

Health check bawaan Laravel (liveness probe murni - cek app bisa boot & serve response, tanpa cek dependency eksternal). Response HTML, bukan JSON. Dipakai internal (load balancer, uptime monitor sederhana) - untuk dashboard monitoring yang butuh JSON terstruktur, pakai `/health`.

---

## OAuth2 (Laravel Passport)

Protokol: **Authorization Code + PKCE** (RFC 6749 + RFC 7636). PKCE **wajib** untuk semua client, tidak ada fallback ke flow tanpa PKCE. Rate limit: 30 request/menit per IP untuk semua endpoint `/oauth/*`.

### `GET /oauth/authorize`

Titik masuk flow OAuth2. Redirect user ke sini dari client app dengan query string:

```
GET /oauth/authorize
    ?client_id=<uuid>
    &redirect_uri=<url terdaftar>
    &response_type=code
    &scope=profile:read
    &state=<random string, CSRF protection>
    &code_challenge=<PKCE challenge>
    &code_challenge_method=S256
    &prompt=login             <opsional, paksa re-auth / tambah akun>
```

- **Silent Authorization**: Untuk aplikasi internal ekosistem Yado (`skipsAuthorization() == true`), setelah terautentikasi pengguna langsung diarahkan kembali ke `redirect_uri` tanpa perlu melalui consent screen manual.
- **`prompt=login`**: Jika disertakan, sesi login aktif di SSO akan diputus dan pengguna dipaksa memasukkan kredensial baru (berguna untuk multi-account linking di client apps).
- Jika pengguna belum terautentikasi, mereka akan diarahkan ke form `/login` (dengan context banner aplikasi target), lalu diproses kembali ke alur otentikasi. Setelah berhasil, diarahkan ke `redirect_uri` dengan `?code=<auth code>&state=<state>`. Auth code berlaku ~10 menit, single-use.

### `POST /oauth/token`

Tukar authorization code (atau refresh token) jadi access token.

**Authorization Code exchange:**
```
POST /oauth/token
Content-Type: application/x-www-form-urlencoded

grant_type=authorization_code
&client_id=<uuid>
&client_secret=<secret>
&redirect_uri=<url terdaftar, harus sama persis>
&code=<auth code dari callback>
&code_verifier=<PKCE verifier>
```

**Refresh token exchange:**
```
POST /oauth/token
Content-Type: application/x-www-form-urlencoded

grant_type=refresh_token
&client_id=<uuid>
&client_secret=<secret>
&refresh_token=<refresh token>
```

**Response - `200 OK`**
```json
{
  "token_type": "Bearer",
  "expires_in": 3600,
  "access_token": "eyJ...",
  "refresh_token": "def502..."
}
```

- Access token berlaku **60 menit**.
- Refresh token berlaku **30 hari**.
- Scope yang tersedia: `profile:read` - baca profil user (nama, username, email, avatar, role). Cuma satu scope saat ini, tidak ada scope lain.

---

## Resource API

### `GET /api/user`

Profil user yang sedang login, diidentifikasi lewat access token.

**Auth**: `Authorization: Bearer <access_token>` - wajib scope `profile:read`. Rate limit: 60 request/menit per user.

```
GET /api/user
Authorization: Bearer eyJ...
```

**Response - `200 OK`**
```json
{
  "id": 12,
  "name": "Budi Santoso",
  "username": "budi",
  "email": "budi@example.com",
  "avatar": "https://sso.yado.my.id/storage/avatars/12.jpg",
  "theme": "system",
  "locale": "id",
  "role": {
    "id": 2,
    "name": "Member",
    "slug": "member"
  }
}
```

**Error responses**

| Status | Kapan terjadi |
|--------|----------------|
| `401 Unauthorized` | Token tidak ada, invalid, expired, atau sudah di-revoke (user logout/revoke device) |
| `403 Forbidden` | Token valid tapi tidak punya scope `profile:read` |
| `403 Forbidden` | User terkait sudah dinonaktifkan superadmin (`is_active = false`) - dicek via middleware `check.user.active` |
| `429 Too Many Requests` | Rate limit terlampaui |

---

## Rate Limit - Ringkasan

| Endpoint | Limit |
|----------|-------|
| `GET /health` | 60/menit per IP |
| `/oauth/*` (authorize + token) | 30/menit per IP |
| `GET /api/user` | 60/menit per user |
| Semua halaman guest lain (`/login`, `/register`, dst.) | 60/menit per IP (submit form lebih ketat, lihat `routes/web.php`) |
| Semua route authenticated (dashboard, account) | 120/menit per user |

Response saat kena limit selalu `429 Too Many Requests` dengan header `Retry-After`.

---

## Lihat Juga

- [docs/INTEGRATION.md](INTEGRATION.md) - panduan integrasi manual step-by-step untuk developer
- [docs/AI_INTEGRATION.md](AI_INTEGRATION.md) - brief buat di-paste ke AI assistant supaya auto-generate kode integrasi
- [docs/SRS.md](SRS.md) - tech spec & DB schema lengkap
