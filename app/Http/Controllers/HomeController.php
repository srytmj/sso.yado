<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Illuminate\InertiaResponse\InertiaResponse;

class HomeController extends Controller
{
    public function index(Request $request): InertiaResponse|RedirectResponse
    {
        if ($user = $request->user()) {
            return redirect()->route(
                $user->role?->slug === 'superadmin' ? 'dashboard.index' : 'account.show'
            );
        }

        return Inertia::render('Home');
    }
}
