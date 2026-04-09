<?php

namespace App\Http\Controllers;

use App\Models\ConsultationSummary;
use App\Models\SystemSetting;
use App\Notifications\ResultReleased;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReleaseController extends Controller
{
    public function index(): Response
    {
        $summaries = ConsultationSummary::with(['applicant', 'recommendedCourse'])
            ->whereIn('status', ['draft', 'released'])
            ->orderBy('updated_at', 'desc')
            ->paginate(50);

        return Inertia::render('Release/Index', [
            'summaries'    => $summaries,
            'release_mode' => SystemSetting::releaseMode(),
        ]);
    }

    public function release(ConsultationSummary $summary): RedirectResponse
    {
        if ($summary->status === ConsultationSummary::STATUS_RELEASED) {
            return back()->with('error', 'Already released.');
        }

        $summary->update([
            'status'      => ConsultationSummary::STATUS_RELEASED,
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
            'ids'   => 'required|array',
            'ids.*' => 'integer|exists:consultation_summaries,id',
        ])['ids'];

        $summaries = ConsultationSummary::whereIn('id', $ids)
            ->where('status', '!=', ConsultationSummary::STATUS_RELEASED)
            ->get();

        foreach ($summaries as $summary) {
            $summary->update([
                'status'      => ConsultationSummary::STATUS_RELEASED,
                'released_at' => now(),
                'released_by' => auth()->id(),
            ]);

            if (SystemSetting::releaseMode() !== 'f2f') {
                $summary->applicant->notify(new ResultReleased($summary));
            }
        }

        return back()->with('success', count($summaries) . ' result(s) released.');
    }
}
