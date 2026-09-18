# AI Agent Integration & Operations Guide - Yado SSO

This specification provides autonomous AI coding agents, DevOps agents, and LLM assistants with exact technical protocols to operate, maintain, and integrate client microservices with **Yado SSO** (`sso.yado.my.id`).

---

## 1. System Overview & Core Invariants

Yado SSO is the authoritative Central Identity Provider (IdP) for the Yado microservice ecosystem.

### Architectural Invariants
1. **Protocol**: RFC 6749 (OAuth 2.0 Authorization Framework) strictly combined with RFC 7636 (Proof Key for Code Exchange by OAuth Public/Confidential Clients).
2. **Mandatory PKCE**: All authorization requests MUST supply `code_challenge` generated via `S256` method. Non-PKCE and implicit grant requests are rejected with `400 Invalid Request`.
3. **Frontend Stack**: Single-page architecture powered by **Svelte 5** and **Inertia.js v2**, styled with Tailwind CSS v4 and DaisyUI v5, animated via GSAP.
4. **Backend Stack**: Laravel 11 / PHP 8.4+ with Laravel Passport. Controllers are thin delegators; business logic lives in `app/Services/` and `app/Actions/`.
5. **Database Durability**: PostgreSQL 16 data lives in the persistent Docker volume `sso_postgres_data`. **NEVER** run `migrate:fresh` in production or updates. Always run `migrate --force`.
6. **Two-Stage Logout**: Terminating a local microservice session MUST redirect to `https://sso.yado.my.id/logout?redirect_uri=<url>` to revoke the SSO portal session.

---

## 2. Agent Operational Rules (DevOps & Maintenance)

When an AI agent is tasked with updating, deploying, or troubleshooting Yado SSO itself:

### Safe Deployment Workflow
```bash
# 1. Update source code
git pull origin main

# 2. Rebuild container image without destroying database
make docker-prod-update
# OR for standalone Caddy:
make docker-standalone-update

# 3. If running commands manually:
docker compose -f docker/compose.prod.yml up -d --build
docker compose -f docker/compose.prod.yml exec app php artisan migrate --force
docker compose -f docker/compose.prod.yml exec app php artisan config:cache
docker compose -f docker/compose.prod.yml exec app php artisan route:cache
docker compose -f docker/compose.prod.yml exec app php artisan view:cache
```

### Safety Rules
- **DO NOT** execute `php artisan migrate:fresh` on non-local environments.
- **DO NOT** wipe named volumes (`sso_postgres_data`, `sso_storage`).
- **DO NOT** hardcode secrets in `.env.example` or commit `.env`.
- Seeders (`AdminUserSeeder`, `RoleSeeder`) use `firstOrCreate()`. Running `db:seed --force` is idempotent and safe.

---

## 3. Client Microservice Integration Specification

When an AI agent is building or integrating a client application (Node, React/Next.js, Python/FastAPI, Go, Laravel, etc.) to use Yado SSO, follow this exact protocol.

### Step 1: Environment Variables
The client microservice requires 4 configuration values:

```env
SSO_BASE_URL=https://sso.yado.my.id
SSO_CLIENT_ID=<uuid-from-dashboard>
SSO_CLIENT_SECRET=<secret-from-dashboard>
SSO_REDIRECT_URI=https://<client-app-domain>/auth/callback
```

### Step 2: Authorization Initiation (GET `/auth/redirect`)
The client app initiates login by creating a cryptographically random code verifier and state parameter, calculating the SHA-256 code challenge, storing them in a secure server-side session, and redirecting the browser to SSO Yado.

#### Cryptographic Generation (RFC 7636):
- `code_verifier`: 43 to 128 random alphanumeric characters (e.g. `bin2hex(random_bytes(32))`).
- `code_challenge`: `BASE64URL-ENCODE(SHA256(ASCII(code_verifier)))` without `=` padding.
- `state`: random anti-CSRF token (e.g. `bin2hex(random_bytes(16))`).

#### Target Redirect URL:
```
https://sso.yado.my.id/oauth/authorize?response_type=code&client_id={SSO_CLIENT_ID}&redirect_uri={ENCODED_REDIRECT_URI}&scope=profile:read&state={STATE}&code_challenge={CODE_CHALLENGE}&code_challenge_method=S256
```

### Step 3: Callback & Token Exchange (GET `/auth/callback`)
SSO Yado redirects back with `?code={AUTH_CODE}&state={STATE}`.

1. **Verify State**: Assert `request.query.state === session.sso_state`. If mismatch, abort with `403 Forbidden`.
2. **Exchange Authorization Code**:
   ```http
   POST https://sso.yado.my.id/oauth/token
   Content-Type: application/x-www-form-urlencoded

   grant_type=authorization_code
   &client_id={SSO_CLIENT_ID}
   &client_secret={SSO_CLIENT_SECRET}
   &redirect_uri={SSO_REDIRECT_URI}
   &code={AUTH_CODE}
   &code_verifier={SESSION_CODE_VERIFIER}
   ```
3. **Response Payload**:
   ```json
   {
     "token_type": "Bearer",
     "expires_in": 3600,
     "access_token": "eyJ0eXAiOiJKV1QiLC...",
     "refresh_token": "def50200..."
   }
   ```

### Step 4: Fetching User Identity (GET `/api/user`)
```http
GET https://sso.yado.my.id/api/user
Authorization: Bearer {access_token}
Accept: application/json
```

#### JSON Response Schema:
```json
{
  "id": "018f9c1b-2d34-7a90-8b12-9876543210ab",
  "name": "Jane Doe",
  "username": "janedoe",
  "email": "jane@yado.my.id",
  "avatar": "https://sso.yado.my.id/storage/avatars/avatar_123.jpg",
  "role": {
    "id": 1,
    "name": "Superadmin",
    "slug": "superadmin"
  },
  "theme": "dark",
  "locale": "en",
  "email_verified_at": "2026-09-18T10:00:00.000000Z",
  "created_at": "2026-09-01T08:00:00.000000Z"
}
```

### Step 5: User Persistence & Local Session
In the client database, upsert the user record by matching `sso_id`:
```sql
INSERT INTO users (sso_id, name, username, email, avatar, role)
VALUES ($1, $2, $3, $4, $5, $6)
ON CONFLICT (sso_id) DO UPDATE SET
  name = EXCLUDED.name,
  username = EXCLUDED.username,
  email = EXCLUDED.email,
  avatar = EXCLUDED.avatar,
  role = EXCLUDED.role,
  updated_at = NOW();
```

### Step 6: Two-Phase Logout
To prevent silent auto-login via lingering cookies:
```
Client App clears local cookies/session
  ? Redirects to: https://sso.yado.my.id/logout?redirect_uri=https://<client-app-domain>/
```

---

## 4. Token Refresh Lifecycle

- **Access Token TTL**: 60 minutes (`3600` seconds).
- **Refresh Token TTL**: 30 days (`2592000` seconds). Refresh tokens are single-use rotation tokens.

```http
POST https://sso.yado.my.id/oauth/token
Content-Type: application/x-www-form-urlencoded

grant_type=refresh_token
&refresh_token={CURRENT_REFRESH_TOKEN}
&client_id={SSO_CLIENT_ID}
&client_secret={SSO_CLIENT_SECRET}
```

---

## 5. Health & Monitoring Probes

For container orchestrators, uptime checks, and autonomous monitor daemons:
- **`GET /up`**: Basic process liveness probe. Returns HTTP 200.
- **`GET /health`**: Deep readiness probe. Checks database connectivity and returns:
  ```json
  {
    "status": "healthy",
    "timestamp": "2026-09-18T18:00:00Z",
    "database": "connected"
  }
  ```

---

## 6. Diagnostic Matrix for AI Agents

| Symptom | Root Cause | Actionable Fix |
|---|---|---|
| `invalid_request: The code_challenge is missing` | Client initiated OAuth without PKCE params | Generate 32-byte code verifier, hash with SHA-256, send `code_challenge` + `code_challenge_method=S256`. |
| `invalid_grant: Code verifier failed` | Client sent a different `code_verifier` than used for `code_challenge` | Ensure `code_verifier` is stored in server session and not re-generated on callback. |
| `403 Invalid state` | CSRF state token missing or mismatched | Confirm browser accepts session cookies across domain redirects. |
| `invalid_client: Client authentication failed` | Incorrect Client ID or Secret | Verify credentials in `/dashboard/applications/<id>`. |
| Client app logs in, but loops back to login | Session cookie collision between apps | In local dev, ensure each app has a distinct port and unique session cookie name (`APP_NAME`). |
