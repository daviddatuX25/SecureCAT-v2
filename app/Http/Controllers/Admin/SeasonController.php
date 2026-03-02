<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSeasonRequest;
use App\Http\Requests\UpdateSeasonRequest;
use App\Models\Season;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class SeasonController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', Season::class);

        $seasons = Season::query()
            ->withCount('applications')
            ->orderByDesc('academic_year')
            ->orderBy('semester')
            ->paginate(15)
            ->withQueryString();

        $seasons->getCollection()->transform(function (Season $s) {
            $label = sprintf('%s – %s', $s->academic_year, $s->semester);
            $windowLabel = $s->applicationWindowLabel();
            $windowOpen = $s->isApplicationWindowOpen();

            return [
                'id' => $s->id,
                'academic_year' => $s->academic_year,
                'semester' => $s->semester,
                'label' => $label,
                'application_start_date' => $s->application_start_date?->toDateString(),
                'application_end_date' => $s->application_end_date?->toDateString(),
                'application_window' => $windowLabel,
                'application_window_open' => $windowOpen,
                'is_active' => $s->is_active,
                'applications_count' => $s->applications_count,
            ];
        });

        return Inertia::render('Admin/Seasons/Index', [
            'seasons' => $seasons,
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Season::class);

        return Inertia::render('Admin/Seasons/Create');
    }

    public function store(StoreSeasonRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $dates = $request->only(['application_start_date', 'application_end_date']);
        $start = $data['application_start_date'] ?? $dates['application_start_date'] ?? null;
        $end = $data['application_end_date'] ?? $dates['application_end_date'] ?? null;
        $startStr = $start ? (\is_string($start) ? $start : $start->format('Y-m-d')) : null;
        $endStr = $end ? (\is_string($end) ? $end : $end->format('Y-m-d')) : null;

        Season::create([
            'academic_year' => $data['academic_year'],
            'semester' => $data['semester'],
            'application_start_date' => $startStr,
            'application_end_date' => $endStr,
            'is_active' => false,
        ]);

        return redirect()->route('admin.seasons.index')->with('success', 'Season created.');
    }

    public function edit(Season $season): Response
    {
        $this->authorize('update', $season);

        return Inertia::render('Admin/Seasons/Edit', [
            'season' => [
                'id' => $season->id,
                'academic_year' => $season->academic_year,
                'semester' => $season->semester,
                'application_start_date' => $season->application_start_date?->toDateString(),
                'application_end_date' => $season->application_end_date?->toDateString(),
                'is_active' => $season->is_active,
            ],
        ]);
    }

    public function update(UpdateSeasonRequest $request, Season $season): RedirectResponse
    {
        $validated = $request->validated();
        $dates = $request->only(['application_start_date', 'application_end_date']);
        $start = $validated['application_start_date'] ?? $dates['application_start_date'] ?? null;
        $end = $validated['application_end_date'] ?? $dates['application_end_date'] ?? null;
        $startStr = $start ? (\is_string($start) ? $start : $start->format('Y-m-d')) : null;
        $endStr = $end ? (\is_string($end) ? $end : $end->format('Y-m-d')) : null;

        $season->update([
            'academic_year' => $validated['academic_year'],
            'semester' => $validated['semester'],
            'application_start_date' => $startStr,
            'application_end_date' => $endStr,
        ]);

        return redirect()->route('admin.seasons.index')->with('success', 'Season updated.');
    }

    public function activate(Season $season): RedirectResponse
    {
        $this->authorize('activate', $season);

        $season->activate();

        return redirect()->route('admin.seasons.index')->with('success', 'Season set as active.');
    }
}
