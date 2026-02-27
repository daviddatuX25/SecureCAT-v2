# Exam Scheduling Assistant

The Exam Scheduling Assistant lets admins use an AI (OpenRouter, free models only) to refine exam schedule options in a chat, then generate a structured schedule and apply it in one action—creating multiple exam sessions and assigning applicants.

## Flow

1. **Open Schedule with AI** — Admin goes to **Registrar → Schedule with AI**. The page loads with context: count of assignable applicants (accepted, not yet in any session) and list of active rooms (id, name, building, capacity).

2. **Conversation** — Admin sends messages (e.g. “I want morning slots only, 9–12, next week”). The assistant asks clarifying questions and suggests options. Messages are stored in the latest conversation for that user (DB: `exam_scheduling_conversations`).

3. **Generate schedule** — When the admin is satisfied, they click **Generate schedule**. The backend calls OpenRouter with a JSON schema; the model returns a structured payload.

4. **Preview and apply** — The UI shows a preview table (room, date, time, # applicants). The admin clicks **Apply schedule**. The backend validates the payload (room conflicts, capacity, each applicant at most once, accepted and unassigned), then in a single transaction creates draft exam sessions and assigns applicants. Proctors are not set by the assistant; the admin can assign them later per session.

## Configuration

- **API key**: `OPENROUTER_API_KEY` in `.env` (used via `config('services.openrouter.key')` in app code).
- **Model**: `OPENROUTER_MODEL` in `.env`, default `openrouter/free`. Only free models are used (e.g. `openrouter/free` or a specific `:free` model).

## Structured schedule schema

The model is asked to return JSON in this shape. Each session is either **new** (room, date, time, applicant_ids) or **existing draft** (exam_session_id, applicant_ids).

**New session:**

```json
{
  "sessions": [
    {
      "room_id": 1,
      "date": "2026-03-01",
      "start_time": "09:00",
      "end_time": "12:00",
      "applicant_ids": [1, 2, 3]
    }
  ]
}
```

**Existing draft session (cross-use):**

```json
{
  "sessions": [
    {
      "exam_session_id": 5,
      "applicant_ids": [4, 5]
    }
  ]
}
```

- **New**: `room_id` must be an active room. `date`: YYYY-MM-DD, today or later. `start_time` / `end_time`: HH:MM; `end_time` optional. Session size must not exceed room capacity; no room double-booking (same room, date, overlapping time).
- **Existing draft**: `exam_session_id` must be a draft exam session. (Current applicant count + len(applicant_ids)) must not exceed that session’s room capacity.
- **Common**: `applicant_ids` — each applicant must be accepted and not yet in any session. Each applicant may appear in at most one session in the payload.

### Using existing draft sessions

The assistant can suggest assigning applicants to **existing draft exam sessions** as well as creating new ones. On page load and for each chat, the backend loads draft sessions (with room and current applicant count) and passes them to the UI and into the AI system prompt. The model is instructed to use draft sessions when appropriate (e.g. same room/date/time) and to respect capacity. On **Apply**, items with `exam_session_id` attach applicants to the existing draft; items without create new draft sessions. The UI preview shows a **Type** column (“Existing draft” vs “New”) and, for existing, room/date/time from the draft.

## Authorization

- Only users with role `admin` or `super_admin` can open the Schedule Assistant, send chat messages, and apply a schedule (same as creating exam sessions).

## Technical notes

- **Package**: [moe-mizrak/laravel-openrouter](https://github.com/moe-mizrak/laravel-openrouter) for chat and structured output.
- **Service**: `App\Services\ExamSchedulingAssistantService` builds the system prompt, calls OpenRouter, and parses the schedule JSON.
- **Conversation persistence**: `exam_scheduling_conversations` stores `user_id` and `messages` (JSON). The latest conversation per user is loaded on page load; each chat response updates that conversation or creates a new one.
- **Proctors**: Not included in the generated schedule; assign from the exam session detail page after applying.
