<?php

namespace App\Http\Controllers;

use App\Models\ConsultationSummary;
use App\Models\Course;
use App\Models\SystemSetting;
use App\Notifications\ResultReleased;
use App\Services\ConsultationSummaryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReleaseController extends Controller
{
    public function index(): Response
    {
        $summaries = ConsultationSummary::with([
            'applicant.application.coursePreference1:id,name,code',
            'applicant.application.coursePreference2:id,name,code',
            'applicant.application.coursePreference3:id,name,code',
            'recommendedCourse',
        ])
            ->whereIn('status', ['draft', 'released'])
            ->orderBy('updated_at', 'desc')
            ->paginate(50);

        $courses = Course::where('is_active', true)->orderBy('name')->get(['id', 'name', 'code']);

        return Inertia::render('Release/Index', [
            'summaries' => $summaries,
            'release_mode' => SystemSetting::releaseMode(),
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

    public function release(ConsultationSummary $summary): RedirectResponse
    {
        if ($summary->status === ConsultationSummary::STATUS_RELEASED) {
            return back()->with('error', 'Already released.');
        }

        $summary->update([
            'status' => ConsultationSummary::STATUS_RELEASED,
            'released_at' => now(),
            'released_by' => auth()->id(),
        ]);

        if (SystemSetting::releaseMode() !== 'f2f') {
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

        $summaries = ConsultationSummary::whereIn('id', $ids)
            ->where('status', '!=', ConsultationSummary::STATUS_RELEASED)
            ->get();

        foreach ($summaries as $summary) {
            $summary->update([
                'status' => ConsultationSummary::STATUS_RELEASED,
                'released_at' => now(),
                'released_by' => auth()->id(),
            ]);

            if (SystemSetting::releaseMode() !== 'f2f') {
                $summary->applicant->notify(new ResultReleased($summary));
            }
        }

        return back()->with('success', count($summaries).' result(s) released.');
    }
}
