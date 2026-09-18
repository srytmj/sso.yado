# STRUCTURE - SSO Engine

Dokumen ini mendeskripsikan struktur folder project dan konvensi penamaan.

## Root

```
sso.yado/
  app/                    # Laravel application code
  config/                 # Laravel config files
  resources/
    js/                   # Svelte 5 pages (Pages/), layouts (Layouts/), app.js
    css/                  # Tailwind CSS v4 & DaisyUI styles (app.css)
    views/app.blade.php   # Inertia HTML root template
  routes/                 # web.php, api.php, oauth.php (Passport)
  docs/                   # Dokumentasi project (PRD, SRS, tickets)
  scripts/                # deploy.sh, deploy-docker-proxmox.sh
  docker/                 # Dockerfile, entrypoint.sh, config nginx/caddy, compose production
    php/                  # Dockerfile + entrypoint.sh
    nginx/                # default.conf (dipakai compose.prod.yml)
    caddy/                # Caddyfile (dipakai compose.standalone.yml)
    compose.prod.yml      # Docker Compose production (homelab/Proxmox + Traefik)
    compose.standalone.yml # Docker Compose universal (Caddy, self-contained, lokal ATAU publik)
  docker-compose.yml      # Docker Compose dev: app, nginx, postgres (tetap di root - default lookup Docker Compose)
  logs/                   # sync.log, deploy.log (gitignored)
  .claude/                # Claude Code config (CLAUDE.md + agents)
  Makefile                # Shortcut commands
  sync.sh                 # Sync stack dari SRS ke CLAUDE.md
  SESSION-PROMPTS.md      # Copy-paste prompts untuk tiap agent session
```

## docs/

```
docs/
  AI_AGENT_GUIDE.md   # Authoritative operating manual untuk AI coding agents & DevOps
  PRD.md              # Product Requirements Document
  SRS.md              # Software Requirements Specification
  STRUCTURE.md        # File ini
  TODO.md             # Backlog dan catatan informal
  INTEGRATION.md      # Panduan integrasi untuk developer client app (manual)
  AI_INTEGRATION.md   # Brief integrasi untuk AI assistant (lempar ke Claude/Cursor)
  API.md              # HTTP API endpoint reference & JSON schemas
  DEPLOY_AZURE.md     # Tutorial deploy ke Azure (VM atau App Service)
  DEPLOY_AWS.md       # Tutorial deploy ke AWS (EC2 atau Elastic Beanstalk)
  DOCKER.md           # Cara jalanin via Docker untuk development lokal & production
  tickets/            # gitignored - internal workflow
    TASK-XXX.md       # Feature tickets
    bugs/
      BUG-XXX.md      # Bug tickets
```

## .claude/

```
.claude/
  CLAUDE.md         # Project context & constraints untuk Claude
  settings.local.json
  agents/
    PM.md           # Persona & rules untuk PM session
    DEV.md          # Persona & rules untuk DEV session
    QA.md           # Persona & rules untuk QA session
```

## Konvensi Ticket

- Feature ticket: `docs/tickets/TASK-001.md`, `TASK-002.md`, dst.
- Bug ticket: `docs/tickets/bugs/BUG-001.md`, dst.
- Nomor sequential, jangan reuse nomor yang sudah ada.

## Konvensi File Kode

- Controller: `app/Http/Controllers/{Domain}/{Name}Controller.php`
- Service: `app/Services/{Domain}/{Name}Service.php`
- Action: `app/Actions/{Domain}/{Name}Action.php`
- Model: `app/Models/{Name}.php`
- Request: `app/Http/Requests/{Name}Request.php`
- Middleware: `app/Http/Middleware/{Name}.php`
