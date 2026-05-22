<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Services\DashboardAnalyticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_analytics_service_methods_dont_throw(): void
    {
        $role = Role::query()->create(['name' => 'super_admin', 'display_name' => 'Super Admin']);
        $user = User::factory()->create();
        $user->roles()->attach($role);

        $service = new DashboardAnalyticsService;

        // Test each method - none should throw
        $service->getApplicationTrends($user);
        $service->getApplicationStatusDistribution($user);
        $service->getCoursePreferenceDistribution($user);
        $service->getSessionTrends($user);
        $service->getSessionStatusDistribution($user);
        $service->getAttendanceTrends($user);
        $service->getGradingStatusDistribution($user);
        $service->getGradingTurnaround($user);
        $service->getUserGrowth();
        $service->getUserRoleDistribution();

        $this->assertTrue(true);
    }
}
