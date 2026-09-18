# CLAUDE.md - SSO Engine (yado)

## Project Overview

Central Identity Provider untuk ekosistem Yado. Menyediakan autentikasi OAuth2 terpusat sehingga user cukup login sekali untuk mengakses semua aplikasi dalam ekosistem (Malas, Scribe, dst.).

Ini adalah **backend-only project** (Laravel). UI-nya minimal - hanya halaman login, register, dan consent screen via Blade. Tidak ada frontend SPA terpisah.

## Project Structure

```
root/
  app/              # Laravel application code
  config/           # Config files (database, passport)
  database/         # Migrations, seeders
  resources/views/  # Blade (auth/, oauth/)
  routes/           # web.php, api.php
  scripts/          # deploy.sh, update.sh
  docs/             # PRD, SRS, STRUCTURE, TODO, tickets/
  logs/             # sync.log (gitignored)
  .claude/          # CLAUDE.md + agents/
  Makefile
  sync.sh
  SESSION-PROMPTS.md
```

---

<!-- STACK_START -->
## Stack (auto-synced from SRS.md)

- Backend: Laravel (latest stable)
- Auth: Laravel Passport (OAuth2 server)
- Frontend: Blade + Alpine.js + Tailwind CSS
- Database: PostgreSQL - `db_sso` (read/write split, sticky mode)
- Email: Resend (transactional email) - `composer require resend/resend-laravel`
- Hosting: Linux VM / EC2
- Tunnel: Cloudflare (DNS + proxy)
<!-- STACK_END -->

---

## Backend (Laravel)

### Constraints

- **Backend only.** Tidak ada SPA. Blade hanya untuk halaman auth (login, register, consent).
- Logic **tidak boleh** di Controller. Controller thin - semua logic di Service atau Action class.
- PSR-12. Type hints wajib di semua method signature.
- Database: `db_sso`. Read/write split dengan `sticky: true`.
- Tidak ada queue atau job (belum dibutuhkan).
- Passport mengelola semua endpoint `/oauth/*` - jangan override kecuali ada kebutuhan spesifik.

### Commands

```bash
composer install
php artisan serve
php artisan migrate
php artisan passport:install
php artisan test
```

### Route Structure

```php
// routes/web.php - halaman Blade
Route::get('/login', [LoginController::class, 'show']);
Route::post('/login', [LoginController::class, 'store']);
Route::get('/register', [RegisterController::class, 'show']);
Route::post('/register', [RegisterController::class, 'store']);
Route::post('/logout', [LogoutController::class, 'destroy']);

// routes/api.php - resource endpoint
Route::middleware(['auth:api', 'scope:profile:read'])
    ->get('/user', [UserController::class, 'show']);

// /oauth/* routes - auto-register oleh Passport
```

### Architecture Pattern

```
Request → Controller → Service/Action → Model → Response
```

- `Controller`: validasi request, panggil service, return response
- `Service`: business logic, boleh inject multiple models/actions
- `Action`: single-responsibility operation (e.g., `IssueTokenAction`, `RevokeTokenAction`)
- `Model`: Eloquent model, relasi, scopes - tidak ada business logic

### Code Standards

- PSR-12 strict
- Type hints wajib: parameter dan return type
- No `var_dump`, no `dd()` di production code
- Conventional commits: `feat:`, `fix:`, `chore:`, `docs:`
- Method names: camelCase, class names: PascalCase

---

## Frontend (Blade + Tailwind)

### UI Constraints

- Styling menggunakan **Tailwind CSS** + **daisyUI** (plugin CSS-only, `@plugin 'daisyui'` di `resources/css/app.css`) - bukan pengecualian, ini satu-satunya component library yang diizinkan karena murni class CSS di atas Tailwind, tidak butuh JS framework, kompatibel penuh dengan Blade + Alpine. Pakai komponen daisyUI (`btn`, `input`, `card`, `modal`, `dropdown`, `alert`, `badge`, `navbar`, `menu`, `avatar`, `toggle`, `tabs`, dll.) dan token warna semantiknya (`bg-base-100`, `text-base-content`, `border-base-300`, dst.) alih-alih hardcode `zinc-*`/`dark:` per elemen - token semantik otomatis ganti sesuai `data-theme` di `<html>`, tidak perlu pasangan `dark:` manual lagi untuk komponen baru.
- Theme daisyUI didefinisikan custom di `resources/css/app.css` (`@plugin 'daisyui/theme'`, dua block: `light` dan `dark`) - jangan pakai theme bawaan daisyUI lain tanpa didiskusikan, supaya brand color (biru) konsisten.
- Interaktivitas ringan (dropdown, toggle, modal) menggunakan **Alpine.js** - selalu tambahkan `x-transition` (atau varian durasi/opacity-nya) di elemen yang muncul/hilang (`x-show`), jangan biarkan muncul/hilang instan tanpa animasi.
- Micro-interaction wajib terasa hidup: elemen interaktif (`button`, `a`, `[role="button"]`) otomatis dapat `transition-colors`/`transform` dari `app.css` - manfaatkan (`hover:`, `active:scale-95`, dll.), jangan override jadi instan tanpa alasan.
- Tidak ada React, Vue, Flux UI, shadcn, MUI, atau framework JS/component library lain selain daisyUI.
- Tidak ada CDN Tailwind di production - gunakan Vite build.
- **Dilarang**: icon dekoratif generik yang tidak punya makna fungsional (AI slop icons).
- Icons: gunakan SVG inline minimal atau Heroicons - hanya icon yang punya makna fungsional.

### Layout Structure

```
resources/views/
  layouts/
    public.blade.php     # Landing page - navbar minimal, no auth
    auth.blade.php       # Login/register - centered card, no sidebar
    dashboard.blade.php  # Superadmin dashboard - sidebar + topbar
    account.blade.php    # My Account - tab navigation
  components/            # Blade components untuk elemen reusable
```

---

## Do Not

- Jangan taruh logic di Controller - gunakan Service atau Action
- Jangan buat frontend SPA terpisah
- Jangan modifikasi Passport core (`vendor/`) - extend via config atau subclass
- Jangan simpan secret/token di log atau response body yang tidak perlu
- Jangan skip PKCE validation
- Jangan override `/oauth/*` routes kecuali benar-benar diperlukan
- Jangan pakai icon dekoratif tanpa makna fungsional (AI slop)
- Jangan import component library UI selain daisyUI (Flux, shadcn, MUI, dll.) - Tailwind + daisyUI saja
- Jangan pakai inline styles

---

## Deployment

- First deploy: `make deploy` → `sudo bash scripts/deploy.sh`
- Updates: `make update` → `bash scripts/update.sh`
- Tidak ada CI/CD - manual deploy via SSH ke server

---

## Docs

- PRD: [docs/PRD.md](../docs/PRD.md)
- SRS: [docs/SRS.md](../docs/SRS.md)
- TODO: [docs/TODO.md](../docs/TODO.md)
- Tickets: [docs/tickets/](../docs/tickets/)
