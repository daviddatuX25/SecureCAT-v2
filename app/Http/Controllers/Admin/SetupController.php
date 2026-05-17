<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SetupController extends Controller
{
    /**
     * Show the setup hub — role-filtered card grid for all configuration pages.
     */
    public function index(Request $request): Response
    {
        return Inertia::render('Admin/Setup/Index', [
            'allowDirectAssessment' => SystemSetting::allowDirectAssessment(),
            'aiCompanionEnabled' => SystemSetting::aiCompanionEnabled(),
        ]);
    }
}
