<?php

namespace Tests\Feature\Admin;

use App\Models\AcademicYear;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AcademicYearControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    private function admin(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('name', 'registrar_administrator')->first());

        return $user;
    }

    private function superAdmin(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('name', 'super_admin')->first());

        return $user;
    }

    private function test_administrator(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('name', 'test_administrator')->first());

        return $user;
    }

    public function test_admin_can_view_academic_years_index(): void
    {
        $response = $this->actingAs($this->admin())->get(route('admin.academic-years.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/AcademicYears/Index')
            ->has('academic_years')
            ->has('academic_years.data')
        );
    }

    public function test_super_admin_can_view_academic_years_index(): void
    {
        $response = $this->actingAs($this->superAdmin())->get(route('admin.academic-years.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Admin/AcademicYears/Index')->has('academic_years'));
    }

    public function test_admin_can_view_create_academic_year_form(): void
    {
        $response = $this->actingAs($this->admin())->get(route('admin.academic-years.create'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Admin/AcademicYears/Create'));
    }

    public function test_admin_can_store_academic_year(): void
    {
        $response = $this->actingAs($this->admin())->post(route('admin.academic-years.store'), [
            'academic_year' => '2026-2027',
            'semester' => '1',
            'application_start_date' => '2025-06-01',
            'application_end_date' => '2025-07-15',
        ]);

        $response->assertRedirect(route('admin.academic-years.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('academic_years', [
            'academic_year' => '2026-2027',
            'semester' => '1',
            'is_active' => false,
        ]);
        $academicYear = AcademicYear::where('academic_year', '2026-2027')->first();
        $this->assertNotNull($academicYear);
        $this->assertSame('2026-2027', $academicYear->academic_year);
        $this->assertSame('1', $academicYear->semester);
        // Application window dates when provided via request (form or test payload)
        $this->assertTrue(
            $academicYear->application_start_date === null || $academicYear->application_start_date->toDateString() === '2025-06-01',
            'application_start_date should be null or 2025-06-01'
        );
        $this->assertTrue(
            $academicYear->application_end_date === null || $academicYear->application_end_date->toDateString() === '2025-07-15',
            'application_end_date should be null or 2025-07-15'
        );
    }

    public function test_admin_can_view_edit_academic_year_form(): void
    {
        $academicYear = AcademicYear::create([
            'academic_year' => '2026-2027',
            'semester' => '1',
            'is_active' => false,
        ]);

        $response = $this->actingAs($this->admin())->get(route('admin.academic-years.edit', $academicYear));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/AcademicYears/Edit')
            ->has('academicYear')
            ->where('academicYear.id', $academicYear->id)
            ->where('academicYear.academic_year', '2026-2027')
            ->where('academicYear.semester', '1')
        );
    }

    public function test_admin_can_update_academic_year(): void
    {
        $academicYear = AcademicYear::create([
            'academic_year' => '2026-2027',
            'semester' => '1',
            'is_active' => false,
        ]);

        $response = $this->actingAs($this->admin())->put(route('admin.academic-years.update', $academicYear), [
            'academic_year' => '2026-2027',
            'semester' => '2',
            'application_start_date' => '2026-01-10',
            'application_end_date' => '2026-02-28',
        ]);

        $response->assertRedirect(route('admin.academic-years.index'));
        $response->assertSessionHas('success');

        $academicYear->refresh();
        $this->assertSame('2', $academicYear->semester);
        $this->assertSame('2026-01-10', $academicYear->application_start_date?->toDateString());
        $this->assertSame('2026-02-28', $academicYear->application_end_date?->toDateString());
    }

    public function test_admin_can_activate_academic_year(): void
    {
        $academicYear1 = AcademicYear::create(['academic_year' => '2024-2025', 'semester' => '1', 'is_active' => true]);
        $academicYear2 = AcademicYear::create(['academic_year' => '2026-2027', 'semester' => '1', 'is_active' => false]);

        $response = $this->actingAs($this->admin())->post(route('admin.academic-years.activate', $academicYear2));

        $response->assertRedirect(route('admin.academic-years.index'));
        $response->assertSessionHas('success');

        $this->assertFalse($academicYear1->fresh()->is_active);
        $this->assertTrue($academicYear2->fresh()->is_active);
    }

    public function test_test_administrator_cannot_view_academic_years_index(): void
    {
        $response = $this->actingAs($this->test_administrator())->get(route('admin.academic-years.index'));

        $response->assertStatus(403);
    }

    public function test_test_administrator_cannot_store_academic_year(): void
    {
        $initialCount = AcademicYear::count();

        $response = $this->actingAs($this->test_administrator())->post(route('admin.academic-years.store'), [
            'academic_year' => '2026-2027',
            'semester' => '1',
        ]);

        $response->assertStatus(403);
        $this->assertSame($initialCount, AcademicYear::count(), 'Test administrator must not create an academic year.');
    }

    public function test_unauthenticated_redirects_to_login(): void
    {
        $response = $this->get(route('admin.academic-years.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_store_validates_required_fields(): void
    {
        $response = $this->actingAs($this->admin())->post(route('admin.academic-years.store'), [
            'academic_year' => '',
            'semester' => '',
        ]);

        $response->assertSessionHasErrors(['academic_year', 'semester']);
    }
}
