<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Illuminate\InertiaResponse\InertiaResponse;

class AuditLogController extends Controller
{
    public function __construct(private readonly AuditLogService $service) {}

    public function index(Request $request): InertiaResponse
    {
        $event = $request->query('event');
        $search = $request->query('q');
        $page = max(1, (int) $request->query('page', 1));

        return Inertia::render('Dashboard/Audit-log/Index', [
            'entries' => $this->service->paginate($event, $search, $page),
            'events' => AuditLogService::EVENTS,
            'event' => $event,
            'search' => $search,
        ]);
    }
}
