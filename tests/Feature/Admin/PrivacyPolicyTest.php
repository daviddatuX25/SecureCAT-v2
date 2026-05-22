<?php

namespace Tests\Feature\Admin;

use App\Models\PrivacyPolicy;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrivacyPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    private function staff(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('name', 'staff')->first());

        return $user;
    }

    private function admin(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('name', 'super_admin')->first());

        return $user;
    }

    public function test_index_lists_policies(): void
    {
        PrivacyPolicy::create(['title' => 'Policy A', 'content' => 'Content A', 'is_active' => true]);
        PrivacyPolicy::create(['title' => 'Policy B', 'content' => 'Content B', 'is_active' => false]);

        $response = $this->actingAs($this->admin())
            ->get('/admin/privacy-policies');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/PrivacyPolicies/Index')
            ->has('policies', 2)
        );
    }

    public function test_store_creates_policy(): void
    {
        $response = $this->actingAs($this->admin())
            ->post('/admin/privacy-policies', [
                'title' => 'Test Policy',
                'content' => 'Test content here.',
                'is_active' => true,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('privacy_policies', [
            'title' => 'Test Policy',
            'is_active' => true,
        ]);
    }

    public function test_store_deactivates_others_when_setting_active(): void
    {
        $existing = PrivacyPolicy::create(['title' => 'Old', 'content' => 'Old', 'is_active' => true]);

        $this->actingAs($this->admin())
            ->post('/admin/privacy-policies', [
                'title' => 'New Active',
                'content' => 'New content.',
                'is_active' => true,
            ]);

        $this->assertFalse($existing->fresh()->is_active);
        $this->assertDatabaseHas('privacy_policies', [
            'title' => 'New Active',
            'is_active' => true,
        ]);
    }

    public function test_update_modifies_policy(): void
    {
        $policy = PrivacyPolicy::create(['title' => 'Original', 'content' => 'Original', 'is_active' => false]);

        $response = $this->actingAs($this->admin())
            ->put("/admin/privacy-policies/{$policy->id}", [
                'title' => 'Updated Title',
                'content' => 'Updated content.',
                'is_active' => true,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('privacy_policies', [
            'id' => $policy->id,
            'title' => 'Updated Title',
            'is_active' => true,
        ]);
    }

    public function test_destroy_deletes_policy(): void
    {
        $policy = PrivacyPolicy::create(['title' => 'Delete Me', 'content' => 'Content', 'is_active' => false]);

        $response = $this->actingAs($this->admin())
            ->delete("/admin/privacy-policies/{$policy->id}");

        $response->assertRedirect();
        $this->assertDatabaseMissing('privacy_policies', ['id' => $policy->id]);
    }

    public function test_public_active_endpoint_returns_active_policy(): void
    {
        PrivacyPolicy::create(['title' => 'Active Policy', 'content' => 'Active content.', 'is_active' => true]);

        $response = $this->get('/api/privacy-policy');

        $response->assertOk();
        $response->assertJson([
            'policy' => [
                'title' => 'Active Policy',
                'content' => 'Active content.',
            ],
        ]);
    }

    public function test_public_active_endpoint_returns_null_when_none_active(): void
    {
        $response = $this->get('/api/privacy-policy');

        $response->assertOk();
        $response->assertJson(['policy' => null]);
    }

    public function test_unauthenticated_user_cannot_access_admin_crud(): void
    {
        $response = $this->get('/admin/privacy-policies');
        $response->assertRedirect('/login');
    }

    public function test_staff_can_access_privacy_policies(): void
    {
        $response = $this->actingAs($this->staff())
            ->get('/admin/privacy-policies');

        $response->assertOk();
    }

    public function test_activate_sets_policy_as_active_and_deactivates_others(): void
    {
        $old = PrivacyPolicy::create(['title' => 'Old', 'content' => 'Old', 'is_active' => true]);
        $new = PrivacyPolicy::create(['title' => 'New', 'content' => 'New', 'is_active' => false]);

        $response = $this->actingAs($this->admin())
            ->post("/admin/privacy-policies/{$new->id}/activate");

        $response->assertRedirect();
        $this->assertTrue($new->fresh()->is_active);
        $this->assertFalse($old->fresh()->is_active);
    }

    public function test_deactivate_sets_policy_as_inactive(): void
    {
        $policy = PrivacyPolicy::create(['title' => 'Active', 'content' => 'Content', 'is_active' => true]);

        $response = $this->actingAs($this->admin())
            ->post("/admin/privacy-policies/{$policy->id}/deactivate");

        $response->assertRedirect();
        $this->assertFalse($policy->fresh()->is_active);
    }

    public function test_staff_cannot_activate_policy(): void
    {
        $policy = PrivacyPolicy::create(['title' => 'Test', 'content' => 'Content', 'is_active' => false]);

        $response = $this->actingAs($this->staff())
            ->post("/admin/privacy-policies/{$policy->id}/activate");

        $response->assertForbidden();
        $this->assertFalse($policy->fresh()->is_active);
    }

    public function test_staff_cannot_edit_active_policy(): void
    {
        $policy = PrivacyPolicy::create(['title' => 'Active', 'content' => 'Content', 'is_active' => true]);

        $response = $this->actingAs($this->staff())
            ->get("/admin/privacy-policies/{$policy->id}/edit");

        $response->assertForbidden();
    }

    public function test_staff_cannot_update_active_policy(): void
    {
        $policy = PrivacyPolicy::create(['title' => 'Active', 'content' => 'Content', 'is_active' => true]);

        $response = $this->actingAs($this->staff())
            ->put("/admin/privacy-policies/{$policy->id}", [
                'title' => 'Changed',
                'content' => 'Changed',
                'is_active' => true,
            ]);

        $response->assertForbidden();
        $this->assertEquals('Active', $policy->fresh()->title);
    }

    public function test_staff_cannot_delete_active_policy(): void
    {
        $policy = PrivacyPolicy::create(['title' => 'Active', 'content' => 'Content', 'is_active' => true]);

        $response = $this->actingAs($this->staff())
            ->delete("/admin/privacy-policies/{$policy->id}");

        $response->assertForbidden();
        $this->assertDatabaseHas('privacy_policies', ['id' => $policy->id]);
    }

    public function test_staff_can_edit_inactive_policy(): void
    {
        $policy = PrivacyPolicy::create(['title' => 'Inactive', 'content' => 'Content', 'is_active' => false]);

        $response = $this->actingAs($this->staff())
            ->get("/admin/privacy-policies/{$policy->id}/edit");

        $response->assertOk();
    }

    public function test_admin_can_edit_active_policy(): void
    {
        $policy = PrivacyPolicy::create(['title' => 'Active', 'content' => 'Content', 'is_active' => true]);

        $response = $this->actingAs($this->admin())
            ->put("/admin/privacy-policies/{$policy->id}", [
                'title' => 'Updated by admin',
                'content' => 'Updated content',
                'is_active' => true,
            ]);

        $response->assertRedirect();
        $this->assertEquals('Updated by admin', $policy->fresh()->title);
    }
}
