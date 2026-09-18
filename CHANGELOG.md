# Changelog

Semua perubahan signifikan pada project ini didokumentasikan di sini.

Format mengikuti [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).

---

## [Unreleased]

### Changed
- Nama ekosistem diperbarui (dari "White Archive" menjadi "Yado").
- Perubahan referensi domain utama dari `*.suryatmaja.dev` menjadi `*.yado.my.id`.
- Database engine dari MySQL ke **PostgreSQL** - semua koneksi (dev, prod, standalone) sekarang pakai `pgsql`, read/write split + sticky dipertahankan. `php artisan db:migrate-to-pgsql` disediakan untuk memindahkan seluruh data dari database MySQL lama (dibaca via koneksi `mysql`/`MYSQL_LEGACY_*`) ke Postgres sekali jalan, aman diulang (truncate tujuan dulu sebelum insert)

### Added
- **Silent Authorization untuk Internal Passport Clients**: `App\Models\OAuth\Client::skipsAuthorization()` mengembalikan `! $this->revoked`, menghilangkan Blade consent screen ("Authorize Application") untuk client app resmi ekosistem Yado.
- **Middleware `HandleOAuthPrompt` (`prompt=login`)**: Mendukung parameter OAuth standar `prompt=login` pada `/oauth/authorize`. Jika user sudah terautentikasi, middleware me-logout sesi SSO aktif dan membersihkan parameter `prompt` sebelum mengarahkan ke halaman login, mencegah loop logout pada session intended.
- **Dukungan Login Username atau Email**: Input form login SSO (`Login.svelte`) diubah menjadi `type="text"` dengan label "Email Address or Username", placeholder instruktif, dan atribut `autocomplete="username"` sehingga pengguna dapat login menggunakan username (mis. `sehnauoi`) maupun alamat email tanpa terblokir validasi format email HTML5 browser.
- **Docker Compose standalone (`docker/compose.standalone.yml`, `make docker-standalone-deploy`)** - self-contained pakai Caddy, satu file untuk testing lokal (`http://localhost`) maupun deploy publik (HTTPS otomatis via Let's Encrypt tinggal ganti `CADDY_SITE_ADDRESS`), tidak butuh Traefik/infrastruktur lain
- Admin Settings (`/dashboard/settings`) - konfigurasi mail (Resend/SMTP) dan avatar storage (local/S3) dari dashboard, bukan hardcode `.env`
- Email verification wajib sebelum akses `/account`, `/dashboard`, atau OAuth flow
- Avatar upload (`/account`) - ke disk local atau S3 sesuai Settings
- My Devices (`/account/sessions`) - lihat & logout browser/device yang login ke SSO, dengan label device
- Audit Log (`/dashboard/audit-log`) - event keamanan & perubahan data (login, ganti password, role, invite, CRUD client, dst.)
- Two-Factor Authentication / TOTP (`/account/two-factor`) - kompatibel Google Authenticator, Microsoft Authenticator, recovery codes
- Theme toggle (system/light/dark) - persisted per user, satu klik ganti tema, no-FOUC via inline script
- Multilanguage: Indonesia, English, 日本語 - switcher di My Account, `theme`/`locale` di-expose lewat `/api/user` untuk disinkronkan client app (opsional)
- Docker Compose setup untuk development lokal (`make docker-fresh`) - PHP 8.4-FPM, Nginx, PostgreSQL 16
- Docker Compose production untuk homelab (`docker/compose.prod.yml`, `make docker-prod-deploy`) - siap Traefik reverse proxy, Postgres dengan password wajib, `migrate` (bukan fresh)
- Dashboard: log viewer (`/dashboard/logs`) - filter by level (custom dropdown, auto-apply), search, expand stack trace
- Health check endpoint `/up` (built-in Laravel)
- Rate limiting menyeluruh - `/oauth/*`, `/api/user`, semua halaman guest, dan semua route authenticated (per-user), dengan limit lebih ketat untuk aksi sensitif (ganti password, kirim invite, dst.)
- Landing page publik di `/` - menjelaskan SSO Engine dan link ke login/register
- Dashboard superadmin - gate akses role, layout sidebar + topbar
- Dashboard: manajemen OAuth client apps (lihat, tambah, edit, hapus client)
- Dashboard: manajemen user - aktifkan/nonaktifkan, assign role, invite via email
- My Account (`/account`) - info akun, ganti password
- My Account: active sessions & revoke device via `oauth_access_tokens`
- Login page context awareness - banner nama aplikasi saat datang dari OAuth flow
- Forgot password flow via email (Resend) - link reset expired 60 menit, single-use
- Register: field full name opsional, fallback ke username jika kosong

### Changed
- Ganti password sekarang otomatis revoke semua OAuth token aktif user (semua client app di-logout)
- Logout SSO support `redirect_uri` - client app bisa redirect balik setelah logout (whitelisted ke domain terdaftar)

### Fixed
- **Navigasi Cross-Origin Pasca-Login OAuth**: `LoginController` dan `TwoFactorChallengeController` menggunakan `Inertia::location($intended)` (HTTP 409) bukan `redirect()->intended()`, mencegah kegagalan CORS pada request AJAX Inertia saat browser dialihkan kembali ke domain client aplikasi ekosistem.
- **Rate Limit 429 pada Form Login**: Menambahkan `$middleware->trustProxies(at: '*')` di `bootstrap/app.php` dan memisahkan pembatasan login ke named limiter `RateLimiter::for('login')` (10 request/menit per email + IP klien asli). Sebelumnya, request GET/POST bertumpuk di anonymous key yang sama dan seluruh IP klien terlipat menjadi IP gateway Docker (`172.20.0.1`).
- Image Docker (`docker/php/Dockerfile`) tidak punya ekstensi `sodium` yang dibutuhkan Passport (via `lcobucci/jwt`) - bikin `composer install` gagal di dalam container. Ditambahkan `libsodium-dev` + `docker-php-ext-install sodium`
- `home.blade.php` (landing page publik) tidak punya dark mode maupun multilanguage sama sekali padahal halaman ini genuinely dirender - sudah dikonversi penuh
- Beberapa icon dropdown kekurangan varian `dark:` dan dua icon checklist pakai `stroke-width` yang tidak konsisten (2 vs 1.5 di tempat lain)

### Removed
- `resources/views/flux/` - komponen Flux UI yang tidak pernah dipakai (dilarang di `.claude/CLAUDE.md`), beserta dependency `livewire/flux`
- `welcome.blade.php` - landing page default Laravel yang tidak pernah di-render

---

## [0.1.0] - 2026-07-20

Implementasi awal SSO Engine - fondasi OAuth2 + autentikasi lokal.

### Added

**Auth Lokal**
- Register user dengan validasi: username, email, password (bcrypt min 8 karakter)
- Login via email/password dengan Laravel session
- Logout: hancurkan session + revoke semua access token aktif
- Role system: `user` dan `superadmin`, di-seed otomatis
- Middleware `CheckUserActive` - user `is_active = false` ditolak di semua endpoint

**OAuth2 Authorization Code + PKCE**
- `GET /oauth/authorize` - titik masuk OAuth2 flow via Laravel Passport
- PKCE (`S256`) wajib - request tanpa `code_challenge` ditolak
- Silent SSO - session aktif langsung redirect dengan auth code tanpa tampilkan login form
- Auto-approve consent untuk first-party client via `skipsAuthorization()`
- `POST /oauth/token` - tukar auth code + code_verifier dengan access token & refresh token

**Token Management**
- Access token TTL: 60 menit
- Refresh token TTL: 30 hari, single-use (rotation on use)
- `RevokeTokenAction` - revoke semua token + refresh token saat logout
- Refresh token flow: `grant_type=refresh_token` via `/oauth/token`

**Userinfo Endpoint**
- `GET /api/user` - return profil user (id, name, username, email, avatar, role)
- Proteksi: `auth:api` + scope `profile:read`
- Response flat tanpa wrapper (sesuai API contract SRS)

**Database**
- Tabel `users` dengan kolom: name, username, email, avatar, role_id, is_active
- Tabel `roles` dengan seeder: user (id=1), superadmin (id=2)
- Read/write split dengan `sticky: true`, database `db_sso`
- Semua tabel OAuth dikelola otomatis oleh Passport

**Security**
- CSRF protection aktif di semua web routes
- Rate limiting: `throttle:5,1` pada `POST /login`, `throttle:3,1` pada `/oauth/token`
- HTTPS enforced via `forceScheme('https')` di production
- Passport keys tidak di-commit ke repository

**Infrastructure**
- `scripts/deploy.sh` - first-time server setup
- `scripts/update.sh` - git pull + migrate + cache clear
- `Makefile` - shortcut `make deploy`, `make update`, `make sync`
- `sync.sh` - auto-sync tech stack dari SRS.md ke CLAUDE.md

---

[Unreleased]: https://github.com/srytmj/sso.yado/compare/v0.1.0...HEAD
[0.1.0]: https://github.com/srytmj/sso.yado/releases/tag/v0.1.0
