<?php

namespace App\Http\Controllers\Grading;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreScoreImportRequest;
use App\Models\GradingSession;
use App\Services\ScoreImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
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

        $gradingSessions = GradingSession::with(['examSession', 'aptitudeAreas'])
            ->orderByDesc('opened_at')
            ->get(['id', 'exam_session_id', 'status', 'opened_at']);

        return Inertia::render('Grading/Import', [
            'gradingSessions' => $gradingSessions,
        ]);
    }

    /**
     * Process CSV import.
     */
    public function import(StoreScoreImportRequest $request): RedirectResponse
    {
        $this->authorize('viewAny', GradingSession::class);

        try {
            // Parse and validate CSV
            $records = $this->importService->parseSpreadsheet($request->file('file'));
            $validation = $this->importService->validateRecords(
                $records,
                $request->integer('grading_session_id')
            );

            // If there are validation errors, redirect back with error message
            if (! empty($validation['errors'])) {
                $errorMessage = implode("\n", $validation['errors']);

                return back()->with('error', $errorMessage);
            }

            // Import valid records
            $result = $this->importService->importScores(
                $validation['valid'],
                $request->integer('grading_session_id'),
                $request->user()->id
            );

            // Build success message
            $message = "Successfully imported {$result['imported']} scores.";
            if ($result['updated'] > 0) {
                $message .= " {$result['updated']} updated.";
            }
            if ($result['skipped'] > 0) {
                $message .= " {$result['skipped']} skipped.";
            }
            if (! empty($result['errors'])) {
                $message .= "\nErrors:\n".implode("\n", $result['errors']);
            }

            Log::info('Bulk score import completed', [
                'user_id' => $request->user()->id,
                'grading_session_id' => $request->integer('grading_session_id'),
                'imported' => $result['imported'],
                'updated' => $result['updated'],
                'skipped' => $result['skipped'],
            ]);

            return back()->with('message', $message);
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Exception $e) {
            Log::error('Bulk score import failed', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Import failed: '.$e->getMessage());
        }
    }

    /**
     * Preview parsed CSV data before importing.
     */
    public function preview(StoreScoreImportRequest $request): RedirectResponse|InertiaResponse
    {
        $this->authorize('viewAny', GradingSession::class);

        try {
            $records = $this->importService->parseSpreadsheet($request->file('file'));
            $validated = $this->importService->validateRecordsWithDetails(
                $records,
                $request->integer('grading_session_id')
            );

            Session::put('score_import_records', $records);
            Session::put('score_import_session_id', $request->integer('grading_session_id'));

            $gradingSessions = GradingSession::with(['examSession', 'aptitudeAreas'])
                ->orderByDesc('opened_at')
                ->get(['id', 'exam_session_id', 'status', 'opened_at']);

            return Inertia::render('Grading/ImportPreview', [
                'records' => $validated,
                'totalCount' => count($records),
                'validCount' => array_sum(array_column($validated, 'is_valid')),
                'gradingSessionId' => $request->integer('grading_session_id'),
                'gradingSessions' => $gradingSessions,
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

        $records = Session::get('score_import_records', []);
        $sessionId = Session::get('score_import_session_id');

        if (empty($records)) {
            return redirect()->route('grading.import')->with('error', 'No import data found. Please upload again.');
        }

        try {
            $selectedIds = $request->input('selected_ids', []);

            if (empty($selectedIds)) {
                $result = $this->importService->importScores($records, $sessionId, $request->user()->id);
            } else {
                $result = $this->importService->importSelectedScores($records, $selectedIds, $sessionId, $request->user()->id);
            }

            Session::forget(['score_import_records', 'score_import_session_id']);

            $message = "Successfully imported {$result['imported']} scores.";
            if ($result['updated'] > 0) {
                $message .= " {$result['updated']} updated.";
            }
            if ($result['skipped'] > 0) {
                $message .= " {$result['skipped']} skipped.";
            }

            return redirect()->route('grading.import')->with('message', $message);
        } catch (\Exception $e) {
            Log::error('Bulk score import confirm failed', ['error' => $e->getMessage()]);

            return redirect()->route('grading.import')->with('error', 'Import failed: '.$e->getMessage());
        }
    }
}
