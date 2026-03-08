<?php

namespace App\Http\Controllers\Consultation;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreConsultationScheduleRequest;
use App\Models\ConsultationSchedule;
use App\Models\GradingSession;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ConsultationScheduleController extends Controller
{
    public function index(): Response
    {
        $gradingSessions = GradingSession::query()
            ->where('status', GradingSession::STATUS_FINALIZED)
            ->with([
                'examSession.room',
                'applicants' => fn ($q) => $q->whereNotNull('grading_session_applicant.result_printed_at')
                    ->with('application'),
            ])
            ->withCount([
                'applicants',
                'applicants as printed_count' => fn ($q) => $q->whereNotNull('grading_session_applicant.result_printed_at'),
            ])
            ->get();

        $batches = [];
        $applicantsByBatch = [];
        foreach ($gradingSessions as $gs) {
            $batches[] = [
                'id' => $gs->id,
                'name' => 'Batch '.$gs->id,
                'exam_date' => $gs->examSession?->date?->format('Y-m-d'),
                'printed_count' => $gs->printed_count ?? 0,
                'total' => $gs->applicants_count ?? 0,
            ];
            $applicants = $gs->applicants
                ->map(fn ($a) => [
                    'applicant_id' => $a->id,
                    'name' => $a->application ? trim(implode(' ', array_filter([$a->application->first_name, $a->application->middle_name, $a->application->last_name, $a->application->suffix]))) : '—',
                    'reference' => $a->application?->reference_number ?? '—',
                    'printed' => true,
                ])
                ->values()
                ->all();
            $applicantsByBatch[$gs->id] = $applicants;
        }

        return Inertia::render('Consultation/ScheduleDay', [
            'batches' => $batches,
            'applicantsByBatch' => $applicantsByBatch,
        ]);
    }

    public function store(StoreConsultationScheduleRequest $request): RedirectResponse
    {
        ConsultationSchedule::create([
            'scheduled_date' => $request->validated('scheduled_date'),
            'grading_session_id' => $request->validated('grading_session_id'),
            'created_by' => $request->user()->id,
        ])->applicants()->sync($request->validated('applicant_ids'));

        return redirect()->route('consultation.schedule.index')->with('success', 'Consultation scheduled.');
    }
}
