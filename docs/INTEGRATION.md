# Integration Guide — SSO Engine (sso.yado.my.id)

Panduan untuk developer yang ingin mengintegrasikan aplikasi ke SSO Engine yado.my.id sebagai OAuth2 client.

---

## Apa itu SSO Engine?

`sso.yado.my.id` adalah **Central Identity Provider** untuk ekosistem yado.my.id. Semua aplikasi (Malas, Scribe, dll.) mendelegasikan autentikasi ke sini — user cukup login sekali dan bisa akses semua app tanpa login ulang.

Protokol: **OAuth2 Authorization Code + PKCE** (RFC 6749 + RFC 7636). PKCE wajib — tidak ada fallback.

---

## Dua Cara Integrasi

| Cara | Cocok untuk | Estimasi waktu |
|------|-------------|----------------|
| [Cara A — Lempar ke AI](#cara-a--lempar-ke-ai) | Project baru atau yang mau cepat | 15–30 menit |
| [Cara B — Manual](#cara-b--manual-step-by-step) | Kamu mau paham setiap langkahnya | 1–2 jam |

---

## Prasyarat (wajib untuk kedua cara)

Sebelum mulai, **minta superadmin** daftarkan aplikasimu di `sso.yado.my.id/dashboard/applications`:

1. Superadmin buka dashboard → **Add Application**
2. Isi nama app dan **Redirect URI** (contoh: `https://malas.yado.my.id/auth/callback`)
   - Wajib HTTPS (kecuali `localhost` untuk development)
   - Redirect URI harus sudah pasti — ini di-whitelist ketat oleh SSO
3. Klik Create → superadmin dapat **Quick Start panel** berisi credentials siap copy

Kamu akan menerima dari superadmin:
```env
SSO_CLIENT_ID=xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx
SSO_CLIENT_SECRET=xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
SSO_REDIRECT_URI=https://yourapp.yado.my.id/auth/callback
SSO_BASE_URL=https://sso.yado.my.id
```

> **Penting**: `client_secret` hanya ditampilkan sekali saat create. Jika hilang, superadmin harus generate ulang.

---

## Cara A — Lempar ke AI

Cara paling cepat. Cocok untuk Laravel, Next.js, atau stack apapun yang AI kamu kenal.

**Langkah:**

1. Dapatkan credentials dari superadmin (lihat Prasyarat di atas)
2. Buka AI coding assistant (Claude, Cursor, Copilot, dll.)
3. Paste prompt berikut:

```
Integrasikan SSO yado.my.id ke project ini menggunakan panduan berikut:
[link ke docs/AI_INTEGRATION.md di repo SSO]

Credentials yang sudah tersedia di .env:
SSO_CLIENT_ID=xxx
SSO_CLIENT_SECRET=xxx
SSO_REDIRECT_URI=xxx
SSO_BASE_URL=https://sso.yado.my.id
```

4. AI akan mengimplementasikan:
   - Tabel `users` lokal (tanpa password, dengan kolom `sso_id`)
   - Route `/auth/redirect` dan `/auth/callback`
   - PKCE generation dan token exchange
   - Sync profil user dari SSO ke DB lokal
   - Middleware proteksi route

5. Test: akses halaman yang diproteksi → seharusnya redirect ke SSO → login → balik ke app

File referensi untuk AI: [`docs/AI_INTEGRATION.md`](AI_INTEGRATION.md)

---

## Cara B — Manual (Step by Step)

### Overview Flow

```
User buka app
  → belum ada session → redirect ke /auth/redirect
  → redirect ke sso.yado.my.id/oauth/authorize
  → [session SSO aktif] → silent, langsung dapat code
  → [belum login SSO] → tampil halaman login SSO → login
  → redirect ke /auth/callback?code=xxx&state=xxx
  → app tukar code + code_verifier → dapat access_token + refresh_token
  → app hit GET /api/user → dapat profil user
  → upsert ke tabel users lokal (berdasarkan sso_id)
  → buat session lokal → user masuk
```

---

### Step 1 — Setup Environment

Tambahkan ke `.env`:

```env
SSO_CLIENT_ID=xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx
SSO_CLIENT_SECRET=xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
SSO_REDIRECT_URI=https://yourapp.yado.my.id/auth/callback
SSO_BASE_URL=https://sso.yado.my.id
```

Buat `config/sso.php`:

```php
return [
    'client_id'     => env('SSO_CLIENT_ID'),
    'client_secret' => env('SSO_CLIENT_SECRET'),
    'redirect_uri'  => env('SSO_REDIRECT_URI'),
    'base_url'      => env('SSO_BASE_URL', 'https://sso.yado.my.id'),
];
```

---

### Step 2 — Siapkan Tabel Users

App kamu butuh tabel `users` dengan kolom dari SSO. **Tidak perlu kolom password** — auth dihandle SSO.

Jika belum ada tabel `users`:

```php
Schema::create('users', function (Blueprint $table) {
    $table->id();
    $table->string('sso_id')->unique();
    $table->string('name');
    $table->string('username')->unique();
    $table->string('email')->unique();
    $table->string('avatar')->nullable();
    $table->string('role')->default('user');
    $table->rememberToken();
    $table->timestamps();
});
```

Jika tabel `users` sudah ada (misal dari Laravel default), tambahkan kolom yang kurang:

```php
Schema::table('users', function (Blueprint $table) {
    $table->string('sso_id')->unique()->after('id');
    $table->string('username')->after('name');
    $table->string('avatar')->nullable()->after('email');
    $table->string('role')->default('user')->after('avatar');
    $table->dropColumn('password'); // hapus jika tidak ada auth lokal
});
```

---

### Step 3 — Generate PKCE

PKCE wajib. Buat utility atau langsung inline di controller.

```php
// Generate code_verifier (random string 43-128 karakter)
$codeVerifier = bin2hex(random_bytes(32)); // 64 karakter hex

// Derive code_challenge (SHA-256 dari verifier, base64url encoded)
$codeChallenge = rtrim(
    strtr(base64_encode(hash('sha256', $codeVerifier, true)), '+/', '-_'),
    '='
);
```

**Simpan `code_verifier` di server-side session** — dibutuhkan saat tukar token.

---

### Step 4 — Redirect ke SSO

```php
// GET /auth/redirect
public function redirect(): RedirectResponse
{
    $codeVerifier = bin2hex(random_bytes(32));
    $codeChallenge = rtrim(
        strtr(base64_encode(hash('sha256', $codeVerifier, true)), '+/', '-_'),
        '='
    );
    $state = bin2hex(random_bytes(16)); // untuk CSRF protection

    session(['sso_code_verifier' => $codeVerifier, 'sso_state' => $state]);

    $query = http_build_query([
        'response_type'         => 'code',
        'client_id'             => config('sso.client_id'),
        'redirect_uri'          => config('sso.redirect_uri'),
        'scope'                 => 'profile:read',
        'code_challenge'        => $codeChallenge,
        'code_challenge_method' => 'S256',
        'state'                 => $state,
    ]);

    return redirect(config('sso.base_url') . '/oauth/authorize?' . $query);
}
```

Jika user sudah punya session aktif di SSO, mereka **tidak akan melihat halaman login** — langsung di-redirect balik dengan auth code (silent SSO).

---

### Step 5 — Handle Callback

```php
// GET /auth/callback
public function callback(Request $request): RedirectResponse
{
    // Validasi state untuk cegah CSRF
    abort_if($request->state !== session('sso_state'), 403, 'Invalid state');

    // Tukar auth code dengan token (server-to-server)
    $tokens = Http::asForm()->post(config('sso.base_url') . '/oauth/token', [
        'grant_type'    => 'authorization_code',
        'code'          => $request->code,
        'redirect_uri'  => config('sso.redirect_uri'),
        'client_id'     => config('sso.client_id'),
        'client_secret' => config('sso.client_secret'),
        'code_verifier' => session('sso_code_verifier'),
    ])->json();

    // Ambil profil user
    $profile = Http::withToken($tokens['access_token'])
        ->get(config('sso.base_url') . '/api/user')
        ->json();

    // Upsert ke tabel users lokal
    $user = User::updateOrCreate(
        ['sso_id' => $profile['id']],
        [
            'name'     => $profile['name'],
            'username' => $profile['username'],
            'email'    => $profile['email'],
            'avatar'   => $profile['avatar'],
            'role'     => $profile['role']['slug'],
        ]
    );

    // Bersihkan session PKCE
    session()->forget(['sso_code_verifier', 'sso_state']);

    // Simpan token untuk refresh nanti
    session([
        'sso_access_token'  => $tokens['access_token'],
        'sso_refresh_token' => $tokens['refresh_token'],
    ]);

    Auth::login($user, remember: true);

    return redirect()->intended('/dashboard');
}
```

> **Penting**: Token exchange harus dilakukan di server-side. Jangan expose `client_secret` ke browser.

---

### Step 6 — Routes dan Middleware

```php
// routes/web.php
Route::get('/auth/redirect', [SsoController::class, 'redirect'])->name('sso.redirect');
Route::get('/auth/callback', [SsoController::class, 'callback'])->name('sso.callback');
Route::post('/auth/logout', [SsoController::class, 'logout'])->name('sso.logout');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', ...);
    // semua route yang butuh login
});
```

Arahkan unauthenticated redirect ke SSO (bukan ke `/login` lokal):

```php
// bootstrap/app.php
->withExceptions(function (Exceptions $exceptions) {
    $exceptions->render(function (AuthenticationException $e, Request $request) {
        if (! $request->expectsJson()) {
            return redirect()->route('sso.redirect');
        }
    });
})
```

---

### Step 7 — Logout

Logout **dua tahap** wajib dilakukan:
1. Hapus session lokal di app kamu
2. Redirect ke SSO logout — kalau dilewati, SSO session masih aktif dan user akan auto-login kembali tanpa diminta password

```php
public function logout(Request $request): RedirectResponse
{
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    // Wajib: redirect ke SSO logout dengan redirect_uri agar session SSO juga hancur.
    // Tanpa ini, klik login lagi akan auto-login tanpa minta password.
    $redirectUri = urlencode(config('sso.redirect_uri_base', url('/')));
    return redirect(config('sso.base_url') . '/logout?redirect_uri=' . $redirectUri);
}
```

> **Penting**: `redirect_uri` harus domain yang terdaftar di SSO sebagai redirect URI client app kamu. SSO hanya akan redirect ke domain yang sudah diwhitelist.

---

### Step 8 — Refresh Token (Opsional tapi Direkomendasikan)

Access token expired setelah **60 menit**. Implementasi refresh agar session tidak putus:

```php
private function refreshToken(): bool
{
    $response = Http::asForm()->post(config('sso.base_url') . '/oauth/token', [
        'grant_type'    => 'refresh_token',
        'refresh_token' => session('sso_refresh_token'),
        'client_id'     => config('sso.client_id'),
        'client_secret' => config('sso.client_secret'),
    ]);

    if ($response->failed()) {
        return false;
    }

    $tokens = $response->json();
    session([
        'sso_access_token'  => $tokens['access_token'],
        'sso_refresh_token' => $tokens['refresh_token'], // selalu simpan yang baru
    ]);

    return true;
}
```

Refresh token berlaku **30 hari** dan single-use — setiap refresh menghasilkan token baru.

---

## Data User yang Tersedia

Setelah login, `Auth::user()` return model User lokal yang sudah di-sync dari SSO:

```php
Auth::user()->sso_id    // ID unik di SSO — gunakan sebagai foreign key antar app
Auth::user()->name      // Nama lengkap
Auth::user()->username  // Username unik
Auth::user()->email
Auth::user()->avatar    // URL atau null
Auth::user()->role      // "user" atau "superadmin"
Auth::user()->theme     // "system" | "light" | "dark" — preferensi theme user
Auth::user()->locale    // "id" | "en" | "ja" — preferensi bahasa user
```

Data di-sync setiap kali user login. Untuk update profil, user pergi ke `sso.yado.my.id/account`.

> **Theme & bahasa cross-app**: SSO cuma nyimpen dan expose preferensi ini — app kamu yang harus baca dan menerapkannya sendiri (SSO tidak bisa styling app lain). Contoh implementasi lengkap ada di [`AI_INTEGRATION.md`](AI_INTEGRATION.md) section "Theme & Bahasa".

---

## Scopes

| Scope | Data yang tersedia |
|-------|--------------------|
| `profile:read` | id, name, username, email, avatar, role |

Gunakan `scope=profile:read` di authorization URL. Scope tambahan akan didefinisikan sesuai kebutuhan ekosistem.

---

## API Reference

### GET /api/user

```
Authorization: Bearer {access_token}
```

Response 200:
```json
{
  "id": 1,
  "name": "Budi Santoso",
  "username": "budi",
  "email": "budi@yado.my.id",
  "avatar": null,
  "role": { "id": 1, "name": "User", "slug": "user" }
}
```

| Status | Kondisi |
|--------|---------|
| 200 | Token valid, scope cukup |
| 401 | Token tidak valid, expired, atau user dinonaktifkan |
| 403 | Token valid tapi scope tidak cukup |

### POST /oauth/token

| Parameter | Keterangan |
|-----------|------------|
| `grant_type` | `authorization_code` atau `refresh_token` |
| `code` | Auth code dari callback (untuk authorization_code) |
| `code_verifier` | PKCE verifier yang dihasilkan di Step 3 |
| `refresh_token` | Refresh token yang disimpan (untuk refresh_token grant) |
| `client_id` | Dari `.env` |
| `client_secret` | Dari `.env` — jangan expose ke frontend |
| `redirect_uri` | Harus sama persis dengan yang didaftarkan |

---

## Testing Lokal (SSO + Client App di 1 Device)

Ini setup paling umum dan paling sering bikin bingung — SSO dan app kamu jalan bareng di laptop yang sama.

1. **Port harus beda.** `php artisan serve` default ke port 8000. Kalau SSO sudah pakai 8000, jalankan client app di port lain:
   ```bash
   # Terminal 1 — SSO Engine
   cd sso.yado && php artisan serve --port=8000

   # Terminal 2 — client app kamu
   cd malas-app && php artisan serve --port=8001
   ```

2. **Redirect URI harus persis match** — termasuk port. Kalau didaftarkan `http://localhost:8001/auth/callback` tapi app kamu jalan di port 8002, akan kena `invalid_client` / redirect mismatch.

3. **`APP_URL` di kedua app harus sesuai port masing-masing.** SSO: `APP_URL=http://localhost:8000`, client: `APP_URL=http://localhost:8001`.

4. **Cookie session tidak akan bentrok** selama `APP_NAME` di kedua app berbeda (Laravel generate nama cookie dari `APP_NAME`, bukan dari port). Kalau kamu clone starter yang sama untuk dua app dan lupa ganti `APP_NAME`, keduanya bisa pakai cookie session yang sama persis dan saling override — pastikan `APP_NAME` unik di tiap `.env`.

5. **`SESSION_DOMAIN` biarkan `null`** di kedua app (default). Browser tidak membedakan cookie berdasarkan port jika `Domain` di-set eksplisit ke `localhost` — membiarkan `null` membuat cookie ter-scope otomatis per origin (host + port + scheme).

6. Setelah keduanya jalan, akses client app di `http://localhost:8001` → klik login → harus redirect ke `http://localhost:8000/login` (SSO), bukan error koneksi atau blank page.

---

## Error Umum

| Error | Penyebab | Solusi |
|-------|----------|--------|
| `invalid_client` | `client_id` atau `client_secret` salah | Cek `.env` |
| `invalid_grant` | `code_verifier` tidak cocok atau code sudah dipakai | Pastikan `code_verifier` disimpan di server session |
| `invalid_request` | `code_challenge` tidak ada | PKCE wajib — pastikan `redirect()` generate dan kirim `code_challenge` |
| 403 Invalid state | `state` di callback tidak cocok | Jangan simpan `state` di cookie/localStorage |
| 401 dari `/api/user` | Token expired | Implementasi refresh token |
| 429 | Rate limit tercapai | Tunggu 1 menit, maksimal 5 request/menit |

---

## Checklist Integrasi

- [ ] Terima `client_id`, `client_secret`, `redirect_uri` dari superadmin
- [ ] Tambah 4 env var ke `.env`
- [ ] Buat `config/sso.php`
- [ ] Siapkan tabel `users` dengan kolom `sso_id`, `username`, `avatar`, `role`
- [ ] Implementasi `SsoController` dengan `redirect()`, `callback()`, `logout()`
- [ ] Daftarkan routes `/auth/redirect`, `/auth/callback`, `/auth/logout`
- [ ] Arahkan unauthenticated redirect ke `route('sso.redirect')`
- [ ] Test: akses protected route → redirect SSO → login → balik ke app ✓
- [ ] Test: akses lagi setelah login → silent SSO, tidak minta login ulang ✓
- [ ] Test: logout → session lokal + SSO hancur ✓

---

## Referensi

- [AI Integration Brief](AI_INTEGRATION.md) — untuk integrasi via AI assistant
- [SRS — API Contract lengkap](SRS.md)
- [PRD — Arsitektur ekosistem](PRD.md)
