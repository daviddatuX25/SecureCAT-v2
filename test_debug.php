<?php

use App\Models\User;
use App\Services\DashboardAnalyticsService;
use Illuminate\Contracts\Console\Kernel;

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$service = new DashboardAnalyticsService;
$user = new User(['name' => 'test']);
$user->id = 999;

$methods = [
    'getApplicationTrends',
    'getApplicationStatusDistribution',
    'getCoursePreferenceDistribution',
    'getSessionTrends',
    'getSessionStatusDistribution',
    'getAttendanceTrends',
    'getGradingStatusDistribution',
    'getGradingTurnaround',
    'getUserGrowth',
    'getUserRoleDistribution',
];

foreach ($methods as $method) {
    try {
        $result = $service->$method($user);
        echo "OK: $method\n";
    } catch (Throwable $e) {
        echo "ERROR in $method: ".$e->getMessage().' at '.$e->getFile().':'.$e->getLine()."\n";
    }
}
