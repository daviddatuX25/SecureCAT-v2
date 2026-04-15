# AI Companion v2 — Mixedbread RAG Implementation Plan

> **For Antigravity:** REQUIRED WORKFLOW: Use `.agent/workflows/execute-plan.md` to execute this plan in single-flow mode.

**Goal:** Replace the brute-force MySQL retrieval in the AI Companion with true semantic RAG using Mixedbread Stores — keeping MySQL for metadata display and using Mixedbread for text + vector storage, retrieval, and generation via OpenRouter.

**Architecture:** Admin creates/updates knowledge docs → Laravel saves to MySQL (for display) AND synchronously uploads content to Mixedbread Store. When an applicant chats, Laravel queries Mixedbread with the user's message + metadata filters (course category) to get top-3 semantic matches, which are injected into the OpenRouter prompt.

**Tech Stack:** Laravel 12, `Illuminate\Http\Client` (no 3rd-party SDK), Mixedbread Stores REST API (`https://api.mixedbread.com/v1`), OpenRouter (existing), PHPUnit feature tests.

**Design spec:** `docs/superpowers/specs/2026-04-11-ai-companion-rag-spec.md`

---

## Task 1: Config — add Mixedbread credentials

**Files:**
- Modify: `config/services.php`
- Modify: `.env.example`

### Step 1: Write failing test

```php
// tests/Unit/Config/MixedbreadConfigTest.php
<?php

namespace Tests\Unit\Config;

use PHPUnit\Framework\TestCase;

class MixedbreadConfigTest extends TestCase
{
    public function test_mixedbread_config_keys_exist(): void
    {
        $config = include __DIR__ . '/../../../config/services.php';

        $this->assertArrayHasKey('mixedbread', $config);
        $this->assertArrayHasKey('api_key', $config['mixedbread']);
        $this->assertArrayHasKey('store_id', $config['mixedbread']);
        $this->assertArrayHasKey('base_url', $config['mixedbread']);
    }
}
```

### Step 2: Run to verify it fails

```bash
./vendor/bin/sail php artisan test --compact tests/Unit/Config/MixedbreadConfigTest.php
```

Expected: FAIL — `Failed asserting that an array has the key 'mixedbread'`

### Step 3: Add config to `config/services.php`

Add after the `openrouter` block (end of array, before closing `]`):

```php
    'mixedbread' => [
        'api_key'  => env('MIXEDBREAD_API_KEY'),
        'store_id' => env('MIXEDBREAD_STORE_ID'),
        'base_url' => env('MIXEDBREAD_BASE_URL', 'https://api.mixedbread.com/v1'),
    ],
```

### Step 4: Add to `.env.example`

```
MIXEDBREAD_API_KEY=
MIXEDBREAD_STORE_ID=
MIXEDBREAD_BASE_URL=https://api.mixedbread.com/v1
```

### Step 5: Run test to verify it passes

```bash
./vendor/bin/sail php artisan test --compact tests/Unit/Config/MixedbreadConfigTest.php
```

Expected: PASS

### Step 6: Commit

```bash
git add config/services.php .env.example tests/Unit/Config/MixedbreadConfigTest.php
git commit -m "feat(rag): add Mixedbread config keys to services.php"
```

---

## Task 2: Migration — add Mixedbread sync columns to `knowledge_documents`

**Files:**
- Create: `database/migrations/YYYY_MM_DD_HHMMSS_add_mxb_sync_columns_to_knowledge_documents_table.php`
- Modify: `app/Mod els/KnowledgeDocument.php`

### Step 1: Generate migration

```bash
./vendor/bin/sail php artisan make:migration add_mxb_sync_columns_to_knowledge_documents_table --no-interaction
```

### Step 2: Write the migration

Replace the generated `up()`/`down()` body:

```php
public function up(): void
{
    Schema::table('knowledge_documents', function (Blueprint $table) {
        $table->string('mxb_file_id')->nullable()->after('is_active');
        $table->enum('mxb_sync_status', ['pending', 'indexed', 'failed'])->default('pending')->after('mxb_file_id');
        $table->timestamp('mxb_synced_at')->nullable()->after('mxb_sync_status');

        $table->index('mxb_sync_status');
    });
}

public function down(): void
{
    Schema::table('knowledge_documents', function (Blueprint $table) {
        $table->dropIndex(['mxb_sync_status']);
        $table->dropColumn(['mxb_file_id', 'mxb_sync_status', 'mxb_synced_at']);
    });
}
```

### Step 3: Run migration

```bash
./vendor/bin/sail php artisan migrate --no-interaction
```

Expected: `Migrating: YYYY_MM_DD_HHMMSS_add_mxb_sync_...` → `Migrated`

### Step 4: Update `KnowledgeDocument` model

Add to `$fillable`:
```php
'mxb_file_id',
'mxb_sync_status',
'mxb_synced_at',
```

Add constants:
```php
public const SYNC_PENDING = 'pending';
public const SYNC_INDEXED = 'indexed';
public const SYNC_FAILED  = 'failed';
```

Add to `casts()`:
```php
'mxb_synced_at' => 'datetime',
```

### Step 5: Write failing test

```php
// tests/Feature/Models/KnowledgeDocumentSyncColumnsTest.php
<?php

namespace Tests\Feature\Models;

use App\Models\KnowledgeDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KnowledgeDocumentSyncColumnsTest extends TestCase
{
    use RefreshDatabase;

    public function test_knowledge_document_has_sync_columns(): void
    {
        $doc = KnowledgeDocument::create([
            'title'          => 'Test',
            'content'        => 'Content',
            'source'         => KnowledgeDocument::SOURCE_MANUAL,
            'mxb_file_id'    => 'file_abc123',
            'mxb_sync_status'=> KnowledgeDocument::SYNC_INDEXED,
            'mxb_synced_at'  => now(),
        ]);

        $fresh = $doc->fresh();

        $this->assertSame('file_abc123', $fresh->mxb_file_id);
        $this->assertSame(KnowledgeDocument::SYNC_INDEXED, $fresh->mxb_sync_status);
        $this->assertNotNull($fresh->mxb_synced_at);
    }

    public function test_default_sync_status_is_pending(): void
    {
        $doc = KnowledgeDocument::create([
            'title'   => 'Test',
            'content' => 'Content',
            'source'  => KnowledgeDocument::SOURCE_MANUAL,
        ]);

        $this->assertSame(KnowledgeDocument::SYNC_PENDING, $doc->fresh()->mxb_sync_status);
    }
}
```

### Step 6: Run test

```bash
./vendor/bin/sail php artisan test --compact tests/Feature/Models/KnowledgeDocumentSyncColumnsTest.php
```

Expected: PASS

### Step 7: Commit

```bash
git add database/migrations/ app/Models/KnowledgeDocument.php tests/Feature/Models/KnowledgeDocumentSyncColumnsTest.php
git commit -m "feat(rag): add mxb sync columns to knowledge_documents"
```

---

## Task 3: `MixedbreadService` — HTTP client wrapper

**Files:**
- Create: `app/Services/MixedbreadService.php`
- Create: `tests/Unit/Services/MixedbreadServiceTest.php`

### Step 1: Write the failing tests

```php
// tests/Unit/Services/MixedbreadServiceTest.php
<?php

namespace Tests\Unit\Services;

use App\Services\MixedbreadService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MixedbreadServiceTest extends TestCase
{
    private MixedbreadService $service;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.mixedbread.api_key' => 'test-key', 'services.mixedbread.base_url' => 'https://api.mixedbread.com/v1']);
        $this->service = app(MixedbreadService::class);
    }

    // --- uploadDocument ---

    public function test_upload_document_posts_to_store_files_endpoint(): void
    {
        Http::fake([
            'https://api.mixedbread.com/v1/stores/store_123/files' => Http::response([
                'id' => 'file_abc', 'status' => 'processing',
            ], 200),
        ]);

        $result = $this->service->uploadDocument('store_123', 'Some content text', 'Doc Title', ['category' => 'Engineering']);

        $this->assertSame('file_abc', $result['id']);
        Http::assertSent(function (Request $request) {
            return str_contains($request->url(), '/stores/store_123/files')
                && $request->hasHeader('Authorization', 'Bearer test-key');
        });
    }

    public function test_upload_document_throws_on_non_2xx(): void
    {
        Http::fake([
            'https://api.mixedbread.com/v1/stores/*/files' => Http::response(['error' => 'Unauthorized'], 401),
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Mixedbread upload failed');

        $this->service->uploadDocument('store_123', 'Content', 'Title');
    }

    // --- deleteDocument ---

    public function test_delete_document_sends_delete_request(): void
    {
        Http::fake([
            'https://api.mixedbread.com/v1/stores/store_123/files/file_abc' => Http::response([], 200),
        ]);

        $this->service->deleteDocument('store_123', 'file_abc');

        Http::assertSent(function (Request $request) {
            return $request->method() === 'DELETE'
                && str_contains($request->url(), '/stores/store_123/files/file_abc');
        });
    }

    public function test_delete_document_is_silent_on_404(): void
    {
        Http::fake([
            'https://api.mixedbread.com/v1/stores/*/files/*' => Http::response([], 404),
        ]);

        // Should not throw — file already gone is fine
        $this->service->deleteDocument('store_123', 'file_not_found');
        $this->expectNotToPerformAssertions();
    }

    // --- search ---

    public function test_search_returns_results(): void
    {
        Http::fake([
            'https://api.mixedbread.com/v1/stores/search' => Http::response([
                'results' => [
                    ['id' => 'chunk_1', 'content' => 'Civil Engineering pass rate is 87%.', 'metadata' => ['category' => 'Engineering'], 'score' => 0.95],
                    ['id' => 'chunk_2', 'content' => 'Nursing applicants average 72 points.', 'metadata' => ['category' => 'Nursing'], 'score' => 0.87],
                ],
            ], 200),
        ]);

        $results = $this->service->search('store_123', 'What are my chances in Engineering?', ['category' => 'Engineering'], 3);

        $this->assertCount(2, $results);
        $this->assertSame('Civil Engineering pass rate is 87%.', $results[0]['content']);
    }

    public function test_search_throws_on_non_2xx(): void
    {
        Http::fake([
            'https://api.mixedbread.com/v1/stores/search' => Http::response(['error' => 'Server Error'], 500),
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Mixedbread search failed');

        $this->service->search('store_123', 'some query');
    }

    // --- getFileStatus ---

    public function test_get_file_status_returns_status_string(): void
    {
        Http::fake([
            'https://api.mixedbread.com/v1/stores/store_123/files/file_abc' => Http::response([
                'id' => 'file_abc', 'status' => 'indexed',
            ], 200),
        ]);

        $status = $this->service->getFileStatus('store_123', 'file_abc');

        $this->assertSame('indexed', $status);
    }
}
```

### Step 2: Run to verify all tests fail

```bash
./vendor/bin/sail php artisan test --compact tests/Unit/Services/MixedbreadServiceTest.php
```

Expected: FAIL — `Class App\Services\MixedbreadService not found`

### Step 3: Implement `MixedbreadService`

```php
// app/Services/MixedbreadService.php
<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MixedbreadService
{
    private string $apiKey;
    private string $baseUrl;

    public function __construct()
    {
        $this->apiKey  = config('services.mixedbread.api_key', '');
        $this->baseUrl = rtrim(config('services.mixedbread.base_url', 'https://api.mixedbread.com/v1'), '/');
    }

    /**
     * Upload text content to a Mixedbread Store as a file.
     *
     * @param  array<string, mixed>  $metadata
     * @return array<string, mixed>  API response body
     *
     * @throws \RuntimeException on non-2xx response
     */
    public function uploadDocument(string $storeId, string $content, string $title, array $metadata = []): array
    {
        // Mixedbread files endpoint expects multipart; we upload as .txt
        $tmpPath = tempnam(sys_get_temp_dir(), 'mxb_') . '.txt';
        file_put_contents($tmpPath, $content);

        try {
            $response = Http::withToken($this->apiKey)
                ->attach('file', fopen($tmpPath, 'r'), "{$title}.txt", ['Content-Type' => 'text/plain'])
                ->post("{$this->baseUrl}/stores/{$storeId}/files", array_filter([
                    'external_id' => $title,
                    'metadata'    => ! empty($metadata) ? json_encode($metadata) : null,
                ]));
        } finally {
            @unlink($tmpPath);
        }

        if (! $response->successful()) {
            throw new \RuntimeException("Mixedbread upload failed: HTTP {$response->status()} — {$response->body()}");
        }

        return $response->json();
    }

    /**
     * Delete a file from a Mixedbread Store. Silent on 404 (already gone).
     *
     * @throws \RuntimeException on non-2xx/404 response
     */
    public function deleteDocument(string $storeId, string $fileId): void
    {
        $response = Http::withToken($this->apiKey)
            ->delete("{$this->baseUrl}/stores/{$storeId}/files/{$fileId}");

        if ($response->status() === 404) {
            Log::debug("Mixedbread deleteDocument: file {$fileId} not found in store {$storeId}, skipping.");
            return;
        }

        if (! $response->successful()) {
            throw new \RuntimeException("Mixedbread delete failed: HTTP {$response->status()} — {$response->body()}");
        }
    }

    /**
     * Semantic search across a store with optional metadata filters and top-K.
     *
     * @param  array<string, mixed>  $filters  e.g. ['category' => 'Engineering']
     * @return array<int, array<string, mixed>>  Array of result chunks
     *
     * @throws \RuntimeException on non-2xx response
     */
    public function search(string $storeId, string $query, array $filters = [], int $topK = 3): array
    {
        $payload = [
            'query'             => $query,
            'store_identifiers' => [$storeId],
            'top_k'             => $topK,
        ];

        if (! empty($filters)) {
            $payload['filters'] = $filters;
        }

        $response = Http::withToken($this->apiKey)
            ->post("{$this->baseUrl}/stores/search", $payload);

        if (! $response->successful()) {
            throw new \RuntimeException("Mixedbread search failed: HTTP {$response->status()} — {$response->body()}");
        }

        return $response->json('results', []);
    }

    /**
     * Get processing status of an uploaded file.
     *
     * @throws \RuntimeException on non-2xx response
     */
    public function getFileStatus(string $storeId, string $fileId): string
    {
        $response = Http::withToken($this->apiKey)
            ->get("{$this->baseUrl}/stores/{$storeId}/files/{$fileId}");

        if (! $response->successful()) {
            throw new \RuntimeException("Mixedbread getFileStatus failed: HTTP {$response->status()} — {$response->body()}");
        }

        return (string) $response->json('status', 'unknown');
    }
}
```

### Step 4: Run tests

```bash
./vendor/bin/sail php artisan test --compact tests/Unit/Services/MixedbreadServiceTest.php
```

Expected: PASS (all 7 tests)

### Step 5: Commit

```bash
git add app/Services/MixedbreadService.php tests/Unit/Services/MixedbreadServiceTest.php
git commit -m "feat(rag): implement MixedbreadService HTTP client"
```

---

## Task 4: Hook ingestion in `KnowledgeDocumentController`

**Files:**
- Modify: `app/Http/Controllers/Admin/KnowledgeDocumentController.php`
- Modify: `tests/Feature/Admin/KnowledgeDocumentControllerTest.php` (add sync assertions)

> **Pattern:** After MySQL save → call `MixedbreadService`, update `mxb_file_id` + `mxb_sync_status`. On Mixedbread failure → mark `failed`, log, do not block user.

### Step 1: Write failing tests (add to the existing test file)

```php
// Add these tests to tests/Feature/Admin/KnowledgeDocumentControllerTest.php

use App\Services\MixedbreadService;

public function test_store_uploads_to_mixedbread_and_sets_indexed_status(): void
{
    $mockMxb = Mockery::mock(MixedbreadService::class);
    $mockMxb->shouldReceive('uploadDocument')
        ->once()
        ->andReturn(['id' => 'file_abc123', 'status' => 'processing']);
    $this->app->instance(MixedbreadService::class, $mockMxb);

    config(['services.mixedbread.store_id' => 'store_xyz']);

    $this->actingAs($this->superAdmin)
        ->post(route('admin.knowledge-documents.store'), [
            'title'   => 'Engineering Guide',
            'content' => 'Civil engineering pass rate is 87%.',
        ])
        ->assertRedirect(route('admin.knowledge-documents.index'));

    $doc = \App\Models\KnowledgeDocument::where('title', 'Engineering Guide')->first();
    $this->assertSame('file_abc123', $doc->mxb_file_id);
    $this->assertSame(\App\Models\KnowledgeDocument::SYNC_INDEXED, $doc->mxb_sync_status);
}

public function test_store_marks_failed_when_mixedbread_throws(): void
{
    $mockMxb = Mockery::mock(MixedbreadService::class);
    $mockMxb->shouldReceive('uploadDocument')->andThrow(new \RuntimeException('API down'));
    $this->app->instance(MixedbreadService::class, $mockMxb);

    $this->actingAs($this->superAdmin)
        ->post(route('admin.knowledge-documents.store'), [
            'title'   => 'Test Doc',
            'content' => 'Some content.',
        ])
        ->assertRedirect();

    $doc = \App\Models\KnowledgeDocument::where('title', 'Test Doc')->first();
    $this->assertSame(\App\Models\KnowledgeDocument::SYNC_FAILED, $doc->mxb_sync_status);
    $this->assertNull($doc->mxb_file_id);
}

public function test_update_re_uploads_to_mixedbread(): void
{
    $doc = \App\Models\KnowledgeDocument::factory()->create([
        'mxb_file_id'     => 'old_file_id',
        'mxb_sync_status' => \App\Models\KnowledgeDocument::SYNC_INDEXED,
    ]);

    $mockMxb = Mockery::mock(MixedbreadService::class);
    $mockMxb->shouldReceive('deleteDocument')->once()->with(anything(), 'old_file_id');
    $mockMxb->shouldReceive('uploadDocument')->once()->andReturn(['id' => 'new_file_id']);
    $this->app->instance(MixedbreadService::class, $mockMxb);

    $this->actingAs($this->superAdmin)
        ->put(route('admin.knowledge-documents.update', $doc), [
            'title'   => 'Updated Title',
            'content' => 'Updated content.',
        ])
        ->assertRedirect();

    $this->assertSame('new_file_id', $doc->fresh()->mxb_file_id);
}

public function test_destroy_deletes_from_mixedbread(): void
{
    $doc = \App\Models\KnowledgeDocument::factory()->create([
        'mxb_file_id' => 'file_to_delete',
    ]);

    $mockMxb = Mockery::mock(MixedbreadService::class);
    $mockMxb->shouldReceive('deleteDocument')->once()->with(anything(), 'file_to_delete');
    $this->app->instance(MixedbreadService::class, $mockMxb);

    $this->actingAs($this->superAdmin)
        ->delete(route('admin.knowledge-documents.destroy', $doc))
        ->assertRedirect();

    $this->assertModelMissing($doc);
}

public function test_destroy_proceeds_even_if_doc_has_no_mxb_file_id(): void
{
    $doc = \App\Models\KnowledgeDocument::factory()->create([
        'mxb_file_id' => null,
    ]);

    $mockMxb = Mockery::mock(MixedbreadService::class);
    $mockMxb->shouldNotReceive('deleteDocument');
    $this->app->instance(MixedbreadService::class, $mockMxb);

    $this->actingAs($this->superAdmin)
        ->delete(route('admin.knowledge-documents.destroy', $doc))
        ->assertRedirect();

    $this->assertModelMissing($doc);
}
```

### Step 2: Run to verify they fail

```bash
./vendor/bin/sail php artisan test --compact --filter="test_store_uploads_to_mixedbread|test_store_marks_failed|test_update_re_uploads|test_destroy_deletes_from_mixedbread|test_destroy_proceeds_even" tests/Feature/Admin/KnowledgeDocumentControllerTest.php
```

Expected: FAIL

### Step 3: Add `MixedbreadService` injection to controller and update `store`, `update`, `destroy`, `import`

**Inject in constructor:**
```php
use App\Services\MixedbreadService;

public function __construct(private readonly MixedbreadService $mixedbread) {}
```

**Helper method (private, add at bottom of class):**
```php
private function syncToMixedbread(KnowledgeDocument $doc): void
{
    $storeId = config('services.mixedbread.store_id', '');
    if (empty($storeId)) {
        return;
    }

    try {
        $result = $this->mixedbread->uploadDocument(
            $storeId,
            $doc->content,
            $doc->title,
            array_filter((array) $doc->metadata)
        );
        $doc->update([
            'mxb_file_id'     => $result['id'] ?? null,
            'mxb_sync_status' => KnowledgeDocument::SYNC_INDEXED,
            'mxb_synced_at'   => now(),
        ]);
    } catch (\Throwable $e) {
        Log::warning('Mixedbread sync failed', ['doc_id' => $doc->id, 'error' => $e->getMessage()]);
        $doc->update(['mxb_sync_status' => KnowledgeDocument::SYNC_FAILED]);
    }
}

private function deleteFromMixedbread(KnowledgeDocument $doc): void
{
    if (empty($doc->mxb_file_id)) {
        return;
    }
    $storeId = config('services.mixedbread.store_id', '');
    if (empty($storeId)) {
        return;
    }
    try {
        $this->mixedbread->deleteDocument($storeId, $doc->mxb_file_id);
    } catch (\Throwable $e) {
        Log::warning('Mixedbread delete failed', ['doc_id' => $doc->id, 'error' => $e->getMessage()]);
    }
}
```

**In `store()` — after `KnowledgeDocument::create(...)`, capture the result and call `syncToMixedbread`:**
```php
$doc = KnowledgeDocument::create([...]);
$this->syncToMixedbread($doc);
```

**In `update()` — before the model update, delete old vector; after update, re-upload:**
```php
$this->deleteFromMixedbread($knowledgeDocument);
$knowledgeDocument->update([...]);
$knowledgeDocument->refresh();
$this->syncToMixedbread($knowledgeDocument);
```

**In `destroy()` — before delete:**
```php
$this->deleteFromMixedbread($knowledgeDocument);
$knowledgeDocument->delete();
```

**In `import()` — after `KnowledgeDocument::create(...)`, capture and sync:**
```php
$doc = KnowledgeDocument::create([...]);
$this->syncToMixedbread($doc);
```

Also add `use Illuminate\Support\Facades\Log;` to the controller imports.

### Step 4: Run tests

```bash
./vendor/bin/sail php artisan test --compact tests/Feature/Admin/KnowledgeDocumentControllerTest.php
```

Expected: PASS (all existing + new tests)

### Step 5: Commit

```bash
git add app/Http/Controllers/Admin/KnowledgeDocumentController.php tests/Feature/Admin/KnowledgeDocumentControllerTest.php
git commit -m "feat(rag): hook Mixedbread sync in KnowledgeDocumentController (store/update/destroy/import)"
```

---

## Task 5: Refactor `KnowledgeRetrievalService` — semantic search with MySQL fallback

**Files:**
- Modify: `app/Services/KnowledgeRetrievalService.php`
- Modify: `tests/Feature/Services/KnowledgeRetrievalServiceTest.php`

> **Key change:** Primary path calls `MixedbreadService::search()`. The existing retrieval logic (all-docs-from-MySQL) becomes `fallbackMysqlRetrieval()`. The method signature adds `string $query` parameter so the user's message drives the semantic search.

### Step 1: Write new/updated failing tests

```php
// Add to tests/Feature/Services/KnowledgeRetrievalServiceTest.php

use App\Services\MixedbreadService;

public function test_retrieves_top_3_via_mixedbread_semantic_search(): void
{
    $mockMxb = Mockery::mock(MixedbreadService::class);
    $mockMxb->shouldReceive('search')
        ->once()
        ->with(anything(), 'What are my chances in engineering?', Mockery::any(), 3)
        ->andReturn([
            ['content' => 'Civil Engineering: 87% pass rate.', 'metadata' => ['category' => 'Engineering']],
            ['content' => 'Engineering requires 75 aptitude score.', 'metadata' => []],
            ['content' => 'BSIT pass rate is 91%.', 'metadata' => ['category' => 'IT']],
        ]);
    $this->app->instance(MixedbreadService::class, $mockMxb);

    config(['services.mixedbread.store_id' => 'store_xyz']);

    $applicant = Applicant::factory()->create();
    $service = app(KnowledgeRetrievalService::class);

    $result = $service->retrieveForApplicant($applicant, 'What are my chances in engineering?');

    $this->assertStringContainsString('Civil Engineering: 87% pass rate.', $result);
    $this->assertStringContainsString('Engineering requires 75 aptitude score.', $result);
}

public function test_falls_back_to_mysql_when_mixedbread_throws(): void
{
    $mockMxb = Mockery::mock(MixedbreadService::class);
    $mockMxb->shouldReceive('search')->andThrow(new \RuntimeException('API down'));
    $this->app->instance(MixedbreadService::class, $mockMxb);

    config(['services.mixedbread.store_id' => 'store_xyz']);

    KnowledgeDocument::factory()->create(['title' => 'Fallback Doc', 'content' => 'Fallback content from MySQL.', 'is_active' => true]);

    $applicant = Applicant::factory()->create();
    $result = app(KnowledgeRetrievalService::class)->retrieveForApplicant($applicant, 'test query');

    $this->assertStringContainsString('Fallback content from MySQL.', $result);
}

public function test_falls_back_to_mysql_when_store_id_not_configured(): void
{
    config(['services.mixedbread.store_id' => null]);

    KnowledgeDocument::factory()->create(['title' => 'Doc A', 'content' => 'Content from MySQL.', 'is_active' => true]);

    $applicant = Applicant::factory()->create();
    $result = app(KnowledgeRetrievalService::class)->retrieveForApplicant($applicant, 'query');

    $this->assertStringContainsString('Content from MySQL.', $result);
}

public function test_returns_no_institutional_data_when_store_empty_and_no_mysql_docs(): void
{
    $mockMxb = Mockery::mock(MixedbreadService::class);
    $mockMxb->shouldReceive('search')->andReturn([]);
    $this->app->instance(MixedbreadService::class, $mockMxb);

    config(['services.mixedbread.store_id' => 'store_xyz']);

    $applicant = Applicant::factory()->create();
    $result = app(KnowledgeRetrievalService::class)->retrieveForApplicant($applicant, 'query');

    $this->assertSame('No institutional data available.', $result);
}

public function test_passes_course_category_as_metadata_filter(): void
{
    $mockMxb = Mockery::mock(MixedbreadService::class);
    $mockMxb->shouldReceive('search')
        ->once()
        ->withArgs(function ($storeId, $query, $filters, $topK) {
            return isset($filters['category']) && $filters['category'] === 'Civil Engineering';
        })
        ->andReturn([['content' => 'Some matched content.', 'metadata' => []]]);
    $this->app->instance(MixedbreadService::class, $mockMxb);

    config(['services.mixedbread.store_id' => 'store_xyz']);

    // Create applicant with course preference
    $course = Course::factory()->create(['name' => 'Civil Engineering']);
    $applicant = Applicant::factory()->create();
    Application::factory()->create(['applicant_id' => $applicant->id, 'course_preference_1' => $course->id]);

    app(KnowledgeRetrievalService::class)->retrieveForApplicant($applicant->fresh(), 'query');
}
```

### Step 2: Run to verify failures

```bash
./vendor/bin/sail php artisan test --compact tests/Feature/Services/KnowledgeRetrievalServiceTest.php
```

### Step 3: Refactor `KnowledgeRetrievalService`

```php
<?php

namespace App\Services;

use App\Models\Applicant;
use App\Models\KnowledgeDocument;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class KnowledgeRetrievalService
{
    public const DEFAULT_MAX_DOCS = 10;
    public const DEFAULT_MAX_TOTAL_CHARS = 8000;

    public function __construct(private readonly MixedbreadService $mixedbread) {}

    /**
     * Primary: semantic search via Mixedbread. Falls back to MySQL if unavailable or unconfigured.
     */
    public function retrieveForApplicant(
        Applicant $applicant,
        string $query = '',
        int $maxDocs = self::DEFAULT_MAX_DOCS,
        int $maxTotalChars = self::DEFAULT_MAX_TOTAL_CHARS
    ): string {
        $storeId = config('services.mixedbread.store_id', '');

        if (!empty($storeId) && !empty($query)) {
            try {
                $filters  = $this->buildMetadataFilters($applicant);
                $results  = $this->mixedbread->search($storeId, $query, $filters, 3);
                $formatted = $this->formatMixedbreadResults($results);
                if (!empty($formatted)) {
                    return $formatted;
                }
                return 'No institutional data available.';
            } catch (\Throwable $e) {
                Log::warning('Mixedbread search failed; falling back to MySQL', [
                    'error'        => $e->getMessage(),
                    'applicant_id' => $applicant->id,
                ]);
            }
        }

        return $this->fallbackMysqlRetrieval($applicant, $maxDocs, $maxTotalChars);
    }

    /**
     * Explicit filters variant (kept for backward compat / admin tooling).
     *
     * @param  array{category?: string, year?: string}  $filters
     */
    public function retrieveWithFilters(
        array $filters = [],
        int $maxDocs = self::DEFAULT_MAX_DOCS,
        int $maxTotalChars = self::DEFAULT_MAX_TOTAL_CHARS
    ): string {
        return $this->fallbackMysqlRetrievalWithFilters($filters, $maxDocs, $maxTotalChars);
    }

    // ─── Private helpers ────────────────────────────────────────────────────

    /**
     * Build metadata filter array from applicant's first course preference name.
     *
     * @return array<string, string>
     */
    private function buildMetadataFilters(Applicant $applicant): array
    {
        $applicant->load('application');
        $application = $applicant->application;
        if (!$application) {
            return [];
        }

        $id = $application->course_preference_1 ?? null;
        if (empty($id) || !is_numeric($id)) {
            return [];
        }

        $name = DB::table('courses')->where('id', $id)->value('name');
        if (!$name) {
            return [];
        }

        return ['category' => $name];
    }

    /**
     * Format Mixedbread search results into labelled text blocks.
     *
     * @param  array<int, array<string, mixed>>  $results
     */
    private function formatMixedbreadResults(array $results): string
    {
        if (empty($results)) {
            return '';
        }

        $chunks = [];
        foreach ($results as $idx => $item) {
            $content = trim((string) ($item['content'] ?? ''));
            if ($content === '') {
                continue;
            }
            $label    = 'Source ' . ($idx + 1);
            $chunks[] = "{$label}:\n{$content}";
        }

        return implode("\n\n", $chunks);
    }

    /**
     * Original MySQL retrieval — now serves as the fallback.
     */
    public function fallbackMysqlRetrieval(
        Applicant $applicant,
        int $maxDocs = self::DEFAULT_MAX_DOCS,
        int $maxTotalChars = self::DEFAULT_MAX_TOTAL_CHARS
    ): string {
        $applicant->load('application');
        $courseNames = $this->getApplicantCoursePreferenceNames($applicant);

        $docs     = KnowledgeDocument::query()->active()->orderByDesc('updated_at')->orderByDesc('id')->get();
        $filtered = $this->filterByCategory($docs, $courseNames);

        return $this->buildTextFromDocs($filtered, $maxDocs, $maxTotalChars);
    }

    private function fallbackMysqlRetrievalWithFilters(
        array $filters,
        int $maxDocs,
        int $maxTotalChars
    ): string {
        $docs     = KnowledgeDocument::query()->active()->orderByDesc('updated_at')->orderByDesc('id')->get();
        $filtered = $this->filterByCategoryAndYear($docs, $filters);

        return $this->buildTextFromDocs($filtered, $maxDocs, $maxTotalChars);
    }

    private function buildTextFromDocs($docs, int $maxDocs, int $maxTotalChars): string
    {
        $chunks = [];
        $total  = 0;

        foreach ($docs as $doc) {
            if (count($chunks) >= $maxDocs) {
                break;
            }
            $content  = trim($doc->content ?? '');
            if ($content === '') {
                continue;
            }
            $label    = "Source: {$doc->title}";
            $block    = "{$label}\n{$content}";
            $blockLen = strlen($block);

            if ($total + $blockLen > $maxTotalChars) {
                $remaining = $maxTotalChars - $total - strlen("\n\n") - strlen($label) - 1;
                if ($remaining > 0) {
                    $truncated = mb_substr($content, 0, $remaining);
                    if ($truncated !== '') {
                        $chunks[] = "{$label}\n{$truncated}";
                    }
                }
                break;
            }

            $chunks[] = $block;
            $total   += $blockLen;
        }

        return $chunks === [] ? 'No institutional data available.' : implode("\n\n", $chunks);
    }

    // ─── Kept from v1 ───────────────────────────────────────────────────────

    /** @return array<int, string> */
    private function getApplicantCoursePreferenceNames(Applicant $applicant): array
    {
        $application = $applicant->application;
        if (!$application) {
            return [];
        }

        $ids = [];
        foreach (['course_preference_1', 'course_preference_2', 'course_preference_3'] as $key) {
            $id = $application->{$key} ?? null;
            if (!empty($id) && is_numeric($id)) {
                $ids[] = (int) $id;
            }
        }

        if ($ids === []) {
            return [];
        }

        $names = DB::table('courses')->whereIn('id', array_unique($ids))->pluck('name')->all();

        return array_values(array_filter(array_map('trim', $names)));
    }

    private function filterByCategory($docs, array $courseNames)
    {
        if ($courseNames === []) {
            return $docs;
        }
        $lowerNames = array_map('strtolower', $courseNames);

        return $docs->filter(function (KnowledgeDocument $doc) use ($lowerNames) {
            $category = strtolower(trim((string) ($doc->metadata['category'] ?? '')));
            if ($category === '') {
                return true;
            }
            return in_array($category, $lowerNames, true)
                || collect($lowerNames)->contains(fn ($n) => str_contains($n, $category) || str_contains($category, $n));
        })->values();
    }

    private function filterByCategoryAndYear($docs, array $filters)
    {
        $year     = isset($filters['year']) ? trim((string) $filters['year']) : null;
        $category = isset($filters['category']) ? trim((string) $filters['category']) : null;

        return $docs->filter(function (KnowledgeDocument $doc) use ($year, $category) {
            if ($year !== null && $year !== '') {
                $docYear = trim((string) ($doc->metadata['year'] ?? ''));
                if ($docYear !== '' && $docYear !== $year) {
                    return false;
                }
            }
            if ($category !== null && $category !== '') {
                $docCat = trim((string) ($doc->metadata['category'] ?? ''));
                if ($docCat !== '' && strtolower($docCat) !== strtolower($category)) {
                    return false;
                }
            }
            return true;
        })->values();
    }
}
```

### Step 4: Run tests

```bash
./vendor/bin/sail php artisan test --compact tests/Feature/Services/KnowledgeRetrievalServiceTest.php
```

Expected: PASS

### Step 5: Commit

```bash
git add app/Services/KnowledgeRetrievalService.php tests/Feature/Services/KnowledgeRetrievalServiceTest.php
git commit -m "feat(rag): refactor KnowledgeRetrievalService — Mixedbread primary, MySQL fallback"
```

---

## Task 6: Update `AiCompanionService` — pass user message to retrieval

**Files:**
- Modify: `app/Services/AiCompanionService.php`
- Modify: `tests/Feature/Portal/AiCompanionChatTest.php`

> **Key change:** `buildSystemPrompt()` receives the user's message and passes it to `retrieveForApplicant()` as the semantic query.

### Step 1: Write failing tests

```php
// Add to tests/Feature/Portal/AiCompanionChatTest.php

use App\Services\MixedbreadService;

public function test_chat_uses_user_message_as_mixedbread_query(): void
{
    $this->enableCompanion();

    $mockMxb = Mockery::mock(MixedbreadService::class);
    $mockMxb->shouldReceive('search')
        ->once()
        ->withArgs(function ($storeId, $query) {
            return str_contains($query, 'What are my chances');
        })
        ->andReturn([['content' => 'Engineering pass rate 87%.', 'metadata' => []]]);
    $this->app->instance(MixedbreadService::class, $mockMxb);

    config(['services.mixedbread.store_id' => 'store_xyz']);

    $this->actingAsApplicant()
        ->postJson(route('portal.ai-companion.chat'), ['message' => 'What are my chances in Engineering?'])
        ->assertOk()
        ->assertJsonStructure(['reply']);
}

public function test_chat_still_works_when_mixedbread_is_down(): void
{
    $this->enableCompanion();

    $mockMxb = Mockery::mock(MixedbreadService::class);
    $mockMxb->shouldReceive('search')->andThrow(new \RuntimeException('timeout'));
    $this->app->instance(MixedbreadService::class, $mockMxb);

    config(['services.mixedbread.store_id' => 'store_xyz']);

    // Should fall back to MySQL and not crash
    $this->actingAsApplicant()
        ->postJson(route('portal.ai-companion.chat'), ['message' => 'Hello!'])
        ->assertOk()
        ->assertJsonStructure(['reply']);
}
```

### Step 2: Run to verify failures

```bash
./vendor/bin/sail php artisan test --compact --filter="test_chat_uses_user_message|test_chat_still_works_when_mixedbread" tests/Feature/Portal/AiCompanionChatTest.php
```

### Step 3: Update `AiCompanionService::buildSystemPrompt()` and `chat()`

In `buildSystemPrompt()` — add `string $userMessage = ''` parameter and pass to retrieval:

```php
public function buildSystemPrompt(Applicant $applicant, string $userMessage = ''): string
{
    $persona         = SystemSetting::personaPrompt();
    $institutional   = $this->retrieval->retrieveForApplicant($applicant, $userMessage);
    $applicantSummary = $this->buildApplicantSummary($applicant);

    return $persona
        . "\n\nInstitutional data (use only this; do not invent):\n"
        . $institutional
        . "\n\n--- Applicant data ---\n"
        . $applicantSummary
        . "\n--- End applicant data ---\n\nUse only the institutional and applicant data above when giving advice. Do not invent statistics. If the data does not cover a question, say so.";
}
```

In `chat()` — pass `$userMessage` to `buildSystemPrompt`:

```php
$systemPrompt = $this->buildSystemPrompt($applicant, $userMessage);
```

### Step 4: Run ALL companion tests

```bash
./vendor/bin/sail php artisan test --compact tests/Feature/Portal/AiCompanionChatTest.php
```

Expected: PASS

### Step 5: Commit

```bash
git add app/Services/AiCompanionService.php tests/Feature/Portal/AiCompanionChatTest.php
git commit -m "feat(rag): pass user message as Mixedbread query in AiCompanionService"
```

---

## Task 7: One-off Artisan command — sync existing docs to Mixedbread

**Files:**
- Create: `app/Console/Commands/SyncKnowledgeDocsToMixedbread.php`
- Create: `tests/Feature/Console/SyncKnowledgeDocsToMixedbreadTest.php`

> **Purpose:** Admins with existing docs can run `php artisan ai:sync-knowledge-docs` to push them all to Mixedbread in one shot. Safe to re-run (skips already-indexed, retries failed).

### Step 1: Generate command

```bash
./vendor/bin/sail php artisan make:command SyncKnowledgeDocsToMixedbread --no-interaction
```

### Step 2: Write failing tests

```php
// tests/Feature/Console/SyncKnowledgeDocsToMixedbreadTest.php
<?php

namespace Tests\Feature\Console;

use App\Models\KnowledgeDocument;
use App\Services\MixedbreadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SyncKnowledgeDocsToMixedbreadTest extends TestCase
{
    use RefreshDatabase;

    public function test_syncs_pending_docs_to_mixedbread(): void
    {
        $doc = KnowledgeDocument::factory()->create([
            'mxb_sync_status' => KnowledgeDocument::SYNC_PENDING,
        ]);

        $mockMxb = Mockery::mock(MixedbreadService::class);
        $mockMxb->shouldReceive('uploadDocument')->once()->andReturn(['id' => 'file_new']);
        $this->app->instance(MixedbreadService::class, $mockMxb);

        config(['services.mixedbread.store_id' => 'store_xyz']);

        $this->artisan('ai:sync-knowledge-docs')->assertSuccessful();

        $this->assertSame(KnowledgeDocument::SYNC_INDEXED, $doc->fresh()->mxb_sync_status);
        $this->assertSame('file_new', $doc->fresh()->mxb_file_id);
    }

    public function test_retries_failed_docs(): void
    {
        $doc = KnowledgeDocument::factory()->create([
            'mxb_sync_status' => KnowledgeDocument::SYNC_FAILED,
        ]);

        $mockMxb = Mockery::mock(MixedbreadService::class);
        $mockMxb->shouldReceive('uploadDocument')->once()->andReturn(['id' => 'file_retry']);
        $this->app->instance(MixedbreadService::class, $mockMxb);

        config(['services.mixedbread.store_id' => 'store_xyz']);

        $this->artisan('ai:sync-knowledge-docs')->assertSuccessful();

        $this->assertSame(KnowledgeDocument::SYNC_INDEXED, $doc->fresh()->mxb_sync_status);
    }

    public function test_skips_already_indexed_docs(): void
    {
        KnowledgeDocument::factory()->create(['mxb_sync_status' => KnowledgeDocument::SYNC_INDEXED]);

        $mockMxb = Mockery::mock(MixedbreadService::class);
        $mockMxb->shouldNotReceive('uploadDocument');
        $this->app->instance(MixedbreadService::class, $mockMxb);

        config(['services.mixedbread.store_id' => 'store_xyz']);

        $this->artisan('ai:sync-knowledge-docs')->assertSuccessful();
    }

    public function test_marks_doc_failed_if_upload_throws(): void
    {
        $doc = KnowledgeDocument::factory()->create(['mxb_sync_status' => KnowledgeDocument::SYNC_PENDING]);

        $mockMxb = Mockery::mock(MixedbreadService::class);
        $mockMxb->shouldReceive('uploadDocument')->andThrow(new \RuntimeException('API error'));
        $this->app->instance(MixedbreadService::class, $mockMxb);

        config(['services.mixedbread.store_id' => 'store_xyz']);

        $this->artisan('ai:sync-knowledge-docs')->assertSuccessful();

        $this->assertSame(KnowledgeDocument::SYNC_FAILED, $doc->fresh()->mxb_sync_status);
    }
}
```

### Step 3: Run to verify failures

```bash
./vendor/bin/sail php artisan test --compact tests/Feature/Console/SyncKnowledgeDocsToMixedbreadTest.php
```

### Step 4: Implement the command

```php
// app/Console/Commands/SyncKnowledgeDocsToMixedbread.php
<?php

namespace App\Console\Commands;

use App\Models\KnowledgeDocument;
use App\Services\MixedbreadService;
use Illuminate\Console\Command;

class SyncKnowledgeDocsToMixedbread extends Command
{
    protected $signature = 'ai:sync-knowledge-docs {--force : Re-sync all docs, including already-indexed}';

    protected $description = 'Sync pending/failed knowledge documents to Mixedbread Stores for semantic search.';

    public function handle(MixedbreadService $mixedbread): int
    {
        $storeId = config('services.mixedbread.store_id', '');

        if (empty($storeId)) {
            $this->error('MIXEDBREAD_STORE_ID is not configured. Aborting.');
            return Command::FAILURE;
        }

        $query = KnowledgeDocument::query()->active();
        if (!$this->option('force')) {
            $query->whereIn('mxb_sync_status', [KnowledgeDocument::SYNC_PENDING, KnowledgeDocument::SYNC_FAILED]);
        }

        $docs  = $query->get();
        $total = $docs->count();

        if ($total === 0) {
            $this->info('No documents to sync.');
            return Command::SUCCESS;
        }

        $this->info("Syncing {$total} document(s) to Mixedbread store: {$storeId}");
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $synced = 0;
        $failed = 0;

        foreach ($docs as $doc) {
            try {
                $result = $mixedbread->uploadDocument($storeId, $doc->content, $doc->title, array_filter((array) $doc->metadata));
                $doc->update([
                    'mxb_file_id'     => $result['id'] ?? null,
                    'mxb_sync_status' => KnowledgeDocument::SYNC_INDEXED,
                    'mxb_synced_at'   => now(),
                ]);
                $synced++;
            } catch (\Throwable $e) {
                $doc->update(['mxb_sync_status' => KnowledgeDocument::SYNC_FAILED]);
                $this->newLine();
                $this->warn("  Failed doc ID {$doc->id}: {$e->getMessage()}");
                $failed++;
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Done. Synced: {$synced}, Failed: {$failed}.");

        return Command::SUCCESS;
    }
}
```

### Step 5: Run tests

```bash
./vendor/bin/sail php artisan test --compact tests/Feature/Console/SyncKnowledgeDocsToMixedbreadTest.php
```

Expected: PASS

### Step 6: Commit

```bash
git add app/Console/Commands/SyncKnowledgeDocsToMixedbread.php tests/Feature/Console/SyncKnowledgeDocsToMixedbreadTest.php
git commit -m "feat(rag): add artisan command ai:sync-knowledge-docs"
```

---

## Task 8: Admin UI — sync status badges + Retry button

**Files:**
- Modify: `app/Http/Controllers/Admin/KnowledgeDocumentController.php` (expose `mxb_sync_status` in index + add retry route)
- Modify: `resources/js/Pages/Admin/KnowledgeDocuments/Index.svelte`
- Modify: `routes/web.php` (add retry route)

### Step 1: Expose `mxb_sync_status` in controller `index()`

In the `through()` callback in `index()`, add:
```php
'mxb_sync_status' => $doc->mxb_sync_status,
'mxb_file_id'     => $doc->mxb_file_id,
```

### Step 2: Add retry endpoint to controller

```php
/**
 * POST /admin/knowledge-documents/{knowledge_document}/retry-sync
 */
public function retrySync(KnowledgeDocument $knowledgeDocument): RedirectResponse
{
    $this->authorize('update', $knowledgeDocument);
    $this->syncToMixedbread($knowledgeDocument);

    return redirect()->route('admin.knowledge-documents.index')
        ->with('success', 'Sync retried for: ' . $knowledgeDocument->title);
}
```

### Step 3: Register route

In `routes/web.php`, inside the knowledge-documents admin group:
```php
Route::post('/knowledge-documents/{knowledge_document}/retry-sync', [KnowledgeDocumentController::class, 'retrySync'])
    ->name('admin.knowledge-documents.retry-sync');
```

### Step 4: Write a test for retry endpoint

```php
// In KnowledgeDocumentControllerTest.php
public function test_retry_sync_re_uploads_failed_doc(): void
{
    $doc = KnowledgeDocument::factory()->create([
        'mxb_sync_status' => KnowledgeDocument::SYNC_FAILED,
        'mxb_file_id'     => null,
    ]);

    $mockMxb = Mockery::mock(MixedbreadService::class);
    $mockMxb->shouldReceive('uploadDocument')->once()->andReturn(['id' => 'file_retried']);
    $this->app->instance(MixedbreadService::class, $mockMxb);

    config(['services.mixedbread.store_id' => 'store_xyz']);

    $this->actingAs($this->superAdmin)
        ->post(route('admin.knowledge-documents.retry-sync', $doc))
        ->assertRedirect(route('admin.knowledge-documents.index'));

    $this->assertSame(KnowledgeDocument::SYNC_INDEXED, $doc->fresh()->mxb_sync_status);
}
```

### Step 5: Run test

```bash
./vendor/bin/sail php artisan test --compact --filter="test_retry_sync" tests/Feature/Admin/KnowledgeDocumentControllerTest.php
```

Expected: PASS

### Step 6: Update `Index.svelte` — add sync status badges

In the table's action column for each document, add a sync badge and conditional retry button:

```svelte
<!-- Sync status badge -->
{#if doc.mxb_sync_status === 'indexed'}
  <Badge variant="default" class="bg-green-600 text-white">Indexed</Badge>
{:else if doc.mxb_sync_status === 'pending'}
  <Badge variant="outline" class="border-yellow-500 text-yellow-600">Pending</Badge>
{:else if doc.mxb_sync_status === 'failed'}
  <Badge variant="destructive">Failed</Badge>
  <form method="POST" action={route('admin.knowledge-documents.retry-sync', { knowledge_document: doc.id })} class="inline">
    <input type="hidden" name="_token" value={csrfToken} />
    <Button type="submit" variant="ghost" size="sm" class="text-destructive hover:text-destructive">
      Retry Sync
    </Button>
  </form>
{/if}
```

> **Note:** Check sibling Svelte files for the exact Badge/Button import pattern and how forms are submitted (Inertia `useForm` vs native `<form>`). Follow whatever pattern `Edit.svelte` or `Index.svelte` in a similar module already uses.

### Step 7: Commit

```bash
git add app/Http/Controllers/Admin/KnowledgeDocumentController.php routes/web.php resources/js/Pages/Admin/KnowledgeDocuments/Index.svelte tests/Feature/Admin/KnowledgeDocumentControllerTest.php
git commit -m "feat(rag): add sync status badges and retry endpoint in admin UI"
```

---

## Task 9: Run full test suite + pint

### Step 1: Format all modified PHP files

```bash
./vendor/bin/sail vendor/bin/pint --dirty --format agent
```

### Step 2: Run full test suite

```bash
./vendor/bin/sail php artisan test --compact
```

Expected: All green. If any test fails, fix before proceeding.

### Step 3: Final commit (if any pint fixes)

```bash
git add -A
git commit -m "style: pint formatting pass after RAG implementation"
```

### Step 4: Push

```bash
git push
```

---

## Summary of new files

| File | Type |
|------|------|
| `app/Services/MixedbreadService.php` | New service |
| `app/Console/Commands/SyncKnowledgeDocsToMixedbread.php` | New artisan command |
| `database/migrations/YYYY_add_mxb_sync_columns_...php` | New migration |
| `tests/Unit/Config/MixedbreadConfigTest.php` | New unit test |
| `tests/Unit/Services/MixedbreadServiceTest.php` | New unit test |
| `tests/Feature/Models/KnowledgeDocumentSyncColumnsTest.php` | New feature test |
| `tests/Feature/Console/SyncKnowledgeDocsToMixedbreadTest.php` | New feature test |

## Summary of modified files

| File | What changes |
|------|-------------|
| `config/services.php` | Add `mixedbread` config block |
| `.env.example` | Add `MIXEDBREAD_*` keys |
| `app/Models/KnowledgeDocument.php` | Add sync fields, constants, casts |
| `app/Services/KnowledgeRetrievalService.php` | Mixedbread primary + MySQL fallback |
| `app/Services/AiCompanionService.php` | Pass user message to retrieval |
| `app/Http/Controllers/Admin/KnowledgeDocumentController.php` | Sync hooks + retry endpoint |
| `routes/web.php` | Add retry-sync route |
| `resources/js/Pages/Admin/KnowledgeDocuments/Index.svelte` | Sync status badges + retry button |
| `tests/Feature/Admin/KnowledgeDocumentControllerTest.php` | New sync assertions |
| `tests/Feature/Portal/AiCompanionChatTest.php` | New Mixedbread-aware assertions |
| `tests/Feature/Services/KnowledgeRetrievalServiceTest.php` | Semantic search + fallback tests |
