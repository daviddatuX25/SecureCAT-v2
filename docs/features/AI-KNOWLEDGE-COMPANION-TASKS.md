# AI Knowledge Loader and Companion — Task Breakdown

Tasks are ordered by **complexity** (foundation first). Each task has detailed acceptance criteria and **rich cases** before implementation. CSV content is **not** restricted to exam score success rates; **metadata defines the document** (category, year, description, etc.).

---

## Status and execution order

**Recommended execution order (dependencies respected):**

```
T1 (no deps)  →  T2 (no deps)  →  T3 (T1)  →  T4 (T1, T3)  →  T5 (T2, T4)
                                                                    ↓
T6 (T2 only)  ————————————————————————————————————————————  T7 (T4 only)
```

| Order | Task | Dependencies | Status |
|-------|------|--------------|--------|
| 1 | T1: Settings and gating | None | Done |
| 2 | T2: Knowledge document model and storage | None | Done |
| 3 | T3: Persona builder | T1 | Done |
| 4 | T4: Student chat API (scores + persona) | T1, T3 | Done |
| 5 | T5: Retrieval by metadata | T2, T4 | Done |
| 6 | T6: CSV → narrative | T2 | Done |
| 7 | T7: Conversation persistence | T4 | Done |

**Case coverage (implemented tasks):**

| Case | Scenario (short) | Covered by |
|------|------------------|------------|
| T1.1 | Super_admin enables and saves | SettingsControllerTest: update setting |
| T1.2 | Super_admin disables | AiCompanionChatTest: 403 when disabled |
| T1.3 | Enabled + consultation released | Portal Dashboard: shows chat card; chat API 200 |
| T1.4 | Enabled + consultation not released | AiCompanionChatTest: 403 "Results not released" |
| T1.5 | Non–super_admin access settings | SettingsControllerTest: policy / 403 |
| T1.6 | Setting never set (new install) | SystemSetting::aiCompanionEnabled() defaults false |
| T2.1 | Create doc with metadata | KnowledgeDocumentControllerTest: create with category/year |
| T2.2 | Create doc with no metadata | KnowledgeDocumentControllerTest: create with no metadata |
| T2.3 | Update doc | KnowledgeDocumentControllerTest: update |
| T2.4 | Delete doc | KnowledgeDocumentControllerTest: destroy; retrieval excludes |
| T2.5 | List many docs | KnowledgeDocumentControllerTest: pagination |
| T2.6 | Very long content | Stored in longText; T5 truncates at 8k for context |
| T3.1 | Admin sets persona and saves | SettingsControllerTest: save persona |
| T3.2 | Persona empty | SystemSetting::personaPrompt() returns default |
| T3.3 | Very long persona | Stored; T4/T5 combine with context (no truncate in v1) |
| T3.4 | Strip HTML from persona | UpdateSystemSettingsRequest: strip_tags |
| T4.1 | Enabled, released, valid message | AiCompanionChatTest: returns reply; mock OpenRouter |
| T4.2 | Companion disabled | AiCompanionChatTest: 403 |
| T4.3 | Consultation not released | AiCompanionChatTest: 403 + message |
| T4.4 | Not logged in | AiCompanionChatTest: 401 |
| T4.5 | No scores yet | AiCompanionService::buildApplicantSummary "Not yet available" |
| T4.6 | Empty/missing message | AiCompanionChatTest: 422 validation |
| T4.7 | Message too long | AiCompanionChatTest: 422 max length |
| T4.8 | OpenRouter 5xx/timeout | AiCompanionController: catch, log, 502/429 to client |
| T5.1 | Course pref "Civil Engineering"; docs category match | KnowledgeRetrievalService: filterByCategory (name/category match) |
| T5.2 | No knowledge docs | KnowledgeRetrievalServiceTest + prompt "No institutional data available" |
| T5.3 | Many docs > 8k chars | KnowledgeRetrievalServiceTest: truncates; deterministic order |
| T5.4 | Doc metadata empty | KnowledgeRetrievalServiceTest: included when no filter |
| T5.5 | Filter by year 2024 | KnowledgeRetrievalServiceTest: retrieveWithFilters(['year'=>'2024']) |
| T6.1 | Valid CSV with metadata | KnowledgeDocumentCsvImportTest |
| T7.1 | First message | AiCompanionChatTest: first message stores user and assistant |
| T7.2 | Follow-up message | AiCompanionChatTest: follow up includes history |
| T7.3 | History very long | AiCompanionService: last N (20) messages |
| T7.4 | Clear history | AiCompanionChatTest: clear history deletes messages |

---

## T1: Settings and gating (AI companion enabled)

**Complexity:** Low  
**Dependencies:** None  
**Goal:** Feature flag so the companion can be turned on/off; portal and API respect it.

### Acceptance criteria

- [x] A system setting (e.g. `ai_exam_companion_enabled` boolean) is stored and editable by super_admin.
- [x] Portal dashboard receives `ai_companion_enabled` (or equivalent) in shared props or page props.
- [x] Portal shows “Chat with advisor” (or similar) only when `ai_companion_enabled === true` and consultation is released.
- [x] Future chat API will return 403 when the setting is off (no API exposure when disabled).

### Rich cases

| ID | Scenario | Expected behavior |
|----|----------|-------------------|
| T1.1 | Super_admin enables “AI exam companion” and saves | Setting persisted; next portal load for any applicant sees the flag true (when consultation released). |
| T1.2 | Super_admin disables the setting | Portal shows no chat entry; later chat API returns 403. |
| T1.3 | Setting enabled, applicant has consultation released | Chat entry point visible on portal dashboard. |
| T1.4 | Setting enabled, applicant has consultation not released | Chat entry point not shown. |
| T1.5 | Non–super_admin user tries to access settings page | 403 or redirect (existing behavior). |
| T1.6 | Setting never set (e.g. new install) | Default false; no chat shown; no errors. |

### Implementation notes

- **Done:** `system_settings` table (migration), `SystemSetting` model with `get`/`set`/`aiCompanionEnabled()`.
- **Done:** `PortalAuthController@dashboard` passes `ai_companion_enabled` and loads real `consultation` (consultationSummary).
- **Done:** Portal Dashboard shows "Chat with advisor" card when `ai_companion_enabled && consultation.status === 'released'`.
- **Done:** Admin Settings: `SettingsController`, `UpdateSystemSettingsRequest`, `SystemSettingPolicy`, `Admin/Settings/Index.svelte` with Switch; PUT updates setting.
- **Required:** Register routes (see [AI-KNOWLEDGE-COMPANION-ROUTES.md](AI-KNOWLEDGE-COMPANION-ROUTES.md)): GET/PUT `/admin/settings` with `auth` and `role:super_admin`.

---

## T2: Knowledge document model and storage

**Complexity:** Low  
**Dependencies:** None  
**Goal:** Store arbitrary text content (narrative) with metadata; retrieval can filter by metadata later.

### Acceptance criteria

- [x] Migration: table `knowledge_documents` with at least `id`, `title`, `content` (text), `metadata` (JSON), `source` (e.g. `manual` | `csv_import`), `created_at`, `updated_at`.
- [x] Model `KnowledgeDocument` with fillable, casts for `metadata` (array), optional accessors.
- [x] Admin can create/edit/delete a “knowledge doc” via UI: title, content (textarea), metadata (e.g. category, year, tags).
- [x] List view: show title, source, metadata summary, created_at.

### Rich cases

| ID | Scenario | Expected behavior |
|----|----------|-------------------|
| T2.1 | Create doc with title, content, category “Engineering”, year “2024” | Doc saved; metadata stored as JSON; list shows it. |
| T2.2 | Create doc with no metadata | Doc saved; metadata empty or `{}`; doc still retrievable in “all” or default filter. |
| T2.3 | Update doc content and metadata | Changes persisted; list and retrieval reflect update. |
| T2.4 | Delete doc | Doc removed; retrieval no longer returns it. |
| T2.5 | List with many docs | Pagination or limit; no N+1; metadata displayed in a compact way. |
| T2.6 | Content very long (e.g. 50k chars) | Stored completely; retrieval later may truncate for context window (separate task). |

### Implementation notes

- **Done:** Migration, model (scope active, metadata_summary), policy, controller, form requests, Admin UI (Index/Create/Edit), routes doc. Register routes and add nav link for super_admin.
- Metadata: flexible JSON; admin form uses category, year, description, tags (comma-separated).
- Authorization: only super_admin (or role that can manage “AI knowledge”) for create/update/delete.

---

## T3: Persona builder

**Complexity:** Low  
**Dependencies:** T1 (settings exist so we have a place or same settings table for persona).  
**Goal:** Admin sets the AI’s system instructions (persona/guardrails); used when building the student chat system prompt.

### Acceptance criteria

- [x] Storage for “persona” text (e.g. `ai_companion_persona` in system_settings or a row in a config table).
- [x] Admin UI: text area for persona/instructions, save button.
- [x] When persona is empty, backend uses a safe default (e.g. “You are a helpful academic counselor. Base your advice only on the data provided. Do not invent statistics.”).
- [x] Persona is included in the system prompt for the student chat (once T4 exists).

### Rich cases

| ID | Scenario | Expected behavior |
|----|----------|-------------------|
| T3.1 | Admin sets persona text and saves | Persisted; next student chat request uses this in system prompt. |
| T3.2 | Persona empty (never set or cleared) | Backend uses default persona; no crash; no leaking of internal data. |
| T3.3 | Persona with very long text (e.g. 5k chars) | Stored; if it exceeds model context when combined with context, truncate or warn (document in T4). |
| T3.4 | Admin strips all HTML/script from persona | Sanitize or store as plain text so no XSS when later displayed in admin UI. |

### Implementation notes

- Same settings store as T1 or a dedicated `ai_companion_config` table with key-value (e.g. `persona_prompt`).
- Default persona in code or in DB seed so “empty” is well-defined.

---

## T4: Student chat API (scores + persona, no retrieval)

**Complexity:** Medium  
**Dependencies:** T1, T3.  
**Goal:** Authenticated applicant can send a message; backend builds system prompt (persona + applicant summary), calls OpenRouter, returns reply. No knowledge-doc retrieval yet.

### Acceptance criteria

- [x] POST endpoint (e.g. `/portal/ai-companion/chat` or `/portal/chat`) for applicant guard; body: `{ "message": "..." }`.
- [x] Gate: if `ai_exam_companion_enabled` is false, return 403. If applicant’s consultation is not released, return 403. If applicant not authenticated (portal guard), 401.
- [x] Load applicant’s pillar scores (ApplicantScore) and optional course preferences from application; build summary text (e.g. “Scores: SA 85, NA 78, …; Course preferences: Civil Engineering, …”).
- [x] System prompt: persona + “Use only the following applicant data. Do not invent statistics.” + applicant summary. No knowledge docs yet.
- [x] Send user message to OpenRouter; return assistant reply (JSON or Inertia with reply).

### Rich cases

| ID | Scenario | Expected behavior |
|----|----------|-------------------|
| T4.1 | Companion enabled, consultation released, valid message | 200; reply from OpenRouter; reply references or acknowledges the applicant’s scores/preferences. |
| T4.2 | Companion disabled | 403. |
| T4.3 | Companion enabled, consultation not released | 403 or “results not released”. |
| T4.4 | Applicant not logged in | 401. |
| T4.5 | Applicant has no scores yet | Summary says “Scores not yet available”; AI does not fabricate numbers (persona instructs this). |
| T4.6 | Empty message or missing `message` | 422 validation error. |
| T4.7 | Message too long (e.g. > 2000 chars) | 422 or truncate; document limit. |
| T4.8 | OpenRouter timeout or 5xx | Return 502/503 and user-friendly message; no stack trace to client. |

### Implementation notes

- Use same OpenRouter config and service pattern as ExamSchedulingAssistantService; new method or new service for companion chat.
- Form request: validate `message` (required, string, max length).

---

## T5: Retrieval by metadata

**Complexity:** Medium  
**Dependencies:** T2, T4.  
**Goal:** When building the student chat context, retrieve knowledge docs filtered by metadata (e.g. category, year) and append their content to the system prompt.

### Acceptance criteria

- [x] Retrieval function: given optional filters (e.g. category, year), return active knowledge docs matching filters; limit to N (e.g. 10) and total content length (e.g. 8k chars) to avoid overflow.
- [x] Use applicant’s course preferences (from application) to optionally filter docs (e.g. include docs whose category matches preferred courses). If no filter, include all (or default set).
- [x] System prompt now: persona + “Institutional data (use only this; do not invent):” + retrieved content + applicant summary. If no docs, “No institutional data available.”
- [x] Chat API (T4) calls retrieval and injects result into prompt.

### Rich cases

| ID | Scenario | Expected behavior |
|----|----------|-------------------|
| T5.1 | Applicant has course preference “Civil Engineering”; 3 docs tagged category “Engineering” or “Civil Engineering” | Those 3 docs (or matching subset) included in context; AI reply can cite them. |
| T5.2 | No knowledge docs exist | Context has “No institutional data available”; AI replies without inventing stats. |
| T5.3 | Many docs match; total content > 8k chars | Truncate or take first N docs until under limit; no silent drop of critical data if order is deterministic. |
| T5.4 | Doc metadata empty | Doc included when “no filter” or “all”; retrieval does not exclude. |
| T5.5 | Filter by year “2024” | Only docs with metadata.year = 2024 included. |

### Implementation notes

- Simple metadata filter: JSON query or whereJsonContains / where on metadata keys. No vector search in v1.
- Optional: add `is_active` to knowledge_documents so admins can soft-disable.

---

## T6: CSV → narrative (generic; metadata defines doc)

**Complexity:** Medium–High  
**Dependencies:** T2.  
**Goal:** Admin uploads a CSV; system converts rows to narrative sentences (generic: not limited to “exam success rate”); admin sets **metadata** that defines what the document is (category, year, description); store as one (or more) knowledge doc(s).

### Acceptance criteria

- [ ] Admin UI: CSV file upload, optional column mapping (e.g. “Column A = category, B = value, C = rate”), and **metadata form** (category, year, description/tags) that **defines the resulting document**.
- [ ] Backend: parse CSV; for each row (or batch), generate narrative sentence(s) (rule-based template or single LLM call per row/batch: “Convert this row to one factual sentence.”). Content is generic (e.g. enrollment, GPA, placement, success rates—whatever the CSV represents).
- [ ] Save result as knowledge doc(s) with the **admin-provided metadata** (not inferred from CSV content). One doc per upload with combined narrative, or one doc per section if chunked.
- [ ] Validation: CSV required, max size, encoding; invalid CSV returns 422 with clear message.

### Rich cases

| ID | Scenario | Expected behavior |
|----|----------|-------------------|
| T6.1 | Valid CSV (e.g. course, value, rate); metadata “Engineering”, “2024”, “Success rates by course” | Rows converted to sentences; one doc created with that metadata; retrieval can find by category/year. |
| T6.2 | CSV with different semantics (e.g. enrollment counts, not success rates) | Converter produces factual sentences from row data; **metadata** (e.g. “Enrollment”, “2024”) defines the doc; retrieval uses metadata. |
| T6.3 | Empty CSV or header-only | No doc created; clear message “No data rows” or doc with “No data” and metadata. |
| T6.4 | Invalid CSV (malformed, wrong encoding) | 422; message “Invalid CSV” or “Unsupported encoding”. |
| T6.5 | Very large CSV (e.g. 5k rows) | Batch processing or async job; admin sees “Processing…” and success/error when done; avoid timeout. |
| T6.6 | Optional column mapping: admin maps “Col1” → “course”, “Col2” → “rate” | Converter uses mapping to build narrative; if mapping missing, use first row as header and generic “Column N” labels. |
| T6.7 | Duplicate upload (same file name + metadata) | Idempotent overwrite or “Document with this title already exists” and option to replace (product choice). |

### Implementation notes

- **Metadata defines the document:** Admin always sets category, year, description (or tags) for the CSV import. The CSV content is converted to text; the metadata is what retrieval and the AI use to “understand” what the doc is.
- CSV content is not restricted to “exam score success rate”; it can be any tabular institutional data. Converter should be generic (e.g. “Column A: X, Column B: Y” → “X is Y” or LLM “Convert to one sentence.”).

---

## T7: Conversation persistence

**Complexity:** Medium  
**Dependencies:** T4.  
**Goal:** Store student chat messages (user + assistant); include last N turns in each OpenRouter request for multi-turn context.

### Acceptance criteria

- [x] Table `ai_companion_messages`: `id`, `applicant_id`, `role` (user/assistant), `content` (text), `created_at`.
- [x] On each student message: (1) append user message to DB; (2) load last N messages (e.g. 20); (3) build OpenRouter request with system + history + new user message; (4) append assistant reply to DB; (5) return reply.
- [x] Optional: Clear history for applicant (POST `/portal/ai-companion/clear-history`) so next request has no prior context.

### Rich cases

| ID | Scenario | Expected behavior |
|----|----------|-------------------|
| T7.1 | First message | No history; system + user message → OpenRouter; reply stored and returned. |
| T7.2 | Follow-up message | Last N messages loaded; sent with new message; reply is coherent with context. |
| T7.3 | History very long (e.g. 50 messages) | Only last N (e.g. 10 or 20) included in request; no token overflow. |
| T7.4 | Clear history | All messages for applicant deleted or marked; next request is “first message” again. |

### Implementation notes

- Order by `created_at`; take last N; format as OpenRouter message array. Consider token limit and truncate oldest if needed.

---

## Implementation order (by complexity)

See **[Status and execution order](#status-and-execution-order)** for dependency graph and status.

1. **T1** — Settings and gating *(Done)*  
2. **T2** — Knowledge document model and storage *(Done)*  
3. **T3** — Persona builder *(Done)*  
4. **T4** — Student chat API (scores + persona) *(Done)*  
5. **T5** — Retrieval by metadata *(Done)*  
6. **T6** — CSV → narrative (generic; metadata defines doc) *(Done)*  
7. **T7** — Conversation persistence *(Done; T4 only)*  

T6 and T7 can be done in either order. T6 completes the Knowledge Loader (CSV import); T7 adds multi-turn chat (persist messages, last N in request). Implement using the same pattern: rich cases → tests → implementation.
