#!/bin/sh
set -e

# public/ di-mount sebagai named volume (dishare dengan container nginx) sehingga
# isinya TIDAK otomatis ter-refresh saat image di-rebuild - Docker cuma auto-populate
# volume kosong sekali di awal. Sync manual di sini memastikan file (termasuk hasil
# build Vite) selalu yang terbaru dari image tiap container start.
rm -rf /var/www/html/public/*
cp -rf /var/www/html/public-src/. /var/www/html/public/

# Symlink storage/app/public -> public/storage (dibutuhkan untuk avatar upload disk lokal).
# public/ baru saja di-wipe+resync di atas jadi symlink lama ikut hilang - buat ulang tiap start.
cd /var/www/html && php artisan storage:link --force > /dev/null 2>&1 || true

exec "$@"
