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

        // #region agent log
        @file_put_contents(
            base_path('.cursor/debug-065a6c.log'),
            json_encode([
                'sessionId' => '065a6c',
                'runId' => 'pre-fix',
                'hypothesisId' => 'H1',
                'location' => 'SeasonController@index',
                'message' => 'Seasons paginator payload',
                'data' => [
                    'total' => $seasons->total(),
                    'count' => $seasons->count(),
                    'first' => $seasons->first(),
                ],
                'timestamp' => (int) round(microtime(true) * 1000),
            ]) . PHP_EOL,
            FILE_APPEND
        );
        // #endregion

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

        Season::create([
            'academic_year' => $data['academic_year'],
            'semester' => $data['semester'],
            'application_start_date' => $data['application_start_date'] ?? null,
            'application_end_date' => $data['application_end_date'] ?? null,
            'is_active' => false,
        ]);

        return redirect()->route('admin.seasons.index')->with('success', 'Season created.');
    }

    public function edit(Season $season): Response
    {
        $this->authorize('update', $season);

        // #region agent log
        @file_put_contents(
            base_path('.cursor/debug-065a6c.log'),
            json_encode([
                'sessionId' => '065a6c',
                'runId' => 'pre-fix',
                'hypothesisId' => 'H2',
                'location' => 'SeasonController@edit',
                'message' => 'Season edit payload',
                'data' => [
                    'id' => $season->id,
                    'academic_year' => $season->academic_year,
                    'semester' => $season->semester,
                    'is_active' => $season->is_active,
                ],
                'timestamp' => (int) round(microtime(true) * 1000),
            ]) . PHP_EOL,
            FILE_APPEND
        );
        // #endregion

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

        // #region agent log
        @file_put_contents(
            base_path('.cursor/debug-065a6c.log'),
            json_encode([
                'sessionId' => '065a6c',
                'runId' => 'debug-update',
                'location' => 'SeasonController@update:before',
                'message' => 'Update request data',
                'data' => [
                    'validated' => $validated,
                    'all_input' => $request->all(),
                    'season_before' => [
                        'id' => $season->id,
                        'application_start_date' => $season->application_start_date?->toDateString(),
                        'application_end_date' => $season->application_end_date?->toDateString(),
                    ],
                ],
                'timestamp' => (int) round(microtime(true) * 1000),
            ]) . PHP_EOL,
            FILE_APPEND
        );
        // #endregion

        $season->update($validated);

        // #region agent log
        @file_put_contents(
            base_path('.cursor/debug-065a6c.log'),
            json_encode([
                'sessionId' => '065a6c',
                'runId' => 'debug-update',
                'location' => 'SeasonController@update:after',
                'message' => 'Season after update',
                'data' => [
                    'season_after' => [
                        'id' => $season->id,
                        'application_start_date' => $season->fresh()->application_start_date?->toDateString(),
                        'application_end_date' => $season->fresh()->application_end_date?->toDateString(),
                    ],
                ],
                'timestamp' => (int) round(microtime(true) * 1000),
            ]) . PHP_EOL,
            FILE_APPEND
        );
        // #endregion

        return redirect()->route('admin.seasons.index')->with('success', 'Season updated.');
    }

    public function activate(Season $season): RedirectResponse
    {
        $this->authorize('activate', $season);

        $season->activate();

        return redirect()->route('admin.seasons.index')->with('success', 'Season set as active.');
    }
}
