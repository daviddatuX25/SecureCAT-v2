<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\ExamSession;
use App\Notifications\ExamSessionReminder;
use Illuminate\Console\Command;

class SendExamReminders extends Command
{
    protected $signature = 'notifications:exam-reminder {--days=1}';

    protected $description = 'Send exam reminders to applicants with sessions in N days';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $targetDate = now()->addDays($days)->startOfDay();
        $endDate = $targetDate->copy()->endOfDay();

        $sessions = ExamSession::whereBetween('scheduled_at', [$targetDate, $endDate])
            ->where('status', ExamSession::STATUS_PUBLISHED)
            ->with('applicants')
            ->get();

        $totalNotified = 0;
        foreach ($sessions as $session) {
            foreach ($session->applicants as $applicant) {
                $applicant->notify(new ExamSessionReminder($session, $days));
                $totalNotified++;
            }
        }

        $this->info("Sent {$totalNotified} reminders for sessions in {$days} day(s).");

        return Command::SUCCESS;
    }
}
