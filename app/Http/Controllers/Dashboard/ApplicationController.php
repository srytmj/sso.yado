<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\OAuth\Client;
use App\Rules\RedirectUriRule;
use App\Services\Dashboard\ApplicationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Illuminate\InertiaResponse\InertiaResponse;

class ApplicationController extends Controller
{
    public function __construct(private readonly ApplicationService $service) {}

    public function index(): InertiaResponse
    {
        return Inertia::render('Dashboard/Applications/Index', [
            'clients' => $this->service->list(),
        ]);
    }

    public function create(): InertiaResponse
    {
        return Inertia::render('Dashboard/Applications/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'redirect_uri' => ['required', 'string', 'max:1000', new RedirectUriRule()],
        ]);

        $client = $this->service->create($validated);

        return redirect()->route('dashboard.applications.show', $client->id)
            ->with('new_secret', $client->plainSecret);
    }

    public function show(Client $application): InertiaResponse
    {
        return Inertia::render('Dashboard/Applications/Show', ['client' => $application]);
    }

    public function update(Request $request, Client $application): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'redirect_uri' => ['required', 'string', 'max:1000', new RedirectUriRule()],
        ]);

        $this->service->update($application, $validated);

        return back()->with('success', 'Application updated.');
    }

    public function destroy(Client $application): RedirectResponse
    {
        $this->service->delete($application);

        return redirect()->route('dashboard.applications.index')
            ->with('success', 'Application deleted and tokens revoked.');
    }
}
