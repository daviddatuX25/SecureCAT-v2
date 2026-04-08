<?php

namespace Tests\Feature\Admin;

use App\Models\Course;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseActivationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RoleSeeder::class);
    }

    private function admin(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('name', 'admin')->first());
        return $user;
    }

    public function test_admin_can_activate_inactive_course(): void
    {
        $course = Course::create([
            'name'      => 'Bachelor of Science in IT',
            'code'      => 'BSIT',
            'is_active' => false,
        ]);

        $response = $this->actingAs($this->admin())
            ->post(route('admin.courses.activate', $course));

        $response->assertRedirect(route('admin.courses.index'));
        $response->assertSessionHas('success');
        $this->assertTrue($course->fresh()->is_active);
    }

    public function test_proctor_cannot_activate_course(): void
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('name', 'proctor')->first());

        $course = Course::create([
            'name'      => 'Bachelor of Science in IT',
            'code'      => 'BSIT',
            'is_active' => false,
        ]);

        $response = $this->actingAs($user)
            ->post(route('admin.courses.activate', $course));

        $response->assertStatus(403);
        $this->assertFalse($course->fresh()->is_active);
    }
}
