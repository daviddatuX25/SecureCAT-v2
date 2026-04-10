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
        $user = $request->user();

        return Inertia::render('Dashboard', [
            'user' => $user,
            'applicationStats' => $this->dashboardService->getApplicationStats($user),
            'sessionStats' => $this->dashboardService->getSessionStats($user),
            'gradingStats' => $this->dashboardService->getGradingStats($user),
        ]);
    }
}
