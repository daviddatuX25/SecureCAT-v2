# AI Hallucination Fix — Design Spec

## Context

The AI scheduling assistant (ExamSchedulingAssistantService) suffers from hallucinations: the AI ignores provided data and makes up factual claims (e.g., "there are no unassigned applicants" when 4 exist). This corrupts both the conversation UX and the saved conversation history.

## Root Causes

1. **Weak system prompt** — the AI is not explicitly told to never make uncorroborated factual claims
2. **Missing context** — non-draft (confirmed) exam sessions are not passed to the AI, creating blind spots that lead to scheduling conflicts and hallucinated claims
3. **Corrupted history** — once a hallucinated reply is saved to `exam_scheduling_conversations`, the AI sees its own wrong claim in subsequent turns and may double down
4. **Missing academic year scoping** — the `applicantCount` query is not scoped to the active academic year, potentially counting applicants from previous cycles
5. **No escape hatch** — users have no way to reset a corrupted conversation; the only fix is clearing browser state

## Solution: Layered Defense

### Layer 1 — Prompt Hardening (Primary Fix)

**File:** `app/Services/ExamSchedulingAssistantService.php`
**Method:** `buildSystemPrompt()`

Tighten the system prompt to constrain the AI from making bare factual claims. The AI is told:

- Always derive counts, room availability, and session status **from the data provided** — never from memory
- Never state "no applicants", "no rooms available", or "all applicants are scheduled" unless the data explicitly shows zero
- If unsure, say "The records show..." or "Based on the data I have..." rather than making absolute claims
- Phrase counts as derived from data: "I see N applicants waiting to be scheduled" (not "there are N applicants")
- **Assigned means confirmed placement:** Applicants assigned only to DRAFT sessions are still counted as "awaiting scheduling" — draft sessions are not confirmed placements. Only applicants in non-draft sessions are considered fully assigned.
- Never speculate about room availability or scheduling conflicts — always check against the room/session lists provided

The conversational style is preserved — only bare factual assertions are restricted.

### Layer 2 — Pass Confirmed Exam Sessions + Academic Year Scoping (Data Completeness)

**Bug fixed:** The `applicantCount` and `applicantSummary` queries were NOT scoped to the active academic year, meaning applicants from previous cycles could be counted. All queries are now scoped via `AcademicYear::active()->id`.

**All data passed to the AI is scoped to the active academic year:**
- `applicantCount` / `applicantSummary` — accepted applicants without a confirmed (non-draft) session, scoped to active academic year via their application
- `draftSessions` — already scoped (existing behavior, confirmed)
- `existingSessions` (new) — non-draft sessions scoped to active academic year

**File:** `app/Http/Controllers/Admin/ExamSchedulingAssistantController.php`
**Method:** `chat()`

Currently only `draft_sessions` (status = DRAFT) are passed to the AI. Non-draft sessions (confirmed/completed) are excluded, causing:
- Scheduling conflicts: AI suggests a room/time that is already booked
- Hallucinations: AI makes up availability claims because it doesn't see existing bookings

**Fix:** Also pass `existing_sessions` — all non-draft exam sessions for the active academic year, with room, date, start_time, end_time, and current applicant count.

**File:** `app/Services/ExamSchedulingAssistantService.php`
**Method:** `buildSystemPrompt()`

Add `existing_sessions` to the system prompt:
- "Existing SCHEDULED (non-draft) exam sessions — do NOT schedule applicants in these rooms/times: [list]"
- Each session: `id, room: X, date: Y, time: HH:MM-HH:MM, current count / capacity`

### Layer 3 — Conversation History Repair

**File:** `app/Http/Controllers/Admin/ExamSchedulingAssistantController.php`
**Method:** `chat()`

Before sending the loaded conversation to the AI:
1. Check each assistant message in `existingMessages` for factual claim phrases that contradict current data
2. If a contradiction is found, replace the assistant message content with a neutral placeholder: `"[The AI previously made a statement here that contradicted the current data and it has been set aside.]"`
3. The user still sees the exchange in UI (history is not modified), but the AI does not receive the corrupted claim

Phrase detection uses simple case-insensitive string matching:
- `"no unassigned applicants"` / `"there are no"` + `applicant`
- `"no draft"` / `"no sessions"` + contradicting current data
- `"0 applicants"` / `"zero applicants"`

This is intentionally narrow — only catches the specific pattern that was observed.

**One-time cleanup:** On deployment, any existing assistant messages in `exam_scheduling_conversations` containing hallucinated phrases (e.g., "no unassigned applicants") are flagged for the user to review. The `scrubContradictingMessages()` logic applies going forward; existing corrupted messages are handled by the next AI turn.

### Layer 4 — Reset Chat (User Escape Hatch)

**File:** `app/Http/Controllers/Admin/ExamSchedulingAssistantController.php`
**New method:** `clearConversation()`

A new endpoint `POST /admin/test-scheduling/schedule-assistant/clear` allows the user to delete their current conversation, starting fresh. This gives users an escape hatch when the AI has gone off the rails.

**File:** `resources/js/Components/ScheduleAssistantPanel.svelte`
**Change:** Add a "Reset conversation" button (trash icon) in the panel header, enabled when `messages.length > 0`. On click, calls the clear endpoint and resets the local messages array.

**UX:** No confirmation dialog — the chat history is not critical data (it's just the AI conversation). The user can re-explain their intent after a reset.

## Files to Modify

| File | Change |
|------|--------|
| `app/Services/ExamSchedulingAssistantService.php` | Update `buildSystemPrompt()` with hardened rules + existing sessions |
| `app/Http/Controllers/Admin/ExamSchedulingAssistantController.php` | Query and pass `existing_sessions`; scope all queries to active academic year; add `scrubContradictingMessages()` before sending to AI; add `clearConversation()` endpoint |
| `routes/web.php` | Add `POST schedule-assistant/clear` route |
| `resources/js/Components/ScheduleAssistantPanel.svelte` | Add "Reset conversation" button wired to clear endpoint |

## Verification

1. With 4 unassigned applicants in DB, send "how many applicants are unassigned?" — AI must reply with a count of 4 (not 0)
2. With a confirmed session booked at Lab Room 1 on 2026-04-15 09:00-12:00, ask AI to schedule applicants then — AI must not suggest that room/time
3. Send a second message after a hallucination — AI must not double down on the wrong claim
4. All existing draft-schedule functionality remains unchanged
5. Click "Reset conversation" — chat clears and AI starts fresh without prior context
6. Applicant count in AI context matches the "N applicants to schedule" badge in the UI panel

## Out of Scope

- Switching to a paid model with tool-calling (Approach 3 from brainstorming — deferred to future work)
- Changes to `apply-schedule` endpoint (already validates correctly server-side)
- Frontend UI changes (the verified data is already shown in the panel header)
