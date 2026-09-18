# PRD — SSO Engine (sso.yado.my.id)

## Problem Statement

Ekosistem Yado terdiri dari beberapa aplikasi (saat ini: Malas, Scribe) yang masing-masing butuh autentikasi. Tanpa identity provider terpusat, setiap app harus maintain auth stack sendiri — duplikasi kode, user harus login ulang per app, dan session management tersebar. SSO Engine menyelesaikan ini dengan menyediakan satu titik autentikasi OAuth2 untuk seluruh ekosistem yado.my.id.

## Target Users

**End users** — individu yang punya akun yado.my.id dan mengakses satu atau lebih aplikasi dalam ekosistem.

**Superadmin** — pengelola ekosistem yang manage user accounts dan registered OAuth client apps.

**Client app developers** — tim internal yang membangun aplikasi baru dalam ekosistem dan perlu integrasi auth.

## Scope

Project ini membangun Central SSO Engine (`sso.yado.my.id`) beserta dashboard pengelolaan. Aplikasi-aplikasi klien (Malas, Scribe, dst.) adalah **konsumer** dari SSO ini — mereka tidak dibangun di sini. Arsitektur OAuth2 + PKCE dipilih karena ekosistem akan terus berkembang dan setiap app baru cukup daftar sebagai OAuth client.

## Core User Stories

### End User
- Saya ingin login sekali di sso.yado.my.id, sehingga bisa langsung masuk ke semua app ekosistem tanpa login ulang.
- Saya ingin register akun baru langsung di sso.yado.my.id, tanpa harus membuka Malas atau Scribe terlebih dahulu.
- Ketika session masih aktif dan app client redirect ke SSO, saya ingin langsung diteruskan ke app tanpa input password lagi (silent SSO).
- Saya ingin melihat halaman profil akun saya — info akun, status verifikasi, ganti password, dan daftar session aktif.
- Saya ingin bisa mencabut session dari device asing yang tidak saya kenali.

### Superadmin
- Saya ingin melihat dan mengelola semua user account — aktifkan/nonaktifkan, assign role.
- Saya ingin mengundang user baru via email (bukan register mandiri).
- Saya ingin mengelola registered OAuth client apps — tambah, edit, lihat credentials (client_id/secret), hapus.
- Saya ingin bisa me-revoke OAuth client tertentu tanpa harus menyentuh database.

### Client App
- Saya ingin bisa request userinfo (name, email, avatar, role) menggunakan access token yang valid.

---

## Features

### P0 — Done ✓

- **Register lokal**: form register dengan name, username, email, password — accessible langsung di `/register`
- **Login lokal**: form login dengan email + password, session berbasis cookie
- **Login context banner**: saat login via OAuth flow, tampilkan nama app yang meminta akses; saat akses langsung, tampilkan notice arahan
- **OAuth2 Authorization Code + PKCE**: endpoint `/oauth/authorize`, `/oauth/token`
- **Silent SSO**: jika session aktif saat `/oauth/authorize` dipanggil, langsung redirect dengan code
- **Auto-approve consent**: client first-party trusted, tidak perlu confirm consent screen
- **Userinfo endpoint**: `GET /api/user` — return profile berdasarkan access token (Bearer)
- **Refresh token flow**: client dapat refresh access token tanpa interaksi user
- **Scope-based authorization**: scope awal `profile:read`
- **Token revocation**: logout revoke session dan semua token aktif

### P1 — Done ✓

- **Landing page** (`/`): halaman publik yang menjelaskan SSO Engine — OAuth2 + PKCE, self-service register, ekosistem yado.my.id. Tautan ke `/login` dan `/register`.
- **My Account** (`/account`): halaman self-service untuk user yang sudah login
  - Info akun: name, username, email, avatar, role, status verifikasi email
  - Ganti password
  - Active sessions: daftar device/session aktif, tombol "Cabut" per session untuk revoke dari device asing
- **Dashboard Superadmin** (`/dashboard`):
  - Hanya bisa diakses user dengan role `superadmin`
  - **Applications**: list registered OAuth clients, tambah client baru, Quick Start panel dengan credentials siap copy, validasi redirect URI (HTTPS + blokir private IP), edit nama/redirect URI, revoke/hapus client
  - **Users**: list semua user, aktifkan/nonaktifkan akun, assign role, invite user baru via email
- **Invite user via email**: superadmin input email → sistem kirim email berisi link satu kali pakai untuk set password dan complete profile
- **Forgot password**: user input email → link reset via Resend, expired 60 menit, single-use
- **Register full name opsional**: jika kosong, fallback ke username

### P2 — Should Have (Next)

- **Avatar upload**
- **Audit log** login events (siapa login kapan, dari mana)
- **Email verification** — verifikasi email setelah register

### P3 — Nice to Have

- **Multi-factor authentication (TOTP)**
- **Webhook** notifikasi ke client app saat user di-deactivate
- **Social login** (Google, GitHub)

---

## Out of Scope

- Frontend/UI untuk app klien (Malas, Scribe, dll.)
- Fitur bisnis apapun selain identity & access management
- Multi-tenant / organisasi
- SAML / OpenID Connect Discovery endpoint
- Subscription plan management (diputuskan: skip)

---

## Security Requirements

- PKCE wajib untuk semua authorization code flow
- Client secret hanya untuk confidential client (server-side), tidak pernah di-expose ke frontend
- Access token TTL: 1 jam; Refresh token TTL: 30 hari
- HTTPS enforced di semua endpoint
- Password: bcrypt, min 8 karakter
- Rate limiting pada endpoint login dan token
- CSRF protection pada form Blade
- Invite link: satu kali pakai, expired 24 jam
- Dashboard superadmin: middleware `role:superadmin`, tidak bisa diakses role lain

---

## Success Metrics

- Login SSO selesai dalam < 2 detik (p95) untuk session yang sudah aktif
- Zero cross-app session leakage
- Semua client app bisa integrasi hanya dengan credentials OAuth client (no custom auth code)
- Superadmin bisa daftarkan OAuth client baru tanpa menyentuh terminal/database
