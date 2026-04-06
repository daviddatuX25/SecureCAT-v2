<?php

namespace App\Http\Controllers;

use App\Models\Season;
use Inertia\Inertia;

class HomeController extends Controller
{
    public function index()
    {
        $activeSeason = Season::where('is_active', true)->first();

        return Inertia::render('Home/Index', [
            'activeSeason' => $activeSeason ? [
                'name' => 'A.Y. ' . $activeSeason->academic_year,
                'application_start' => $activeSeason->application_start_date ? \Carbon\Carbon::parse($activeSeason->application_start_date)->format('M d, Y') : null,
                'application_end' => $activeSeason->application_end_date ? \Carbon\Carbon::parse($activeSeason->application_end_date)->format('M d, Y') : null,
            ] : null,
            'systemName' => 'SecureCAT',
        ]);
    }

    public function about()
    {
        return Inertia::render('Home/About', [
            'systemName' => 'SecureCAT',
        ]);
    }
}
