# TASK-005: Silent SSO + Auto-Approve Consent

Status: Done
Priority: High
Created: 2026-07-20 20:00
Request: Implementasikan dua behavior kunci SSO:
1. **Silent SSO** - jika user sudah punya session aktif saat `/oauth/authorize` dipanggil, langsung redirect balik ke client dengan auth code tanpa tampilkan login form.
2. **Auto-approve consent** - semua client yang terdaftar sebagai first-party tidak perlu confirm consent screen, langsung approve.

Depends on: TASK-004

---

## DEV Response

- [x] Buat `ConsentService` di `app/Services/OAuth/ConsentService.php`
  - Method `shouldAutoApprove(Client $client, User $user): bool`
  - Logic: return `$client->firstParty()` (Passport built-in: client dengan `user_id = null` adalah first-party)
- [x] Buat `app/Models/OAuth/Client.php` extends `Laravel\Passport\Client`
  - Override `skipsAuthorization(Authenticatable $user, array $scopes): bool` → return `$this->firstParty()`
  - Register via `Passport::useClientModel(Client::class)` di `AppServiceProvider`
- [x] Register custom Client model di `AppServiceProvider`
- [x] Register `Passport::authorizationView('oauth.authorize')` - Passport akan show view ini hanya jika `skipsAuthorization()` return false (non-first-party)

**Silent SSO behavior (built-in Passport):**
- Passport's `AuthorizationController` sudah handle: jika user guest → `promptForLogin()` → redirect ke `/login` dengan session `intended`
- Jika user sudah login → langsung validasi auth request → check `skipsAuthorization()` → jika true, langsung issue auth code dan redirect (tidak show consent view)
- Tidak perlu override controller

**Notes:**
- `firstParty()` di Passport mengecek `user_id IS NULL` di `oauth_clients` - semua client yang dibuat via `passport:install` atau `passport:client` tanpa `--user-id` adalah first-party.
- `is_active` check saat silent SSO: tidak bisa dilakukan di level Passport controller. Ini dihandle di `LoginService` (user inactive tidak bisa login) dan di `CheckUserActive` middleware (token-based requests). Jika session ada tapi user di-deactivate setelah login, auth code masih bisa diissue - ini acceptable karena token revocation di logout dan `CheckUserActive` di API endpoint sudah mengcover impactnya.

---

## QA Response

> **Method**: Static code review.

- [x] User belum login → GET `/oauth/authorize` → redirect ke `/login`, intended URL tersimpan - Passport `AuthorizationController` built-in behavior ✓
- [x] User sudah login (session aktif) → GET `/oauth/authorize` langsung → redirect ke `redirect_uri` dengan code - Passport silent SSO, `skipsAuthorization()` = true untuk first-party ✓
- [x] Client bukan first-party → consent screen tampil - `Client::skipsAuthorization()` return `false` → Passport show `oauth.authorize` view ✓
- [x] Auth code dari silent SSO valid dan bisa ditukar token - Passport standard flow ✓
- [x] `ConsentService::shouldAutoApprove()` exist dan return `$client->firstParty()` ✓
- [x] Jika user `is_active = false` → meskipun ada session, auth code **tidak** dikeluarkan - **BUG-002 FIXED**

  **Fix**: Buat `CheckWebUserActive` middleware, register ke `config/passport.php` middleware array.
  Middleware intercept semua Passport routes - jika authenticated user `is_active = false`: logout, invalidate session, redirect ke `/login` dengan error message. Auth code tidak pernah diissue.

**Status: Done**
