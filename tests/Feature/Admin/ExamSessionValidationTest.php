<?php

namespace Tests\Feature\Admin;

use App\Models\Role;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExamSessionValidationTest extends TestCase
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
        $user->roles()->attach(Role::where('name', 'registrar_administrator')->first());

        return $user;
    }

    private function createRoom(): Room
    {
        return Room::factory()->create();
    }

    public function test_store_rejects_past_date(): void
    {
        $room = $this->createRoom();

        $response = $this->actingAs($this->admin())->post(route('admin.test-scheduling.store'), [
            'room_id' => $room->id,
            'date' => '2020-01-01',
            'start_time' => '09:00',
            'end_time' => '12:00',
        ]);

        $response->assertSessionHasErrors('date');
    }

    public function test_store_accepts_today_date(): void
    {
        $room = $this->createRoom();

        $response = $this->actingAs($this->admin())->post(route('admin.test-scheduling.store'), [
            'room_id' => $room->id,
            'date' => now()->toDateString(),
            'start_time' => '09:00',
            'end_time' => '12:00',
        ]);

        $response->assertSessionHasNoErrors('date');
    }

    public function test_store_rejects_invalid_start_time_format(): void
    {
        $room = $this->createRoom();

        $response = $this->actingAs($this->admin())->post(route('admin.test-scheduling.store'), [
            'room_id' => $room->id,
            'date' => now()->toDateString(),
            'start_time' => '25:99',
            'end_time' => '12:00',
        ]);

        $response->assertSessionHasErrors('start_time');
    }

    public function test_store_accepts_valid_start_time_format(): void
    {
        $room = $this->createRoom();

        $response = $this->actingAs($this->admin())->post(route('admin.test-scheduling.store'), [
            'room_id' => $room->id,
            'date' => now()->toDateString(),
            'start_time' => '08:00',
            'end_time' => '12:00',
        ]);

        $response->assertSessionHasNoErrors('start_time');
    }

    public function test_store_rejects_invalid_end_time_format(): void
    {
        $room = $this->createRoom();

        $response = $this->actingAs($this->admin())->post(route('admin.test-scheduling.store'), [
            'room_id' => $room->id,
            'date' => now()->toDateString(),
            'start_time' => '09:00',
            'end_time' => 'not-a-time',
        ]);

        $response->assertSessionHasErrors('end_time');
    }
}
