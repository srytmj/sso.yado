#!/usr/bin/env bash
# Deploy / update script untuk SSO Engine
# First deploy : sudo bash scripts/deploy.sh
# Update       : bash scripts/deploy.sh

set -euo pipefail

echo "=== SSO Engine - Deploy ==="

# Setup .env jika belum ada
if [ ! -f .env ]; then
    cp .env.example .env
    echo "[!] .env dibuat dari .env.example. Edit APP_URL, DB_*, ADMIN_EMAIL, ADMIN_PASSWORD lalu jalankan ulang."
    exit 0
fi

# Generate APP_KEY jika kosong
if ! grep -q "^APP_KEY=base64:" .env; then
    php artisan key:generate
fi

# Pull latest
git pull origin main 2>/dev/null || true

# Install dependencies
composer install --no-dev --optimize-autoloader

# Fix permission storage sebelum artisan jalan
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

# Migrate database (bukan migrate:fresh agar data lama aman)
php artisan migrate --force

# Symlink storage/app/public -> public/storage (dibutuhkan untuk avatar upload disk lokal)
php artisan storage:link --force || true

# Generate Passport encryption keys jika belum ada
if [ ! -f storage/oauth-private.key ]; then
    php artisan passport:keys --force
fi

# Seed roles + admin user (hanya saat setup awal)
php artisan db:seed --force

# Optimize - jalankan sebagai www-data agar file cache writable oleh Nginx
sudo -u www-data php artisan config:cache
sudo -u www-data php artisan route:cache
sudo -u www-data php artisan view:cache

echo ""
echo "=== Deploy selesai ==="
echo "Pastikan ASSET_URL di .env sudah sesuai dengan domain/IP yang diakses."
