<?php

namespace Tests\Feature\Admin;

use App\Models\Role;
use App\Models\SystemSetting;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstitutionControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    private function superAdmin(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('name', 'super_admin')->first());

        return $user;
    }

    private function staffUser(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('name', 'staff')->first());

        return $user;
    }

    public function test_institution_page_loads_for_super_admin(): void
    {
        $response = $this->actingAs($this->superAdmin())->get(route('admin.setup.institution.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Institution/Index')
            ->has('profile')
            ->has('personnel')
        );
    }

    public function test_institution_page_blocked_for_non_admin(): void
    {
        $response = $this->actingAs($this->staffUser())->get(route('admin.setup.institution.index'));

        $response->assertStatus(403);
    }

    public function test_institution_shows_env_defaults(): void
    {
        $response = $this->actingAs($this->superAdmin())->get(route('admin.setup.institution.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->where('profile.name.env_default', config('institution.name'))
            ->where('profile.name.overridden', false)
        );
    }

    public function test_institution_update_creates_overrides(): void
    {
        $this->actingAs($this->superAdmin())->put(route('admin.setup.institution.update'), [
            'profile' => [
                'name' => 'Custom University',
            ],
        ]);

        $this->assertSame('Custom University', SystemSetting::get('institution.name'));
        $this->assertSame('Custom University', SystemSetting::institution('name'));
    }

    public function test_institution_update_clears_matching_defaults(): void
    {
        $envDefault = config('institution.name', '');
        SystemSetting::set('institution.name', 'Something Else');
        $this->assertSame('Something Else', SystemSetting::get('institution.name'));

        $this->actingAs($this->superAdmin())->put(route('admin.setup.institution.update'), [
            'profile' => [
                'name' => $envDefault,
            ],
        ]);

        $this->assertNull(SystemSetting::get('institution.name'));
    }

    public function test_institution_reset_clears_all_overrides(): void
    {
        SystemSetting::set('institution.name', 'A');
        SystemSetting::set('institution.campus', 'B');
        SystemSetting::set('institution.personnel.guidance_counselor.name', 'Dr. X');

        $this->actingAs($this->superAdmin())->post(route('admin.setup.institution.reset'));

        $this->assertNull(SystemSetting::get('institution.name'));
        $this->assertNull(SystemSetting::get('institution.campus'));
        $this->assertNull(SystemSetting::get('institution.personnel.guidance_counselor.name'));
    }

    public function test_institution_override_takes_precedence(): void
    {
        SystemSetting::set('institution.name', 'Override University');

        $this->assertSame('Override University', SystemSetting::institution('name'));
    }

    public function test_institution_fallback_to_env(): void
    {
        $default = config('institution.name', 'My Institution');

        $this->assertSame($default, SystemSetting::institution('name'));
    }

    public function test_institution_page_loads_for_test_administrator_with_filtered_data(): void
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('name', 'test_administrator')->first());

        $response = $this->actingAs($user)->get(route('admin.setup.institution.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Institution/Index')
            ->where('profile', [])
            ->has('personnel.guidance_counselor')
            ->has('personnel.testing_coordinator')
            ->missing('personnel.registrar')
            ->where('personnelRoles', ['guidance_counselor', 'testing_coordinator'])
        );
    }

    public function test_test_administrator_cannot_update_profile_or_unauthorized_personnel(): void
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('name', 'test_administrator')->first());

        // Setup some overrides first
        SystemSetting::set('institution.name', 'Initial Name');
        SystemSetting::set('institution.personnel.registrar.name', 'Initial Registrar');
        SystemSetting::set('institution.personnel.guidance_counselor.name', 'Initial Guidance');

        $this->actingAs($user)->put(route('admin.setup.institution.update'), [
            'profile' => [
                'name' => 'Attempted Change',
            ],
            'personnel' => [
                'registrar' => [
                    'name' => 'Attempted Registrar Change',
                ],
                'guidance_counselor' => [
                    'name' => 'Changed Guidance Counselor',
                ],
            ],
        ]);

        // Assert guidance counselor was updated
        $this->assertSame('Changed Guidance Counselor', SystemSetting::institution('personnel.guidance_counselor.name'));
        // Assert other values remain unchanged
        $this->assertSame('Initial Name', SystemSetting::institution('name'));
        $this->assertSame('Initial Registrar', SystemSetting::institution('personnel.registrar.name'));
    }

    public function test_test_administrator_reset_only_clears_allowed_personnel(): void
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('name', 'test_administrator')->first());

        SystemSetting::set('institution.name', 'Override Name');
        SystemSetting::set('institution.personnel.registrar.name', 'Override Registrar');
        SystemSetting::set('institution.personnel.guidance_counselor.name', 'Override Guidance');

        $this->actingAs($user)->post(route('admin.setup.institution.reset'));

        // Guidance counselor should be reset
        $this->assertNull(SystemSetting::get('institution.personnel.guidance_counselor.name'));
        // Name and registrar should NOT be reset
        $this->assertSame('Override Name', SystemSetting::get('institution.name'));
        $this->assertSame('Override Registrar', SystemSetting::get('institution.personnel.registrar.name'));
    }
}
