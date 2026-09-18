# Docker - SSO Engine

Ada 3 cara jalanin SSO Engine via Docker, pilih sesuai kebutuhan:

| File | Kapan dipakai |
|---|---|
| `docker-compose.yml` | Development lokal - hot-friendly, port di-publish langsung ke host, Postgres `POSTGRES_HOST_AUTH_METHOD=trust` (tanpa password, khusus lokal) |
| `docker/compose.prod.yml` | Production di homelab yang **sudah punya** Traefik jalan (banyak project di 1 host, network `proxy` dishare) |
| **`docker/compose.standalone.yml`** | **Universal** - satu file yang sama buat testing/pakai lokal ATAU deploy ke server publik mana pun, **tidak butuh infrastruktur lain** (pakai Caddy, self-contained, auto-HTTPS) |

Section ini fokus ke `docker-compose.yml` (dev). Untuk `docker/compose.standalone.yml`, lihat section ["Standalone: Universal"](#standalone-universal--testing-lokal-atau-deploy-publik) di bawah. Untuk `docker/compose.prod.yml`, lihat ["Production: Homelab"](#production-homelab-proxmox--banyak-project-di-1-host).

---

## Prasyarat

- [Docker Desktop](https://www.docker.com/products/docker-desktop/) terinstall dan jalan

---

## Quick Start

```bash
git clone https://github.com/srytmj/sso.yado.git
cd sso.yado
make docker-fresh
```

`make docker-fresh` otomatis: copy `.env.example` → `.env`, build image, jalankan container, generate `APP_KEY`, migrate database, generate Passport keys, dan seed roles + admin user.

Setelah selesai, akses di **http://localhost:8000**.

Login superadmin pakai `ADMIN_EMAIL` / `ADMIN_PASSWORD` dari `.env` (default: `admin@yado.my.id` / `change-me-in-production` - **wajib ganti** kalau bukan sekadar testing lokal).

---

## Command Sehari-hari

| Command | Keterangan |
|---------|------------|
| `make docker-up` | Start container (build otomatis kalau image belum ada) |
| `make docker-down` | Stop semua container |
| `make docker-build` | Rebuild image - jalankan setelah ubah `composer.json` atau `package.json` |
| `make docker-shell` | Masuk shell container `app` (buat jalanin `artisan` manual, dll) |
| `make docker-logs` | Tail log semua container (app, nginx, postgres) |

Contoh jalanin artisan command manual:

```bash
make docker-shell
php artisan migrate
php artisan tinker
exit
```

Atau langsung tanpa masuk shell:

```bash
docker compose exec app php artisan migrate
```

---

## Struktur

```
docker/
  php/
    Dockerfile            # Multi-stage: build assets (Node) → install deps (Composer) → runtime (PHP-FPM Alpine)
    entrypoint.sh          # Sync public/ dari image ke named volume tiap container start
  nginx/default.conf       # Nginx config (dipakai compose.prod.yml)
  caddy/Caddyfile          # Caddy config (dipakai compose.standalone.yml)
  compose.prod.yml         # Production homelab/Traefik
  compose.standalone.yml   # Production universal/Caddy (atau testing lokal)
docker-compose.yml          # Dev - tetap di root, ikut konvensi default lookup Docker Compose
```

`docker-compose.yml` (dev) sengaja tetap di root supaya `docker compose up` polos (tanpa `-f`) langsung jalan. Dua file production (`compose.prod.yml`, `compose.standalone.yml`) dipindah ke dalam `docker/` supaya root repo tidak penuh - konsekuensinya, manggil keduanya manual **wajib** pakai flag `--project-directory .` (dijelaskan di section "Standalone: Universal" di bawah). `make docker-prod-*` dan `make docker-standalone-*` sudah menangani ini otomatis, jadi kalau selalu pakai `make`, tidak perlu mikirin flag ini sama sekali.

- **app** - PHP 8.4-FPM. `storage/` di-mount read-write (log, cache, session). `public/` di-mount sebagai named volume (`sso_public`) yang dishare dengan `nginx` - disinkron ulang dari isi image tiap container start via `docker/php/entrypoint.sh`, supaya asset hasil `npm run build` (yang ada di dalam image, bukan di host) bisa diakses langsung oleh Nginx.
- **nginx** - serve `public/` dari volume `sso_public`, proxy `.php` ke `app:9000`
- **postgres** - Postgres 16 Alpine, trust auth (tanpa password, khusus lokal - jangan pernah begini di production), data persist di volume `sso_postgres_data`

> Kenapa `public/` tidak di-bind-mount langsung dari host seperti `storage/`? Karena `public/build` (hasil Vite) di-`.gitignore`, jadi tidak ada di host kecuali kamu jalankan `npm run build` manual - itu justru hal yang mau dihindari dengan Docker. Named volume + sync di entrypoint memastikan Nginx dan PHP-FPM selalu lihat file yang sama persis, hasil build terbaru dari image.

Port bisa diubah lewat `.env`:
```env
APP_PORT=8000
DB_PORT=5432
```

---

## Testing Lokal: SSO + Client App, Keduanya di Docker

Ini bagian yang paling sering bikin bingung - begitu client app juga di-Docker-kan, container tidak bisa saling panggil via `localhost` seperti biasa.

### Kenapa `localhost` tidak jalan antar container

`localhost` di dalam sebuah container merujuk ke **container itu sendiri**, bukan ke host atau ke container lain. Ini penting karena OAuth flow punya dua jenis komunikasi:

1. **Browser → SSO** (redirect ke halaman login, dsb.) - ini request dari browser user, bukan dari dalam container. Selama nginx SSO map ke port host (`localhost:8000`), ini selalu jalan normal.
2. **Client app (server) → SSO (server)** - pertukaran `code` jadi `access_token` di `/oauth/token`, dan fetch profil di `/api/user`. Ini **request dari dalam container client app**, jadi `http://localhost:8000` tidak akan sampai ke container SSO.

### Solusi: `host.docker.internal`

Docker Desktop (Windows & Mac) sudah sediakan DNS khusus `host.docker.internal` yang selalu mengarah ke mesin host. Manfaatkan ini untuk komunikasi server-to-server, sementara redirect browser tetap pakai `localhost`.

Di client app, split jadi dua config:

```php
// config/sso.php (di client app)
return [
    'client_id'     => env('SSO_CLIENT_ID'),
    'client_secret' => env('SSO_CLIENT_SECRET'),
    'redirect_uri'  => env('SSO_REDIRECT_URI'),

    // Dipakai untuk redirect browser (harus localhost, sesuai apa yang diketik user)
    'base_url'      => env('SSO_BASE_URL', 'http://localhost:8000'),

    // Dipakai untuk request server-to-server (token exchange, fetch profil)
    // Kalau kosong, fallback ke base_url (aman untuk setup non-Docker)
    'internal_url'  => env('SSO_INTERNAL_URL', env('SSO_BASE_URL', 'http://localhost:8000')),
];
```

```env
# .env client app (jalan di Docker juga)
SSO_BASE_URL=http://localhost:8000
SSO_INTERNAL_URL=http://host.docker.internal:8000
```

Lalu di `SsoController` client app:

```php
// redirect() - tetap pakai base_url karena ini URL yang dibuka browser
return redirect(config('sso.base_url') . '/oauth/authorize?' . $query);

// callback() - pakai internal_url karena ini HTTP call dari server client ke server SSO
$tokenResponse = Http::asForm()->post(config('sso.internal_url') . '/oauth/token', [...]);
$profile = Http::withToken($token)->get(config('sso.internal_url') . '/api/user')->json();
```

### Langkah Setup

1. Jalankan SSO via `make docker-fresh` - nginx map ke `localhost:8000`
2. Daftarkan client app di dashboard SSO dengan redirect URI `http://localhost:8001/auth/callback` (asumsi client app di port 8001)
3. Di project client app, buat `docker-compose.yml` serupa dengan nginx map ke port **berbeda** (`8001:80`)
4. Isi `.env` client app sesuai contoh di atas - `SSO_BASE_URL` pakai `localhost`, `SSO_INTERNAL_URL` pakai `host.docker.internal`
5. Test: buka `http://localhost:8001` → klik login → redirect ke `http://localhost:8000/login` → login → redirect balik ke `http://localhost:8001/dashboard` dengan session aktif

> Linux tanpa Docker Desktop: `host.docker.internal` tidak otomatis tersedia. Tambahkan `extra_hosts: ["host.docker.internal:host-gateway"]` di service `app` pada `docker-compose.yml` client app.

---

## Standalone: Universal (Testing Lokal ATAU Deploy Publik)

`docker/compose.standalone.yml` beda dari dua file lain - **tidak ada infrastruktur yang harus disiapkan lebih dulu** (bukan Traefik seperti `docker/compose.prod.yml`, bukan juga sekadar dev seperti `docker-compose.yml`). Pakai [Caddy](https://caddyserver.com) sebagai reverse proxy, yang punya satu sifat penting: **HTTPS otomatis** - tinggal kasih domain asli, Caddy langsung provision sertifikat Let's Encrypt sendiri tanpa certbot, tanpa konfigurasi manual.

Satu file ini bisa dipakai untuk dua skenario, dibedakan cuma dari satu variabel di `.env`:

| Skenario | `.env` |
|---|---|
| Testing / pakai lokal | `CADDY_SITE_ADDRESS=http://localhost` (default - HTTP polos, tidak ada percobaan HTTPS) |
| Deploy ke server publik | `CADDY_SITE_ADDRESS=sso.namadomain.com` (tanpa skema - Caddy otomatis pakai HTTPS + Let's Encrypt) |

### Kenapa ini beda dari `docker/compose.prod.yml`

`docker/compose.prod.yml` mengasumsikan kamu sudah punya Traefik jalan (cocok untuk homelab dengan banyak project). `docker/compose.standalone.yml` sebaliknya - **berdiri sendiri**, tidak butuh network eksternal, tidak butuh reverse proxy lain sudah terpasang. Cocok untuk:
- Testing cepat sebelum commit ke setup homelab yang lebih besar
- Deploy ke VPS tunggal (DigitalOcean, Linode, EC2 standalone, dll.) yang cuma menjalankan SSO Engine ini saja
- Demo atau staging environment yang perlu HTTPS instan tanpa ribet

### Setup

1. Copy `.env.example` → `.env`, isi `DB_USERNAME`/`DB_PASSWORD` (wajib, sama seperti `docker/compose.prod.yml` - tidak ada opsi password kosong)
2. Untuk **testing lokal**: biarkan `CADDY_SITE_ADDRESS=http://localhost` (default)
3. Untuk **deploy publik**: set `CADDY_SITE_ADDRESS=sso.namadomain.com` - pastikan DNS domain itu sudah mengarah ke IP server **sebelum** container jalan (Caddy butuh itu buat verifikasi Let's Encrypt), dan port 80+443 harus terbuka ke internet (Caddy pakai 80 untuk HTTP-01 challenge, 443 untuk trafik HTTPS)
4. Deploy:
   ```bash
   make docker-standalone-deploy
   ```
5. Akses sesuai `CADDY_SITE_ADDRESS` - `http://localhost` untuk lokal, `https://sso.namadomain.com` untuk publik (otomatis HTTPS, tidak perlu langkah tambahan)

### Update

```bash
make docker-standalone-update
```

### Command

| Command | Keterangan |
|---------|------------|
| `make docker-standalone-deploy` | First-time deploy: build + up + migrate + seed |
| `make docker-standalone-update` | Update: pull + rebuild + migrate (data aman) |
| `make docker-standalone-down` | Stop semua container |
| `make docker-standalone-logs` | Tail log semua container (termasuk Caddy) |
| `make docker-standalone-shell` | Masuk shell container `app` |

### Kalau sebelumnya kena error saat coba Docker

Kalau kamu pernah coba jalanin SSO ini via Docker sebelumnya dan error - kemungkinan besar penyebabnya ekstensi PHP `sodium` yang belum ada di image (dibutuhkan Passport untuk JWT, lewat `lcobucci/jwt`). Ini sudah diperbaiki di `docker/php/Dockerfile` (ditambahkan `libsodium-dev` + `docker-php-ext-install sodium`) - kalau masih pakai image lama, jalankan ulang dengan `--build` supaya image di-rebuild dari awal:

```bash
docker compose -f docker/compose.standalone.yml --project-directory . up -d --build
```

> Perhatikan flag `--project-directory .` - wajib ada tiap kali panggil `docker compose` manual dengan `-f docker/compose.*.yml`, supaya semua path relatif di dalam file itu (env_file, volume `.env`, dst.) tetap resolve ke root repo, bukan ke folder `docker/` tempat file compose-nya berada. `make docker-standalone-*` sudah otomatis menyertakan flag ini.

### Deploy ke VM/LXC Proxmox (sekali jalan)

Kalau targetnya VM atau LXC container baru di Proxmox, ada skrip khusus yang otomatis: cek/install Docker, setup `.env` kalau belum ada, dan deteksi first-deploy vs update:

```bash
git clone https://github.com/srytmj/sso.yado.git
cd sso.yado
bash scripts/deploy-docker-proxmox.sh
```

Jalankan skrip yang sama lagi kapan pun untuk update (otomatis pull + migrate, bukan migrate:fresh - data aman). Skrip ini akan memperingatkan (dan minta konfirmasi) kalau terdeteksi dijalankan langsung di host Proxmox alih-alih di VM/LXC guest-nya - Docker sebaiknya tidak jalan langsung di host hypervisor.

---

## Production: Homelab (Proxmox + Banyak Project di 1 Host)

`docker/compose.prod.yml` dipisah dari `docker-compose.yml` (dev) - bedanya:

| | Dev (`docker-compose.yml`) | Prod (`docker/compose.prod.yml`) |
|---|---|---|
| Port | Publish langsung ke host (`localhost:8000`) | Tidak publish port - masuk lewat reverse proxy |
| PostgreSQL | Trust auth (tanpa password) | `POSTGRES_USER`/`POSTGRES_PASSWORD` wajib diisi kuat |
| Migration | `migrate:fresh` (boleh hilang data, dev) | `migrate` (data production tidak boleh hilang) |
| Restart policy | Tidak diset | `unless-stopped` |
| Network | Berdiri sendiri | Join network eksternal `proxy` (Traefik) |

### Kenapa Traefik?

Kalau kamu punya ~10 project Docker Compose di satu host Proxmox, cara paling maintainable adalah **satu reverse proxy bersama** yang otomatis "menemukan" container baru lewat label - bukan tiap project bikin sendiri-sendiri mapping port + Nginx + SSL. Traefik adalah pilihan paling umum untuk pola ini karena native baca Docker socket dan auto-provision SSL Let's Encrypt tanpa config manual per-domain.

`docker/compose.prod.yml` sudah siap untuk itu - service `nginx` join network eksternal bernama `proxy` dan punya label Traefik. Kalau kamu sudah punya Traefik jalan untuk project lain, tinggal pastikan nama network-nya cocok.

> **Pakai reverse proxy lain** (Nginx Proxy Manager, Caddy, dll.)? Prinsipnya sama: hapus block `labels:` Traefik, join network yang dipakai proxy kamu, lalu daftarkan host/domain di UI/config proxy tersebut mengarah ke container `nginx` port 80.

### Setup Awal

1. **Buat network Traefik** (sekali saja, dishare semua project):
   ```bash
   docker network create proxy
   ```
   Kalau Traefik kamu sudah jalan dan sudah punya network sendiri, pakai nama itu - edit `networks.proxy.name` di `docker/compose.prod.yml` supaya cocok.

2. **Siapkan `.env`** - selain variabel biasa, production butuh:
   ```env
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://sso.yado.my.id
   ASSET_URL=https://sso.yado.my.id

   DB_USERNAME=postgres
   DB_PASSWORD=isi-password-kuat
   ```
   Beda dari dev: production **wajib** isi `DB_USERNAME`/`DB_PASSWORD` dengan password asli (tidak boleh kosong) - keduanya langsung jadi kredensial superuser Postgres yang dipakai app buat autentikasi. Tidak ada pemisahan root/user seperti MySQL, karena image resmi Postgres cuma punya satu superuser yang dibuat dari `POSTGRES_USER`/`POSTGRES_PASSWORD`. `docker/compose.prod.yml` sudah di-set untuk require ini.

3. **Sesuaikan domain** di `docker/compose.prod.yml`, ganti bagian label Traefik:
   ```yaml
   - "traefik.http.routers.sso.rule=Host(`sso.yado.my.id`)"
   ```

4. **Deploy**:
   ```bash
   make docker-prod-deploy
   ```
   Ini build image, jalankan container, generate `APP_KEY` + Passport keys, migrate (bukan fresh), dan seed roles + admin user sekali di awal.

5. Traefik akan otomatis detect container dan provision SSL. Cek dashboard Traefik kamu untuk konfirmasi router `sso` muncul dan sehat.

### Update

```bash
make docker-prod-update
```

Pull kode terbaru, rebuild image, `migrate --force` (bukan fresh - data aman), lalu re-cache config/route/view.

### Command Production

| Command | Keterangan |
|---------|------------|
| `make docker-prod-deploy` | First-time deploy: build + up + migrate + seed |
| `make docker-prod-update` | Update: pull + rebuild + migrate (data aman) |
| `make docker-prod-down` | Stop container production |
| `make docker-prod-logs` | Tail log semua container production |
| `make docker-prod-shell` | Masuk shell container `app` production |

### Kalau Punya 10 Project di Host yang Sama

- Tiap project punya `docker/compose.prod.yml` sendiri dengan `name:` unik di baris pertama (supaya nama volume/network tidak tabrakan) - SSO Engine ini sudah pakai `name: sso-yado`.
- Semua join network `proxy` yang **sama** (satu Traefik untuk semua), tapi masing-masing punya network internal sendiri (`yado_sso_prod` di sini) untuk komunikasi app↔nginx↔postgres yang terisolasi dari project lain.
- Postgres tiap project idealnya **container terpisah** (seperti setup ini) kecuali kamu sengaja mau satu Postgres server dishare banyak project - kalau begitu, ubah `DB_HOST` ke hostname Postgres bersama dan hapus service `postgres` dari compose file ini.
- Backup: volume `sso_postgres_data` dan `sso_storage` yang perlu di-backup rutin (bukan `sso_public`, itu regenerable dari image).

---

## Migrasi Data dari MySQL Lama (Upgrade Deployment)

Kalau kamu upgrade deployment lama yang masih pakai MySQL ke versi ini (Postgres), tersedia command sekali-jalan buat mindahin semua data:

```bash
php artisan db:migrate-to-pgsql
```

Command ini baca koneksi MySQL lama dari env `MYSQL_LEGACY_HOST`/`MYSQL_LEGACY_PORT`/`MYSQL_LEGACY_DATABASE`/`MYSQL_LEGACY_USERNAME`/`MYSQL_LEGACY_PASSWORD` (lihat `.env.example`), lalu **TRUNCATE** dulu tabel-tabel tujuan di Postgres sebelum copy data - aman dijalankan berkali-kali, tapi destruktif terhadap data yang sudah ada di Postgres saat ini.

- `php artisan db:migrate-to-pgsql` - interaktif, minta konfirmasi dulu
- `php artisan db:migrate-to-pgsql --force` - skip konfirmasi (cocok buat scripted upgrade flow)

Hanya relevan untuk deployment lama era-MySQL yang sedang di-upgrade. Instalasi baru bisa abaikan ini dan biarkan `MYSQL_LEGACY_*` kosong.

---

## Troubleshooting

| Problem | Solusi |
|---------|--------|
| `docker compose up` gagal, port sudah dipakai | Ganti `APP_PORT` / `DB_PORT` di `.env`, atau stop service lokal yang pakai port sama |
| CSS/JS tidak muncul | Assets di-build saat `docker build` (stage Node) - kalau ganti file di `resources/`, jalankan `make docker-build` ulang |
| `Connection refused` ke Postgres | Container `postgres` belum ready - `docker-compose.yml` sudah pakai `healthcheck` + `depends_on: condition: service_healthy`, tapi kalau masih gagal cek `docker compose logs postgres` |
| Perubahan kode PHP tidak kerasa | Hanya `storage/` yang di-mount volume; kalau ubah file di `app/`, `routes/`, dll. perlu `make docker-build` ulang (karena kode di-copy saat build, bukan mount) |
| `host.docker.internal` tidak resolve (Linux) | Tambah `extra_hosts: ["host.docker.internal:host-gateway"]` di compose file |
| `docker-prod-deploy` gagal: network `proxy` tidak ada | `docker network create proxy` dulu, atau sesuaikan nama network di `docker/compose.prod.yml` dengan Traefik yang sudah ada |
| Traefik tidak detect container | Cek label `traefik.enable=true` ada, container join network yang sama dengan Traefik, dan Traefik punya akses ke Docker socket (`/var/run/docker.sock`) |
| Postgres production gagal start | `POSTGRES_USER`/`POSTGRES_PASSWORD` diisi dari `DB_USERNAME`/`DB_PASSWORD` - kalau kosong, container `postgres` bisa gagal start atau fallback ke default yang tidak aman. Pastikan keduanya terisi di `.env` production |
| Error terkait `ext-sodium` / JWT saat `composer install` di dalam container | Image lama belum punya ekstensi `sodium` - jalankan ulang dengan `--build` supaya image di-rebuild dari `docker/php/Dockerfile` yang sudah include `libsodium-dev` + `docker-php-ext-install sodium` |
| Caddy gagal provision HTTPS (`docker-standalone`) | Pastikan DNS domain di `CADDY_SITE_ADDRESS` sudah mengarah ke IP server **sebelum** container start, dan port 80+443 terbuka ke internet (bukan cuma di firewall lokal - cek juga security group/NSG kalau di cloud) |
| `docker-standalone` gak bisa diakses sama sekali walau container "Up" | Cek jangan taruh port di `CADDY_SITE_ADDRESS` (mis. `http://localhost:8080`) kalau `HTTP_PORT` sudah diganti dari default - itu bikin Caddy listen di port yang disebutkan DI DALAM alamat itu (jadi 8080 di dalam container), padahal Docker cuma mapping `HTTP_PORT:80`. `CADDY_SITE_ADDRESS` biarkan tanpa port, atur port host lewat `HTTP_PORT`/`HTTPS_PORT` saja |
| `docker-prod`/`docker-standalone` - `composer install` gagal dengan error `Class ... not found` (mis. `Laravel\Pail\PailServiceProvider`) | `bootstrap/cache/*.php` dari host ikut ke-copy ke image dan isinya menyebut package dev yang tidak ikut ter-install di `--no-dev`. Sudah difix di `.dockerignore` + `Dockerfile` (copy `bootstrap/cache` hasil regenerate dari stage vendor) - kalau masih kena, rebuild image dari awal dengan `--build` |

---

## Referensi

- Setup manual tanpa Docker (2 app di 1 device pakai `php artisan serve`): lihat section "Testing Lokal" di [`INTEGRATION.md`](INTEGRATION.md)
- Deploy production non-Docker (EC2/VM manual via SSH): [`DEPLOY_AWS.md`](DEPLOY_AWS.md), [`DEPLOY_AZURE.md`](DEPLOY_AZURE.md)
- Deploy production Docker, homelab/Proxmox dengan Traefik yang sudah ada: section "Production: Homelab" di atas
- Deploy production Docker, universal/standalone (VPS tunggal, tanpa Traefik, HTTPS otomatis): section "Standalone: Universal" di atas
