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
