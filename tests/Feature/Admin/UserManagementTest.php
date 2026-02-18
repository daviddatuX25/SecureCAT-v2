<?php

namespace Tests\Feature\Admin;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RoleSeeder::class);
        $this->superAdmin = User::factory()->create();
        $this->superAdmin->roles()->attach(Role::where('name', 'super_admin')->first());
    }

    public function test_super_admin_can_list_users(): void
    {
        $response = $this->actingAs($this->superAdmin)->get('/admin/users');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Users/Index')
            ->has('users')
            ->has('roles')
        );
    }

    public function test_super_admin_can_create_user_with_roles(): void
    {
        $response = $this->actingAs($this->superAdmin)->post('/admin/users', [
            'name' => 'New Staff',
            'email' => 'newstaff@example.com',
            'password' => 'Password1!',
            'password_confirmation' => 'Password1!',
            'roles' => ['staff', 'admin'],
        ]);

        $response->assertRedirect(route('admin.users.index'));
        $this->assertDatabaseHas('users', ['email' => 'newstaff@example.com']);
        $user = User::where('email', 'newstaff@example.com')->first();
        $this->assertCount(2, $user->roles);
    }

    public function test_super_admin_cannot_delete_self(): void
    {
        $response = $this->actingAs($this->superAdmin)->delete("/admin/users/{$this->superAdmin->id}");

        $response->assertStatus(403);
        $this->assertDatabaseHas('users', ['id' => $this->superAdmin->id]);
    }

    public function test_super_admin_can_delete_other_user(): void
    {
        $other = User::factory()->create();
        $other->roles()->attach(Role::where('name', 'staff')->first());

        $response = $this->actingAs($this->superAdmin)->delete("/admin/users/{$other->id}");

        $response->assertRedirect(route('admin.users.index'));
        $this->assertDatabaseMissing('users', ['id' => $other->id]);
    }
}
