<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Auth\LoginService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\InertiaResponse\InertiaResponse;

class LoginController extends Controller
{
    public function __construct(private readonly LoginService $loginService) {}

    public function show(Request $request): InertiaResponse
    {
        $clientName = $this->resolveClientName($request);

        return view('auth.login', compact('clientName'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $requiresTwoFactor = $this->loginService->attempt($validated, $request->boolean('remember'));

        if ($requiresTwoFactor) {
            return redirect()->route('two-factor.challenge');
        }

        $request->session()->regenerate();

        $default = auth()->user()->role?->slug === 'superadmin'
            ? route('dashboard.index')
            : route('account.show');

        return redirect()->intended($default);
    }

    private function resolveClientName(Request $request): ?string
    {
        $intended = $request->session()->get('url.intended', '');

        if (empty($intended)) {
            return null;
        }

        $query = parse_url($intended, PHP_URL_QUERY) ?? '';
        parse_str($query, $params);

        $clientId = $params['client_id'] ?? null;

        if (empty($clientId)) {
            return null;
        }

        return DB::table('oauth_clients')
            ->where('id', $clientId)
            ->value('name');
    }
}
