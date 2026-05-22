<?php

namespace Tests\Feature\Admin;

use App\Models\KnowledgeDocument;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class KnowledgeDocumentCsvImportTest extends TestCase
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

    private function csvFile(string $content, string $name = 'data.csv'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, $content);
    }

    /** T6.1: Valid CSV + metadata → doc created with metadata, source csv_import */
    public function test_super_admin_can_import_csv_with_metadata(): void
    {
        $csv = "course,value,rate\nCivil Engineering,85,92\nMechanical,78,88";
        $file = $this->csvFile($csv);

        $response = $this->actingAs($this->superAdmin())
            ->post('/admin/knowledge-documents/import', [
                'file' => $file,
                'title' => 'Engineering success rates 2024',
                'metadata' => [
                    'category' => 'Engineering',
                    'year' => '2024',
                    'description' => 'Success rates by course',
                ],
            ]);

        $response->assertRedirect(route('admin.knowledge-documents.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('knowledge_documents', [
            'title' => 'Engineering success rates 2024',
            'source' => KnowledgeDocument::SOURCE_CSV_IMPORT,
        ]);

        $doc = KnowledgeDocument::where('source', KnowledgeDocument::SOURCE_CSV_IMPORT)->first();
        $this->assertSame('Engineering', $doc->metadata['category'] ?? null);
        $this->assertSame('2024', $doc->metadata['year'] ?? null);
        $this->assertStringContainsString('Row 1:', $doc->content);
        $this->assertStringContainsString('Row 2:', $doc->content);
        $this->assertStringContainsString('Civil Engineering', $doc->content);
    }

    /** T6.2: CSV with different semantics (enrollment) → generic converter; metadata defines doc */
    public function test_csv_with_different_semantics_uses_metadata_to_define_doc(): void
    {
        $csv = "department,enrollment\nIT,150\nEngineering,200";
        $file = $this->csvFile($csv);

        $response = $this->actingAs($this->superAdmin())
            ->post('/admin/knowledge-documents/import', [
                'file' => $file,
                'title' => 'Enrollment 2024',
                'metadata' => [
                    'category' => 'Enrollment',
                    'year' => '2024',
                ],
            ]);

        $response->assertRedirect();
        $doc = KnowledgeDocument::where('source', KnowledgeDocument::SOURCE_CSV_IMPORT)->first();
        $this->assertSame('Enrollment', $doc->metadata['category'] ?? null);
        $this->assertStringContainsString('enrollment', strtolower($doc->content));
        $this->assertStringContainsString('150', $doc->content);
    }

    /** T6.3: Empty CSV or header-only → error, no doc created */
    public function test_empty_csv_returns_error(): void
    {
        $file = $this->csvFile("header1,header2\n");

        $response = $this->actingAs($this->superAdmin())
            ->post('/admin/knowledge-documents/import', [
                'file' => $file,
                'title' => 'Empty',
                'metadata' => [],
            ]);

        $response->assertSessionHasErrors('file');
        $this->assertDatabaseMissing('knowledge_documents', ['title' => 'Empty']);
    }

    /** T6.3: Header-only (no data rows) → error */
    public function test_header_only_csv_returns_error(): void
    {
        $file = $this->csvFile("col1,col2,col3\n");

        $response = $this->actingAs($this->superAdmin())
            ->post('/admin/knowledge-documents/import', [
                'file' => $file,
                'title' => 'Header only',
                'metadata' => [],
            ]);

        $response->assertSessionHasErrors('file');
    }

    /** T6.4: Invalid file type → 422 */
    public function test_invalid_file_type_returns_validation_error(): void
    {
        $file = UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf');

        $response = $this->actingAs($this->superAdmin())
            ->post('/admin/knowledge-documents/import', [
                'file' => $file,
                'title' => 'Invalid',
                'metadata' => [],
            ]);

        $response->assertSessionHasErrors('file');
    }

    /** Validation: file required */
    public function test_file_required(): void
    {
        $response = $this->actingAs($this->superAdmin())
            ->post('/admin/knowledge-documents/import', [
                'title' => 'No file',
                'metadata' => [],
            ]);

        $response->assertSessionHasErrors('file');
    }

    /** Validation: title required */
    public function test_title_required(): void
    {
        $file = $this->csvFile("a,b\n1,2");

        $response = $this->actingAs($this->superAdmin())
            ->post('/admin/knowledge-documents/import', [
                'file' => $file,
                'title' => '',
                'metadata' => [],
            ]);

        $response->assertSessionHasErrors('title');
    }

    /** Staff cannot access import */
    public function test_non_super_admin_cannot_import(): void
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('name', 'staff')->first());
        $file = $this->csvFile("a,b\n1,2");

        $response = $this->actingAs($user)
            ->get('/admin/knowledge-documents/import');

        $response->assertStatus(403);

        $response = $this->actingAs($user)
            ->post('/admin/knowledge-documents/import', [
                'file' => $file,
                'title' => 'Test',
                'metadata' => [],
            ]);

        $response->assertStatus(403);
    }

    /** Import form renders */
    public function test_super_admin_can_view_import_form(): void
    {
        $response = $this->actingAs($this->superAdmin())->get('/admin/knowledge-documents/import');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/KnowledgeDocuments/Import')
        );
    }
}
