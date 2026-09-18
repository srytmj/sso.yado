# SRS — SSO Engine (sso.yado.my.id)

## Tech Stack

| Layer | Choice |
|-------|--------|
| Backend | Laravel (latest stable) |
| Auth | Laravel Passport (OAuth2 server) |
| Frontend | Blade + Alpine.js + Tailwind CSS |
| Database | PostgreSQL — `db_sso` (read/write split, sticky mode) |
| Hosting | Linux VM / EC2 |
| Email | Resend (transactional email) |
| Tunnel | Cloudflare (DNS + proxy) |

---

## Project Structure

```
root/
  app/
    Http/
      Controllers/
        Auth/
          LoginController.php
          RegisterController.php
          LogoutController.php
        OAuth/
          AuthorizationController.php   # extends Passport's built-in
        Api/
          UserController.php            # GET /api/user userinfo
      Middleware/
        EnsureTokenHasScope.php
    Models/
      User.php
      Role.php
    Services/
      Auth/
        LoginService.php
        RegisterService.php
      OAuth/
        ConsentService.php              # auto-approve logic
    Actions/
      Auth/
        IssueTokenAction.php
        RevokeTokenAction.php
  config/
    passport.php
    database.php                        # read/write split config
  database/
    migrations/
  resources/
    views/
      auth/
        login.blade.php
        register.blade.php
      oauth/
        authorize.blade.php             # consent screen (hidden for trusted)
  routes/
    web.php                             # login, register, logout (Blade)
    api.php                             # /api/user
    oauth.php                           # /oauth/* (Passport)
  docs/
  scripts/
  .claude/
```

---

## Database Schema

### Table: `users`

| Column | Type | Notes |
|--------|------|-------|
| id | bigint unsigned PK | |
| name | varchar(255) | |
| username | varchar(50) unique | |
| email | varchar(255) unique | |
| email_verified_at | timestamp nullable | |
| password | varchar(255) | bcrypt |
| avatar | varchar(255) nullable | path atau URL |
| role_id | bigint unsigned FK | → roles.id |
| is_active | boolean | default true |
| remember_token | varchar(100) nullable | |
| created_at / updated_at | timestamp | |

### Table: `roles`

| Column | Type | Notes |
|--------|------|-------|
| id | bigint unsigned PK | |
| name | varchar(100) | e.g. "User", "Admin" |
| slug | varchar(100) unique | e.g. "user", "admin" |
| created_at / updated_at | timestamp | |

### Tabel OAuth (auto dari Passport)

- `oauth_clients`
- `oauth_access_tokens`
- `oauth_refresh_tokens`
- `oauth_auth_codes`
- `oauth_personal_access_clients`

---

## Database Config (Read/Write Split)

```php
// config/database.php
'mysql' => [
    'read' => [
        'host' => [env('DB_READ_HOST', env('DB_HOST', '127.0.0.1'))],
    ],
    'write' => [
        'host' => [env('DB_WRITE_HOST', env('DB_HOST', '127.0.0.1'))],
    ],
    'sticky' => true,
    'driver' => 'mysql',
    'database' => env('DB_DATABASE', 'db_sso'),
    // ...
],
```

Sticky mode memastikan write langsung dibaca dari write connection dalam request yang sama.

---

## OAuth2 Flow

### Authorization Code + PKCE

```
Client App                         SSO Engine
    |                                  |
    |-- GET /oauth/authorize ---------->|
    |   ?response_type=code             |
    |   &client_id=xxx                  |
    |   &redirect_uri=xxx               |
    |   &scope=profile:read             |
    |   &code_challenge=xxx             |
    |   &code_challenge_method=S256     |
    |                                   |
    |   [jika session aktif]            |
    |<-- 302 redirect + code -----------|  ← silent SSO
    |                                   |
    |   [jika belum login]              |
    |<-- show login.blade.php ----------|
    |-- POST /login ------------------->|
    |<-- 302 /oauth/authorize (retry) --|
    |<-- 302 redirect + code -----------|
    |                                   |
    |-- POST /oauth/token ------------->|
    |   grant_type=authorization_code   |
    |   code=xxx                        |
    |   code_verifier=xxx               |
    |<-- { access_token, refresh_token }|
    |                                   |
    |-- GET /api/user ----------------->|  Bearer token
    |<-- { id, name, username, email,   |
    |       avatar, role }              |
```

### Refresh Token

```
POST /oauth/token
  grant_type=refresh_token
  refresh_token=xxx
  client_id=xxx
  client_secret=xxx

Response: { access_token, refresh_token, expires_in }
```

---

## API Contract

### GET /api/user

Memerlukan `Authorization: Bearer {access_token}` dengan scope `profile:read`.

**Response 200**
```json
{
  "id": 1,
  "name": "Budi Santoso",
  "username": "budi",
  "email": "budi@yado.my.id",
  "avatar": "https://sso.yado.my.id/avatars/budi.jpg",
  "role": {
    "id": 1,
    "name": "User",
    "slug": "user"
  }
}
```

**Response 401** — token tidak valid / expired
```json
{ "message": "Unauthenticated." }
```

**Response 403** — token valid tapi scope kurang
```json
{ "message": "Invalid scope(s) provided." }
```

### POST /login (Web)

```
email: string required
password: string required
remember: boolean optional
```

Redirect ke `/oauth/authorize` jika ada `intended` URL tersimpan, atau ke `/` jika tidak.

### POST /register (Web)

```
name: string nullable max:255   # opsional — jika kosong, fallback ke username
username: string required unique max:50 regex:[a-z0-9_]
email: string required email unique
password: string required min:8 confirmed
```

---

## Scopes

| Scope | Akses |
|-------|-------|
| `profile:read` | id, name, username, email, avatar, role |

Scope tambahan akan didefinisikan di tiket tersendiri saat dibutuhkan.

---

## Client Registration

OAuth client didaftarkan sebagai **confidential client** dengan:
- `client_id` — UUID auto-generate Passport
- `client_secret` — hashed, dikirim sekali saat create
- `redirect_uri` — whitelist URI
- `is_first_party` — flag untuk auto-approve consent (custom field atau via Passport `personal_access_client`)

---

## Token TTL

| Token | TTL |
|-------|-----|
| Access Token | 60 menit |
| Refresh Token | 30 hari |
| Auth Code | 10 menit |

---

## Security Spec

- PKCE (`S256`) wajib — tolak request tanpa `code_challenge`
- CSRF middleware aktif di semua route web (form login/register)
- Rate limiting: `throttle:5,1` pada `POST /login` dan `POST /oauth/token`
- Password minimal 8 karakter, bcrypt cost default
- HTTPS enforced via Cloudflare + Laravel `forceScheme('https')` di production
- `is_active = false` → semua request ditolak termasuk token valid

---

## Acceptance Criteria

| Feature | Kriteria |
|---------|----------|
| Register | User baru bisa daftar, role default "user" terassign otomatis |
| Login | Session terbentuk, redirect ke intended URL |
| Silent SSO | Session aktif → `/oauth/authorize` langsung redirect tanpa login form |
| Auth code | `code` valid 10 menit, hanya bisa dipakai sekali |
| Token exchange | POST /oauth/token dengan code + verifier → dapat access & refresh token |
| Userinfo | GET /api/user dengan token valid scope `profile:read` → return profile |
| Refresh | Refresh token → access token baru, refresh token lama revoked |
| Logout | Session dihapus, token aktif revoked |
| PKCE | Request tanpa code_challenge ditolak 400 |
| Rate limit | > 5 login gagal dalam 1 menit → 429 |
