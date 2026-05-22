<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\ApplicationStatusChanged;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class NotificationSystemTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_fetch_notifications(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'web');

        // Create some notifications
        $user->notifications()->create([
            'id' => Str::uuid()->toString(),
            'type' => ApplicationStatusChanged::class,
            'data' => ['message' => 'Test notification'],
        ]);

        $response = $this->getJson('/notifications');

        $response->assertOk()
            ->assertJsonStructure(['notifications', 'unread_count']);
    }

    public function test_notifications_sorted_newest_first(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'web');

        $older = $user->notifications()->create([
            'id' => Str::uuid()->toString(),
            'type' => ApplicationStatusChanged::class,
            'data' => ['message' => 'Older'],
            'created_at' => now()->subHour(),
        ]);
        $newer = $user->notifications()->create([
            'id' => Str::uuid()->toString(),
            'type' => ApplicationStatusChanged::class,
            'data' => ['message' => 'Newer'],
            'created_at' => now(),
        ]);

        $response = $this->getJson('/notifications');

        $response->assertOk();
        $notifications = $response->json('notifications');
        $this->assertEquals('Newer', $notifications[0]['message']);
        $this->assertEquals('Older', $notifications[1]['message']);
    }

    public function test_mark_notification_as_read(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'web');

        $notification = $user->notifications()->create([
            'id' => Str::uuid()->toString(),
            'type' => ApplicationStatusChanged::class,
            'data' => ['message' => 'Test'],
        ]);

        $response = $this->postJson("/notifications/{$notification->id}/read");

        $response->assertOk()->assertJson(['ok' => true]);
        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_cannot_mark_another_users_notification_as_read(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $this->actingAs($user1, 'web');

        $notification = $user2->notifications()->create([
            'id' => Str::uuid()->toString(),
            'type' => ApplicationStatusChanged::class,
            'data' => ['message' => 'Test'],
        ]);

        $response = $this->postJson("/notifications/{$notification->id}/read");

        $response->assertForbidden();
    }

    public function test_mark_all_notifications_as_read(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'web');

        $user->notifications()->createMany([
            ['id' => Str::uuid()->toString(), 'type' => ApplicationStatusChanged::class, 'data' => ['message' => 'Test 1']],
            ['id' => Str::uuid()->toString(), 'type' => ApplicationStatusChanged::class, 'data' => ['message' => 'Test 2']],
        ]);

        $response = $this->postJson('/notifications/read-all');

        $response->assertOk()->assertJson(['ok' => true]);
        $this->assertEquals(0, $user->unreadNotifications()->count());
    }
}
