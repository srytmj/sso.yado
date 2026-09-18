# SSO Engine - Yado

SSO Engine adalah salah satu microservice dari project Yado. Service ini berfungsi sebagai Central Identity Provider untuk ekosistem Yado. User cukup login sekali di sini, lalu bisa mengakses semua aplikasi lain (seperti Malas, Scribe, dll.) tanpa perlu login ulang.

**Protokol**: OAuth2 Authorization Code + PKCE (RFC 6749 + RFC 7636)
**URL produksi**: `https://sso.yado.my.id` *(sementara, domain final belum ditentukan)*

---

## Stack

- **Backend**: Laravel (latest stable) + Laravel Passport
- **Frontend**: Blade + Alpine.js + Tailwind CSS (no SPA)
- **Database**: PostgreSQL - `db_sso` (read/write split, sticky mode)
- **Email**: Resend
- **Infra**: Linux VM / EC2, Cloudflare DNS + proxy

---

## Setup Lokal

### Cara A - Docker (rekomendasi, tidak perlu install PHP/PostgreSQL manual)

```bash
git clone <repo-url> sso.yado
cd sso.yado
make docker-fresh
```

Akses di `http://localhost:8000`. Detail lengkap: [docs/DOCKER.md](docs/DOCKER.md).

### Cara B - Manual

**Prasyarat**: PHP 8.4+, Composer, PostgreSQL

```bash
git clone <repo-url> sso.yado
cd sso.yado

composer install
cp .env.example .env
php artisan key:generate
```

Buat database `db_sso` di PostgreSQL, lalu isi `.env`:

```env
DB_CONNECTION=pgsql
DB_PORT=5432
DB_DATABASE=db_sso
DB_USERNAME=postgres
DB_PASSWORD=

MAIL_MAILER=resend
RESEND_API_KEY=re_xxx
MAIL_FROM_ADDRESS=noreply@yado.my.id
MAIL_FROM_NAME="SSO Yado"
```

```bash
php artisan migrate
php artisan passport:install
php artisan db:seed          # seed roles + superadmin
php artisan serve
```

Akses di `http://localhost:8000`.

---

## Deployment

Ada **5 cara** deploy SSO Engine ke production - pilih sesuai infrastruktur yang kamu punya. Tiap panduan sudah termasuk cara deploy-nya *dan* checklist "cara pakai" setelah jalan (login superadmin, verifikasi setup, dst.).

| # | Cara | Kapan dipakai | Panduan |
|---|------|----------------|---------|
| 1 | **Docker Standalone** | VPS tunggal, demo, staging, atau VM/LXC Proxmox baru - self-contained (Caddy), HTTPS otomatis, gak butuh infra lain sama sekali | [docs/DOCKER.md](docs/DOCKER.md) → section "Standalone: Universal" (Proxmox: `bash scripts/deploy-docker-proxmox.sh`) |
| 2 | **Docker Homelab (Traefik)** | Homelab/Proxmox dengan banyak project, Traefik sudah jalan duluan | [docs/DOCKER.md](docs/DOCKER.md) → section "Production: Homelab" |
| 3 | **Manual SSH - AWS EC2** | VM/EC2 tanpa Docker, mau kontrol penuh di level OS | [docs/DEPLOY_AWS.md](docs/DEPLOY_AWS.md) |
| 4 | **Manual SSH - Azure VM** | VM Azure tanpa Docker | [docs/DEPLOY_AZURE.md](docs/DEPLOY_AZURE.md) |
| 5 | **Manual SSH - generic** | Server Linux mana pun, pakai `scripts/deploy.sh` langsung | lihat di bawah |

Cara #1 dan #2 pakai `docker/compose.standalone.yml` / `docker/compose.prod.yml` - keduanya beda dari `docker-compose.yml` yang dipakai buat [Setup Lokal](#setup-lokal) di atas (itu murni buat development, bukan production).

### Cara #5 - Manual SSH generic

**First deploy** (server baru):
```bash
make deploy
# atau: sudo bash scripts/deploy.sh
```

**Update** (kode sudah di server):
```bash
make update
# atau: bash scripts/deploy.sh
```

Bisa juga dari lokal tanpa SSH manual - isi `SERVER_HOST` di `.env`, lalu:
```bash
make remote-deploy   # first deploy
make remote-update   # update
```

Tidak ada CI/CD di cara mana pun - semua deploy dipicu manual.

---

## Setelah Deploy - Mulai Pakai

1. **Login sebagai superadmin** - pakai `ADMIN_EMAIL`/`ADMIN_PASSWORD` dari `.env` (ganti default-nya kalau belum). Halaman login otomatis diarahkan ke `/dashboard` untuk superadmin, `/account` untuk user biasa.
2. **(Opsional) Aktifkan Two-Factor Authentication** di `/account/two-factor` - scan QR pakai Google/Microsoft Authenticator, simpan recovery codes-nya.
3. **Cek `/dashboard/settings`** - pastikan konfigurasi Mail (Resend/SMTP) dan Avatar Storage (local/S3) sesuai, lalu coba "Send Test Email" buat mastiin email jalan.
4. **Daftarkan aplikasi client pertama** di `/dashboard/applications` → Add Application → isi nama + Redirect URI. Setelah dibuat, muncul Quick Start panel berisi credentials (Client ID, Secret, contoh `.env` snippet) - **simpan sekarang, secret cuma tampil sekali**.
5. **Integrasikan ke aplikasi client** - ikuti [docs/INTEGRATION.md](docs/INTEGRATION.md) (panduan manual step-by-step) atau lempar [docs/AI_INTEGRATION.md](docs/AI_INTEGRATION.md) ke AI assistant di project client app buat auto-generate kodenya.
6. **Undang user lain** lewat `/dashboard/users` → Invite User (khusus superadmin), atau biarkan user daftar sendiri via `/register` (wajib verifikasi email dulu sebelum bisa akses app).
7. **Pantau aktivitas** - `/dashboard/audit-log` buat event keamanan (login, ganti password, dst.), `/dashboard/logs` buat log aplikasi/error mentah, `/dashboard/sessions` buat lihat semua session aktif lintas user.

---

## Arsitektur

```
Request → Controller (validasi) → Service/Action (logic) → Model → Response
```

- Controller: thin, hanya validasi dan delegasi
- Service: business logic
- Action: single-responsibility (contoh: `RevokeTokenAction`)
- Model: relasi dan scopes saja

```
app/
  Http/
    Controllers/Auth/     # Login, Register, Logout, ForgotPassword, ResetPassword
    Controllers/Api/      # UserController (GET /api/user)
  Services/Auth/          # LoginService, RegisterService
  Actions/Auth/           # RevokeTokenAction
  Models/                 # User, Role
resources/views/
  layouts/                # public, auth, dashboard, account
  auth/                   # login, register, forgot-password, reset-password
docs/
  PRD.md                  # Product requirements
  SRS.md                  # Tech spec & API contract
  INTEGRATION.md          # Panduan integrasi untuk developer client app
  tickets/                # TASK-001 s/d TASK-017
```

---

## Endpoints Utama

| Endpoint | Keterangan |
|----------|------------|
| `GET /` | Landing page |
| `GET /login` | Halaman login |
| `GET /register` | Halaman register |
| `GET /forgot-password` | Form lupa password |
| `GET /email/verify` | Halaman verifikasi email (wajib sebelum akses account/dashboard) |
| `GET /two-factor-challenge` | Input kode TOTP saat login (kalau 2FA aktif) |
| `GET /oauth/authorize` | Titik masuk OAuth2 flow (Passport) |
| `POST /oauth/token` | Tukar code/refresh token (Passport) |
| `GET /api/user` | Profil user (butuh Bearer token + scope `profile:read`) |
| `GET /account` | My Account (auth) |
| `GET /account/sessions` | Active sessions + My Devices |
| `GET /account/two-factor` | Setup/disable 2FA |
| `GET /dashboard` | Dashboard superadmin |
| `GET /dashboard/settings` | Konfigurasi Mail & Avatar Storage (superadmin) |
| `GET /dashboard/audit-log` | Log event keamanan & perubahan data (superadmin) |
| `GET /dashboard/logs` | Log aplikasi/error mentah (superadmin) |
| `GET /up` | Health check bawaan Laravel (liveness murni, HTML) |
| `GET /health` | Health check buat monitoring eksternal (landing page Yado) - JSON, cek koneksi DB |

---

## Integrasi Client App

Lihat [docs/INTEGRATION.md](docs/INTEGRATION.md) untuk panduan lengkap OAuth2 flow, contoh kode, dan checklist integrasi.

---

## Docs

| File | Isi |
|------|-----|
| [docs/PRD.md](docs/PRD.md) | Product requirements & user stories |
| [docs/SRS.md](docs/SRS.md) | Tech spec, DB schema, API contract |
| [docs/API.md](docs/API.md) | Referensi endpoint HTTP - health check, OAuth2, `/api/user`, rate limit |
| [docs/INTEGRATION.md](docs/INTEGRATION.md) | Panduan integrasi untuk developer (manual) |
| [docs/AI_INTEGRATION.md](docs/AI_INTEGRATION.md) | Brief integrasi untuk AI assistant |
| [docs/DEPLOY_AZURE.md](docs/DEPLOY_AZURE.md) | Deploy manual ke Azure VM (tanpa Docker) |
| [docs/DEPLOY_AWS.md](docs/DEPLOY_AWS.md) | Deploy manual ke AWS EC2 (tanpa Docker) |
| [docs/DOCKER.md](docs/DOCKER.md) | Docker - dev lokal, standalone (universal, HTTPS otomatis), dan homelab/Traefik |
| [docs/TODO.md](docs/TODO.md) | Backlog informal |
| [.claude/CLAUDE.md](.claude/CLAUDE.md) | Context untuk AI dev sessions |
