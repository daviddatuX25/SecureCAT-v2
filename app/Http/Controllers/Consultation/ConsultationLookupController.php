<?php

namespace App\Http\Controllers\Consultation;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\GradingSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConsultationLookupController extends Controller
{
    /**
     * Look up applicant by reference number for consultation.
     * Returns applicant_id if applicant has finalized scores.
     */
    public function index(Request $request): JsonResponse
    {
        $ref = trim((string) $request->query('ref', ''));
        if ($ref === '') {
            return response()->json(['message' => 'Reference number required.'], 422);
        }

        $application = Application::where('reference_number', $ref)->first();
        if (! $application || ! $application->applicant) {
            return response()->json(['message' => 'Applicant not found.'], 404);
        }

        $applicant = $application->applicant;

        $gs = GradingSession::query()
            ->where('status', GradingSession::STATUS_FINALIZED)
            ->whereHas('applicantScores', fn ($q) => $q->where('applicant_id', $applicant->id))
            ->first();

        if (! $gs) {
            return response()->json(['message' => 'Applicant has no finalized scores.'], 404);
        }

        return response()->json([
            'applicant_id' => $applicant->id,
        ]);
    }
}
