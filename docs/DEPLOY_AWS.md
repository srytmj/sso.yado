# Deploy ke AWS - SSO Engine

Panduan deploy `sso.yado.my.id` ke AWS menggunakan **EC2** (mirip VPS, paling fleksibel) atau **Elastic Beanstalk** (managed, auto-scaling).

---

## Pilih Pendekatan

| | EC2 | Elastic Beanstalk |
|---|---|---|
| Setup | Manual via SSH | CLI / console |
| Kontrol | Penuh | Terbatas |
| Harga | Dari ~$8/bln (t3.micro) | EC2 + overhead ~$10-15/bln |
| Cocok untuk | Full control, mirip VPS | Cepat deploy, auto-scaling |

**Rekomendasi**: EC2 - paling mirip dengan workflow `make deploy` yang sudah ada dan paling mudah di-debug.

---

## Cara A - EC2 (Rekomendasi)

### 1. Buat EC2 Instance

1. Buka [console.aws.amazon.com](https://console.aws.amazon.com) → **EC2** → **Launch Instance**
2. Konfigurasi:
   - **Name**: `sso-yado`
   - **AMI**: Ubuntu Server 24.04 LTS (Free Tier eligible) - **jangan pilih 26.04**, ondrej PPA belum support
   - **Instance type**: `t3.micro` (2 vCPU, 1GB RAM) - cukup untuk mulai
   - **Key pair**: Create new → download `.pem` → simpan baik-baik
   - **Security Group** - buka inbound:
     - SSH (22) dari IP kamu saja
     - HTTP (80) dari anywhere
     - HTTPS (443) dari anywhere
3. Klik **Launch Instance**

### 2. Alokasi Elastic IP (Opsional tapi Direkomendasikan)

IP EC2 berubah setiap restart jika tidak pakai Elastic IP:

1. EC2 → **Elastic IPs** → **Allocate Elastic IP**
2. **Associate** ke instance yang baru dibuat
3. Gunakan Elastic IP ini untuk DNS record

### 3. Connect ke Instance

```bash
chmod 400 your-key.pem
ssh -i your-key.pem ubuntu@<ELASTIC_IP_ATAU_PUBLIC_IP>
```

### 4. Install Dependencies

```bash
# Update system
sudo apt update && sudo apt upgrade -y

# Install PHP 8.4 + extensions (wajib - symfony 8.x require PHP >=8.4.1)
sudo apt install -y software-properties-common
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update
sudo apt install -y php8.4 php8.4-fpm php8.4-pgsql php8.4-mbstring \
    php8.4-xml php8.4-curl php8.4-zip php8.4-bcmath php8.4-cli

# Jika pakai Ubuntu 26.04 (Resolute), skip PPA - gunakan PHP 8.5 dari default repo:
# sudo apt install -y php8.5 php8.5-fpm php8.5-pgsql php8.5-mbstring \
#     php8.5-xml php8.5-curl php8.5-zip php8.5-bcmath php8.5-cli

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Install Nginx
sudo apt install -y nginx

# Install PostgreSQL
sudo apt install -y postgresql postgresql-contrib
# Tidak ada mysql_secure_installation di Postgres - Ubuntu default pakai peer/md5 auth,
# set password user postgres langsung di step "Setup Database" di bawah

# Install Node (untuk Vite build)
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs

# Install Git
sudo apt install -y git
```

### 5. Setup Database

Masuk sebagai superuser `postgres` (peer auth lokal, tanpa password):

```bash
sudo -u postgres psql
```

```sql
CREATE DATABASE db_sso;
ALTER USER postgres WITH PASSWORD 'strong-password';
\q
```

Proyek ini pakai model satu superuser Postgres (bukan pola root + user dedicated seperti MySQL) - `.env` nanti diisi `DB_USERNAME=postgres` dan `DB_PASSWORD` sama dengan password yang di-set di atas.

### 6. Clone & Setup Project

```bash
cd /var/www
sudo git clone https://github.com/srytmj/sso.yado.git sso
sudo chown -R ubuntu:ubuntu /var/www/sso
cd /var/www/sso

cp .env.example .env
nano .env
```

Isi `.env` yang wajib diubah:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://sso.yado.my.id
ASSET_URL=https://sso.yado.my.id   # harus sama dengan APP_URL - tanpa ini CSS tidak load

DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=db_sso
DB_USERNAME=postgres
DB_PASSWORD=strong-password

SESSION_DRIVER=database

MAIL_MAILER=resend
RESEND_API_KEY=re_xxx
MAIL_FROM_ADDRESS=noreply@yado.my.id

ADMIN_EMAIL=your@email.com
ADMIN_PASSWORD=strong-admin-password
```

```bash
# Jalankan deploy script (sama dengan make deploy)
sudo bash scripts/deploy.sh

# Build assets
npm install && npm run build

# Seed roles + admin user
php artisan db:seed
```

### 7. Konfigurasi Nginx

```bash
sudo nano /etc/nginx/sites-available/sso
```

```nginx
server {
    listen 80;
    server_name sso.yado.my.id;
    root /var/www/sso/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.4-fpm.sock;  # ganti ke php8.5-fpm.sock jika Ubuntu 26.04
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

```bash
# Aktifkan site
sudo ln -s /etc/nginx/sites-available/sso /etc/nginx/sites-enabled/
sudo rm -f /etc/nginx/sites-enabled/default
sudo nginx -t
sudo systemctl reload nginx

# Set permission storage
sudo chown -R www-data:www-data /var/www/sso/storage /var/www/sso/bootstrap/cache
sudo chmod -R 775 /var/www/sso/storage /var/www/sso/bootstrap/cache
```

### 8. Setup HTTPS via Let's Encrypt

```bash
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d sso.yado.my.id

# Auto-renewal
sudo systemctl enable certbot.timer
```

### 9. Arahkan Domain ke EC2

Di Cloudflare DNS:
- Tambah **A record**: `sso` → `<ELASTIC_IP>` (proxy: ON)
- SSL/TLS mode di Cloudflare: **Full (strict)**

### 10. Verifikasi

```bash
sudo systemctl status nginx
sudo systemctl status php8.4-fpm
sudo systemctl status postgresql

curl -I https://sso.yado.my.id
```

---

## Cara B - Elastic Beanstalk

### 1. Install EB CLI

```bash
pip install awsebcli
aws configure  # isi Access Key ID + Secret dari IAM
```

### 2. Buat RDS (PostgreSQL Managed)

1. AWS Console → **RDS** → **Create database**
2. Engine: PostgreSQL 16, Template: Free tier
3. DB identifier: `sso-db`, username: `postgres`, password: isi sendiri
4. Catat **Endpoint** (hostname) setelah database ready

### 3. Init Elastic Beanstalk

```bash
cd /path/to/sso.yado

eb init
# Pilih region, platform: PHP 8.3, create new app: sso-yado
```

Buat `.ebextensions/nginx.conf` untuk PHP-FPM:

```yaml
# .ebextensions/laravel.config
option_settings:
  aws:elasticbeanstalk:container:php:phpini:
    document_root: /public
  aws:elasticbeanstalk:application:environment:
    APP_ENV: production
    APP_DEBUG: false
```

### 4. Set Environment Variables

```bash
eb setenv \
  APP_ENV=production \
  APP_DEBUG=false \
  APP_URL=https://sso.yado.my.id \
  APP_KEY=$(php artisan key:generate --show) \
  DB_HOST=<rds-endpoint> \
  DB_PORT=5432 \
  DB_DATABASE=db_sso \
  DB_USERNAME=postgres \
  DB_PASSWORD=xxx \
  SESSION_DRIVER=database \
  RESEND_API_KEY=re_xxx \
  MAIL_FROM_ADDRESS=noreply@yado.my.id
```

### 5. Deploy

```bash
eb create sso-production
# atau update setelah ada:
eb deploy
```

### 6. Jalankan Migration (sekali)

```bash
eb ssh
cd /var/app/current
php artisan migrate --force
php artisan passport:install --force
php artisan db:seed
```

### 7. Custom Domain + HTTPS

1. Buka EB environment → **Configuration** → **Load Balancer** → tambah HTTPS listener (port 443)
2. Upload SSL certificate via **AWS Certificate Manager** (ACM) - request certificate untuk `sso.yado.my.id`
3. Cloudflare DNS: CNAME `sso` → EB environment URL

---

## Update (Kedua Cara)

### EC2

**Dari lokal** (tanpa perlu SSH manual - butuh `SERVER_HOST` di `.env`):

```bash
make remote-update
```

**Manual via SSH**:

```bash
# SSH ke server
make ssh
# atau: ssh -i your-key.pem ubuntu@<IP>

# Di dalam server:
cd /var/www/sso
bash scripts/update.sh   # sama dengan: make update

# Jika ada perubahan assets:
npm run build
sudo systemctl reload nginx
```

### Elastic Beanstalk

```bash
eb deploy
```

---

## Make Commands (EC2)

Tambahkan variabel berikut ke `.env` lokal untuk enable remote commands:

```env
SERVER_HOST=<elastic-ip-ec2>
SSH_KEY_PATH=~/.ssh/your-key.pem
SERVER_USER=ubuntu
SERVER_PATH=/var/www/sso
```

| Command | Keterangan |
|---------|------------|
| `make ssh` | SSH ke server |
| `make remote-deploy` | First-time deploy dari lokal via SSH |
| `make remote-update` | Pull latest + rebuild dari lokal via SSH |
| `make deploy` | First-time deploy (dijalankan **di dalam** server) |
| `make update` | Pull latest + rebuild (dijalankan **di dalam** server) |

---

## Opsional: RDS sebagai Database di EC2

Kalau mau PostgreSQL di server terpisah (lebih production-grade dari Postgres lokal):

1. AWS Console → RDS → Create database → PostgreSQL 16
2. **VPC Security Group**: izinkan koneksi dari EC2 Security Group ke port 5432
3. Update `.env` di EC2:
   ```env
   DB_HOST=<rds-endpoint>
   DB_READ_HOST=<rds-endpoint>
   DB_WRITE_HOST=<rds-endpoint>
   ```

---

## Checklist Post-Deploy

- [ ] `https://sso.yado.my.id` accessible, tampil landing page
- [ ] Login superadmin berhasil
- [ ] `GET /api/user` dengan token valid → return JSON profil
- [ ] Create OAuth client di dashboard → Quick Start panel muncul
- [ ] Forgot password → email terkirim (Resend domain harus sudah verified)
- [ ] SSL aktif, tidak ada mixed content warning
- [ ] `APP_DEBUG=false` - error tidak expose stack trace ke browser
- [ ] `storage/` dan `bootstrap/cache/` writable oleh `www-data`

---

## Troubleshooting

| Problem | Solusi |
|---------|--------|
| 500 error | `tail -f /var/www/sso/storage/logs/laravel.log` |
| 403 Forbidden | `sudo chown -R www-data:www-data /var/www/sso/storage /var/www/sso/bootstrap/cache` |
| DB connection failed | Cek `DB_HOST`, `DB_USERNAME`, `DB_PASSWORD` di `.env` - jika RDS, cek Security Group |
| Passport keys error | `php artisan passport:install --force` |
| Assets 404 | `npm run build` belum dijalankan |
| HTTPS redirect loop | Di Cloudflare SSL/TLS mode pastikan **Full (strict)**, bukan Flexible |
| Session tidak persist | `SESSION_DRIVER=database` dan `php artisan session:table && php artisan migrate` |
| CSS/JS tidak load | Pastikan `ASSET_URL` di `.env` sama dengan URL yang diakses (http vs https, domain vs IP) |
| `composer install` gagal (PHP version) | Symfony 8.x butuh PHP >=8.4.1 - install PHP 8.4 dari ondrej PPA |
| `psql: FATAL: password authentication failed for user postgres` | Set/reset password via `sudo -u postgres psql` lalu `ALTER USER postgres WITH PASSWORD '...';`, dan pastikan `DB_PASSWORD` di `.env` sama |
| `config:cache` Permission denied | Jalankan sebagai www-data: `sudo -u www-data php artisan config:cache` |
| Cloudflare 522 | EC2 Security Group belum allow port 80/443 inbound dari 0.0.0.0/0 |
