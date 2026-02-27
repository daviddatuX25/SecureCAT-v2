<?php

namespace App\Console\Commands;

use App\Models\Application;
use App\Models\Season;
use Illuminate\Console\Command;

class ExpireSeasonApplications extends Command
{
    protected $signature = 'seasons:expire-applications';

    protected $description = 'Mark pending applications as expired when their season application window has ended.';

    public function handle(): int
    {
        $today = now()->toDateString();

        $seasonIds = Season::query()
            ->whereNotNull('application_end_date')
            ->whereDate('application_end_date', '<', $today)
            ->pluck('id');

        if ($seasonIds->isEmpty()) {
            $this->info('No seasons with a closed application window found.');
            return self::SUCCESS;
        }

        $affected = Application::query()
            ->whereIn('season_id', $seasonIds)
            ->where('status', 'pending')
            ->update(['status' => 'expired']);

        $this->info("Expired {$affected} pending applications.");

        return self::SUCCESS;
    }
}

