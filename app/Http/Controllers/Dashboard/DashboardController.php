<?php

namespace App\Http\Controllers\Dashboard;

use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

use App\Http\Controllers\Controller;
use App\Models\OAuth\Client;
use App\Models\User;

class DashboardController extends Controller
{
    public function index(): InertiaResponse
    {
        $stats = [
            'users_active' => User::active()->count(),
            'users_total' => User::count(),
            'clients' => Client::count(),
        ];

        $clients = Client::orderByDesc('created_at')->get();

        return view('dashboard.index', compact('stats', 'clients'));
    }
}
