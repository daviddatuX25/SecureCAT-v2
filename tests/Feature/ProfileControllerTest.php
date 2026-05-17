<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Models\UserCredential;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfileControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    private function staffUser(): User
    {
        $user = User::factory()->create([
            'password' => Hash::make('Password1'),
        ]);
        $user->roles()->attach(Role::where('name', 'staff')->first());

        return $user;
    }

    private function adminUser(): User
    {
        $user = User::factory()->create([
            'password' => Hash::make('Password1'),
        ]);
        $user->roles()->attach(Role::where('name', 'super_admin')->first());

        return $user;
    }

    // ── Profile Edit Page ──

    public function test_profile_page_is_displayed(): void
    {
        $user = $this->staffUser();

        $response = $this->actingAs($user)->get('/profile');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Profile/Edit')
            ->has('user')
            ->where('user.name', $user->name)
            ->where('user.email', $user->email)
            ->has('googleLinked')
        );
    }

    public function test_profile_page_requires_authentication(): void
    {
        $response = $this->get('/profile');

        $response->assertRedirect('/login');
    }

    // ── Update Profile Info ──

    public function test_user_can_update_name(): void
    {
        $user = $this->staffUser();

        $response = $this->actingAs($user)->put('/profile', [
            'name' => 'New Name',
            'email' => $user->email,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Profile updated.');
        $this->assertEquals('New Name', $user->fresh()->name);
    }

    public function test_user_can_update_email(): void
    {
        $user = $this->staffUser();

        $response = $this->actingAs($user)->put('/profile', [
            'name' => $user->name,
            'email' => 'newemail@example.com',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Profile updated.');
        $this->assertEquals('newemail@example.com', $user->fresh()->email);
    }

    public function test_email_change_clears_email_verified_at(): void
    {
        $user = $this->staffUser();
        $user->email_verified_at = now();
        $user->save();

        $this->actingAs($user)->put('/profile', [
            'name' => $user->name,
            'email' => 'changed@example.com',
        ]);

        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_email_change_unlinks_google_credential(): void
    {
        $user = $this->staffUser();
        UserCredential::create([
            'user_id' => $user->id,
            'provider' => UserCredential::PROVIDER_GOOGLE,
            'identifier' => 'google-123',
        ]);

        $this->assertTrue($user->hasGoogleLinked());

        $this->actingAs($user)->put('/profile', [
            'name' => $user->name,
            'email' => 'different@example.com',
        ]);

        $this->assertFalse($user->fresh()->hasGoogleLinked());
    }

    public function test_same_email_does_not_unlink_google(): void
    {
        $user = $this->staffUser();
        UserCredential::create([
            'user_id' => $user->id,
            'provider' => UserCredential::PROVIDER_GOOGLE,
            'identifier' => 'google-456',
        ]);

        $this->actingAs($user)->put('/profile', [
            'name' => 'Updated Name',
            'email' => $user->email,
        ]);

        $this->assertTrue($user->fresh()->hasGoogleLinked());
    }

    public function test_no_changes_returns_no_changes_message(): void
    {
        $user = $this->staffUser();

        $response = $this->actingAs($user)->put('/profile', [
            'name' => $user->name,
            'email' => $user->email,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'No changes to save.');
    }

    public function test_email_must_be_unique(): void
    {
        $user = $this->staffUser();
        $other = User::factory()->create(['email' => 'taken@example.com']);

        $response = $this->actingAs($user)->put('/profile', [
            'name' => $user->name,
            'email' => 'taken@example.com',
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_name_is_required(): void
    {
        $user = $this->staffUser();

        $response = $this->actingAs($user)->put('/profile', [
            'name' => '',
            'email' => $user->email,
        ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_email_must_be_valid(): void
    {
        $user = $this->staffUser();

        $response = $this->actingAs($user)->put('/profile', [
            'name' => $user->name,
            'email' => 'not-an-email',
        ]);

        $response->assertSessionHasErrors('email');
    }

    // ── Update Password ──

    public function test_user_can_change_password(): void
    {
        $user = $this->staffUser();

        $response = $this->actingAs($user)->put('/profile/password', [
            'current_password' => 'Password1',
            'password' => 'NewPassword2',
            'password_confirmation' => 'NewPassword2',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Password changed.');
        $this->assertTrue(Hash::check('NewPassword2', $user->fresh()->password));
    }

    public function test_current_password_must_be_correct(): void
    {
        $user = $this->staffUser();

        $response = $this->actingAs($user)->put('/profile/password', [
            'current_password' => 'WrongPassword',
            'password' => 'NewPassword2',
            'password_confirmation' => 'NewPassword2',
        ]);

        $response->assertSessionHasErrors('current_password');
    }

    public function test_new_password_must_be_confirmed(): void
    {
        $user = $this->staffUser();

        $response = $this->actingAs($user)->put('/profile/password', [
            'current_password' => 'Password1',
            'password' => 'NewPassword2',
            'password_confirmation' => 'Mismatch99',
        ]);

        $response->assertSessionHasErrors('password');
    }

    public function test_new_password_must_meet_minimum_length(): void
    {
        $user = $this->staffUser();

        $response = $this->actingAs($user)->put('/profile/password', [
            'current_password' => 'Password1',
            'password' => 'short',
            'password_confirmation' => 'short',
        ]);

        $response->assertSessionHasErrors('password');
    }

    public function test_password_change_requires_authentication(): void
    {
        $response = $this->put('/profile/password', [
            'current_password' => 'Password1',
            'password' => 'NewPassword2',
            'password_confirmation' => 'NewPassword2',
        ]);

        $response->assertRedirect('/login');
    }

    // ── Role-agnostic access ──

    public function test_admin_can_access_profile(): void
    {
        $user = $this->adminUser();

        $response = $this->actingAs($user)->get('/profile');

        $response->assertOk();
    }

    public function test_admin_can_update_profile(): void
    {
        $user = $this->adminUser();

        $response = $this->actingAs($user)->put('/profile', [
            'name' => 'Admin Updated',
            'email' => $user->email,
        ]);

        $response->assertRedirect();
        $this->assertEquals('Admin Updated', $user->fresh()->name);
    }

    public function test_admin_can_change_password(): void
    {
        $user = $this->adminUser();

        $response = $this->actingAs($user)->put('/profile/password', [
            'current_password' => 'Password1',
            'password' => 'AdminNewPass2',
            'password_confirmation' => 'AdminNewPass2',
        ]);

        $response->assertRedirect();
        $this->assertTrue(Hash::check('AdminNewPass2', $user->fresh()->password));
    }
}
