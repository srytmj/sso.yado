# TASK-006: Userinfo Endpoint (GET /api/user)

Status: Done
Priority: High
Created: 2026-07-20 20:00
Request: Buat endpoint `GET /api/user` yang return profile user berdasarkan Bearer access token. Endpoint ini yang dipakai oleh semua client app untuk fetch data user setelah dapat access token. Response harus sesuai API contract di SRS.

Depends on: TASK-004

---

## DEV Response

- [x] Buat `UserController` di `app/Http/Controllers/Api/UserController.php`
  - Method `show(Request $request): UserResource`
  - Ambil user dari `$request->user()`, load relasi `role`
  - Return `UserResource`
- [x] Route di `routes/api.php`:
  ```php
  Route::middleware(['auth:api', 'scope:profile:read', 'check.user.active'])
      ->get('/user', [UserController::class, 'show']);
  ```
- [x] Buat `UserResource` di `app/Http/Resources/UserResource.php` - shape sesuai API contract SRS (id, name, username, email, avatar, role{id,name,slug})
- [x] Register `routes/api.php` di `bootstrap/app.php` via `->withRouting(api: ...)`
- [x] Register middleware alias di `bootstrap/app.php`:
  - `scope` → `CheckForAnyScope` (Passport)
  - `scopes` → `CheckScopes` (Passport)
  - `check.user.active` → `CheckUserActive`
- [x] Buat `CheckUserActive` middleware: jika `is_active = false` → return 401

**Response shape:**
```json
{
  "data": {
    "id": 1,
    "name": "...",
    "username": "...",
    "email": "...",
    "avatar": null,
    "role": { "id": 1, "name": "User", "slug": "user" }
  }
}
```
> Note: Laravel `JsonResource` wraps response dalam `data` key by default. Jika SRS tidak menginginkan wrapper ini, tambahkan `JsonResource::withoutWrapping()` di `AppServiceProvider`.

---

## QA Response

> **Method**: Static code review.

- [x] GET `/api/user` tanpa token → HTTP 401 `{"message": "Unauthenticated."}` - `auth:api` middleware + `shouldRenderJsonWhen` di `bootstrap/app.php:24` memastikan JSON response untuk `api/*` ✓
- [x] GET `/api/user` dengan token expired → HTTP 401 - Passport `auth:api` guard menolak token expired ✓
- [x] GET `/api/user` dengan token valid scope `profile:read` → HTTP 200 semua field sesuai SRS - `UserResource` return: id, name, username, email, avatar, role{id,name,slug}. `withoutWrapping()` aktif → tidak ada `data` wrapper, sesuai SRS ✓
- [x] Response tidak mengandung `password`, `remember_token` - keduanya di `$hidden` di `User.php:28-31`, tidak ada di `UserResource::toArray()` ✓
- [x] GET `/api/user` dengan scope bukan `profile:read` → HTTP 403 - `scope:profile:read` middleware alias ke `CheckForAnyScope` di `bootstrap/app.php:19` ✓
- [x] User `is_active = false` setelah token diissue → GET `/api/user` return 401 - `CheckUserActive` middleware: `!$user->is_active` → `response()->json(['message' => 'Unauthenticated.'], 401)` ✓
- [x] Avatar null → field `avatar` return `null` - `$this->avatar` di `UserResource`, kolom nullable, tidak ada coercion ke string ✓

**Additional checks:**
- [x] Controller thin: `UserController::show()` hanya load relation + return resource ✓
- [x] Route `check.user.active` alias terdaftar di `bootstrap/app.php:18` ✓
- [x] `scope` alias → `CheckForAnyScope`, `scopes` alias → `CheckScopes` - keduanya terdaftar ✓

**Note DEV Response**: DEV menampilkan response dengan `data` wrapper, tapi actual implementation pakai `withoutWrapping()` → response flat tanpa wrapper. Ini sesuai SRS. Dokumentasi di DEV Response perlu dikoreksi (minor).

**Status: Done**
