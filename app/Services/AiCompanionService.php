<?php

namespace App\Services;

use App\Models\AiCompanionMessage;
use App\Models\Applicant;
use App\Models\SystemSetting;
use Illuminate\Support\Arr;
use MoeMizrak\LaravelOpenrouter\DTO\ChatData;
use MoeMizrak\LaravelOpenrouter\DTO\ErrorData;
use MoeMizrak\LaravelOpenrouter\DTO\MessageData;
use MoeMizrak\LaravelOpenrouter\DTO\ResponseData;
use MoeMizrak\LaravelOpenrouter\Facades\LaravelOpenRouter;
use MoeMizrak\LaravelOpenrouter\Types\RoleType;

class AiCompanionService
{
    public const DEFAULT_MAX_HISTORY = 20;

    public function __construct(
        protected KnowledgeRetrievalService $retrieval
    ) {}

    /**
     * Build applicant context summary (scores + course preferences) for the system prompt.
     */
    public function buildApplicantSummary(Applicant $applicant): string
    {
        $applicant->load(['applicantScores.domain', 'application']);

        $parts = [];

        $scores = $applicant->applicantScores;
        if ($scores->isEmpty()) {
            $parts[] = 'Applicant scores: Not yet available.';
        } else {
            $scoreLines = $scores->map(fn ($s) => sprintf(
                '%s: %s/%s',
                $s->domain?->name ?? $s->domain?->code ?? 'Domain',
                $s->raw_score ?? '—',
                $s->max_score ?? '—'
            ))->unique()->values()->all();
            $parts[] = 'Applicant scores: ' . implode('; ', $scoreLines);
        }

        $application = $applicant->application;
        if ($application && (isset($application->course_preference_1) || isset($application->course_preference_2) || isset($application->course_preference_3))) {
            $prefs = [];
            foreach (['course_preference_1', 'course_preference_2', 'course_preference_3'] as $key) {
                $id = $application->{$key} ?? null;
                if (! empty($id)) {
                    $prefs[] = $this->resolveCourseName($id);
                }
            }
            if ($prefs !== []) {
                $parts[] = 'Course preferences: ' . implode(', ', array_filter($prefs));
            }
        }

        return implode("\n", $parts);
    }

    private function resolveCourseName($courseId): string
    {
        if (is_numeric($courseId)) {
            $name = \Illuminate\Support\Facades\DB::table('courses')->where('id', $courseId)->value('name');

            return $name ?? "Course #{$courseId}";
        }

        return (string) $courseId;
    }

    /**
     * Build system prompt: persona + institutional data (retrieved by metadata) + applicant summary (T5).
     */
    public function buildSystemPrompt(Applicant $applicant): string
    {
        $persona = SystemSetting::personaPrompt();
        $institutional = $this->retrieval->retrieveForApplicant($applicant);
        $applicantSummary = $this->buildApplicantSummary($applicant);

        return $persona
            . "\n\nInstitutional data (use only this; do not invent):\n"
            . $institutional
            . "\n\n--- Applicant data ---\n"
            . $applicantSummary
            . "\n--- End applicant data ---\n\nUse only the institutional and applicant data above when giving advice. Do not invent statistics. If the data does not cover a question, say so.";
    }

    /**
     * Send a single user message, persist user + assistant, and return the assistant reply (T7).
     * Loads last N messages for context; stores both user and assistant messages.
     *
     * @return array{reply: string}
     */
    public function chat(Applicant $applicant, string $userMessage, int $maxHistory = self::DEFAULT_MAX_HISTORY): array
    {
        // (1) Append user message to DB (T7.1)
        $userMsg = AiCompanionMessage::create([
            'applicant_id' => $applicant->id,
            'role' => AiCompanionMessage::ROLE_USER,
            'content' => $userMessage,
            'created_at' => now(),
        ]);

        // (2) Load last N messages (T7.3), chronological order for API
        $history = AiCompanionMessage::lastForApplicant($applicant->id, $maxHistory)
            ->get()
            ->sortBy('created_at')
            ->values()
            ->all();

        $systemPrompt = $this->buildSystemPrompt($applicant);
        $model = config('services.openrouter.model', 'openrouter/free');

        $messageData = [
            new MessageData(content: $systemPrompt, role: RoleType::SYSTEM),
        ];

        foreach ($history as $m) {
            $role = $m->role === AiCompanionMessage::ROLE_ASSISTANT ? RoleType::ASSISTANT : RoleType::USER;
            $messageData[] = new MessageData(content: $m->content, role: $role);
        }

        $chatData = new ChatData(
            messages: $messageData,
            model: $model,
            max_tokens: 1024
        );

        $response = LaravelOpenRouter::chatRequest($chatData);

        if ($response instanceof ErrorData) {
            if ($response->code === 429) {
                throw new \RuntimeException('Rate limit reached. Please try again in a moment.');
            }
            throw new \RuntimeException($response->message ?: 'Request failed. Please try again.');
        }

        /** @var ResponseData $response */
        $choice = is_array($response->choices) ? ($response->choices[0] ?? null) : null;
        $content = $choice ? (Arr::get($choice, 'message.content') ?? (is_object($choice) && isset($choice->message) ? $choice->message->content : '')) : '';
        if (is_array($content)) {
            $content = Arr::get($content, '0.text', json_encode($content));
        }
        $reply = (string) $content;

        // (4) Append assistant reply to DB
        AiCompanionMessage::create([
            'applicant_id' => $applicant->id,
            'role' => AiCompanionMessage::ROLE_ASSISTANT,
            'content' => $reply,
            'created_at' => now(),
        ]);

        return ['reply' => $reply];
    }

    /**
     * Clear all chat history for an applicant (T7.4).
     */
    public function clearHistory(Applicant $applicant): void
    {
        AiCompanionMessage::where('applicant_id', $applicant->id)->delete();
    }
}
