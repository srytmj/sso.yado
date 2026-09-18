#!/usr/bin/env bash
# Deploy SSO Engine via Docker (docker/compose.standalone.yml) ke VM/LXC guest di Proxmox.
#
# PENTING: jalankan skrip ini DI DALAM VM atau LXC container (guest) yang hidup di
# Proxmox - BUKAN langsung di host Proxmox itu sendiri. Menjalankan Docker di host
# Proxmox tidak direkomendasikan (bentrok dengan resource management Proxmox, dan
# bikin host lebih rentan). Guest OS yang diasumsikan: Debian/Ubuntu.
#
# Script ini otomatis deteksi first-deploy vs update - aman dipakai berulang kali.
#
# Pemakaian (dari dalam folder hasil git clone):
#   bash scripts/deploy-docker-proxmox.sh

set -euo pipefail

echo "=== SSO Engine - Deploy Docker (Proxmox VM/LXC) ==="

# Peringatan kalau ternyata dijalankan di host Proxmox asli, bukan guest-nya
if command -v pveversion >/dev/null 2>&1; then
    echo "[!] Terdeteksi jalan di HOST Proxmox (ada 'pveversion'), bukan di VM/LXC guest."
    echo "    Sangat disarankan jalankan Docker di dalam VM/LXC, bukan langsung di host Proxmox."
    read -p "    Tetap lanjutkan di host ini? (y/N) " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        exit 1
    fi
fi

# Install Docker kalau belum ada
if ! command -v docker >/dev/null 2>&1; then
    echo "Docker belum terinstall - install sekarang via get.docker.com (Debian/Ubuntu)..."
    curl -fsSL https://get.docker.com | sh
    sudo usermod -aG docker "$USER"
    echo ""
    echo "[!] Docker baru diinstall. Logout lalu login ulang (atau jalankan: newgrp docker)"
    echo "    supaya user ini bisa pakai 'docker' tanpa sudo, lalu jalankan skrip ini lagi."
    exit 0
fi

# Setup .env jika belum ada
if [ ! -f .env ]; then
    cp .env.example .env
    echo "[!] .env dibuat dari .env.example. Wajib diisi sebelum lanjut, minimal:"
    echo "    - CADDY_SITE_ADDRESS   domain publik tanpa skema (mis. sso.namadomain.com),"
    echo "                           atau biarkan http://localhost untuk sekadar testing"
    echo "    - DB_USERNAME, DB_PASSWORD  (WAJIB diisi, tidak boleh kosong untuk deploy"
    echo "                           Docker - dipakai langsung sebagai superuser Postgres)"
    echo "    - ADMIN_EMAIL, ADMIN_PASSWORD"
    echo ""
    echo "Jalankan lagi skrip ini setelah .env diisi."
    exit 0
fi

if ! grep -qE "^DB_PASSWORD=.+" .env; then
    echo "[!] DB_PASSWORD masih kosong di .env."
    echo "    Deploy Docker (standalone/production) tidak mengizinkan password kosong -"
    echo "    beda dari setup lokal biasa yang boleh pakai user tanpa password."
    exit 1
fi

# Pull latest (aman diabaikan kalau belum ada remote/branch, mis. first clone manual)
git pull origin main 2>/dev/null || true

COMPOSE="docker compose -f docker/compose.standalone.yml --project-directory ."

# Deteksi first-deploy vs update: kalau container app belum pernah dibuat, ini first-deploy
IS_FIRST_DEPLOY=0
if ! $COMPOSE ps --status running --services 2>/dev/null | grep -q "^app$"; then
    IS_FIRST_DEPLOY=1
fi

echo "Build image + start container..."
$COMPOSE up -d --build

if [ "$IS_FIRST_DEPLOY" = "1" ]; then
    echo ""
    echo "Deploy pertama terdeteksi - generate key, migrate, Passport keys, seed..."
    $COMPOSE exec -T app php artisan key:generate
    $COMPOSE exec -T app php artisan migrate --force
    $COMPOSE exec -T app php artisan passport:keys --force
    $COMPOSE exec -T app php artisan db:seed --force
else
    echo ""
    echo "Update terdeteksi - migrate (bukan migrate:fresh, data aman)..."
    $COMPOSE exec -T app php artisan migrate --force
fi

$COMPOSE exec -T app php artisan config:cache
$COMPOSE exec -T app php artisan route:cache
$COMPOSE exec -T app php artisan view:cache

echo ""
echo "=== Deploy selesai ==="
echo "Akses sesuai CADDY_SITE_ADDRESS di .env - kalau diisi domain publik, Caddy sudah"
echo "otomatis provision HTTPS Let's Encrypt (pastikan DNS domain itu sudah mengarah ke"
echo "IP VM/LXC ini dan port 80+443 terbuka ke internet)."
