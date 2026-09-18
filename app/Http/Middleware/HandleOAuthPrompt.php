<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class HandleOAuthPrompt
{
    /**
     * Menangani parameter prompt=login dari client OAuth (mis. fitur "Tambah Akun" di Malas).
     * Jika user sedang terautentikasi dan prompt=login dikirim ke /oauth/authorize,
     * logout user aktif dan redirect kembali ke URL authorize tanpa prompt=login
     * agar middleware auth Passport mengirim user ke halaman login untuk memasukkan kredensial akun baru.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->is('oauth/authorize') && $request->query('prompt') === 'login' && Auth::check()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            $url = $request->fullUrlWithQuery(['prompt' => null]);

            return redirect($url);
        }

        return $next($request);
    }
}
