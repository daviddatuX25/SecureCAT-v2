<?php

namespace Tests\Feature\Admin;

use App\Models\KnowledgeDocument;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KnowledgeDocumentControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RoleSeeder::class);
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

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/KnowledgeDocuments/Index')
            ->has('documents')
        );
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

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->has('documents')->has('documents.data'));

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
}
