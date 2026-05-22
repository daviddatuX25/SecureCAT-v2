<?php

namespace Tests\Unit\Services;

use App\Models\AuditLog;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_log_creates_immutable_audit_entry(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $service = app(AuditService::class);
        $service->log('user.login', null, null, [], ['user_id' => $user->id], 'Staff login');

        $this->assertDatabaseCount('audit_logs', 1);
        $log = AuditLog::first();
        $this->assertSame('user.login', $log->event);
        $this->assertSame('auth', $log->category);
        $this->assertSame('Staff login', $log->summary);
        $this->assertSame($user->getMorphClass(), $log->actor_type);
        $this->assertSame((string) $user->id, (string) $log->actor_id);
    }

    public function test_log_redacts_sensitive_keys(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $service = app(AuditService::class);
        $service->log('user.updated', User::class, $user->id, [
            'password' => 'old-secret',
            'name' => 'Old Name',
        ], [
            'password' => 'new-secret',
            'name' => 'New Name',
        ], 'User updated');

        $log = AuditLog::first();
        $this->assertArrayNotHasKey('password', $log->old_values ?? []);
        $this->assertArrayNotHasKey('password', $log->new_values ?? []);
        $this->assertSame('Old Name', ($log->old_values ?? [])['name'] ?? null);
        $this->assertSame('New Name', ($log->new_values ?? [])['name'] ?? null);
    }
}
