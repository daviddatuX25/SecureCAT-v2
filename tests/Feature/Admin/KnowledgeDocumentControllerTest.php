<?php

namespace Tests\Feature\Admin;

use App\Models\KnowledgeDocument;
use App\Models\Role;
use App\Models\User;
use App\Services\MixedbreadService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class KnowledgeDocumentControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);

        // Provide default config so the real service can be constructed without errors
        // when a test doesn't need to mock Mixedbread specifically.
        config([
            'services.mixedbread.api_key' => 'test-api-key',
            'services.mixedbread.base_url' => 'https://api.mixedbread.com/v1',
            'services.mixedbread.store_id' => '',
        ]);
    }

    private function superAdmin(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('name', 'super_admin')->first());

        return $user;
    }

    private function staff(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('name', 'staff')->first());

        return $user;
    }

    public function test_super_admin_can_view_index(): void
    {
        $response = $this->actingAs($this->superAdmin())->get('/admin/knowledge-documents');

        $response->assertRedirect(route('admin.ai-companion.index'));
    }

    public function test_super_admin_can_create_doc_with_metadata(): void
    {
        $response = $this->actingAs($this->superAdmin())->post('/admin/knowledge-documents', [
            'title' => 'Engineering success rates',
            'content' => 'In Civil Engineering, students with Math 85–90 had a 92% pass rate.',
            'metadata' => [
                'category' => 'Engineering',
                'year' => '2024',
            ],
        ]);

        $response->assertRedirect(route('admin.knowledge-documents.index'));

        $this->assertDatabaseHas('knowledge_documents', [
            'title' => 'Engineering success rates',
            'source' => 'manual',
        ]);

        $doc = KnowledgeDocument::first();
        $this->assertSame('Engineering', $doc->metadata['category'] ?? null);
        $this->assertSame('2024', $doc->metadata['year'] ?? null);
    }

    public function test_super_admin_can_create_doc_with_no_metadata(): void
    {
        $response = $this->actingAs($this->superAdmin())->post('/admin/knowledge-documents', [
            'title' => 'Generic notes',
            'content' => 'Some text.',
        ]);

        $response->assertRedirect();

        $doc = KnowledgeDocument::first();
        $this->assertNotNull($doc);
        $this->assertSame([], $doc->metadata ?? []);
    }

    public function test_super_admin_can_update_doc(): void
    {
        $doc = KnowledgeDocument::create([
            'title' => 'Old title',
            'content' => 'Old content',
            'metadata' => ['category' => 'A'],
            'source' => 'manual',
        ]);

        $response = $this->actingAs($this->superAdmin())->put("/admin/knowledge-documents/{$doc->id}", [
            'title' => 'New title',
            'content' => 'New content',
            'metadata' => ['category' => 'B', 'year' => '2024'],
        ]);

        $response->assertRedirect();

        $doc->refresh();
        $this->assertSame('New title', $doc->title);
        $this->assertSame('New content', $doc->content);
        $this->assertSame('B', $doc->metadata['category'] ?? null);
        $this->assertSame('2024', $doc->metadata['year'] ?? null);
    }

    public function test_super_admin_can_delete_doc(): void
    {
        $doc = KnowledgeDocument::create([
            'title' => 'To delete',
            'content' => 'Content',
            'source' => 'manual',
        ]);

        $response = $this->actingAs($this->superAdmin())->delete("/admin/knowledge-documents/{$doc->id}");

        $response->assertRedirect();
        $this->assertDatabaseMissing('knowledge_documents', ['id' => $doc->id]);
    }

    public function test_index_paginates_and_shows_metadata_summary(): void
    {
        KnowledgeDocument::create([
            'title' => 'Doc 1',
            'content' => 'Content 1',
            'metadata' => ['category' => 'Engineering', 'year' => '2024'],
            'source' => 'manual',
        ]);

        $response = $this->actingAs($this->superAdmin())->get('/admin/knowledge-documents');

        $response->assertRedirect(route('admin.ai-companion.index'));

        $doc = KnowledgeDocument::first();
        $this->assertSame('Engineering · 2024', $doc->metadata_summary);
    }

    public function test_staff_cannot_access_index(): void
    {
        $response = $this->actingAs($this->staff())->get('/admin/knowledge-documents');

        $response->assertStatus(403);
    }

    public function test_unauthenticated_cannot_access_index(): void
    {
        $response = $this->get('/admin/knowledge-documents');

        $response->assertRedirect(route('login'));
    }

    public function test_validation_requires_title_and_content(): void
    {
        $response = $this->actingAs($this->superAdmin())->post('/admin/knowledge-documents', [
            'title' => '',
            'content' => '',
        ]);

        $response->assertSessionHasErrors(['title', 'content']);
    }

    public function test_content_can_be_very_long(): void
    {
        $longContent = str_repeat('x', 50000);

        $response = $this->actingAs($this->superAdmin())->post('/admin/knowledge-documents', [
            'title' => 'Long doc',
            'content' => $longContent,
        ]);

        $response->assertRedirect();
        $doc = KnowledgeDocument::first();
        $this->assertSame(50000, strlen($doc->content));
    }

    /**
     * @return Mockery\Mock|MixedbreadService
     */
    private function mixedbreadMock()
    {
        config([
            'services.mixedbread.api_key' => 'test-api-key',
            'services.mixedbread.base_url' => 'https://api.mixedbread.com/v1',
        ]);

        return Mockery::mock(MixedbreadService::class);
    }

    public function test_store_uploads_to_mixedbread_and_sets_indexed_status(): void
    {
        $mockMxb = $this->mixedbreadMock();
        $mockMxb->shouldReceive('uploadDocument')
            ->once()
            ->andReturn(['id' => 'file_abc123', 'status' => 'processing']);
        $this->app->instance(MixedbreadService::class, $mockMxb);
        config(['services.mixedbread.store_id' => 'store_xyz']);

        $this->actingAs($this->superAdmin())
            ->post(route('admin.knowledge-documents.store'), [
                'title' => 'Engineering Guide',
                'content' => 'Civil engineering pass rate is 87%.',
            ])
            ->assertRedirect(route('admin.knowledge-documents.index'));

        $doc = KnowledgeDocument::where('title', 'Engineering Guide')->first();
        $this->assertSame('file_abc123', $doc->mxb_file_id);
        $this->assertSame(KnowledgeDocument::SYNC_INDEXED, $doc->mxb_sync_status);
    }

    public function test_store_marks_failed_when_mixedbread_throws(): void
    {
        $mockMxb = $this->mixedbreadMock();
        $mockMxb->shouldReceive('uploadDocument')->andThrow(new \RuntimeException('API down'));
        $this->app->instance(MixedbreadService::class, $mockMxb);
        config(['services.mixedbread.store_id' => 'store_xyz']);

        $this->actingAs($this->superAdmin())
            ->post(route('admin.knowledge-documents.store'), [
                'title' => 'Test Doc',
                'content' => 'Some content.',
            ])
            ->assertRedirect();

        $doc = KnowledgeDocument::where('title', 'Test Doc')->first();
        $this->assertSame(KnowledgeDocument::SYNC_FAILED, $doc->mxb_sync_status);
        $this->assertNull($doc->mxb_file_id);
    }

    public function test_update_re_uploads_to_mixedbread(): void
    {
        $doc = KnowledgeDocument::factory()->create([
            'mxb_file_id' => 'old_file_id',
            'mxb_sync_status' => KnowledgeDocument::SYNC_INDEXED,
        ]);
        $mockMxb = $this->mixedbreadMock();
        $mockMxb->shouldReceive('deleteDocument')->once()->with(Mockery::any(), 'old_file_id');
        $mockMxb->shouldReceive('uploadDocument')->once()->andReturn(['id' => 'new_file_id']);
        $this->app->instance(MixedbreadService::class, $mockMxb);
        config(['services.mixedbread.store_id' => 'store_xyz']);

        $this->actingAs($this->superAdmin())
            ->put(route('admin.knowledge-documents.update', $doc), [
                'title' => 'Updated Title',
                'content' => 'Updated content.',
            ])
            ->assertRedirect();

        $this->assertSame('new_file_id', $doc->fresh()->mxb_file_id);
    }

    public function test_destroy_deletes_from_mixedbread(): void
    {
        $doc = KnowledgeDocument::factory()->create([
            'mxb_file_id' => 'file_to_delete',
        ]);
        $mockMxb = $this->mixedbreadMock();
        $mockMxb->shouldReceive('deleteDocument')->once()->with(Mockery::any(), 'file_to_delete');
        $this->app->instance(MixedbreadService::class, $mockMxb);
        config(['services.mixedbread.store_id' => 'store_xyz']);

        $this->actingAs($this->superAdmin())
            ->delete(route('admin.knowledge-documents.destroy', $doc))
            ->assertRedirect();

        $this->assertModelMissing($doc);
    }

    public function test_destroy_proceeds_even_if_doc_has_no_mxb_file_id(): void
    {
        $doc = KnowledgeDocument::factory()->create([
            'mxb_file_id' => null,
        ]);
        $mockMxb = $this->mixedbreadMock();
        $mockMxb->shouldNotReceive('deleteDocument');
        $this->app->instance(MixedbreadService::class, $mockMxb);

        $this->actingAs($this->superAdmin())
            ->delete(route('admin.knowledge-documents.destroy', $doc))
            ->assertRedirect();

        $this->assertModelMissing($doc);
    }
}
