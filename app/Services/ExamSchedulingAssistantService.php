<?php

namespace App\Services;

use Illuminate\Support\Arr;
use MoeMizrak\LaravelOpenrouter\DTO\ChatData;
use MoeMizrak\LaravelOpenrouter\DTO\ErrorData;
use MoeMizrak\LaravelOpenrouter\DTO\MessageData;
use MoeMizrak\LaravelOpenrouter\DTO\ProviderPreferencesData;
use MoeMizrak\LaravelOpenrouter\DTO\ResponseFormatData;
use MoeMizrak\LaravelOpenrouter\DTO\ResponseData;
use MoeMizrak\LaravelOpenrouter\Facades\LaravelOpenRouter;
use MoeMizrak\LaravelOpenrouter\Types\RoleType;

class ExamSchedulingAssistantService
{
    /**
     * JSON schema for the final exam schedule (used for structured output and validation).
     * Each session is either: (exam_session_id + applicant_ids) for existing draft, or
     * (room_id, date, start_time, end_time, applicant_ids) for a new session.
     */
    public static function scheduleJsonSchema(): array
    {
        return [
            'name' => 'exam_schedule',
            'strict' => true,
            'schema' => [
                'type' => 'object',
                'properties' => [
                    'sessions' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'object',
                            'properties' => [
                                'exam_session_id' => ['type' => 'integer', 'description' => 'Existing draft exam session ID (use this OR room_id+date+start_time for new)'],
                                'room_id' => ['type' => 'integer', 'description' => 'Room ID for new session'],
                                'date' => ['type' => 'string', 'description' => 'Date YYYY-MM-DD for new session'],
                                'start_time' => ['type' => 'string', 'description' => 'Start time HH:MM for new session'],
                                'end_time' => ['type' => 'string', 'description' => 'End time HH:MM or null for new session'],
                                'applicant_ids' => [
                                    'type' => 'array',
                                    'items' => ['type' => 'integer'],
                                    'description' => 'Applicant IDs to assign to this session',
                                ],
                            ],
                            'required' => ['applicant_ids'],
                            'additionalProperties' => false,
                        ],
                    ],
                ],
                'required' => ['sessions'],
                'additionalProperties' => false,
            ],
        ];
    }

    /**
     * Build system prompt with applicant, room, and draft session context.
     *
     * @param  array<int, array{id: int, room?: array, date?: string, start_time?: string, end_time?: string, current_count: int, capacity: int}>  $draftSessions
     */
    public function buildSystemPrompt(int $applicantCount, array $rooms, array $applicantSummary = [], array $draftSessions = []): string
    {
        $roomList = collect($rooms)->map(fn ($r) => sprintf(
            'id: %s, name: %s, capacity: %s',
            $r['id'],
            $r['name'] ?? '—',
            $r['capacity'] ?? 0
        ))->join('; ');

        $summary = '';
        if (! empty($applicantSummary)) {
            $ids = collect($applicantSummary)->pluck('id')->take(50)->implode(', ');
            $summary = " Applicant IDs to schedule (sample): {$ids}.";
        }

        $draftList = '';
        if (! empty($draftSessions)) {
            $draftList = ' Existing DRAFT exam sessions (you may assign more applicants up to room capacity): '
                . collect($draftSessions)->map(fn ($s) => sprintf(
                    'id: %s, room: %s, date: %s, time: %s-%s, current: %s, capacity: %s',
                    $s['id'],
                    $s['room']['name'] ?? $s['room_id'] ?? '?',
                    $s['date'] ?? '—',
                    $s['start_time'] ?? '—',
                    $s['end_time'] ?? '—',
                    $s['current_count'] ?? 0,
                    $s['capacity'] ?? 0
                ))->join('; ') . '.';
        }

        return "You are an assistant helping an admin schedule exam sessions. "
            . "There are {$applicantCount} applicants to schedule.{$summary} "
            . "Available rooms: {$roomList}.{$draftList} "
            . "Constraints: each applicant must be assigned to exactly one session; each session cannot exceed room capacity; the same room cannot be double-booked (no overlapping date/time). "
            . "You may assign applicants to EXISTING DRAFT sessions (use exam_session_id + applicant_ids) or create NEW sessions (use room_id, date, start_time, end_time, applicant_ids). "
            . "Ask clarifying questions (e.g. preferred dates, morning/afternoon slots) and only output the final schedule when the user confirms. "
            . "In the chat, always reply in plain conversational language: no code blocks, no JSON. When the user asks to generate the schedule, the structured output is captured separately—do not repeat the full JSON in your reply. "
            . "When the user asks to generate or output the schedule, respond with valid JSON: sessions array where each item has applicant_ids and either exam_session_id (existing draft) or room_id, date, start_time, end_time (new session).";
    }

    /**
     * Send chat request to OpenRouter and return reply content or throw/user-friendly error.
     *
     * @param  array<int, array{role: string, content: string}>  $messages  Conversation messages (role + content)
     * @param  array{applicant_count: int, rooms: array, applicant_summary?: array}  $context  For system prompt
     * @param  bool  $requestStructured  If true, request JSON schema response and return parsed schedule
     * @return array{reply: string, structured_schedule?: array}
     */
    public function chat(array $messages, array $context, bool $requestStructured = false): array
    {
        $model = config('services.openrouter.model', 'openrouter/free');
        $systemPrompt = $this->buildSystemPrompt(
            $context['applicant_count'],
            $context['rooms'],
            $context['applicant_summary'] ?? [],
            $context['draft_sessions'] ?? []
        );

        $messageData = [
            new MessageData(content: $systemPrompt, role: RoleType::SYSTEM),
        ];
        foreach ($messages as $m) {
            $role = $m['role'] === 'assistant' ? RoleType::ASSISTANT : RoleType::USER;
            $messageData[] = new MessageData(content: (string) $m['content'], role: $role);
        }

        $responseFormat = null;
        if ($requestStructured) {
            $schema = self::scheduleJsonSchema();
            $responseFormat = new ResponseFormatData(
                type: 'json_schema',
                json_schema: $schema
            );
        }

        $chatData = new ChatData(
            messages: $messageData,
            model: $model,
            response_format: $responseFormat,
            max_tokens: 2048,
            provider: $requestStructured ? new ProviderPreferencesData(require_parameters: true) : null
        );

        $response = LaravelOpenRouter::chatRequest($chatData);

        if ($response instanceof ErrorData) {
            if ($response->code === 429) {
                throw new \RuntimeException('OpenRouter rate limit reached. Please try again in a moment.');
            }
            throw new \RuntimeException($response->message ?: 'OpenRouter request failed.');
        }

        /** @var ResponseData $response */
        $choice = is_array($response->choices) ? ($response->choices[0] ?? null) : null;
        $content = $choice ? (Arr::get($choice, 'message.content') ?? (is_object($choice) && isset($choice->message) ? $choice->message->content : '')) : '';
        if (is_array($content)) {
            $content = Arr::get($content, '0.text', json_encode($content));
        }
        $content = (string) $content;

        $result = ['reply' => $content];

        if ($requestStructured && $content !== '') {
            $decoded = json_decode($content, true);
            if (is_array($decoded) && isset($decoded['sessions'])) {
                $result['structured_schedule'] = $decoded;
            }
        }

        return $result;
    }
}
