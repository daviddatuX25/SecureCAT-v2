<?php

namespace App\Console\Commands;

use App\Models\PrintJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class CleanupPrintJobs extends Command
{
    protected $signature = 'app:cleanup-print-jobs {--hours=24 : Delete jobs older than this many hours}';

    protected $description = 'Remove completed/failed print jobs and their PDF files older than N hours';

    public function handle(): int
    {
        $hours = (int) $this->option('hours');
        $cutoff = now()->subHours($hours);

        $jobs = PrintJob::whereIn('status', ['completed', 'failed'])
            ->where('created_at', '<', $cutoff)
            ->get();

        $deleted = 0;
        foreach ($jobs as $job) {
            if ($job->pdf_path) {
                Storage::disk('local')->delete($job->pdf_path);
            }
            $job->delete();
            $deleted++;
        }

        $this->info("Cleaned up {$deleted} print job(s) older than {$hours} hours.");

        return self::SUCCESS;
    }
}
