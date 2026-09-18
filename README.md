# Yado SSO

[![OAuth 2.0](https://img.shields.io/badge/OAuth_2.0-RFC_6749_%2B_RFC_7636_(PKCE)-000000?style=flat-square)](#)
[![Laravel](https://img.shields.io/badge/Laravel-11.x-000000?style=flat-square&logo=laravel&logoColor=white)](#)
[![Svelte](https://img.shields.io/badge/Svelte-5.x_(Runes)-000000?style=flat-square&logo=svelte&logoColor=white)](#)
[![Inertia.js](https://img.shields.io/badge/Inertia.js-v2-000000?style=flat-square)](#)
[![Tailwind CSS](https://img.shields.io/badge/Tailwind_CSS-v4-000000?style=flat-square&logo=tailwindcss&logoColor=white)](#)
[![PostgreSQL](https://img.shields.io/badge/PostgreSQL-16-000000?style=flat-square&logo=postgresql&logoColor=white)](#)
[![Docker](https://img.shields.io/badge/Docker-Multi--stage_Production-000000?style=flat-square&logo=docker&logoColor=white)](#)
[![License](https://img.shields.io/badge/License-MIT-000000?style=flat-square)](#)

Central Identity Provider (IdP) and single sign-on engine for the **Yado** microservice ecosystem. Built on **OAuth 2.0 Authorization Code with mandatory PKCE (S256)**, Yado SSO issues scoped identity tokens, coordinates centralized session invalidation, and provides an administrative interface constructed in **Svelte 5** and **Inertia.js**.

- **Production Endpoint**: `https://sso.yado.my.id`
- **Protocol**: OAuth 2.0 Authorization Code Grant with Proof Key for Code Exchange (RFC 6749 + RFC 7636)
- **Token Format**: Bearer Access Tokens (60 min TTL) + Single-Use Rotating Refresh Tokens (30 days TTL)

> [!IMPORTANT]
> ### 🤖 If you are an AI Agent, Read This First
> If you are an autonomous AI coding agent (*Cursor, Claude Code, Antigravity, GitHub Copilot, Codex, etc.*) or automated DevOps runner:
> - **Maintaining or operating this repository**: You MUST read and strictly adhere to [docs/AI_AGENT_GUIDE.md](docs/AI_AGENT_GUIDE.md) before proposing modifications, executing terminal tasks, or rebuilding containers. **Strict Rule**: NEVER execute `php artisan migrate:fresh` on production or live containers. Database state is persistent in the `sso_postgres_data` volume and all updates must be non-destructive (`php artisan migrate --force`).
> - **Integrating an ecosystem microservice**: Read [docs/AI_AGENT_GUIDE.md](docs/AI_AGENT_GUIDE.md) and [docs/AI_INTEGRATION.md](docs/AI_INTEGRATION.md). Enforcing PKCE `S256`, passing `code_challenge`, handling 60-minute token rotation, and implementing two-phase logout are required specifications.

---

## Architecture Overview

```
                       +-----------------------------------+
                       |         End-User Browser          |
                       +-----------------------------------+
                             |                       ^
              1. /oauth/authorize                    | 2. Redirect with Code
              (PKCE Challenge S256)                  |
                             v                       |
+-------------------------------------------------------------------------+
|                               Yado SSO                                  |
|   +-----------------------+                 +-----------------------+   |
|   |   Svelte 5 Frontend   |                 |   Laravel 11 Engine   |   |
|   |  - Auth & Consent     | <--- Inertia ---|  - OAuth2 Server      |   |
|   |  - Admin Dashboard    |                 |  - Session / Audit    |   |
|   +-----------------------+                 +-----------------------+   |
+-------------------------------------------------------------------------+
       |                  |                                   ^
       | Persistence      | Transactional                     | 3. /oauth/token
       v                  v                                   | (Code + Verifier)
+--------------+   +--------------+                 +--------------------+
|  PostgreSQL  |   | Resend /     |                 | Client Microservice|
|  16 Database |   | SMTP Server  |                 | (Anime Reader, etc)|
+--------------+   +--------------+                 +--------------------+
```

---

## Technical Stack

| Domain | Technology | Details |
|---|---|---|
| **Backend Core** | PHP 8.4+, Laravel 11 | Strict typing, thin controllers, domain service classes, single-responsibility actions. |
| **Auth Server** | Laravel Passport | OAuth2 Server implementing RFC 6749 and RFC 7636 (PKCE enforced). |
| **Frontend SPA** | Svelte 5 (Runes) + Inertia.js v2 | Zero-client-side-router full SPA with reactive form bindings. |
| **Styling & Motion** | Tailwind CSS v4, DaisyUI v5, GSAP | Pure monochrome aesthetic (`oklch` light/dark), Playfair Display serif paired with Instrument Sans. |
| **Database** | PostgreSQL 16 | ACID-compliant persistence, read/write splitting configuration, persistent Docker volumes. |
| **Storage & Mail** | Local disk or S3/R2; Resend API or SMTP | Runtime configurable from the superadmin dashboard without requiring `.env` reboots. |

---

## Core Capabilities

- **Strict PKCE Enforcement**: Every authorization flow requires `code_challenge` with `code_challenge_method=S256`. Insecure implicit flows and legacy grants are disabled.
- **Two-Factor Authentication (TOTP)**: RFC 6238 time-based one-time password security with inline SVG QR codes and single-use emergency recovery codes.
- **Comprehensive Administration**:
  - **Applications**: Client registration with real-time URI validation (scheme, localhost warnings, fragment detection), secret generation, and `.env` snippet generation.
  - **User Governance**: Role assignment, instant activation toggling, and administrative password resets.
  - **Session Invalidation**: Global and granular revocation of active OAuth tokens and browser portal sessions.
  - **Audit & Logging**: Immutable security audit trail and expandable server-side application log viewer.
- **Zero-Data-Loss Deployment**: Safe database migration pipeline. Deployments and container rebuilds run non-destructive `migrate --force`; seeders use idempotent `firstOrCreate()`.

---

## Quickstart (Local Development)

### Option A: Docker (Recommended)

Requires Docker Desktop or Docker Engine + Docker Compose.

```bash
git clone https://github.com/srytmj/sso.yado.git
cd sso.yado

# Installs containers, generates keys, migrates PostgreSQL, and seeds defaults:
make docker-fresh
```

Access the application at `http://localhost:8000`.
- Default Admin: `admin@yado.my.id` / `admin`
- Default User: `user@yado.my.id` / `user`

### Option B: Bare Metal Native

Requires PHP 8.4+, Composer, Node.js 20+, and PostgreSQL 16+.

```bash
git clone https://github.com/srytmj/sso.yado.git
cd sso.yado

composer install
npm install

cp .env.example .env
php artisan key:generate

# Configure your DB_DATABASE, DB_USERNAME, DB_PASSWORD in .env
php artisan migrate
php artisan passport:keys --force
php artisan db:seed

npm run build
php artisan serve
```

---

## Production Deployment

Yado SSO is containerized for zero-downtime, production-ready operation across multiple deployment topologies:

### 1. Standalone Deployment (Caddy Automated HTTPS)
Best suited for single VPS or isolated LXC containers. Caddy automatically provisions and renews TLS certificates via Let's Encrypt / ZeroSSL.

```bash
# Configure CADDY_SITE_ADDRESS in .env (e.g., sso.yado.my.id)
make docker-standalone-deploy
```

### 2. Homelab / Multi-Service (Traefik Reverse Proxy)
Best suited for environments running Traefik over an external Docker network (`proxy`).

```bash
docker network create proxy || true
make docker-prod-deploy
```

### 3. Safe Updates (Guaranteed Zero Data Loss)
To pull updates and rebuild without resetting the database:

```bash
# For Traefik production:
make docker-prod-update

# For Caddy standalone:
make docker-standalone-update
```

> **Database Safety Notice**: The PostgreSQL database data is isolated in the Docker named volume `sso_postgres_data` mapped to `/var/lib/postgresql/data`. Update commands invoke `php artisan migrate --force` to apply incremental schema updates safely without dropping existing tables.

Detailed server deployment manuals:
- [Docker Architectures & Configuration](docs/DOCKER.md)
- [Bare-metal AWS EC2 Setup](docs/DEPLOY_AWS.md)
- [Bare-metal Azure VM Setup](docs/DEPLOY_AZURE.md)

---

## Client Microservice Integration

Any application in the Yado ecosystem can authenticate users against Yado SSO in 6 straightforward steps.

### 1. Environment Setup
Register the application in the SSO Dashboard (`/dashboard/applications`) to obtain:
```env
SSO_BASE_URL=https://sso.yado.my.id
SSO_CLIENT_ID=<uuid>
SSO_CLIENT_SECRET=<secret>
SSO_REDIRECT_URI=https://<your-app>/auth/callback
```

### 2. Authorization Flow
1. Generate a cryptographic `code_verifier` (32 random bytes) and hash it with SHA-256 for the `code_challenge`.
2. Redirect the user to:
   ```
   https://sso.yado.my.id/oauth/authorize?response_type=code&client_id={SSO_CLIENT_ID}&redirect_uri={SSO_REDIRECT_URI}&scope=profile:read&state={STATE}&code_challenge={CHALLENGE}&code_challenge_method=S256
   ```
3. Receive the authorization `code` at `SSO_REDIRECT_URI`.
4. Exchange the code via `POST https://sso.yado.my.id/oauth/token` supplying `code_verifier`.
5. Fetch user profile via `GET https://sso.yado.my.id/api/user` with Bearer token.
6. Terminate session via two-phase logout: clear local session, then redirect to `https://sso.yado.my.id/logout?redirect_uri=<your-app>`.

Comprehensive integration references:
- **Developer Guide (Manual)**: [docs/INTEGRATION.md](docs/INTEGRATION.md)
- **AI Agent Integration Brief**: [docs/AI_INTEGRATION.md](docs/AI_INTEGRATION.md)
- **AI Agent System Operations Specification**: [docs/AI_AGENT_GUIDE.md](docs/AI_AGENT_GUIDE.md)

---

## AI Agent Integration & Operations

When an AI coding agent (e.g. Cursor, Claude Code, Antigravity, GitHub Copilot) is tasked with working on this repository or integrating another microservice:

1. **Protocol Specifications**: Read [docs/AI_AGENT_GUIDE.md](docs/AI_AGENT_GUIDE.md) before implementing client libraries.
2. **Migrations Constraint**: Never invoke `migrate:fresh` or `migrate:reset` outside automated test suites.
3. **Frontend Rules**: All UI code MUST use **Svelte 5**, Inertia `<Link>` components for client-side navigation (not `<a use:inertia>`), and adhere to the monochrome design tokens.
4. **Session Cookie Separation**: When testing multiple apps locally on `localhost`, assign unique `APP_NAME` values in `.env` to avoid session cookie collision.

---

## API Endpoints Reference

| Method | URI | Access | Description |
|---|---|---|---|
| `GET` | `/` | Public | Landing page and system identity portal. |
| `GET` | `/login` | Guest | Svelte authentication portal. |
| `GET` | `/register` | Guest | Self-registration view. |
| `GET` | `/forgot-password` | Guest | Password reset link request. |
| `GET` | `/oauth/authorize` | Authenticated | OAuth2 Authorization Code endpoint (requires PKCE). |
| `POST` | `/oauth/token` | Public | Token issuance (Auth code, refresh token). |
| `GET` | `/api/user` | Scoped Token | Returns identity JSON (`profile:read` scope). |
| `GET` | `/account` | User | Personal profile, avatar, and password settings. |
| `GET` | `/account/sessions` | User | Personal device and authorized token manager. |
| `GET` | `/account/two-factor` | User | TOTP 2FA setup and recovery key generation. |
| `GET` | `/dashboard` | Superadmin | Global analytics and registered client apps. |
| `GET` | `/dashboard/applications` | Superadmin | OAuth client application registry. |
| `GET` | `/dashboard/users` | Superadmin | User directory, role assignment, password resets. |
| `GET` | `/dashboard/sessions` | Superadmin | Cross-system session and token revocation. |
| `GET` | `/dashboard/logs` | Superadmin | Real-time application log viewer with stack traces. |
| `GET` | `/dashboard/audit-log` | Superadmin | Immutable security events and administrative history. |
| `GET` | `/dashboard/settings` | Superadmin | Dynamic mail (Resend/SMTP) and storage configuration. |
| `GET` | `/up` | Public | Lightweight HTTP 200 liveness probe. |
| `GET` | `/health` | Public | Deep readiness check with database ping. |

---

## Documentation Directory

| Document | Purpose |
|---|---|
| [docs/AI_AGENT_GUIDE.md](docs/AI_AGENT_GUIDE.md) | Authoritative operating manual for AI coding agents and autonomous DevOps. |
| [docs/INTEGRATION.md](docs/INTEGRATION.md) | Step-by-step developer integration tutorial for client applications. |
| [docs/AI_INTEGRATION.md](docs/AI_INTEGRATION.md) | Concise prompt context for LLMs generating client authentication modules. |
| [docs/API.md](docs/API.md) | Exhaustive HTTP API reference, payload examples, and error responses. |
| [docs/DOCKER.md](docs/DOCKER.md) | Comprehensive Docker deployment architecture across standalone and homelab modes. |
| [docs/SRS.md](docs/SRS.md) | Software Requirements Specification and database schema definitions. |
| [docs/PRD.md](docs/PRD.md) | Product Requirements Document and ecosystem vision. |
| [docs/DEPLOY_AWS.md](docs/DEPLOY_AWS.md) | Bare-metal production deployment on AWS EC2. |
| [docs/DEPLOY_AZURE.md](docs/DEPLOY_AZURE.md) | Bare-metal production deployment on Azure Linux VMs. |

---

## License

This project is licensed under the [MIT License](LICENSE).
