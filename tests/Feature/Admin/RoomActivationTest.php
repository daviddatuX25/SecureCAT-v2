<?php

namespace Tests\Feature\Admin;

use App\Models\Role;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoomActivationTest extends TestCase
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

    public function test_admin_can_activate_inactive_room(): void
    {
        $room = Room::create([
            'name' => 'Room 101',
            'building' => 'Main Building',
            'capacity' => 30,
            'is_active' => false,
        ]);

        $response = $this->actingAs($this->admin())
            ->post(route('admin.rooms.activate', $room));

        $response->assertRedirect(route('admin.rooms.index'));
        $response->assertSessionHas('success');
        $this->assertTrue($room->fresh()->is_active);
    }

    public function test_proctor_cannot_activate_room(): void
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('name', 'proctor')->first());

        $room = Room::create([
            'name' => 'Room 101',
            'building' => 'Main Building',
            'capacity' => 30,
            'is_active' => false,
        ]);

        $response = $this->actingAs($user)
            ->post(route('admin.rooms.activate', $room));

        $response->assertStatus(403);
        $this->assertFalse($room->fresh()->is_active);
    }
}
