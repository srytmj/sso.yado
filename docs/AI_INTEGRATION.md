# SSO Integration Brief - yado.my.id

> Gunakan file ini sebagai context untuk AI: *"Integrasikan SSO Yado.my.id ke project ini menggunakan panduan berikut."*

---

## Konteks

`sso.yado.my.id` adalah Central Identity Provider untuk ekosistem Yado.my.id.
Protokol: **OAuth2 Authorization Code + PKCE** (wajib, tidak ada fallback).
Semua aplikasi dalam ekosistem mendelegasikan auth ke sini - tidak perlu bikin login sendiri.

---

## Prasyarat

Superadmin sudah mendaftarkan app ini di `sso.yado.my.id/dashboard/applications` dan memberikan:

```env
SSO_CLIENT_ID=<uuid>
SSO_CLIENT_SECRET=<secret>
SSO_REDIRECT_URI=https://<domain-app-ini>/auth/callback
SSO_BASE_URL=https://sso.yado.my.id
```

Tambahkan 4 variabel ini ke `.env` app.

---

## Yang Harus Diimplementasikan

### 1. Tabel users lokal

App membutuhkan tabel `users` lokal dengan kolom dari SSO. **Tidak perlu kolom password** - auth dihandle SSO.

```
sso_id          string unique   # primary key dari SSO, gunakan ini sebagai foreign key
name            string
username        string
email           string unique
avatar          string nullable
role            string          # "user" atau "superadmin"
created_at
updated_at
```

Jika tabel `users` sudah ada dengan kolom `password`, tambahkan `sso_id` sebagai kolom baru (nullable dulu, lalu migrate data jika perlu).

### 2. Dua route auth

```
GET /auth/redirect   → mulai OAuth flow (generate PKCE, redirect ke SSO)
GET /auth/callback   → terima code dari SSO, tukar token, fetch profil, login user
```

### 3. Middleware proteksi

Semua route yang butuh login → redirect ke `/auth/redirect` jika belum ada session.

---

## Implementasi Lengkap (Laravel)

### Migration

```php
Schema::table('users', function (Blueprint $table) {
    $table->string('sso_id')->unique()->after('id');
    $table->string('username')->after('name');
    $table->string('avatar')->nullable()->after('email');
    $table->string('role')->default('user')->after('avatar');
    // Hapus $table->string('password') jika ada dan tidak dibutuhkan
});
```

### Controller

```php
// app/Http/Controllers/Auth/SsoController.php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class SsoController extends Controller
{
    public function redirect(): RedirectResponse
    {
        $codeVerifier = bin2hex(random_bytes(32));
        $codeChallenge = rtrim(
            strtr(base64_encode(hash('sha256', $codeVerifier, true)), '+/', '-_'),
            '='
        );
        $state = bin2hex(random_bytes(16));

        session([
            'sso_code_verifier' => $codeVerifier,
            'sso_state'         => $state,
        ]);

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

    public function callback(Request $request): RedirectResponse
    {
        abort_if($request->state !== session('sso_state'), 403, 'Invalid state');

        $tokenResponse = Http::asForm()->post(config('sso.base_url') . '/oauth/token', [
            'grant_type'    => 'authorization_code',
            'code'          => $request->code,
            'redirect_uri'  => config('sso.redirect_uri'),
            'client_id'     => config('sso.client_id'),
            'client_secret' => config('sso.client_secret'),
            'code_verifier' => session('sso_code_verifier'),
        ])->json();

        $profile = Http::withToken($tokenResponse['access_token'])
            ->get(config('sso.base_url') . '/api/user')
            ->json();

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

        session()->forget(['sso_code_verifier', 'sso_state']);
        session([
            'sso_access_token'  => $tokenResponse['access_token'],
            'sso_refresh_token' => $tokenResponse['refresh_token'],
        ]);

        Auth::login($user, remember: true);

        return redirect()->intended('/dashboard');
    }
}
```

### Config

```php
// config/sso.php
return [
    'client_id'     => env('SSO_CLIENT_ID'),
    'client_secret' => env('SSO_CLIENT_SECRET'),
    'redirect_uri'  => env('SSO_REDIRECT_URI'),
    'base_url'      => env('SSO_BASE_URL', 'https://sso.yado.my.id'),
];
```

### Routes

```php
// routes/web.php
Route::get('/auth/redirect', [SsoController::class, 'redirect'])->name('sso.redirect');
Route::get('/auth/callback', [SsoController::class, 'callback'])->name('sso.callback');

// Proteksi semua route yang butuh login:
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', ...);
    // semua route lain
});
```

### Middleware redirect ke SSO (opsional - ganti default Laravel auth redirect)

Di `bootstrap/app.php` atau `AppServiceProvider`, arahkan unauthenticated redirect ke SSO:

```php
// bootstrap/app.php
->withExceptions(function (Exceptions $exceptions) {
    $exceptions->render(function (AuthenticationException $e, Request $request) {
        return redirect()->route('sso.redirect');
    });
})
```

---

## Refresh Token (opsional tapi direkomendasikan)

Access token expired setelah **60 menit**. Implementasi refresh agar session tidak putus:

```php
// Cek dan refresh token jika expired (misalnya di middleware atau scheduled job)
$tokenResponse = Http::asForm()->post(config('sso.base_url') . '/oauth/token', [
    'grant_type'    => 'refresh_token',
    'refresh_token' => session('sso_refresh_token'),
    'client_id'     => config('sso.client_id'),
    'client_secret' => config('sso.client_secret'),
])->json();

session([
    'sso_access_token'  => $tokenResponse['access_token'],
    'sso_refresh_token' => $tokenResponse['refresh_token'],
]);
```

Refresh token berlaku **30 hari** dan single-use - selalu simpan token baru dari response.

---

## Logout

Logout **wajib dua tahap**: hapus session lokal, lalu redirect ke SSO logout.
Jika tahap dua dilewati, SSO session masih aktif - klik login lagi akan auto-login tanpa minta password.

```php
public function logout(Request $request): RedirectResponse
{
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    // Wajib redirect ke SSO logout dengan redirect_uri supaya SSO session juga hancur.
    // redirect_uri harus domain yang terdaftar di SSO sebagai redirect URI client ini.
    $redirectUri = urlencode(url('/'));
    return redirect(config('sso.base_url') . '/logout?redirect_uri=' . $redirectUri);
}
```

Route logout di client (pastikan POST atau GET keduanya support):

```php
Route::post('/auth/logout', [SsoController::class, 'logout'])->name('sso.logout');
Route::get('/auth/logout', [SsoController::class, 'logout']);  // untuk redirect dari SSO
```

---

## Data User yang Tersedia

Setelah login, `Auth::user()` return model User lokal yang sudah di-sync dari SSO:

```php
Auth::user()->sso_id    // ID di SSO (gunakan ini jika perlu cross-reference)
Auth::user()->name
Auth::user()->username
Auth::user()->email
Auth::user()->avatar    // URL avatar atau null
Auth::user()->role      // "user" atau "superadmin"
Auth::user()->theme     // "system" | "light" | "dark"
Auth::user()->locale    // "id" | "en" | "ja"
```

Data di-sync setiap kali user login. Untuk update profil, user harus ke `sso.yado.my.id/account`.

---

## Theme & Bahasa (Opsional, Cross-App)

SSO menyimpan preferensi `theme` dan `locale` per user dan mengexpose-nya lewat `/api/user`. **SSO tidak bisa memaksa styling atau bahasa di app kamu** - itu domain terpisah. Yang SSO sediakan cuma sumber kebenaran (single source of truth) untuk preferensi user; app kamu yang menerapkannya sendiri.

Kalau mau ikutan sinkron dengan preferensi yang user set di SSO:

### Theme

```php
// Simpan theme dari SSO API response ke session/DB saat callback()
session(['theme' => $profile['theme']]);
```

Terapkan di layout kamu pakai strategi yang sama dengan SSO - dark mode berbasis class `.dark` di `<html>`, di-set lewat inline script sebelum CSS load (hindari FOUC):

```html
<script>
    var stored = localStorage.getItem('theme') || '{{ session('theme', 'system') }}';
    var isDark = stored === 'dark' || (stored === 'system' && matchMedia('(prefers-color-scheme: dark)').matches);
    document.documentElement.classList.toggle('dark', isDark);
</script>
```

### Bahasa

Laravel punya i18n bawaan (`__()`, file `lang/{locale}/*.php`). Baca `locale` dari SSO dan set via middleware:

```php
App::setLocale($profile['locale'] ?? 'id');
```

Kalau app kamu bukan Laravel, terapkan pola yang setara di framework kamu - intinya baca `theme`/`locale` dari `/api/user`, simpan lokal (session/DB), lalu terapkan di render.

> Sinkron ini best-effort, bukan realtime. Kalau user ganti bahasa di SSO setelah sesi login berjalan, app kamu baru dapat nilai baru di login berikutnya (kecuali kamu fetch ulang `/api/user` secara berkala).

---

## Checklist Implementasi

- [ ] Tambah 4 env var (`SSO_CLIENT_ID`, `SSO_CLIENT_SECRET`, `SSO_REDIRECT_URI`, `SSO_BASE_URL`)
- [ ] Buat/update tabel `users` dengan kolom `sso_id`, `username`, `avatar`, `role`
- [ ] Buat `config/sso.php`
- [ ] Buat `SsoController` dengan method `redirect()` dan `callback()`
- [ ] Daftarkan route `/auth/redirect` dan `/auth/callback`
- [ ] Arahkan unauthenticated request ke `route('sso.redirect')` bukan ke `/login`
- [ ] Test: akses halaman protected → redirect ke SSO → login → balik ke app → session aktif
- [ ] Test: akses lagi setelah login SSO → silent (tidak perlu login ulang)
- [ ] Test: logout → session lokal hancur + SSO session hancur → klik login lagi → muncul halaman login SSO (bukan auto-login)

---

## Testing Lokal (SSO + Client App di 1 Device)

- SSO dan client app **wajib port berbeda** (`php artisan serve --port=8000` untuk SSO, `--port=8001` untuk client)
- `redirect_uri` yang didaftarkan di SSO harus persis sama termasuk port
- `APP_URL` di masing-masing `.env` harus sesuai port masing-masing
- `APP_NAME` di client app harus berbeda dari SSO - nama cookie session Laravel diturunkan dari `APP_NAME`, jika sama persis kedua app bisa saling override session cookie
- `SESSION_DOMAIN` biarkan `null` di kedua app (default) - jangan di-set eksplisit ke `localhost`
- Kalau app kamu jalan di **Docker container terpisah** dari SSO, `localhost` tidak bisa dipakai untuk request server-to-server (token exchange, fetch profil) - container tidak saling kenal lewat `localhost`. Split config jadi `base_url` (untuk redirect browser, tetap `localhost`) dan `internal_url` (untuk HTTP call server-to-server, pakai `host.docker.internal`). Detail lengkap + contoh kode: [`DOCKER.md`](DOCKER.md)

---

## Error Umum

| Error | Penyebab | Fix |
|-------|----------|-----|
| `invalid_client` | `client_id` atau `client_secret` salah | Cek `.env` |
| `invalid_grant` | `code_verifier` tidak cocok atau code sudah dipakai | Pastikan `code_verifier` disimpan di server-side session |
| 403 Invalid state | `state` di callback tidak cocok session | Jangan simpan `state` di cookie/localStorage |
| `invalid_request` | `code_challenge` tidak ada | PKCE wajib - pastikan `redirect()` generate dan kirim `code_challenge` |
| Token expired (401) | Access token > 60 menit | Implementasi refresh token flow |
| Auto-login setelah logout | SSO session tidak dihancurkan | Pastikan logout redirect ke `/logout?redirect_uri=...` bukan hanya clear session lokal |
| GET method not allowed (logout) | Client punya route logout POST-only tapi di-hit via GET redirect | Tambah `Route::get('/auth/logout', ...)` di client |
| Tidak bisa integrasi di 1 device (local) | Port bentrok, `redirect_uri` tidak match port, atau `APP_NAME` sama di kedua app (cookie collision) | Lihat section "Testing Lokal" di atas - pastikan port, `redirect_uri`, dan `APP_NAME` berbeda |

---

## Referensi

- API Contract lengkap: `docs/SRS.md`
- Panduan manual (untuk manusia): `docs/INTEGRATION.md`
- SSO Engine repo: `sso.yado.my.id`
