<?php

namespace Tests\Feature\Admin;

use App\Models\Course;
use App\Models\Department;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RoleSeeder::class);

        $this->admin = User::factory()->create();
        $this->admin->roles()->attach(Role::where('name', 'admin')->first());

        $this->superAdmin = User::factory()->create();
        $this->superAdmin->roles()->attach(Role::where('name', 'super_admin')->first());
    }

    public function test_admin_can_list_courses(): void
    {
        Course::factory()->count(2)->create();

        $response = $this->actingAs($this->admin)->get('/admin/courses');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Courses/Index')
            ->has('courses')
        );
    }

    public function test_super_admin_can_list_courses(): void
    {
        $response = $this->actingAs($this->superAdmin)->get('/admin/courses');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Admin/Courses/Index'));
    }

    public function test_admin_can_create_course(): void
    {
        $dept = Department::factory()->create(['code' => 'CIT']);

        $response = $this->actingAs($this->admin)->post('/admin/courses', [
            'department_id' => $dept->id,
            'name' => 'Bachelor of Science in Information Technology',
            'code' => 'BSIT',
            'quota' => 200,
            'score_cutoff' => 75.50,
        ]);

        $response->assertRedirect(route('admin.courses.index'));
        $this->assertDatabaseHas('courses', [
            'department_id' => $dept->id,
            'name' => 'Bachelor of Science in Information Technology',
            'code' => 'BSIT',
            'quota' => 200,
            'is_active' => true,
        ]);
    }

    public function test_admin_can_update_course(): void
    {
        $dept1 = Department::factory()->create(['code' => 'CIT']);
        $dept2 = Department::factory()->create(['code' => 'CAS']);
        $course = Course::factory()->create([
            'department_id' => $dept1->id,
            'name' => 'Old Name',
            'code' => 'OLD',
            'quota' => 100,
            'score_cutoff' => 70.00,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->put("/admin/courses/{$course->id}", [
            'department_id' => $dept2->id,
            'name' => 'New Name',
            'code' => 'NEW',
            'quota' => null,
            'score_cutoff' => 80.25,
            'is_active' => false,
        ]);

        $response->assertRedirect(route('admin.courses.index'));
        $course->refresh();
        $this->assertSame('New Name', $course->name);
        $this->assertSame('NEW', $course->code);
        $this->assertSame($dept2->id, $course->department_id);
        $this->assertNull($course->quota);
        $this->assertFalse($course->is_active);
    }

    public function test_admin_can_deactivate_course(): void
    {
        $course = Course::factory()->create(['is_active' => true]);

        $response = $this->actingAs($this->admin)->delete("/admin/courses/{$course->id}");

        $response->assertRedirect(route('admin.courses.index'));
        $course->refresh();
        $this->assertFalse($course->is_active);
    }

    public function test_guest_cannot_access_courses(): void
    {
        $response = $this->get('/admin/courses');

        $response->assertRedirect(route('login'));
    }

    public function test_staff_cannot_access_courses(): void
    {
        $staff = User::factory()->create();
        $staff->roles()->attach(Role::where('name', 'staff')->first());

        $response = $this->actingAs($staff)->get('/admin/courses');

        $response->assertStatus(403);
    }

    public function test_course_code_must_be_unique(): void
    {
        Course::factory()->create(['code' => 'BSIT']);
        $dept = Department::factory()->create();

        $response = $this->actingAs($this->admin)->post('/admin/courses', [
            'department_id' => $dept->id,
            'name' => 'Duplicate Course',
            'code' => 'BSIT',
        ]);

        $response->assertSessionHasErrors('code');
    }
}

