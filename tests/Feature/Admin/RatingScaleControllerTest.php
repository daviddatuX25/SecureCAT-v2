<?php

namespace Tests\Feature\Admin;

use App\Models\RatingScale;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RatingScaleControllerTest extends TestCase
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

    public function test_index_lists_rating_scales(): void
    {
        RatingScale::create([
            'name' => 'Scale A',
            'ranges' => [['min' => 0, 'max' => 50, 'label' => 'Low']],
            'is_default' => true,
        ]);
        RatingScale::create([
            'name' => 'Scale B',
            'ranges' => [['min' => 0, 'max' => 50, 'label' => 'Low']],
            'is_default' => false,
        ]);

        $response = $this->actingAs($this->superAdmin())
            ->get(route('admin.rating-scales.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/RatingScales/Index')
            ->has('rating_scales', 2)
        );
    }

    public function test_create_rating_scale_with_valid_ranges(): void
    {
        $response = $this->actingAs($this->superAdmin())
            ->post(route('admin.rating-scales.store'), [
                'name' => 'Standard Scale',
                'ranges' => [
                    ['min' => 0, 'max' => 49, 'label' => 'Below Average'],
                    ['min' => 50, 'max' => 74, 'label' => 'Average'],
                    ['min' => 75, 'max' => 100, 'label' => 'Above Average'],
                ],
                'is_default' => true,
            ]);

        $response->assertRedirect(route('admin.rating-scales.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('rating_scales', ['name' => 'Standard Scale', 'is_default' => true]);
    }

    public function test_create_validates_ranges(): void
    {
        $response = $this->actingAs($this->superAdmin())
            ->post(route('admin.rating-scales.store'), [
                'name' => 'Bad Scale',
                'ranges' => [],
            ]);

        $response->assertSessionHasErrors('ranges');
    }

    public function test_update_rating_scale(): void
    {
        $scale = RatingScale::create([
            'name' => 'Old Name',
            'ranges' => [['min' => 0, 'max' => 100, 'label' => 'All']],
            'is_default' => false,
        ]);

        $response = $this->actingAs($this->superAdmin())
            ->put(route('admin.rating-scales.update', $scale), [
                'name' => 'New Name',
                'ranges' => [['min' => 0, 'max' => 100, 'label' => 'Everything']],
                'is_default' => false,
            ]);

        $response->assertRedirect(route('admin.rating-scales.index'));
        $response->assertSessionHas('success');
        $scale->refresh();
        $this->assertSame('New Name', $scale->name);
    }

    public function test_setting_default_unsets_previous_default(): void
    {
        RatingScale::create([
            'name' => 'First',
            'ranges' => [['min' => 0, 'max' => 100, 'label' => 'All']],
            'is_default' => true,
        ]);
        $second = RatingScale::create([
            'name' => 'Second',
            'ranges' => [['min' => 0, 'max' => 100, 'label' => 'All']],
            'is_default' => false,
        ]);

        $this->actingAs($this->superAdmin())
            ->put(route('admin.rating-scales.update', $second), [
                'name' => 'Second',
                'ranges' => [['min' => 0, 'max' => 100, 'label' => 'All']],
                'is_default' => true,
            ]);

        $this->assertFalse(RatingScale::where('name', 'First')->first()->is_default);
        $this->assertTrue($second->fresh()->is_default);
    }

    public function test_delete_rating_scale(): void
    {
        $scale = RatingScale::create([
            'name' => 'Deletable',
            'ranges' => [['min' => 0, 'max' => 100, 'label' => 'All']],
            'is_default' => false,
        ]);

        $response = $this->actingAs($this->superAdmin())
            ->delete(route('admin.rating-scales.destroy', $scale));

        $response->assertRedirect(route('admin.rating-scales.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('rating_scales', ['id' => $scale->id]);
    }

    public function test_rating_for_method(): void
    {
        $scale = RatingScale::create([
            'name' => 'Test Scale',
            'ranges' => [
                ['min' => 0, 'max' => 49, 'label' => 'Low'],
                ['min' => 50, 'max' => 74, 'label' => 'Medium'],
                ['min' => 75, 'max' => 100, 'label' => 'High'],
            ],
            'is_default' => false,
        ]);

        $this->assertSame('High', $scale->ratingFor(85));
        $this->assertSame('Medium', $scale->ratingFor(60));
        $this->assertSame('Low', $scale->ratingFor(25));
    }

    public function test_rating_for_out_of_range(): void
    {
        $scale = RatingScale::create([
            'name' => 'Test Scale',
            'ranges' => [
                ['min' => 0, 'max' => 49, 'label' => 'Low'],
                ['min' => 50, 'max' => 100, 'label' => 'High'],
            ],
            'is_default' => false,
        ]);

        $this->assertSame('—', $scale->ratingFor(101));
    }
}
