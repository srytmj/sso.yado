<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Services\Dashboard\LogViewerService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Illuminate\InertiaResponse\InertiaResponse;

class LogController extends Controller
{
    public function __construct(private readonly LogViewerService $service) {}

    public function index(Request $request): InertiaResponse
    {
        $level = $request->query('level');
        $search = $request->query('q');
        $page = max(1, (int) $request->query('page', 1));

        return Inertia::render('Dashboard/Logs/Index', [
            'entries' => $this->service->paginate($level, $search, $page),
            'levels' => LogViewerService::LEVELS,
            'level' => $level,
            'search' => $search,
        ]);
    }
}
