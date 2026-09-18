# TODO - SSO Engine

Catatan informal, backlog item, dan hal-hal yang perlu diputuskan. Bukan pengganti ticket - kalau sudah cukup jelas, buat TASK di docs/tickets/.

---

## Segera (Pre-Deploy)

- [x] `composer update` dijalankan, `composer.lock` sudah sinkron - google2fa-laravel diganti `pragmarx/google2fa` (framework-agnostic) karena wrapper Laravel-nya belum support versi Laravel project ini
- [x] Migration lokal jalan bersih (`settings`, `audit_logs`, avatar/2FA columns, theme/locale, backfill verifikasi user lama)
- [x] Smoke test dasar lokal - 66 route ke-load tanpa error, halaman publik (`/`, `/login`, `/register`, `/forgot-password`, `/reset-password`, `/register/invite`, `/two-factor-challenge`, `/up`) semua respons benar setelah `view:clear` (sempat 500 karena stale compiled view cache dari sebelum Flux dihapus - bukan bug kode, sudah beres)
- [ ] **Runtime verification interaktif** (butuh browser, belum bisa dites via curl): register → verifikasi email → aktifkan 2FA (scan QR) → login pakai kode TOTP; upload avatar; `/dashboard/settings`; `/dashboard/audit-log`
- [ ] Runtime verification full OAuth flow lintas app via HTTP sungguhan (belum dilakukan)
- [ ] SMTP dan S3 di Settings belum pernah dites kirim/upload sungguhan (butuh kredensial asli)
- [ ] Setup Resend: verifikasi domain `yado.my.id` di Cloudflare + Resend dashboard, isi `RESEND_API_KEY` di `.env` production (atau lewat dashboard Settings sekarang)
- [ ] Migrate + first deploy ke **server production** (baru dites di lokal, belum di server)

## Setelah Deploy

- [ ] Daftarkan client app pertama (Malas atau Scribe) via dashboard
- [ ] Test SSO end-to-end lintas app di staging/production
- [ ] Verifikasi email invite user berfungsi (Resend live)
- [ ] Verifikasi forgot password email terkirim dan link valid

## Perlu Diputuskan

- ~~Avatar storage: local disk atau S3?~~ → **Selesai diputuskan**: keduanya didukung, admin pilih via `/dashboard/settings`
- ~~Email verification setelah register~~ → **Selesai diputuskan**: wajib, sudah diimplementasikan
- Refresh token: di sisi client app, implementasi auto-refresh atau handle 401 on-demand?

## Theming & i18n - Status

Infra theme (system/light/dark) dan multilanguage (id/en/ja) sudah jalan penuh: migration `theme`/`locale` di `users`, middleware `SetLocale`, toggle button di semua layout, lang files `lang/{id,en,ja}/*.php`, custom dropdown Alpine (bukan native `<select>`) dipakai konsisten di semua filter/pilihan.

Semua halaman aplikasi sudah dikonversi (string + `dark:` classes): 4 layout, auth (login, register, forgot-password, reset-password, invite), oauth authorize, account (show, sessions), dashboard (index, applications CRUD, users, sessions, logs), email invitation.

**Sisa (nice-to-have, bukan blocker):**

- [ ] Pesan validasi Laravel bawaan (butuh `php artisan lang:publish` lalu translate `lang/{id,en,ja}/validation.php`) - sekarang masih default Bahasa Inggris dari framework
- [ ] Pagination view default Laravel belum ada `dark:` classes (styling ikut warna terang meski dark mode aktif) - perlu `php artisan vendor:publish --tag=laravel-pagination` lalu tambah dark classes manual
- [ ] Email invitation dikirim pakai locale sesi superadmin yang invite, bukan locale penerima (penerima belum punya akun jadi belum ada preferensi) - cukup wajar untuk sekarang

## Fitur Baru (Selesai Diimplementasikan, Belum Runtime-Tested)

- [x] **Admin Settings** (`/dashboard/settings`) - mail (Resend/SMTP) dan avatar storage (local/S3) sekarang bisa dikonfigurasi dari dashboard, bukan hardcode `.env`. Data lama di `.env` otomatis di-migrate ke tabel `settings` sekali saat deploy pertama. Ada tombol "Send Test Email".
- [x] **Email verification wajib** - user baru harus klik link verifikasi sebelum bisa akses `/account`, `/dashboard`, atau lanjut OAuth flow (`/oauth/authorize`). User yang dibuat via invite atau seeder otomatis terverifikasi (sudah divouch).
- [x] **Avatar upload** (`/account`) - upload ke disk yang dikonfigurasi di Settings (local atau S3). URL avatar disimpan penuh jadi tetap valid meski disk diganti nanti.
- [x] **My Devices** (`/account/sessions`) - user bisa lihat semua browser/device yang login ke SSO (bukan cuma OAuth token app lain), dengan label device (mis. "Chrome on Windows"), dan bisa logout device tertentu atau semua device lain sekaligus.
- [x] **Audit Log** (`/dashboard/audit-log`) - mencatat event keamanan & perubahan data (login/logout, ganti password, ganti role, invite, CRUD OAuth client, dll - bukan semua page view). Terpisah dari `/dashboard/logs` (log aplikasi mentah).
- [x] **Two-Factor Authentication / TOTP** (`/account/two-factor`) - kompatibel dengan Google Authenticator, Microsoft Authenticator, atau aplikasi TOTP apa pun. Opsional per user, ada recovery codes (8 kode sekali pakai).
- [x] Cleanup: hapus `resources/views/flux/` (dead code, ~400 file, dilarang di CLAUDE.md) dan `welcome.blade.php` (tidak pernah di-render)

**Catatan implementasi yang perlu diverifikasi saat runtime testing:**
- API persis `pragmarx/google2fa-laravel` (`generateSecretKey`, `getQRCodeUrl`, `verifyKey`) diasumsikan sesuai dokumentasi standar - belum divalidasi jalan karena tidak ada PHP di environment saat coding
- `php artisan storage:link` dijalankan otomatis di `deploy.sh` dan Docker entrypoint untuk avatar disk lokal - pastikan symlink kebentuk dengan benar
- SMTP sebagai alternatif Resend di Settings belum pernah dites kirim email sungguhan
- S3 sebagai avatar storage belum pernah dites upload sungguhan (butuh bucket + kredensial asli)

## Backlog (P2/P3)

- [ ] Webhook notifikasi ke client app saat user di-deactivate
- [ ] Social login (Google, GitHub) - bukan MFA, tapi login utama pakai akun Google/GitHub
- [ ] OpenID Connect Discovery endpoint (`/.well-known/openid-configuration`)

## Catatan

- Roles yang valid: `user` dan `superadmin` saja - role `admin` sudah dihapus (migration + seeder diupdate)
- Ekosistem saat ini: Malas, Scribe - keduanya akan jadi OAuth client
- Untuk integrasi client app baru: lihat `docs/INTEGRATION.md` atau `docs/AI_INTEGRATION.md`
