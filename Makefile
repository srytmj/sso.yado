.PHONY: help sync deploy update ssh remote-deploy remote-update docker-up docker-down docker-build docker-fresh docker-shell docker-logs docker-prod-deploy docker-prod-update docker-prod-down docker-prod-logs docker-prod-shell docker-standalone-deploy docker-standalone-update docker-standalone-down docker-standalone-logs docker-standalone-shell

# Load SSH config dari .env jika ada
-include .env
SERVER_USER ?= ubuntu
SERVER_PATH ?= /var/www/sso

# docker/compose.prod.yml dan docker/compose.standalone.yml hidup di dalam docker/,
# tapi semua path relatif di dalamnya (env_file, volumes, build context) tetap ditulis
# seolah-olah relatif ke root repo - --project-directory . yang bikin itu valid tanpa
# perlu ubah isi file compose sama sekali.
PROD_COMPOSE := docker compose -f docker/compose.prod.yml --project-directory .
STANDALONE_COMPOSE := docker compose -f docker/compose.standalone.yml --project-directory .

help:
	@echo "Available commands:"
	@echo "  make sync           Sync stack dari docs/SRS.md ke .claude/CLAUDE.md"
	@echo ""
	@echo "  -- Di server (jalankan setelah SSH) --"
	@echo "  make deploy         First-time deploy wizard di server"
	@echo "  make update         Pull latest + rebuild di server"
	@echo ""
	@echo "  -- Dari lokal ke server (butuh SERVER_HOST di .env) --"
	@echo "  make ssh            SSH ke server"
	@echo "  make remote-deploy  First-time deploy via SSH dari lokal"
	@echo "  make remote-update  Update server via SSH dari lokal"
	@echo ""
	@echo "  -- Docker (lokal) --"
	@echo "  make docker-fresh   First-time setup: build + up + migrate + seed"
	@echo "  make docker-up      Start container (build jika perlu)"
	@echo "  make docker-down    Stop container"
	@echo "  make docker-build   Rebuild image (setelah ubah composer.json/package.json)"
	@echo "  make docker-shell   Masuk shell container app"
	@echo "  make docker-logs    Tail log semua container"
	@echo ""
	@echo "  -- Docker Production (homelab/Proxmox, butuh network 'proxy' Traefik) --"
	@echo "  make docker-prod-deploy  First-time deploy: build + up + migrate + seed"
	@echo "  make docker-prod-update  Update: pull + rebuild + migrate (data aman)"
	@echo "  make docker-prod-down    Stop container production"
	@echo "  make docker-prod-logs    Tail log container production"
	@echo "  make docker-prod-shell   Masuk shell container app production"
	@echo ""
	@echo "  -- Docker Standalone (universal - testing lokal ATAU deploy publik, tanpa Traefik) --"
	@echo "  make docker-standalone-deploy  First-time deploy: build + up + migrate + seed"
	@echo "  make docker-standalone-update  Update: pull + rebuild + migrate (data aman)"
	@echo "  make docker-standalone-down    Stop container"
	@echo "  make docker-standalone-logs    Tail log semua container (termasuk Caddy)"
	@echo "  make docker-standalone-shell   Masuk shell container app"
	@echo ""
	@echo "  -- Deploy Docker ke Proxmox VM/LXC baru (sekali jalan, dari dalam guest) --"
	@echo "  bash scripts/deploy-docker-proxmox.sh   Install Docker + first-time standalone deploy"

sync:
	bash sync.sh

deploy:
	sudo bash scripts/deploy.sh

update:
	bash scripts/deploy.sh

ssh:
	@if [ -z "$(SERVER_HOST)" ]; then echo "Error: SERVER_HOST belum diset di .env"; exit 1; fi
	ssh -i $(SSH_KEY_PATH) $(SERVER_USER)@$(SERVER_HOST)

remote-deploy:
	@if [ -z "$(SERVER_HOST)" ]; then echo "Error: SERVER_HOST belum diset di .env"; exit 1; fi
	ssh -i $(SSH_KEY_PATH) $(SERVER_USER)@$(SERVER_HOST) "cd $(SERVER_PATH) && sudo bash scripts/deploy.sh"

remote-update:
	@if [ -z "$(SERVER_HOST)" ]; then echo "Error: SERVER_HOST belum diset di .env"; exit 1; fi
	ssh -i $(SSH_KEY_PATH) $(SERVER_USER)@$(SERVER_HOST) "cd $(SERVER_PATH) && bash scripts/deploy.sh"

docker-fresh:
	@if [ ! -f .env ]; then cp .env.example .env; fi
	docker compose up -d --build
	docker compose exec app php artisan key:generate
	docker compose exec app php artisan migrate:fresh --force
	docker compose exec app php artisan passport:keys --force
	docker compose exec app php artisan db:seed --force
	@echo "Selesai. Akses di http://localhost:8000"

docker-up:
	docker compose up -d

docker-down:
	docker compose down

docker-build:
	docker compose up -d --build

docker-shell:
	docker compose exec app sh

docker-logs:
	docker compose logs -f

docker-prod-deploy:
	@if [ ! -f .env ]; then echo "Error: .env belum ada. Copy dari .env.example dan isi dulu."; exit 1; fi
	@if ! docker network inspect proxy >/dev/null 2>&1; then echo "Error: network 'proxy' (Traefik) belum ada. Buat dulu: docker network create proxy"; exit 1; fi
	$(PROD_COMPOSE) up -d --build
	$(PROD_COMPOSE) exec app php artisan key:generate
	$(PROD_COMPOSE) exec app php artisan migrate --force
	$(PROD_COMPOSE) exec app php artisan passport:keys --force
	$(PROD_COMPOSE) exec app php artisan db:seed --force
	$(PROD_COMPOSE) exec app php artisan config:cache
	$(PROD_COMPOSE) exec app php artisan route:cache
	$(PROD_COMPOSE) exec app php artisan view:cache
	@echo "Selesai. Cek routing Traefik untuk domain yang dikonfigurasi di docker/compose.prod.yml."

docker-prod-update:
	git pull origin main
	$(PROD_COMPOSE) up -d --build
	$(PROD_COMPOSE) exec app php artisan migrate --force
	$(PROD_COMPOSE) exec app php artisan config:cache
	$(PROD_COMPOSE) exec app php artisan route:cache
	$(PROD_COMPOSE) exec app php artisan view:cache

docker-prod-down:
	$(PROD_COMPOSE) down

docker-prod-logs:
	$(PROD_COMPOSE) logs -f

docker-prod-shell:
	$(PROD_COMPOSE) exec app sh

docker-standalone-deploy:
	@if [ ! -f .env ]; then cp .env.example .env; echo "[!] .env dibuat dari .env.example. Edit CADDY_SITE_ADDRESS, DB_*, ADMIN_EMAIL/PASSWORD lalu jalankan ulang."; exit 0; fi
	$(STANDALONE_COMPOSE) up -d --build
	$(STANDALONE_COMPOSE) exec app php artisan key:generate
	$(STANDALONE_COMPOSE) exec app php artisan migrate --force
	$(STANDALONE_COMPOSE) exec app php artisan passport:keys --force
	$(STANDALONE_COMPOSE) exec app php artisan db:seed --force
	$(STANDALONE_COMPOSE) exec app php artisan config:cache
	$(STANDALONE_COMPOSE) exec app php artisan route:cache
	$(STANDALONE_COMPOSE) exec app php artisan view:cache
	@echo "Selesai. Akses sesuai CADDY_SITE_ADDRESS di .env (default: http://localhost)."

docker-standalone-update:
	git pull origin main
	$(STANDALONE_COMPOSE) up -d --build
	$(STANDALONE_COMPOSE) exec app php artisan migrate --force
	$(STANDALONE_COMPOSE) exec app php artisan config:cache
	$(STANDALONE_COMPOSE) exec app php artisan route:cache
	$(STANDALONE_COMPOSE) exec app php artisan view:cache

docker-standalone-down:
	$(STANDALONE_COMPOSE) down

docker-standalone-logs:
	$(STANDALONE_COMPOSE) logs -f

docker-standalone-shell:
	$(STANDALONE_COMPOSE) exec app sh
