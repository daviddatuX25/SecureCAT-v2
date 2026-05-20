<?php

namespace Tests\Feature;

use App\Models\AptitudeArea;
use App\Models\ResultSheetTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResultSheetTemplateControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create();
        $this->admin->assignRole('super_admin');
        AptitudeArea::factory()->create(['name' => 'Spatial Awareness', 'is_active' => true, 'display_order' => 0]);
    }

    public function test_store_deactivates_other_templates_when_creating_active(): void
    {
        $existing = ResultSheetTemplate::factory()->active()->create(['name' => 'Old Active']);

        $this->actingAs($this->admin)
            ->post(route('admin.release.result-templates.store'), [
                'name' => 'New Active',
                'mode' => 'html',
                'content' => '<div>{{applicant_name}}</div>',
                'is_active' => true,
                'paper_size' => 'a4',
                'orientation' => 'portrait',
                'logical_unit' => 'full',
            ])
            ->assertRedirect(route('admin.release.result-templates.index'));

        $this->assertFalse($existing->fresh()->is_active);
        $this->assertEquals(1, ResultSheetTemplate::where('is_active', true)->count());
        $this->assertTrue(ResultSheetTemplate::where('name', 'New Active')->first()->is_active);
    }

    public function test_store_does_not_deactivate_when_creating_inactive(): void
    {
        $existing = ResultSheetTemplate::factory()->active()->create(['name' => 'Old Active']);

        $this->actingAs($this->admin)
            ->post(route('admin.release.result-templates.store'), [
                'name' => 'New Inactive',
                'mode' => 'html',
                'content' => '<div>{{applicant_name}}</div>',
                'is_active' => false,
                'paper_size' => 'a4',
                'orientation' => 'portrait',
                'logical_unit' => 'full',
            ])
            ->assertRedirect(route('admin.release.result-templates.index'));

        $this->assertTrue($existing->fresh()->is_active);
        $this->assertFalse(ResultSheetTemplate::where('name', 'New Inactive')->first()->is_active);
    }

    public function test_update_deactivates_other_templates_when_activating(): void
    {
        $active = ResultSheetTemplate::factory()->active()->create(['name' => 'Active']);
        $inactive = ResultSheetTemplate::factory()->create(['name' => 'Inactive', 'is_active' => false]);

        $this->actingAs($this->admin)
            ->put(route('admin.release.result-templates.update', $inactive), [
                'is_active' => true,
            ])
            ->assertRedirect(route('admin.release.result-templates.index'));

        $this->assertFalse($active->fresh()->is_active);
        $this->assertTrue($inactive->fresh()->is_active);
        $this->assertEquals(1, ResultSheetTemplate::where('is_active', true)->count());
    }

    public function test_update_does_not_deactivate_self(): void
    {
        $active = ResultSheetTemplate::factory()->active()->create(['name' => 'Active']);

        $this->actingAs($this->admin)
            ->put(route('admin.release.result-templates.update', $active), [
                'name' => 'Still Active',
            ])
            ->assertRedirect(route('admin.release.result-templates.index'));

        $this->assertTrue($active->fresh()->is_active);
    }

    public function test_only_one_template_can_be_active_at_a_time(): void
    {
        ResultSheetTemplate::factory()->active()->create(['name' => 'A']);
        ResultSheetTemplate::factory()->active()->create(['name' => 'B']);
        ResultSheetTemplate::factory()->active()->create(['name' => 'C']);

        $this->assertGreaterThan(1, ResultSheetTemplate::where('is_active', true)->count());

        $target = ResultSheetTemplate::where('name', 'B')->first();

        $this->actingAs($this->admin)
            ->put(route('admin.release.result-templates.update', $target), [
                'is_active' => true,
            ])
            ->assertRedirect(route('admin.release.result-templates.index'));

        $this->assertEquals(1, ResultSheetTemplate::where('is_active', true)->count());
        $this->assertTrue($target->fresh()->is_active);
    }

    public function test_activate_endpoint_sets_template_active_and_deactivates_others(): void
    {
        $active = ResultSheetTemplate::factory()->active()->create(['name' => 'Active']);
        $inactive = ResultSheetTemplate::factory()->create(['name' => 'Inactive', 'is_active' => false]);

        $this->actingAs($this->admin)
            ->post(route('admin.release.result-templates.activate', $inactive))
            ->assertRedirect(route('admin.release.result-templates.index'));

        $this->assertFalse($active->fresh()->is_active);
        $this->assertTrue($inactive->fresh()->is_active);
        $this->assertEquals(1, ResultSheetTemplate::where('is_active', true)->count());
    }

    public function test_deactivate_endpoint_sets_template_inactive(): void
    {
        $active = ResultSheetTemplate::factory()->active()->create(['name' => 'Active']);

        $this->actingAs($this->admin)
            ->post(route('admin.release.result-templates.deactivate', $active))
            ->assertRedirect(route('admin.release.result-templates.index'));

        $this->assertFalse($active->fresh()->is_active);
        $this->assertEquals(0, ResultSheetTemplate::where('is_active', true)->count());
    }
}
