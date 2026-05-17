<?php

namespace App\Services;

use App\Models\Application;
use Illuminate\Support\Facades\Log;

class ApplicationPipelineService
{
    /**
     * Ordered pipeline statuses. Higher index = further along in the lifecycle.
     * `dismissed` uses 99 as a sentinel — it is always allowed regardless of current position.
     *
     * @var array<string, int>
     */
    public const PIPELINE_ORDER = [
        'pending' => 0,
        'accepted' => 1,
        'draft_scheduled' => 2,
        'scheduled' => 3,
        'printed' => 4,
        'attended' => 5,
        'submitted' => 6,
        'scored' => 7,
        'graded' => 8,
        'released' => 9,
        'dismissed' => 99,
    ];

    /**
     * Transition an application forward in the pipeline.
     *
     * This is a forward-only guard: the application will only advance if `$newStatus`
     * is further along than the current `pipeline_status`. The only exception is
     * `dismissed`, which is always allowed from any state.
     *
     * Silently no-ops when the application is already at or past the requested status.
     *
     * @param  array<string, mixed>  $milestoneMeta  Extra data stored alongside the milestone timestamp.
     * @return bool True if the status was actually changed, false if it was a no-op.
     */
    public function transition(Application $app, string $newStatus, array $milestoneMeta = []): bool
    {
        if (! array_key_exists($newStatus, self::PIPELINE_ORDER)) {
            Log::warning('ApplicationPipelineService: unknown status attempted', [
                'app_id' => $app->id,
                'new_status' => $newStatus,
            ]);

            return false;
        }

        $currentOrder = self::PIPELINE_ORDER[$app->pipeline_status ?? 'pending'] ?? 0;
        $newOrder = self::PIPELINE_ORDER[$newStatus];

        // dismissed is always allowed; otherwise we only advance
        if ($newStatus !== 'dismissed' && $newOrder <= $currentOrder) {
            return false;
        }

        $prev = $app->pipeline_status;
        $app->updatePipelineStatus($newStatus, $milestoneMeta);

        Log::info('Pipeline status transitioned', [
            'app_id' => $app->id,
            'from' => $prev,
            'to' => $newStatus,
        ]);

        return true;
    }

    /**
     * Force-set a pipeline status, bypassing the forward-only guard.
     *
     * Reserved for the one-time backfill command (`pipeline:sync-statuses`).
     * Do NOT call this from application code — use `transition()` instead.
     *
     * @param  array<string, mixed>  $milestoneMeta
     */
    public function forceSet(Application $app, string $status, array $milestoneMeta = []): void
    {
        $app->updatePipelineStatus($status, $milestoneMeta);
    }
}
