<?php
namespace Tests\Feature\Console;

use App\Models\KnowledgeDocument;
use App\Services\MixedbreadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
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
