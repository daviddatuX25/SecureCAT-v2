<?php

namespace Tests\Feature\Admin;

use App\Models\Role;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RoleSeeder::class);
    }

    public function test_super_admin_can_view_settings_index(): void
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('name', 'super_admin')->first());

        $response = $this->actingAs($user)->get('/admin/settings');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Settings/Index')
            ->has('ai_exam_companion_enabled')
        );
    }

    public function test_super_admin_can_enable_ai_companion(): void
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('name', 'super_admin')->first());

        $response = $this->actingAs($user)->put('/admin/settings', [
            'ai_exam_companion_enabled' => true,
        ]);

        $response->assertRedirect();
        $this->assertTrue(SystemSetting::aiCompanionEnabled());
    }

    public function test_super_admin_can_disable_ai_companion(): void
    {
        SystemSetting::set('ai_exam_companion_enabled', true);
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('name', 'super_admin')->first());

        $response = $this->actingAs($user)->put('/admin/settings', [
            'ai_exam_companion_enabled' => false,
        ]);

        $response->assertRedirect();
        $this->assertFalse(SystemSetting::aiCompanionEnabled());
    }

    public function test_non_super_admin_cannot_view_settings(): void
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('name', 'staff')->first());

        $response = $this->actingAs($user)->get('/admin/settings');

        $response->assertStatus(403);
    }

    public function test_unauthenticated_user_cannot_view_settings(): void
    {
        $response = $this->get('/admin/settings');

        $response->assertRedirect(route('login'));
    }

    public function test_super_admin_can_save_persona(): void
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('name', 'super_admin')->first());

        $persona = 'You are an encouraging academic counselor. Base advice only on data provided.';

        $response = $this->actingAs($user)->put('/admin/settings', [
            'ai_exam_companion_enabled' => true,
            'ai_companion_persona' => $persona,
        ]);

        $response->assertRedirect();
        $this->assertSame($persona, SystemSetting::get('ai_companion_persona'));
        $this->assertSame($persona, SystemSetting::personaPrompt());
    }

    public function test_empty_persona_uses_default(): void
    {
        $this->assertSame(SystemSetting::defaultPersonaPrompt(), SystemSetting::personaPrompt());

        SystemSetting::set('ai_companion_persona', '');
        $this->assertSame(SystemSetting::defaultPersonaPrompt(), SystemSetting::personaPrompt());
    }

    public function test_persona_strips_html(): void
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('name', 'super_admin')->first());

        $this->actingAs($user)->put('/admin/settings', [
            'ai_companion_persona' => '<script>alert(1)</script>You are helpful.',
        ]);

        $this->assertSame('You are helpful.', SystemSetting::get('ai_companion_persona'));
    }
}
