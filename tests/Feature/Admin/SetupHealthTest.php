<?php

namespace Tests\Feature\Admin;

use App\Models\AcademicYear;
use App\Models\AdmissionSlipTemplate;
use App\Models\AptitudeArea;
use App\Models\Course;
use App\Models\PrivacyPolicy;
use App\Models\RatingScale;
use App\Models\ResultSheetTemplate;
use App\Models\Role;
use App\Models\Room;
use App\Models\SystemSetting;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SetupHealthTest extends TestCase
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

    private function admin(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('name', 'registrar_administrator')->first());

        return $user;
    }

    /**
     * Clear all setup-related tables so health checks start from a known blank state.
     */
    private function clearSetupTables(): void
    {
        AcademicYear::query()->delete();
        Course::withTrashed()->forceDelete();
        Room::withTrashed()->forceDelete();
        AptitudeArea::query()->delete();
        ResultSheetTemplate::query()->delete();
        AdmissionSlipTemplate::query()->delete();
        PrivacyPolicy::query()->delete();
    }

    public function test_setup_page_returns_health_data(): void
    {
        $response = $this->actingAs($this->superAdmin())->get(route('admin.setup.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Setup/Index')
            ->has('health')
            ->has('health.overall')
            ->has('health.overall.score')
            ->has('health.overall.total')
            ->has('health.overall.percentage')
            ->has('health.categories')
        );
    }

    public function test_empty_database_shows_minimal_health(): void
    {
        $this->clearSetupTables();
        // Remove all user roles except the acting super admin
        User::query()->delete();

        $user = $this->superAdmin(); // creates 1 super admin

        $response = $this->actingAs($user)->get(route('admin.setup.index'));

        $response->assertInertia(function ($page) {
            $health = $page->toArray()['props']['health'];

            // Only super_admin staff check should pass (from the acting user)
            $allChecks = collect($health['categories'])->pluck('checks')->flatten(1);
            $passedKeys = $allChecks->where('passed', true)->pluck('key')->toArray();

            // The only passing check should be staff_super_admin
            $this->assertContains('staff_super_admin', $passedKeys);

            // All setup-data checks should fail
            $this->assertNotContains('ay_exists', $passedKeys);
            $this->assertNotContains('courses_exist', $passedKeys);
            $this->assertNotContains('rooms_exist', $passedKeys);
            $this->assertNotContains('aptitude_exist', $passedKeys);
        });
    }

    public function test_health_detects_active_academic_year_with_open_window(): void
    {
        $this->clearSetupTables();

        AcademicYear::create([
            'academic_year' => '2025-2026',
            'semester' => '1',
            'is_active' => true,
            'application_start_date' => now()->subDays(10),
            'application_end_date' => now()->addDays(30),
        ]);

        $response = $this->actingAs($this->superAdmin())->get(route('admin.setup.index'));

        $response->assertInertia(function ($page) {
            $health = $page->toArray()['props']['health'];
            $ayCategory = collect($health['categories'])->firstWhere('key', 'academic_years');
            $checks = collect($ayCategory['checks']);

            $this->assertTrue($checks->firstWhere('key', 'ay_exists')['passed']);
            $this->assertTrue($checks->firstWhere('key', 'ay_active')['passed']);
            $this->assertTrue($checks->firstWhere('key', 'ay_window_configured')['passed']);
            $this->assertTrue($checks->firstWhere('key', 'ay_window_not_expired')['passed']);
            $this->assertTrue($checks->firstWhere('key', 'ay_window_open')['passed']);
        });
    }

    public function test_health_flags_expired_application_window(): void
    {
        $this->clearSetupTables();

        AcademicYear::create([
            'academic_year' => '2024-2025',
            'semester' => '2',
            'is_active' => true,
            'application_start_date' => now()->subDays(60),
            'application_end_date' => now()->subDays(10),
        ]);

        $response = $this->actingAs($this->superAdmin())->get(route('admin.setup.index'));

        $response->assertInertia(function ($page) {
            $health = $page->toArray()['props']['health'];
            $ayCategory = collect($health['categories'])->firstWhere('key', 'academic_years');
            $checks = collect($ayCategory['checks']);

            $this->assertTrue($checks->firstWhere('key', 'ay_exists')['passed']);
            $this->assertTrue($checks->firstWhere('key', 'ay_active')['passed']);
            $this->assertTrue($checks->firstWhere('key', 'ay_window_configured')['passed']);
            // Expired window should be flagged
            $this->assertFalse($checks->firstWhere('key', 'ay_window_not_expired')['passed'], 'Expired window should fail.');
            $this->assertFalse($checks->firstWhere('key', 'ay_window_open')['passed'], 'Expired window should not be open.');
            // Message should mention expiry
            $this->assertStringContainsString('expired', $checks->firstWhere('key', 'ay_window_not_expired')['message']);
        });
    }

    public function test_health_flags_future_application_window(): void
    {
        $this->clearSetupTables();

        AcademicYear::create([
            'academic_year' => '2026-2027',
            'semester' => '1',
            'is_active' => true,
            'application_start_date' => now()->addDays(30),
            'application_end_date' => now()->addDays(90),
        ]);

        $response = $this->actingAs($this->superAdmin())->get(route('admin.setup.index'));

        $response->assertInertia(function ($page) {
            $health = $page->toArray()['props']['health'];
            $ayCategory = collect($health['categories'])->firstWhere('key', 'academic_years');
            $checks = collect($ayCategory['checks']);

            $this->assertTrue($checks->firstWhere('key', 'ay_window_configured')['passed']);
            $this->assertTrue($checks->firstWhere('key', 'ay_window_not_expired')['passed'], 'Future window is not expired.');
            $this->assertFalse($checks->firstWhere('key', 'ay_window_open')['passed'], 'Future window is not open yet.');
        });
    }

    public function test_health_detects_inactive_academic_year(): void
    {
        $this->clearSetupTables();

        AcademicYear::create([
            'academic_year' => '2025-2026',
            'semester' => '1',
            'is_active' => false,
        ]);

        $response = $this->actingAs($this->superAdmin())->get(route('admin.setup.index'));

        $response->assertInertia(function ($page) {
            $health = $page->toArray()['props']['health'];
            $ayCategory = collect($health['categories'])->firstWhere('key', 'academic_years');
            $checks = collect($ayCategory['checks']);

            $this->assertTrue($checks->firstWhere('key', 'ay_exists')['passed']);
            $this->assertFalse($checks->firstWhere('key', 'ay_active')['passed']);
        });
    }

    public function test_health_detects_missing_window_on_active_year(): void
    {
        $this->clearSetupTables();

        AcademicYear::create([
            'academic_year' => '2025-2026',
            'semester' => '1',
            'is_active' => true,
            // No window dates
        ]);

        $response = $this->actingAs($this->superAdmin())->get(route('admin.setup.index'));

        $response->assertInertia(function ($page) {
            $health = $page->toArray()['props']['health'];
            $ayCategory = collect($health['categories'])->firstWhere('key', 'academic_years');
            $checks = collect($ayCategory['checks']);

            $this->assertTrue($checks->firstWhere('key', 'ay_active')['passed']);
            $this->assertFalse($checks->firstWhere('key', 'ay_window_configured')['passed'], 'Missing window dates should fail.');
        });
    }

    public function test_health_detects_courses_all_deactivated(): void
    {
        $this->clearSetupTables();

        Course::create(['name' => 'BSIT', 'code' => 'BSIT', 'is_active' => false]);
        Course::create(['name' => 'BSCS', 'code' => 'BSCS', 'is_active' => false]);

        $response = $this->actingAs($this->superAdmin())->get(route('admin.setup.index'));

        $response->assertInertia(function ($page) {
            $health = $page->toArray()['props']['health'];
            $category = collect($health['categories'])->firstWhere('key', 'courses');
            $checks = collect($category['checks']);

            $this->assertTrue($checks->firstWhere('key', 'courses_exist')['passed']);
            $this->assertFalse($checks->firstWhere('key', 'courses_active')['passed']);
            $this->assertStringContainsString('deactivated', $checks->firstWhere('key', 'courses_active')['message']);
        });
    }

    public function test_health_detects_rooms_with_capacity(): void
    {
        $this->clearSetupTables();

        Room::create(['name' => 'Room 101', 'building' => 'Main', 'floor' => '1', 'capacity' => 30, 'is_active' => true]);
        Room::create(['name' => 'Room 102', 'building' => 'Main', 'floor' => '1', 'capacity' => 25, 'is_active' => true]);

        $response = $this->actingAs($this->superAdmin())->get(route('admin.setup.index'));

        $response->assertInertia(function ($page) {
            $health = $page->toArray()['props']['health'];
            $category = collect($health['categories'])->firstWhere('key', 'rooms');
            $checks = collect($category['checks']);

            $activeCheck = $checks->firstWhere('key', 'rooms_active');
            $this->assertTrue($activeCheck['passed']);
            $this->assertStringContainsString('55', $activeCheck['message']); // 30 + 25 capacity
        });
    }

    public function test_health_detects_aptitude_areas_missing_formulas(): void
    {
        $this->clearSetupTables();

        AptitudeArea::create(['name' => 'Verbal', 'code' => 'VRB', 'max_items' => 50, 'formula' => '(x/max_items)*100', 'scoring_method' => 'formula', 'is_active' => true, 'display_order' => 1]);
        AptitudeArea::create(['name' => 'Numerical', 'code' => 'NUM', 'max_items' => 40, 'formula' => null, 'scoring_method' => 'formula', 'is_active' => true, 'display_order' => 2]);

        $response = $this->actingAs($this->superAdmin())->get(route('admin.setup.index'));

        $response->assertInertia(function ($page) {
            $health = $page->toArray()['props']['health'];
            $category = collect($health['categories'])->firstWhere('key', 'aptitude_areas');
            $checks = collect($category['checks']);

            $formulaCheck = $checks->firstWhere('key', 'aptitude_scoring');
            $this->assertFalse($formulaCheck['passed'], '1 of 2 areas missing scoring.');
            $this->assertStringContainsString('1 active area(s) missing', $formulaCheck['message']);
            $this->assertStringContainsString('Numerical (formula)', $formulaCheck['message']);
        });
    }

    public function test_health_detects_aptitude_areas_missing_conversion_tables(): void
    {
        $this->clearSetupTables();

        AptitudeArea::create(['name' => 'Verbal', 'code' => 'VRB', 'max_items' => 50, 'formula' => '(x/max_items)*100', 'scoring_method' => 'formula', 'is_active' => true, 'display_order' => 1]);
        AptitudeArea::create(['name' => 'Numerical', 'code' => 'NUM', 'max_items' => 40, 'scoring_method' => 'conversion_table', 'is_active' => true, 'display_order' => 2]);

        $response = $this->actingAs($this->superAdmin())->get(route('admin.setup.index'));

        $response->assertInertia(function ($page) {
            $health = $page->toArray()['props']['health'];
            $category = collect($health['categories'])->firstWhere('key', 'aptitude_areas');
            $checks = collect($category['checks']);

            $formulaCheck = $checks->firstWhere('key', 'aptitude_scoring');
            $this->assertFalse($formulaCheck['passed']);
            $this->assertStringContainsString('Numerical (conversion table)', $formulaCheck['message']);
        });
    }

    public function test_health_detects_staff_roles(): void
    {
        // The superAdmin() creates 1 super admin.
        // No registrar or test admin yet.
        $user = $this->superAdmin();

        $response = $this->actingAs($user)->get(route('admin.setup.index'));

        $response->assertInertia(function ($page) {
            $health = $page->toArray()['props']['health'];
            $category = collect($health['categories'])->firstWhere('key', 'staff');
            $checks = collect($category['checks']);

            $this->assertTrue($checks->firstWhere('key', 'staff_super_admin')['passed']);
            // Registrar and test_admin depend on whether seeder created them
            // At minimum, verify the checks exist and have correct structure
            $this->assertNotNull($checks->firstWhere('key', 'staff_registrar'));
            $this->assertNotNull($checks->firstWhere('key', 'staff_test_admin'));
        });
    }

    public function test_health_detects_privacy_policy(): void
    {
        $this->clearSetupTables();

        PrivacyPolicy::create(['title' => 'Privacy v1', 'content' => 'Content here', 'is_active' => true]);

        $response = $this->actingAs($this->superAdmin())->get(route('admin.setup.index'));

        $response->assertInertia(function ($page) {
            $health = $page->toArray()['props']['health'];
            $ppCategory = collect($health['categories'])->firstWhere('key', 'privacy_policies');

            $allPassed = collect($ppCategory['checks'])->every('passed', true);
            $this->assertTrue($allPassed, 'Privacy policy checks should all pass.');
        });
    }

    public function test_fully_configured_system_shows_high_health(): void
    {
        $this->clearSetupTables();

        // Academic Year — active with open window
        AcademicYear::create([
            'academic_year' => '2025-2026',
            'semester' => '1',
            'is_active' => true,
            'application_start_date' => now()->subDays(10),
            'application_end_date' => now()->addDays(30),
        ]);

        Course::create(['name' => 'BSIT', 'code' => 'BSIT', 'is_active' => true]);
        Room::create(['name' => 'Room 101', 'building' => 'Main', 'floor' => '1', 'capacity' => 30, 'is_active' => true]);
        AptitudeArea::create(['name' => 'Verbal', 'code' => 'VRB', 'max_items' => 50, 'formula' => '(x/max_items)*100', 'is_active' => true, 'display_order' => 1]);
        ResultSheetTemplate::create(['name' => 'Default', 'mode' => 'html', 'paper_size' => 'a4', 'orientation' => 'portrait', 'logical_unit' => 'full', 'content' => '<p>Result</p>', 'is_active' => true]);
        SystemSetting::set('admission_slip_enabled', true);
        SystemSetting::set('admission_slip_html_template', '<p>Slip</p>');
        PrivacyPolicy::create(['title' => 'Privacy v1', 'content' => 'Content', 'is_active' => true]);

        // Also create required staff roles
        $registrar = User::factory()->create();
        $registrar->roles()->attach(Role::where('name', 'registrar_administrator')->first());
        $testAdmin = User::factory()->create();
        $testAdmin->roles()->attach(Role::where('name', 'test_administrator')->first());

        SystemSetting::set('institution.name', 'ISPSC');
        SystemSetting::set('institution.exam_name', 'ISPSC College Admission Test');
        SystemSetting::set('institution.personnel.guidance_counselor.name', 'Dr. Counselor');

        RatingScale::create([
            'name' => 'Standard',
            'ranges' => [['min' => 0, 'max' => 100, 'label' => 'Pass']],
            'is_default' => true,
        ]);

        $response = $this->actingAs($this->superAdmin())->get(route('admin.setup.index'));

        $response->assertInertia(function ($page) {
            $health = $page->toArray()['props']['health'];
            $this->assertSame(100, $health['overall']['percentage']);
        });
    }

    public function test_admin_can_access_setup_health(): void
    {
        $response = $this->actingAs($this->admin())->get(route('admin.setup.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->has('health.categories')
            ->has('health.overall')
        );
    }

    public function test_health_categories_include_all_keys(): void
    {
        $response = $this->actingAs($this->superAdmin())->get(route('admin.setup.index'));

        $response->assertInertia(function ($page) {
            $health = $page->toArray()['props']['health'];
            $keys = collect($health['categories'])->pluck('key')->toArray();

            $this->assertContains('academic_years', $keys);
            $this->assertContains('courses', $keys);
            $this->assertContains('rooms', $keys);
            $this->assertContains('aptitude_areas', $keys);
            $this->assertContains('result_templates', $keys);
            $this->assertContains('admission_templates', $keys);
            $this->assertContains('privacy_policies', $keys);
            $this->assertContains('staff', $keys);
            $this->assertContains('institution', $keys);
            $this->assertContains('rating_scales', $keys);
        });
    }

    public function test_each_check_has_required_fields(): void
    {
        $response = $this->actingAs($this->superAdmin())->get(route('admin.setup.index'));

        $response->assertInertia(function ($page) {
            $health = $page->toArray()['props']['health'];

            foreach ($health['categories'] as $category) {
                $this->assertArrayHasKey('key', $category);
                $this->assertArrayHasKey('label', $category);
                $this->assertArrayHasKey('href', $category);
                $this->assertArrayHasKey('checks', $category);

                foreach ($category['checks'] as $check) {
                    $this->assertArrayHasKey('key', $check, "Missing 'key' in {$category['key']}");
                    $this->assertArrayHasKey('label', $check, "Missing 'label' in {$category['key']}");
                    $this->assertArrayHasKey('passed', $check, "Missing 'passed' in {$category['key']}");
                    $this->assertArrayHasKey('severity', $check, "Missing 'severity' in {$category['key']}");
                    $this->assertArrayHasKey('message', $check, "Missing 'message' in {$category['key']}");
                    $this->assertContains($check['severity'], ['critical', 'important', 'optional'], "Invalid severity in {$category['key']}/{$check['key']}");
                }
            }
        });
    }
}
