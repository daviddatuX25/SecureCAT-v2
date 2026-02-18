<?php

namespace Tests\Feature\Admin;

use App\Models\Role;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoomManagementTest extends TestCase
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

    public function test_admin_can_list_rooms(): void
    {
        Room::factory()->count(2)->create();

        $response = $this->actingAs($this->admin)->get('/admin/rooms');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Rooms/Index')
            ->has('rooms')
        );
    }

    public function test_super_admin_can_list_rooms(): void
    {
        $response = $this->actingAs($this->superAdmin)->get('/admin/rooms');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Admin/Rooms/Index'));
    }

    public function test_admin_can_create_room(): void
    {
        $response = $this->actingAs($this->admin)->post('/admin/rooms', [
            'name' => 'Room 101',
            'building' => 'ITBR',
            'floor' => '2nd Floor',
            'capacity' => 30,
            'facilities' => ['projector' => true, 'ac' => true],
        ]);

        $response->assertRedirect(route('admin.rooms.index'));
        $this->assertDatabaseHas('rooms', [
            'name' => 'Room 101',
            'building' => 'ITBR',
            'floor' => '2nd Floor',
            'capacity' => 30,
            'is_active' => true,
        ]);
        $room = Room::where('name', 'Room 101')->first();
        $this->assertSame(['projector' => true, 'ac' => true], $room->facilities);
    }

    public function test_admin_can_update_room(): void
    {
        $room = Room::factory()->create([
            'name' => 'Room 101',
            'building' => 'ITBR',
            'capacity' => 25,
        ]);

        $response = $this->actingAs($this->admin)->put("/admin/rooms/{$room->id}", [
            'name' => 'Room 102',
            'building' => 'ITBR',
            'floor' => '3rd Floor',
            'capacity' => 40,
            'facilities' => ['projector' => true],
        ]);

        $response->assertRedirect(route('admin.rooms.index'));
        $room->refresh();
        $this->assertSame('Room 102', $room->name);
        $this->assertSame(40, $room->capacity);
        $this->assertSame('3rd Floor', $room->floor);
    }

    public function test_admin_can_deactivate_room(): void
    {
        $room = Room::factory()->create(['is_active' => true]);

        $response = $this->actingAs($this->admin)->delete("/admin/rooms/{$room->id}");

        $response->assertRedirect(route('admin.rooms.index'));
        $room->refresh();
        $this->assertFalse($room->is_active);
    }

    public function test_guest_cannot_access_rooms(): void
    {
        $response = $this->get('/admin/rooms');

        $response->assertRedirect(route('login'));
    }

    public function test_staff_cannot_access_rooms(): void
    {
        $staff = User::factory()->create();
        $staff->roles()->attach(Role::where('name', 'staff')->first());

        $response = $this->actingAs($staff)->get('/admin/rooms');

        $response->assertStatus(403);
    }

    public function test_room_name_must_be_unique_per_building(): void
    {
        Room::factory()->create(['name' => 'Room 101', 'building' => 'ITBR']);

        $response = $this->actingAs($this->admin)->post('/admin/rooms', [
            'name' => 'Room 101',
            'building' => 'ITBR',
            'capacity' => 30,
        ]);

        $response->assertSessionHasErrors('name');
        $this->assertDatabaseCount('rooms', 1);
    }

    public function test_same_room_name_in_different_building_is_allowed(): void
    {
        Room::factory()->create(['name' => 'Room 101', 'building' => 'ITBR']);

        $response = $this->actingAs($this->admin)->post('/admin/rooms', [
            'name' => 'Room 101',
            'building' => 'MAIN',
            'capacity' => 30,
        ]);

        $response->assertRedirect(route('admin.rooms.index'));
        $this->assertDatabaseCount('rooms', 2);
    }
}
