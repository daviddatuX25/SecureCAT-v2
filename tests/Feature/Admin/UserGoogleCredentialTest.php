<?php

namespace Tests\Feature\Admin;

use App\Models\Role;
use App\Models\User;
use App\Models\UserCredential;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserGoogleCredentialTest extends TestCase
{
    use RefreshDatabase;

    private function superAdmin(): User
    {
        $user = User::factory()->create();
        $role = Role::firstOrCreate(['name' => 'super_admin'], ['display_name' => 'Super Admin']);
        $user->roles()->attach($role);
        return $user;
    }

    public function test_updating_user_email_deletes_existing_google_credential(): void
    {
        $admin = $this->superAdmin();
        $staff = User::factory()->create(['email' => 'old@gmail.com']);

        UserCredential::create([
            'user_id'    => $staff->id,
            'provider'   => UserCredential::PROVIDER_GOOGLE,
            'identifier' => 'sub-old-123',
        ]);

        $this->actingAs($admin)->put(route('admin.users.update', $staff), [
            'name'  => $staff->name,
            'email' => 'new@gmail.com',
            'roles' => [],
        ]);

        $this->assertDatabaseMissing('user_credentials', [
            'user_id'  => $staff->id,
            'provider' => 'google',
        ]);
    }

    public function test_updating_user_email_to_same_value_keeps_credential(): void
    {
        $admin = $this->superAdmin();
        $staff = User::factory()->create(['email' => 'same@gmail.com']);

        UserCredential::create([
            'user_id'    => $staff->id,
            'provider'   => UserCredential::PROVIDER_GOOGLE,
            'identifier' => 'sub-same-456',
        ]);

        $this->actingAs($admin)->put(route('admin.users.update', $staff), [
            'name'  => $staff->name,
            'email' => 'same@gmail.com',
            'roles' => [],
        ]);

        $this->assertDatabaseHas('user_credentials', [
            'user_id'    => $staff->id,
            'provider'   => 'google',
            'identifier' => 'sub-same-456',
        ]);
    }

    public function test_edit_view_receives_google_linked_prop(): void
    {
        $admin = $this->superAdmin();
        $staff = User::factory()->create();

        UserCredential::create([
            'user_id'    => $staff->id,
            'provider'   => UserCredential::PROVIDER_GOOGLE,
            'identifier' => 'sub-edit-789',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.users.edit', $staff));

        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Users/Edit')
            ->where('googleLinked', true)
        );
    }
}
