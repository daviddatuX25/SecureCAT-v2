<?php

namespace App\Console\Commands;

use App\Models\Application;
use App\Models\ConsultationSummary;
use App\Models\ExamSession;
use App\Services\ApplicationPipelineService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SyncPipelineStatusesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pipeline:sync-statuses
                            {--dry-run : Print what would change without writing to DB}
                            {--chunk=100 : Number of applications to process per chunk}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backfill pipeline_status and pipeline_milestones for all applications. Safe to re-run.';

    /**
     * Execute the console command.
     */
    public function handle(ApplicationPipelineService $pipeline): int
    {
        $isDryRun = (bool) $this->option('dry-run');
        $chunkSize = (int) $this->option('chunk');

        if ($isDryRun) {
            $this->warn('[DRY RUN] No changes will be written.');
        }

        $this->info('Syncing pipeline_status for all applications…');
        $count = 0;
        $changed = 0;

        Application::with([
            'applicant.examSessions',
            'applicant.applicantScores',
            'applicant.consultationSummary',
        ])->chunkById($chunkSize, function ($applications) use ($pipeline, $isDryRun, &$count, &$changed) {
            foreach ($applications as $app) {
                [$status, $milestones] = $this->compute($app);
                $count++;

                if ($isDryRun) {
                    $this->line("  [{$app->id}] {$app->pipeline_status} → {$status}");
                } else {
                    $pipeline->forceSet($app, $status, $milestones);
                    if ($app->pipeline_status !== $status) {
                        $changed++;
                    }
                }
            }
        });

        $this->info("Done. Processed {$count} applications".($isDryRun ? ' (dry run).' : ", updated {$changed}."));

        return Command::SUCCESS;
    }

    /**
     * Compute the expected pipeline status and milestones for an application.
     *
     * This is a self-contained copy of the original pipelineStatus() / pipelineDetails()
     * computation logic, inlined here so the command remains usable even after those
     * accessor methods are simplified to DB-backed wrappers.
     *
     * @return array{0: string, 1: array<string, mixed>} [$status, $milestones]
     */
    private function compute(Application $app): array
    {
        $milestones = [];

        // ── Dismissed overrides everything ──────────────────────────────────
        if ($app->status === 'dismissed') {
            $milestones['dismissed'] = ['at' => $app->processed_at?->toIso8601String()];

            return ['dismissed', $milestones];
        }

        // ── Pending ─────────────────────────────────────────────────────────
        if ($app->status === 'pending') {
            return ['pending', []];
        }

        // ── Accepted milestone ───────────────────────────────────────────────
        $milestones['accepted'] = ['at' => $app->processed_at?->toIso8601String()];

        $applicant = $app->applicant;
        if (! $applicant) {
            return ['accepted', $milestones];
        }

        $examSessions = $applicant->relationLoaded('examSessions')
            ? $applicant->examSessions
            : $applicant->examSessions()->get();

        if ($examSessions->isEmpty()) {
            return ['accepted', $milestones];
        }

        // Filter out cancelled sessions
        $activeSessions = $examSessions->reject(
            fn (ExamSession $s) => $s->status === ExamSession::STATUS_CANCELLED
        );

        if ($activeSessions->isEmpty()) {
            return ['accepted', $milestones];
        }

        $statusPriority = [
            ExamSession::STATUS_COMPLETED => 4,
            ExamSession::STATUS_IN_PROGRESS => 3,
            ExamSession::STATUS_PUBLISHED => 2,
            ExamSession::STATUS_DRAFT => 1,
        ];

        $sortedSessions = $activeSessions->sortByDesc(
            fn (ExamSession $s) => $statusPriority[$s->status] ?? 0
        );

        $bestStatus = 'accepted';

        foreach ($sortedSessions as $session) {
            $pivot = $session->pivot;

            // ── Direct assessment ────────────────────────────────────────────
            if ($session->type === ExamSession::TYPE_DIRECT) {
                $hasScores = $applicant->relationLoaded('applicantScores')
                    ? $applicant->applicantScores->isNotEmpty()
                    : $applicant->applicantScores()->exists();

                if ($hasScores) {
                    $milestones['scored'] = ['at' => $session->created_at?->toIso8601String()];

                    $summary = $applicant->consultationSummary;
                    if ($summary && $summary->status === ConsultationSummary::STATUS_RELEASED) {
                        $milestones['released'] = ['at' => $summary->released_at?->toIso8601String()];
                        $milestones['graded'] = ['at' => null];

                        return ['released', $milestones];
                    }

                    $milestones['graded'] = ['at' => null];

                    return ['graded', $milestones];
                }

                if ($bestStatus === 'accepted') {
                    $bestStatus = 'scored';
                    $milestones['scored'] = ['at' => $session->created_at?->toIso8601String()];
                }

                continue;
            }

            // ── F2F / Scheduled session ──────────────────────────────────────
            if ($session->status === ExamSession::STATUS_DRAFT) {
                $milestones['draft_scheduled'] = ['at' => $session->created_at?->toIso8601String(), 'session_id' => $session->id];

                if ($app->admission_slip_printed_at) {
                    $milestones['printed'] = ['at' => $app->admission_slip_printed_at->toIso8601String()];

                    return ['printed', $milestones];
                }

                return ['draft_scheduled', $milestones];
            }

            if (in_array($session->status, [ExamSession::STATUS_PUBLISHED, ExamSession::STATUS_IN_PROGRESS, ExamSession::STATUS_COMPLETED], true)) {
                $milestones['scheduled'] = [
                    'at' => $session->created_at?->toIso8601String(),
                    'session_id' => $session->id,
                ];

                if ($app->admission_slip_printed_at && $bestStatus === 'accepted') {
                    $bestStatus = 'printed';
                    $milestones['printed'] = ['at' => $app->admission_slip_printed_at->toIso8601String()];
                }

                if ($pivot && $pivot->attendance_status === 'present') {
                    $attendedAt = $pivot->attendance_marked_at
                        ? (is_string($pivot->attendance_marked_at) ? Carbon::parse($pivot->attendance_marked_at) : $pivot->attendance_marked_at)
                        : null;
                    $milestones['attended'] = ['at' => $attendedAt?->toIso8601String()];

                    if ($pivot->submission_status === 'submitted') {
                        $submittedAt = $pivot->submitted_at
                            ? (is_string($pivot->submitted_at) ? Carbon::parse($pivot->submitted_at) : $pivot->submitted_at)
                            : null;
                        $milestones['submitted'] = ['at' => $submittedAt?->toIso8601String()];

                        $hasScores = $applicant->relationLoaded('applicantScores')
                            ? $applicant->applicantScores->isNotEmpty()
                            : $applicant->applicantScores()->exists();

                        if ($hasScores) {
                            $milestones['graded'] = ['at' => null];

                            $summary = $applicant->consultationSummary;
                            if ($summary && $summary->status === ConsultationSummary::STATUS_RELEASED) {
                                $milestones['released'] = ['at' => $summary->released_at?->toIso8601String()];

                                return ['released', $milestones];
                            }

                            return ['graded', $milestones];
                        }

                        $bestStatus = 'submitted';

                        continue;
                    }

                    $bestStatus = 'attended';

                    continue;
                }

                if ($bestStatus === 'accepted') {
                    $bestStatus = 'scheduled';
                }
            }
        }

        return [$bestStatus, $milestones];
    }
}
