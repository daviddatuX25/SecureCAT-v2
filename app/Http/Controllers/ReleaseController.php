<?php

namespace App\Http\Controllers;

use App\Models\ConsultationSummary;
use App\Models\Course;
use App\Models\SystemSetting;
use App\Notifications\ResultReleased;
use App\Notifications\ResultReleasedF2F;
use App\Services\ConsultationSummaryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReleaseController extends Controller
{
    public function index(): Response
    {
        $mode = SystemSetting::releaseMode();
        $courses = Course::where('is_active', true)->orderBy('name')->get(['id', 'name', 'code']);

        $summaries = ConsultationSummary::with([
            'applicant.application.coursePreference1:id,name,code',
            'applicant.application.coursePreference2:id,name,code',
            'applicant.application.coursePreference3:id,name,code',
            'recommendedCourse:id,name,code',
        ])
            ->whereIn('status', ['draft', 'released'])
            ->orderBy('updated_at', 'desc')
            ->paginate(50)
            ->through(function ($summary) {
                $app = $summary->applicant?->application;
                if ($summary->applicant && $app) {
                    $summary->applicant->setAttribute('full_name', trim(implode(' ', array_filter([
                        $app->first_name,
                        $app->middle_name,
                        $app->last_name,
                        $app->suffix,
                    ]))));
                    $summary->applicant->setAttribute('reference_number', $app->reference_number ?? null);
                }

                return $summary;
            });

        return Inertia::render('Release/Index', [
            'summaries' => $summaries,
            'release_mode' => $mode,
            'courses' => $courses,
        ]);
    }

    public function storeOrUpdateByApplicant(Request $request, int $applicantId): RedirectResponse
    {
        $summary = app(ConsultationSummaryService::class)->getOrCreateForApplicant($applicantId);

        $releaseMode = SystemSetting::releaseMode();

        $rules = [
            'recommended_course_id' => ['nullable', 'integer', 'exists:courses,id'],
            'counselor_comments' => ['nullable', 'string', 'max:5000'],
        ];

        if ($releaseMode === 'online') {
            $rules['recommended_course_id'] = ['required', 'integer', 'exists:courses,id'];
            $rules['counselor_comments'] = ['required', 'string', 'max:5000'];
        }

        $validated = $request->validate($rules);

        $summary->update([
            'recommended_course_id' => $validated['recommended_course_id'] ?? null,
            'counselor_comments' => $validated['counselor_comments'] ?? null,
        ]);

        return back()->with('success', 'Summary updated.');
    }

    public function release(Request $request, ConsultationSummary $summary): RedirectResponse
    {
        if ($summary->status === ConsultationSummary::STATUS_RELEASED) {
            return back()->with('error', 'Already released.');
        }

        $summary->update([
            'status' => ConsultationSummary::STATUS_RELEASED,
            'released_at' => now(),
            'released_by' => auth()->id(),
        ]);

        $mode = SystemSetting::releaseMode();
        $context = $request->input('release_context', $mode);

        if ($context === 'f2f') {
            $summary->applicant->notify(new ResultReleasedF2F($summary));
        } else {
            $summary->applicant->notify(new ResultReleased($summary));
        }

        return back()->with('success', 'Result released.');
    }

    public function releaseBulk(Request $request): RedirectResponse
    {
        $ids = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:consultation_summaries,id',
        ])['ids'];

        $mode = SystemSetting::releaseMode();
        $context = $request->input('release_context', $mode);

        $summaries = ConsultationSummary::whereIn('id', $ids)
            ->where('status', '!=', ConsultationSummary::STATUS_RELEASED)
            ->get();

        foreach ($summaries as $summary) {
            $summary->update([
                'status' => ConsultationSummary::STATUS_RELEASED,
                'released_at' => now(),
                'released_by' => auth()->id(),
            ]);

            if ($context === 'f2f') {
                $summary->applicant->notify(new ResultReleasedF2F($summary));
            } else {
                $summary->applicant->notify(new ResultReleased($summary));
            }
        }

        return back()->with('success', count($summaries).' result(s) released.');
    }

    public function releaseAll(Request $request): RedirectResponse
    {
        $mode = SystemSetting::releaseMode();

        if ($mode === 'f2f') {
            return back()->with('error', 'Release All is not available in F2F mode.');
        }

        $summaries = ConsultationSummary::with('applicant')
            ->where('status', '!=', ConsultationSummary::STATUS_RELEASED)
            ->get();

        $releasedCount = 0;

        foreach ($summaries as $summary) {
            $summary->update([
                'status' => ConsultationSummary::STATUS_RELEASED,
                'released_at' => now(),
                'released_by' => auth()->id(),
            ]);

            $summary->applicant->notify(new ResultReleased($summary));
            $releasedCount++;
        }

        if ($releasedCount === 0) {
            return back()->with('info', 'All results have already been released.');
        }

        return back()->with('success', "{$releasedCount} result(s) released.");
    }
}
