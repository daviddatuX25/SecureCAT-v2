<?php

namespace App\Http\Controllers\Grading;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreScoreImportRequest;
use App\Models\AptitudeArea;
use App\Models\GradingSession;
use App\Models\SystemSetting;
use App\Services\ScoreImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class ScoreImportController extends Controller
{
    public function __construct(
        private readonly ScoreImportService $importService,
    ) {}

    /**
     * Show the CSV import form.
     */
    public function importForm(): InertiaResponse
    {
        $this->authorize('viewAny', GradingSession::class);

        $stalePath = Session::get('score_import_temp_path');
        if ($stalePath && Storage::exists($stalePath)) {
            Storage::delete($stalePath);
        }
        Session::forget('score_import_temp_path');

        return Inertia::render('Grading/Import', [
            'enableNormalizedScores' => SystemSetting::enableNormalizedScores(),
            'aptitudeAreaCodes' => AptitudeArea::where('is_active', true)->pluck('code')->toArray(),
            'previewUrl' => route('admin.grading.import.preview'),
        ]);
    }

    /**
     * Process CSV import.
     */
    public function import(StoreScoreImportRequest $request): RedirectResponse
    {
        $this->authorize('viewAny', GradingSession::class);

        try {
            $records = $this->importService->parseSpreadsheet($request->file('file'));
            $result = $this->importService->importScores($records, $request->user()->id);

            $message = "Successfully imported {$result['imported']} scores.";
            if ($result['skipped'] > 0) {
                $message .= " {$result['skipped']} skipped.";
            }

            return back()->with('message', $message);
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Preview parsed CSV data before importing.
     */
    public function preview(StoreScoreImportRequest $request): RedirectResponse|InertiaResponse
    {
        $this->authorize('viewAny', GradingSession::class);

        try {
            $file = $request->file('file');
            $tempPath = $file->store('temp/score_imports');
            Session::put('score_import_temp_path', $tempPath);

            $records = $this->importService->parseSpreadsheet(Storage::path($tempPath));
            $validated = $this->importService->validateRecords($records);

            return Inertia::render('Grading/ImportPreview', [
                'records' => $validated['records'],
                'totalCount' => $validated['summary']['total'],
                'validCount' => $validated['summary']['valid'],
                'enableNormalizedScores' => SystemSetting::enableNormalizedScores(),
                'aptitudeAreaCodes' => AptitudeArea::where('is_active', true)->pluck('code')->toArray(),
                'confirmUrl' => route('admin.grading.import.confirm'),
            ]);
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Exception $e) {
            Log::error('Bulk score import preview failed', ['error' => $e->getMessage()]);

            return back()->with('error', 'Preview failed: '.$e->getMessage());
        }
    }

    /**
     * Confirm and import selected records.
     */
    public function confirm(Request $request): RedirectResponse
    {
        $this->authorize('viewAny', GradingSession::class);

        $tempPath = Session::get('score_import_temp_path');

        if (! $tempPath || ! Storage::exists($tempPath)) {
            return redirect()->route('admin.grading.import')->with('error', 'Import session expired. Please upload again.');
        }

        try {
            $records = $this->importService->parseSpreadsheet(Storage::path($tempPath));
            $selectedIds = $request->input('selected_ids', []);

            $result = $this->importService->importSelectedScores($records, $selectedIds, $request->user()->id);

            Storage::delete($tempPath);
            Session::forget('score_import_temp_path');

            $message = "Successfully imported {$result['imported']} scores.";
            if ($result['skipped'] > 0) {
                $message .= " {$result['skipped']} skipped.";
            }
            if (! empty($result['errors'])) {
                $message .= "\nErrors:\n".implode("\n", $result['errors']);
            }

            Log::info('Bulk score import confirmed', [
                'user_id' => $request->user()->id,
                'imported' => $result['imported'],
                'skipped' => $result['skipped'],
            ]);

            return redirect()->route('admin.grading.import')->with('message', $message);
        } catch (\Exception $e) {
            Log::error('Bulk score import confirm failed', ['error' => $e->getMessage()]);

            return redirect()->route('admin.grading.import')->with('error', 'Import failed: '.$e->getMessage());
        }
    }
}
