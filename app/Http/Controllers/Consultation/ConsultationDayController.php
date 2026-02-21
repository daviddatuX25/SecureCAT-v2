<?php

namespace App\Http\Controllers\Consultation;

use App\Http\Controllers\Controller;
use App\Models\Applicant;
use App\Models\ApplicantScore;
use App\Models\ConsultationSchedule;
use App\Models\GradingSession;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ConsultationDayController extends Controller
{
    public function index(Request $request): Response
    {
        $today = Carbon::today()->format('Y-m-d');
        $scheduleIds = ConsultationSchedule::query()
            ->whereDate('scheduled_date', $today)
            ->pluck('id');
        $scheduledApplicantIds = DB::table('consultation_schedule_applicant')
            ->whereIn('consultation_schedule_id', $scheduleIds)
            ->pluck('applicant_id')
            ->unique()
            ->values()
            ->all();

        $search = $request->query('search', '');
        $applicants = collect();
        if (strlen($search) >= 2) {
            $applicants = Applicant::query()
                ->whereHas('application', function ($q) use ($search) {
                    $q->where('reference_number', 'like', "%{$search}%")
                        ->orWhere('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%");
                })
                ->with('application')
                ->limit(20)
                ->get();
        } elseif (! empty($scheduledApplicantIds)) {
            $applicants = Applicant::query()
                ->whereIn('id', $scheduledApplicantIds)
                ->with('application')
                ->get();
        }

        $applicantsWithScores = $applicants->map(function ($a) {
            $gs = GradingSession::query()
                ->where('status', GradingSession::STATUS_FINALIZED)
                ->whereHas('applicantScores', fn ($q) => $q->where('applicant_id', $a->id))
                ->with('examSession')
                ->first();
            $scores = $gs ? ApplicantScore::query()
                ->where('grading_session_id', $gs->id)
                ->where('applicant_id', $a->id)
                ->with('domain')
                ->get() : collect();
            $overallPct = $scores->isNotEmpty() ? (int) round($scores->avg('normalized_score')) : 0;
            return [
                'id' => $a->id,
                'name' => $a->application ? trim(implode(' ', array_filter([$a->application->first_name, $a->application->middle_name, $a->application->last_name, $a->application->suffix]))) : '—',
                'reference' => $a->application?->reference_number ?? '—',
                'scores' => $scores->map(fn ($s) => ['domain' => $s->domain?->name ?? '—', 'pct' => (int) round($s->normalized_score ?? 0)])->values()->all(),
                'overallPct' => $overallPct,
                'systemRecommendation' => null, // Would come from DecisionRuleService
            ];
        });

        return Inertia::render('Consultation/ConsultationDay', [
            'applicants' => $applicantsWithScores->values()->all(),
            'scheduledApplicantIds' => $scheduledApplicantIds,
        ]);
    }
}
