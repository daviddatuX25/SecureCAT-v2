<?php

namespace Tests\Feature\Admin;

use App\Models\ResultSheetTemplate;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResultSheetTemplateManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $counselor;

    protected User $grader;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RoleSeeder::class);
        $this->admin = User::factory()->create();
        $this->admin->roles()->attach(Role::where('name', 'admin')->first());
        $this->counselor = User::factory()->create();
        $this->counselor->roles()->attach(Role::where('name', 'counselor')->first());
        $this->grader = User::factory()->create();
        $this->grader->roles()->attach(Role::where('name', 'grader')->first());
    }

    public function test_admin_can_list_templates(): void
    {
        ResultSheetTemplate::create(['name' => 'A', 'mode' => 'html', 'content' => '<p>A</p>', 'is_active' => true]);
        ResultSheetTemplate::create(['name' => 'B', 'mode' => 'html', 'content' => '<p>B</p>', 'is_active' => false]);

        $response = $this->actingAs($this->admin)->get('/admin/result-sheet-templates');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/ResultSheetTemplates/Index')
            ->has('templates')
        );
    }

    public function test_counselor_can_list_templates(): void
    {
        $response = $this->actingAs($this->counselor)->get('/admin/result-sheet-templates');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Admin/ResultSheetTemplates/Index'));
    }

    public function test_grader_cannot_access_template_routes(): void
    {
        $response = $this->actingAs($this->grader)->get('/admin/result-sheet-templates');
        $response->assertStatus(403);
    }

    public function test_admin_can_create_html_template(): void
    {
        $content = '<div>Test {{applicant_name}}</div>';

        $response = $this->actingAs($this->admin)->post('/admin/result-sheet-templates', [
            'name' => 'Test HTML',
            'mode' => 'html',
            'content' => $content,
            'paper_size' => 'a4',
            'orientation' => 'portrait',
            'logical_unit' => 'full',
            'is_active' => true,
        ]);

        $response->assertRedirect(route('admin.result-sheet-templates.index'));
        $this->assertDatabaseHas('result_sheet_templates', [
            'name' => 'Test HTML',
            'mode' => 'html',
            'content' => $content,
            'paper_size' => 'a4',
            'orientation' => 'portrait',
            'logical_unit' => 'full',
            'is_active' => true,
        ]);
    }

    public function test_admin_can_update_template(): void
    {
        $template = ResultSheetTemplate::create([
            'name' => 'Original',
            'mode' => 'html',
            'content' => '<p>Old</p>',
        ]);

        $response = $this->actingAs($this->admin)->put("/admin/result-sheet-templates/{$template->id}", [
            'name' => 'Updated',
            'mode' => 'html',
            'content' => '<p>New content</p>',
        ]);

        $response->assertRedirect(route('admin.result-sheet-templates.index'));
        $template->refresh();
        $this->assertSame('Updated', $template->name);
        $this->assertSame('<p>New content</p>', $template->content);
    }

    public function test_admin_can_delete_template(): void
    {
        $template = ResultSheetTemplate::create(['name' => 'ToDelete', 'mode' => 'html', 'content' => '<p>x</p>', 'is_active' => true]);

        $response = $this->actingAs($this->admin)->delete("/admin/result-sheet-templates/{$template->id}");

        $response->assertRedirect(route('admin.result-sheet-templates.index'));
        $this->assertDatabaseMissing('result_sheet_templates', ['id' => $template->id]);
    }

    public function test_preview_endpoint_returns_html(): void
    {
        $response = $this->actingAs($this->admin)->post('/admin/result-sheet-templates/preview', [
            'mode' => 'html',
            'content' => '<p>Hello {{applicant_name}}</p>',
            'paper_size' => 'a4',
            'orientation' => 'portrait',
            'logical_unit' => 'full',
        ]);

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertArrayHasKey('html', $data);
        $this->assertStringContainsString('Juan Dela Cruz', $data['html']);
    }

    /** Structural placeholder (scores-rows-placeholder) survives Purifier and is replaced with score rows. */
    public function test_structural_placeholder_renders_scores_in_preview(): void
    {
        $content = '<table><thead><tr><th>Domain</th><th>Score</th></tr></thead><tbody><tr class="scores-rows-placeholder"><td colspan="2"></td></tr></tbody></table>';
        $response = $this->actingAs($this->admin)->post('/admin/result-sheet-templates/preview', [
            'mode' => 'html',
            'content' => $content,
            'paper_size' => 'a4',
            'orientation' => 'portrait',
            'logical_unit' => 'full',
        ]);

        $response->assertStatus(200);
        $html = $response->json('html');
        $this->assertStringContainsString('Spatial Awareness', $html);
        $this->assertStringContainsString('Numerical Ability', $html);
        $this->assertStringContainsString('Verbal Reasoning', $html);
        $this->assertStringNotContainsString('scores-rows-placeholder', $html);
    }

    public function test_javascript_stripped_from_html_template(): void
    {
        $malicious = '<p>OK</p><script>alert(1)</script><div onclick="evil()">X</div>';
        $response = $this->actingAs($this->admin)->post('/admin/result-sheet-templates', [
            'name' => 'Safe',
            'mode' => 'html',
            'content' => $malicious,
            'paper_size' => 'a4',
            'orientation' => 'portrait',
            'logical_unit' => 'full',
            'is_active' => true,
        ]);

        $response->assertRedirect(route('admin.result-sheet-templates.index'));
        $template = ResultSheetTemplate::where('name', 'Safe')->first();
        $this->assertNotNull($template);
        $this->assertStringNotContainsString('<script>', $template->content);
        $this->assertStringNotContainsString('onclick', $template->content);
    }
}
