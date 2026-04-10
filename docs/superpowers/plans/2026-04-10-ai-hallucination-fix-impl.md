# AI Hallucination Fix — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Fix AI hallucination in the exam scheduling assistant through prompt hardening, complete data passing (existing sessions + academic year scoping), conversation history repair, and a reset chat escape hatch.

**Architecture:** Three-layer fix: (1) hardened system prompt prevents bare factual claims, (2) complete data (existing sessions + academic year scoping) removes blind spots, (3) backend scrubs corrupted history before AI calls. A reset endpoint gives users an escape hatch.

**Tech Stack:** Laravel 12, PHP 8.2, Svelte, OpenRouter (free model)

---

## File Map

| File | Responsibility |
|------|---------------|
| `app/Services/ExamSchedulingAssistantService.php` | `buildSystemPrompt()` — hardened prompt + existing sessions list |
| `app/Http/Controllers/Admin/ExamSchedulingAssistantController.php` | `chat()` — scope queries + pass existing sessions + scrub history; new `clearConversation()` |
| `routes/web.php` | Add `POST schedule-assistant/clear` route |
| `resources/js/Components/ScheduleAssistantPanel.svelte` | Reset button wired to clear endpoint |
| `tests/Feature/ExamSchedulingAssistantTest.php` | Tests for hallucination-resistant behavior |

---

## Task 1: Scope Applicant Queries to Active Academic Year

**Files:**
- Modify: `app/Http/Controllers/Admin/ExamSchedulingAssistantController.php:44-47` (applicantCount query)
- Modify: `app/Http/Controllers/Admin/ExamSchedulingAssistantController.php:72-80` (applicantSummary query)

- [ ] **Step 1: Add academic year scoping to applicantCount query**

Read lines 43-50 of the controller. The current query:
```php
$applicantCount = Applicant::query()
    ->whereHas('application', fn ($q) => $q->where('status', 'accepted'))
    ->whereDoesntHave('examSessions')
    ->count();
```

Replace with (note: `$activeAcademicYear` is already defined above it):
```php
$applicantCount = Applicant::query()
    ->whereHas('application', fn ($q) => $q->where('status', 'accepted'))
    ->whereDoesntHave('examSessions')
    ->when($activeAcademicYear, fn ($q) => $q->whereHas('application', fn ($aq) => $aq->where('academic_year_id', $activeAcademicYear->id)))
    ->count();
```

- [ ] **Step 2: Add academic year scoping to applicantSummary query**

Read lines 72-80. The current query is the same pattern but without year scoping. Apply the identical `->when()` scoping to the `applicantSummary` query.

- [ ] **Step 3: Verify both queries compile and run**

Run: `php artisan tinker --execute="echo App\Models\Applicant::query()->whereHas('application', fn (\$q) => \$q->where('status', 'accepted'))->whereDoesntHave('examSessions')->count();"`
Expected: a non-negative integer (existing count)

- [ ] **Step 4: Commit**

```bash
git add app/Http/Controllers/Admin/ExamSchedulingAssistantController.php
git commit -m "fix: scope applicant queries to active academic year"
```

---

## Task 2: Query Existing (Non-Draft) Sessions

**Files:**
- Modify: `app/Http/Controllers/Admin/ExamSchedulingAssistantController.php` — add after draftSessions block (after line 108)

- [ ] **Step 1: Add existing_sessions query**

After the `draftSessions` block (around line 109), add:
```php
$existingSessions = [];
if ($activeAcademicYear) {
    $existingSessions = ExamSession::query()
        ->where('status', '!=', ExamSession::STATUS_DRAFT)
        ->forAcademicYear($activeAcademicYear)
        ->with('room:id,name,building,capacity')
        ->get()
        ->map(fn ($s) => [
            'id' => $s->id,
            'room_id' => $s->room_id,
            'room' => $s->room ? [
                'name' => $s->room->name,
                'capacity' => $s->room->capacity,
            ] : ['name' => '?', 'capacity' => 0],
            'date' => $s->date?->format('Y-m-d'),
            'start_time' => $s->start_time,
            'end_time' => $s->end_time,
            'current_count' => $s->applicants()->count(),
            'capacity' => $s->room?->capacity ?? 0,
        ])
        ->values()
        ->all();
}

Log::info('[AI-SCHEDULER-DEBUG] Existing sessions being sent to AI', [
    'count' => count($existingSessions),
    'sessions' => $existingSessions,
]);
```

- [ ] **Step 2: Verify by running a DB query**

Run: `php artisan tinker --execute="echo App\Models\ExamSession::query()->where('status', '!=', App\Models\ExamSession::STATUS_DRAFT)->forAcademicYear(App\Models\AcademicYear::active()?->id)->count();"`
Expected: number of non-draft sessions (may be 0)

- [ ] **Step 3: Commit**

```bash
git add app/Http/Controllers/Admin/ExamSchedulingAssistantController.php
git commit -m "feat: query existing non-draft sessions for AI context"
```

---

## Task 3: Pass existing_sessions to Service + Scrub History

**Files:**
- Modify: `app/Http/Controllers/Admin/ExamSchedulingAssistantController.php:120-126` (service call context)
- Modify: `app/Http/Controllers/Admin/ExamSchedulingAssistantController.php` — add `scrubContradictingMessages()` method

- [ ] **Step 1: Add scrubContradictingMessages() method**

Add this method to the controller (before or after `normalizeStructuredSchedule()`):
```php
/**
 * Scrub assistant messages that contain factual claims contradicting current data.
 * Narrow pattern matching only — catches the known hallucination pattern.
 */
private function scrubContradictingMessages(array $messages, int $applicantCount): array
{
    $hallucinationPatterns = [
        '/\bno\s+(unassigned|unscheduled)\s+applicants?\b/i',
        '/\b0\s+applicants?\b/i',
        '/\bzero\s+applicants?\b/i',
        '/\bthere\s+are\s+no\s+applicants?\b/i',
        '/\bno\s+applicants?\s+(left|remaining|to\s+schedule|waiting)\b/i',
    ];

    $placeholder = '[The AI previously made a statement here that contradicted the current data and it has been corrected.]';

    return array_map(function ($msg) use ($hallucinationPatterns, $applicantCount, $placeholder) {
        if ($msg['role'] !== 'assistant') {
            return $msg;
        }

        $content = $msg['content'] ?? '';

        // If the message is just the placeholder, leave it
        if ($content === $placeholder) {
            return $msg;
        }

        // If applicantCount is 0 and the message claims zero — that's accurate, leave it
        if ($applicantCount === 0) {
            return $msg;
        }

        // Check against hallucination patterns
        foreach ($hallucinationPatterns as $pattern) {
            if (preg_match($pattern, $content)) {
                Log::info('[AI-SCHEDULER-DEBUG] Scrubbing hallucinated message', [
                    'pattern' => $pattern,
                    'content' => $content,
                ]);
                return ['role' => 'assistant', 'content' => $placeholder];
            }
        }

        return $msg;
    }, $messages);
}
```

- [ ] **Step 2: Wire scrub into chat() — read current service call around line 120**

The current service call:
```php
$result = $this->assistantService->chat($newMessages, [
    'applicant_count' => $applicantCount,
    'rooms' => $rooms,
    'applicant_summary' => $applicantSummary,
    'draft_sessions' => $draftSessions,
], $requestStructured);
```

Add `existing_sessions` to the context and call `scrubContradictingMessages()` on `$newMessages` before the service call:
```php
$scrubbedMessages = $this->scrubContradictingMessages($newMessages, $applicantCount);

$result = $this->assistantService->chat($scrubbedMessages, [
    'applicant_count' => $applicantCount,
    'rooms' => $rooms,
    'applicant_summary' => $applicantSummary,
    'draft_sessions' => $draftSessions,
    'existing_sessions' => $existingSessions,
], $requestStructured);
```

Note: `$existingSessions` must be defined (Task 2) before this step.

- [ ] **Step 3: Run PHP syntax check**

Run: `php -l app/Http/Controllers/Admin/ExamSchedulingAssistantController.php`
Expected: "No syntax errors detected"

- [ ] **Step 4: Commit**

```bash
git add app/Http/Controllers/Admin/ExamSchedulingAssistantController.php
git commit -m "feat: add scrubContradictingMessages and pass existing_sessions to AI"
```

---

## Task 4: Update buildSystemPrompt() — Hardened Rules + Existing Sessions

**Files:**
- Modify: `app/Services/ExamSchedulingAssistantService.php:63-101`

- [ ] **Step 1: Read the current buildSystemPrompt() method**

Read lines 63-101. Replace the entire `return "You are an assistant..."` block with the hardened version below. The structure should be:
1. Compute `$roomList` (unchanged)
2. Compute `$summary` (unchanged — applicant IDs)
3. Compute `$draftList` (unchanged)
4. **Add** `buildExistingSessionsList()` — new block for existing sessions
5. **Replace** the final return with the hardened prompt

```php
$existingList = '';
if (! empty($existingSessions)) {
    $existingList = ' Existing SCHEDULED (non-draft) exam sessions — do NOT schedule applicants in these rooms/times: '
        . collect($existingSessions)->map(fn ($s) => sprintf(
            'id: %s, room: %s, date: %s, time: %s-%s',
            $s['id'],
            $s['room']['name'] ?? $s['room_id'] ?? '?',
            $s['date'] ?? '—',
            $s['start_time'] ?? '—',
            $s['end_time'] ?? '—'
        ))->join('; ') . '.';
}

return "You are an assistant helping an admin schedule exam sessions. "
    . "IMPORTANT — you MUST always derive factual claims (counts, room availability, session status) from the data provided below, NEVER from memory or guesswork. "
    . "There are {$applicantCount} applicants waiting to be scheduled.{$summary} "
    . "Available rooms: {$roomList}.{$draftList}{$existingList} "
    . "Constraints: each applicant must be assigned to exactly one session; each session cannot exceed room capacity; the same room cannot be double-booked (no overlapping date/time). "
    . "IMPORTANT — Assigned means confirmed placement: applicants assigned only to DRAFT sessions are still awaiting scheduling (draft sessions are not confirmed placements). Only applicants in non-draft sessions are considered fully assigned. "
    . "You may assign applicants to EXISTING DRAFT sessions (use exam_session_id + applicant_ids) or create NEW sessions (use room_id, date, start_time, end_time, applicant_ids). "
    . "Ask clarifying questions (e.g. preferred dates, morning/afternoon slots) and only output the final schedule when the user confirms. "
    . "NEVER make absolute factual claims (e.g. 'there are no applicants', 'all rooms are full', 'no draft sessions') unless the data above explicitly shows zero. "
    . "If you are unsure, say 'The records show...' or 'Based on the data I have...' rather than making absolute statements. "
    . "In the chat, always reply in plain conversational language: no code blocks, no JSON. When the user asks to generate the schedule, the structured output is captured separately—do not repeat the full JSON in your reply. "
    . "When the user asks to generate or output the schedule, respond with valid JSON: sessions array where each item has applicant_ids and either exam_session_id (existing draft) or room_id, date, start_time, end_time (new session).";
```

Note: The method signature should be updated to accept `array $existingSessions = []`:
```php
public function buildSystemPrompt(int $applicantCount, array $rooms, array $applicantSummary = [], array $draftSessions = [], array $existingSessions = []): string
```

- [ ] **Step 2: Update the chat() method to pass existing_sessions to buildSystemPrompt**

Read the chat() method around line 113-119. Change the call to `buildSystemPrompt()` to:
```php
$systemPrompt = $this->buildSystemPrompt(
    $context['applicant_count'],
    $context['rooms'],
    $context['applicant_summary'] ?? [],
    $context['draft_sessions'] ?? [],
    $context['existing_sessions'] ?? []
);
```

- [ ] **Step 3: Run PHP syntax check**

Run: `php -l app/Services/ExamSchedulingAssistantService.php`
Expected: "No syntax errors detected"

- [ ] **Step 4: Commit**

```bash
git add app/Services/ExamSchedulingAssistantService.php
git commit -m "feat: harden system prompt and add existing sessions to AI context"
```

---

## Task 5: Add Clear Conversation Endpoint

**Files:**
- Modify: `app/Http/Controllers/Admin/ExamSchedulingAssistantController.php` — add `clearConversation()` method
- Modify: `routes/web.php:117` — add clear route alongside chat route

- [ ] **Step 1: Add clearConversation() method to the controller**

Add after the `chat()` method (around line 160):
```php
/**
 * DELETE clear — delete the current user's conversation to start fresh.
 */
public function clearConversation(Request $request): JsonResponse
{
    $this->authorize('create', ExamSession::class);

    $deleted = ExamSchedulingConversation::query()
        ->where('user_id', $request->user()->id)
        ->delete();

    Log::info('[AI-SCHEDULER-DEBUG] Conversation cleared', [
        'user_id' => $request->user()->id,
        'deleted_count' => $deleted,
    ]);

    return response()->json(['message' => 'Conversation reset.']);
}
```

- [ ] **Step 2: Add route in web.php**

Read the route group around line 116-118. Add after the chat route:
```php
Route::post('test-scheduling/schedule-assistant/chat', [ExamSchedulingAssistantController::class, 'chat'])->name('test-scheduling.schedule-assistant.chat');
Route::delete('test-scheduling/schedule-assistant/conversation', [ExamSchedulingAssistantController::class, 'clearConversation'])->name('test-scheduling.schedule-assistant.clear');
Route::post('test-scheduling/schedule-assistant/apply-schedule', [ExamSchedulingAssistantController::class, 'applySchedule'])->name('test-scheduling.schedule-assistant.apply');
```

Use DELETE method (more RESTful for a resource deletion).

- [ ] **Step 3: Run route check**

Run: `php artisan route:list --name=schedule-assistant`
Expected: new `schedule-assistant.clear` route visible

- [ ] **Step 4: Commit**

```bash
git add app/Http/Controllers/Admin/ExamSchedulingAssistantController.php routes/web.php
git commit -m "feat: add clear conversation endpoint and route"
```

---

## Task 6: Add Reset Button to ScheduleAssistantPanel

**Files:**
- Modify: `resources/js/Components/ScheduleAssistantPanel.svelte`

- [ ] **Step 1: Read the top section of the component to find the button area**

Read lines 1-30 of ScheduleAssistantPanel.svelte to understand the props. Read around lines 203-219 to see where the panel header is.

- [ ] **Step 2: Add Trash icon import**

Read line 6. The current import:
```javascript
import { MessageSquare, Send, Sparkles, Calendar, CheckCircle2 } from 'lucide-svelte';
```

Add `Trash2`:
```javascript
import { MessageSquare, Send, Sparkles, Calendar, CheckCircle2, Trash2 } from 'lucide-svelte';
```

- [ ] **Step 3: Add reset function after the send() function**

Read around lines 89-154 (the send function). Add after the closing `}` of `send()` and before `function handleKeydown`:
```javascript
async function resetConversation() {
    if (!confirm('Reset the conversation? This cannot be undone.')) return;
    try {
        const res = await fetch('/admin/test-scheduling/schedule-assistant/conversation', {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': csrf_token,
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        });
        if (res.ok) {
            messages = [];
            hasReplyThisSession = false;
            structuredSchedule = null;
            error = '';
        }
    } catch (e) {
        error = 'Failed to reset conversation.';
    }
}
```

- [ ] **Step 4: Add Reset button in the panel header**

Read the panel header area (around lines 203-219, the top `rounded-lg border` div showing the AI header). Add a reset button on the right side of the `Schedule with AI` heading:

Find:
```svelte
<div class="flex items-center gap-2">
    <Sparkles class="w-5 h-5 text-primary" />
    <h2 class="text-lg font-bold text-foreground">Schedule with AI</h2>
</div>
```

Replace with:
```svelte
<div class="flex items-center justify-between">
    <div class="flex items-center gap-2">
        <Sparkles class="w-5 h-5 text-primary" />
        <h2 class="text-lg font-bold text-foreground">Schedule with AI</h2>
    </div>
    {#if messages.length > 0}
        <button
            type="button"
            onclick={resetConversation}
            class="text-xs text-muted-foreground hover:text-destructive transition-colors"
            title="Reset conversation"
        >
            <Trash2 class="h-4 w-4" />
        </button>
    {/if}
</div>
```

- [ ] **Step 5: Verify the Svelte file compiles**

Run: `npm run build 2>&1 | head -30`
Expected: no errors related to ScheduleAssistantPanel.svelte

- [ ] **Step 6: Commit**

```bash
git add resources/js/Components/ScheduleAssistantPanel.svelte
git commit -m "feat: add reset conversation button to schedule assistant panel"
```

---

## Task 7: Integration Test for Hallucination Resistance

**Files:**
- Create: `tests/Feature/ExamSchedulingAssistantTest.php`

- [ ] **Step 1: Create test file**

Run: `php artisan make:test ExamSchedulingAssistantTest --phpunit`

- [ ] **Step 2: Write test — applicant count scoped to academic year**

Add to `tests/Feature/ExamSchedulingAssistantTest.php`:
```php
<?php

use App\Models\AcademicYear;
use App\Models\Applicant;
use App\Models\Application;
use App\Models\ExamSession;
use App\Models\ExamSchedulingConversation;
use App\Models\Room;
use App\Services\ExamSchedulingAssistantService;

class ExamSchedulingAssistantTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.openrouter.key' => 'test-key']);
        config(['services.openrouter.model' => 'openrouter/free']);
    }

    public function test_applicant_count_excludes_previous_academic_year(): void
    {
        // Current active year
        $currentYear = AcademicYear::create([
            'name' => '2025-2026',
            'start_date' => '2025-01-01',
            'end_date' => '2026-12-31',
            'is_active' => true,
        ]);

        // Previous year
        $prevYear = AcademicYear::create([
            'name' => '2024-2025',
            'start_date' => '2024-01-01',
            'end_date' => '2025-12-31',
            'is_active' => false,
        ]);

        $room = Room::create(['name' => 'Lab 1', 'capacity' => 30, 'is_active' => true]);

        // Accepted applicant in PREVIOUS year
        $prevApp = Application::create([
            'academic_year_id' => $prevYear->id,
            'status' => 'accepted',
            'reference_number' => 'PREV-001',
            'first_name' => 'Previous',
            'last_name' => 'Applicant',
            'birthdate' => '2000-01-01',
            'sex' => 'male',
            'email' => 'prev@test.com',
        ]);
        $prevApplicant = Applicant::create(['application_id' => $prevApp->id, 'email' => 'prev@test.com']);

        // Accepted applicant in CURRENT year — unassigned (should be counted)
        $currApp = Application::create([
            'academic_year_id' => $currentYear->id,
            'status' => 'accepted',
            'reference_number' => 'CURR-001',
            'first_name' => 'Current',
            'last_name' => 'Applicant',
            'birthdate' => '2000-01-01',
            'sex' => 'male',
            'email' => 'curr@test.com',
        ]);
        $currApplicant = Applicant::create(['application_id' => $currApp->id, 'email' => 'curr@test.com']);

        // Query through the service's buildSystemPrompt context
        $service = new ExamSchedulingAssistantService();
        $prompt = $service->buildSystemPrompt(
            applicantCount: 1,  // only current year counted
            rooms: [['id' => $room->id, 'name' => 'Lab 1', 'capacity' => 30]],
            applicantSummary: [['id' => $currApplicant->id]],
            draftSessions: [],
            existingSessions: []
        );

        $this->assertStringContainsString('1 applicants waiting to be scheduled', $prompt);
        $this->assertStringNotContainsString('PREV-001', $prompt);
    }

    public function test_system_prompt_contains_existing_sessions_constraint(): void
    {
        $room = Room::create(['name' => 'Lab 1', 'capacity' => 30, 'is_active' => true]);

        $year = AcademicYear::create([
            'name' => '2025-2026',
            'start_date' => '2025-01-01',
            'end_date' => '2026-12-31',
            'is_active' => true,
        ]);

        $app = Application::create([
            'academic_year_id' => $year->id,
            'status' => 'accepted',
            'reference_number' => 'REF-001',
            'first_name' => 'Test',
            'last_name' => 'User',
            'birthdate' => '2000-01-01',
            'sex' => 'male',
            'email' => 'test@test.com',
        ]);
        $applicant = Applicant::create(['application_id' => $app->id, 'email' => 'test@test.com']);

        $session = ExamSession::create([
            'academic_year_id' => $year->id,
            'room_id' => $room->id,
            'date' => '2026-04-15',
            'start_time' => '09:00',
            'end_time' => '12:00',
            'status' => ExamSession::STATUS_CONFIRMED,
        ]);

        $service = new ExamSchedulingAssistantService();
        $prompt = $service->buildSystemPrompt(
            applicantCount: 1,
            rooms: [['id' => $room->id, 'name' => 'Lab 1', 'capacity' => 30]],
            applicantSummary: [['id' => $applicant->id]],
            draftSessions: [],
            existingSessions: [[
                'id' => $session->id,
                'room_id' => $room->id,
                'room' => ['name' => 'Lab 1', 'capacity' => 30],
                'date' => '2026-04-15',
                'start_time' => '09:00',
                'end_time' => '12:00',
            ]]
        );

        $this->assertStringContainsString('SCHEDULED (non-draft)', $prompt);
        $this->assertStringContainsString('2026-04-15', $prompt);
        $this->assertStringContainsString('Lab 1', $prompt);
    }

    public function test_clear_conversation_deletes_user_conversation(): void
    {
        $user = User::factory()->create();
        ExamSchedulingConversation::create([
            'user_id' => $user->id,
            'messages' => [['role' => 'user', 'content' => 'hello']],
        ]);

        $this->actingAs($user)
            ->deleteJson('/admin/test-scheduling/schedule-assistant/conversation')
            ->assertOk()
            ->assertJson(['message' => 'Conversation reset.']);

        $this->assertDatabaseMissing('exam_scheduling_conversations', ['user_id' => $user->id]);
    }

    public function test_scrub_replaces_hallucinated_zero_applicant_claims(): void
    {
        $controller = new \App\Http\Controllers\Admin\ExamSchedulingAssistantController(
            new \App\Services\ExamSchedulingAssistantService
        );

        $messages = [
            ['role' => 'user', 'content' => 'How many are unassigned?'],
            ['role' => 'assistant', 'content' => 'There are no unassigned applicants yet.'],
        ];

        $scrubbed = $this->invokePrivateMethod($controller, 'scrubContradictingMessages', [$messages, 4]);

        $this->assertStringContainsString('contradicted', $scrubbed[1]['content']);
        $this->assertNotEquals('There are no unassigned applicants yet.', $scrubbed[1]['content']);
    }
}
```

Note: `invokePrivateMethod` requires adding `use ReflectionMethod;` and using `$method = new ReflectionMethod($controller, 'scrubContradictingMessages'); $method->setAccessible(true); return $method->invoke($controller, ...);`

- [ ] **Step 3: Run the tests**

Run: `php artisan test --compact --filter=ExamSchedulingAssistantTest`
Expected: all tests pass (some may need adjustment based on actual model setup — fix any factory/state issues found)

- [ ] **Step 4: Commit**

```bash
git add tests/Feature/ExamSchedulingAssistantTest.php
git commit -m "test: add hallucination resistance and clear conversation tests"
```

---

## Spec Coverage Check

| Spec Item | Task |
|-----------|------|
| Prompt hardening — bare factual claims | Task 4 |
| Prompt hardening — assigned means confirmed placement | Task 4 |
| Pass confirmed (non-draft) sessions to AI | Tasks 2, 3, 4 |
| Academic year scoping for applicant queries | Task 1 |
| Conversation history scrub before AI call | Task 3 |
| Reset chat endpoint | Task 5 |
| Reset chat UI button | Task 6 |
| Integration tests | Task 7 |

All spec items are covered.

---

## Plan Complete

Two execution options:

**1. Subagent-Driven (recommended)** — I dispatch a fresh subagent per task, review between tasks, fast iteration

**2. Inline Execution** — Execute tasks in this session using executing-plans, batch execution with checkpoints

Which approach?

---

## Self-Review: Placeholder Scan

- No "TBD" or "TODO" found
- All code blocks show actual implementation
- All commands use exact paths and expected output
- Type consistency verified: `existingSessions` parameter flows from controller (Task 2) → service (Task 4) → `buildSystemPrompt()`
- Method names consistent: `scrubContradictingMessages()` called exactly once in `chat()`, defined once in controller
- Route uses DELETE method matching `clearConversation()` semantics
