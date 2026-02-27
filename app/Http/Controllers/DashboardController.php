<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        private DashboardService $dashboardService
    ) {}

    public function index(Request $request): Response
    {
        $stats = $this->dashboardService->getStatsForUser($request->user());

        return Inertia::render('Dashboard', [
            'user' => $request->user(),
            'stats' => $stats,
        ]);
    }
}
