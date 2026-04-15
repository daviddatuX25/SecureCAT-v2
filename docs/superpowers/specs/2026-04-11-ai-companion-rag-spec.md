# AI Companion v2 — Mixedbread RAG Architecture

**Date:** 2026-04-11
**Status:** Approved
**Decisions:** Dual storage (A), Synchronous ingestion (A), Semantic + metadata filtering (B)

---

## Architecture

```
Admin UI ──▶ Laravel (MySQL metadata) ──▶ Mixedbread Stores (text + vectors)
                    │                              │
                    │  Applicant chats              │ Top-3 semantic matches
                    │         │                     │
                    ▼         ▼                     ▼
             AiCompanionService ◀──── MixedbreadService.search()
                    │
                    ▼
              OpenRouter (Gemini/Llama) ──▶ Reply
```

**Storage:** MySQL stores metadata (file name, owner, sync status). Mixedbread Stores holds text content and vectors.
**Ingestion:** Admin saves doc → Laravel persists to MySQL AND uploads to Mixedbread synchronously.
**Retrieval:** Applicant chats → Laravel queries Mixedbread for top-3 semantic matches (with metadata filters).
**Generation:** Laravel sends matches + applicant summary + persona to OpenRouter → returns answer.

---

## Components

### 1. `MixedbreadService` (new)

HTTP client wrapping `https://api.mixedbread.com/v1/`.

```php
class MixedbreadService
{
    // Store management
    public function createStore(string $name, ?string $description = null): array;
    public function deleteStore(string $storeId): void;

    // Document lifecycle
    public function uploadDocument(string $storeId, string $content, string $title, array $metadata = []): array;
    public function uploadFile(string $storeId, string $filePath, array $metadata = []): array;
    public function deleteDocument(string $storeId, string $fileId): void;

    // Retrieval
    public function search(string $storeId, string $query, array $filters = [], int $topK = 3): array;

    // Status
    public function getFileStatus(string $storeId, string $fileId): string;
}
```

**Config** (`.env`):
```
MIXEDBREAD_API_KEY=
MIXEDBREAD_STORE_ID=
```

**Config** (`config/services.php`):
```php
'mixedbread' => [
    'api_key' => env('MIXEDBREAD_API_KEY'),
    'store_id' => env('MIXEDBREAD_STORE_ID'),
    'base_url' => env('MIXEDBREAD_BASE_URL', 'https://api.mixedbread.com/v1'),
],
```

Uses Laravel HTTP client (`Http::withToken()`). No third-party SDK needed.

### 2. `KnowledgeDocument` model changes

New columns:
```
mxb_file_id       VARCHAR(255) NULLABLE  — Mixedbread file reference
mxb_sync_status   ENUM('pending','indexed','failed') DEFAULT 'pending'
mxb_synced_at     TIMESTAMP NULLABLE
```

### 3. `KnowledgeRetrievalService` changes

Replace the current "load all from MySQL + filter in PHP" with:

```php
public function retrieveForApplicant(Applicant $applicant, string $query, ...): string
{
    $filters = $this->buildMetadataFilters($applicant); // category from course prefs

    try {
        $results = $this->mixedbread->search(
            config('services.mixedbread.store_id'),
            $query,
            $filters,
            topK: 3
        );
        return $this->formatResults($results);
    } catch (\Exception $e) {
        Log::warning('Mixedbread search failed, falling back to MySQL', ['error' => $e->getMessage()]);
        return $this->fallbackMysqlRetrieval($applicant);
    }
}
```

**Fallback:** If Mixedbread is down, use the existing MySQL-based retrieval (current code becomes the fallback path).

### 4. `AiCompanionService` changes

- `buildSystemPrompt()` now receives the user's message to pass as the search query
- Prompt structure:

```
{persona}

Institutional data (use only this; do not invent):
[Top-3 Mixedbread results with source labels]

--- Applicant data ---
{applicant summary}
--- End applicant data ---

Use only the institutional and applicant data above when giving advice.
Do not invent statistics. If the data does not cover a question, say so.
```

### 5. Ingestion hooks (Controller changes)

In `KnowledgeDocumentController`:
- `store()` → after MySQL save, call `MixedbreadService::uploadDocument()`, update `mxb_file_id` and `mxb_sync_status`
- `update()` → delete old Mixedbread doc, re-upload, update references
- `destroy()` → delete from Mixedbread, then MySQL
- CSV import → same pattern per generated doc

### 6. Admin UI changes

`Admin/KnowledgeDocuments/Index.svelte`:
- Show sync status badge per document: `Indexed` (green), `Pending` (yellow), `Failed` (red)
- Failed docs show "Retry Sync" button

---

## Error Handling

| Scenario | Behavior |
|----------|----------|
| Mixedbread down during **ingestion** | Save to MySQL, set `sync_status = failed`, admin sees badge + retry button |
| Mixedbread down during **retrieval** | Fall back to MySQL-based retrieval (current logic), log warning |
| Mixedbread rate limit (429) | Retry once after 1s; if still 429, fail gracefully |
| OpenRouter down | Existing error handling (502 to client) |
| Doc uploaded but not yet indexed | `getFileStatus()` returns processing state; search may not find it yet |

---

## Migration Plan

No breaking changes. The existing MySQL retrieval becomes the fallback.

1. Add columns to `knowledge_documents`
2. Add Mixedbread config to `.env` / `config/services.php`
3. Create `MixedbreadService`
4. Modify `KnowledgeRetrievalService` (old logic → `fallbackMysqlRetrieval()`)
5. Hook ingestion in controllers
6. Update admin UI with sync badges
7. One-time: sync existing docs to Mixedbread store (artisan command)
