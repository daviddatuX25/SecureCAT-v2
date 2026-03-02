<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Http\Requests\Portal\AiCompanionChatRequest;
use App\Models\AiCompanionMessage;
use App\Models\Applicant;
use App\Models\SystemSetting;
use App\Services\AiCompanionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class AiCompanionController extends Controller
{
    public function __construct(
        private AiCompanionService $companionService
    ) {}

    /**
     * Show chat page. Redirect to dashboard if companion disabled or results not released.
     */
    public function index(Request $request): Response|JsonResponse|RedirectResponse
    {
        if (! SystemSetting::aiCompanionEnabled()) {
            return redirect()->route('portal.dashboard')->with('error', 'AI companion is not available.');
        }

        /** @var Applicant|null $applicant */
        $applicant = Auth::guard('applicant')->user();
        if (! $applicant) {
            return redirect()->route('portal.login');
        }

        $applicant->load('consultationSummary');
        $summary = $applicant->consultationSummary;
        if (! $summary || $summary->status !== 'released') {
            return redirect()->route('portal.dashboard')->with('error', 'Your results have not been released yet.');
        }

        $messages = AiCompanionMessage::lastForApplicant($applicant->id, AiCompanionService::DEFAULT_MAX_HISTORY)
            ->get()
            ->sortBy('created_at')
            ->values()
            ->map(fn ($m) => ['role' => $m->role, 'content' => $m->content])
            ->all();

        return Inertia::render('Portal/AiCompanion', [
            'csrf_token' => csrf_token(),
            'messages' => $messages,
        ]);
    }

    /**
     * POST /portal/ai-companion/chat — applicant sends a message, receives AI reply.
     */
    public function chat(AiCompanionChatRequest $request): JsonResponse
    {
        if (! SystemSetting::aiCompanionEnabled()) {
            return response()->json(['message' => 'AI companion is not enabled.'], 403);
        }

        /** @var Applicant $applicant */
        $applicant = Auth::guard('applicant')->user();
        $applicant->load('consultationSummary');

        $summary = $applicant->consultationSummary;
        if (! $summary || $summary->status !== 'released') {
            return response()->json(['message' => 'Results have not been released yet.'], 403);
        }

        $message = $request->validated('message');

        try {
            $result = $this->companionService->chat($applicant, $message);

            return response()->json(['reply' => $result['reply']]);
        } catch (\Throwable $e) {
            Log::warning('AiCompanion chat error', [
                'applicant_id' => $applicant->id,
                'error' => $e->getMessage(),
            ]);

            $code = str_contains($e->getMessage(), 'Rate limit') ? 429 : 502;

            return response()->json([
                'message' => $e->getMessage() ?: 'Request failed. Please try again.',
            ], $code);
        }
    }

    /**
     * POST /portal/ai-companion/clear-history — clear chat history for applicant (T7.4).
     */
    public function clearHistory(): \Illuminate\Http\JsonResponse
    {
        if (! SystemSetting::aiCompanionEnabled()) {
            return response()->json(['message' => 'AI companion is not enabled.'], 403);
        }

        /** @var Applicant $applicant */
        $applicant = Auth::guard('applicant')->user();
        if (! $applicant) {
            return response()->json(['message' => 'Unauthorized.'], 401);
        }

        $applicant->load('consultationSummary');
        $summary = $applicant->consultationSummary;
        if (! $summary || $summary->status !== 'released') {
            return response()->json(['message' => 'Results have not been released yet.'], 403);
        }

        $this->companionService->clearHistory($applicant);

        return response()->json(['message' => 'History cleared.']);
    }
}
