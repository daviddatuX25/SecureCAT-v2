<?php

namespace App\Services;

use App\Models\Application;
use App\Models\ConsultationSummary;
use App\Models\GradingSession;
use App\Models\Room;
use App\Models\Season;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DashboardService
{
    /**
     * Stats for the dashboard. Each item: key, label, value, href (optional).
     * Keys must match the Dashboard frontend iconMap.
     *
     * @return array<int, array{key: string, label: string, value: int|string, href?: string}>
     */
    public function getStatsForUser(User $user): array
    {
        $stats = [];

        $activeSeason = Season::active();

        if ($user->hasAnyRole(['super_admin', 'admin', 'staff', 'test_administrator'])) {
            $pendingQuery = Application::query()->where('status', 'pending');
            if ($activeSeason !== null) {
                $pendingQuery->forSeason($activeSeason);
            }
            $pending = $pendingQuery->count();
            $stats[] = [
                'key' => 'applications_pending',
                'label' => 'Applications pending',
                'value' => $pending,
                'href' => '/applications',
            ];
        }

        if ($user->hasAnyRole(['super_admin', 'admin', 'proctor'])) {
            $examSessionsCount = $this->getExamSessionsCount($activeSeason?->id);
            $stats[] = [
                'key' => 'exam_sessions',
                'label' => $user->hasRole('proctor') ? 'My sessions' : 'Exam sessions',
                'value' => $examSessionsCount,
                'href' => '/admin/exam-sessions',
            ];
        }

        if ($user->hasAnyRole(['super_admin', 'admin'])) {
            $roomsCount = Room::query()->where('is_active', true)->count();
            $stats[] = [
                'key' => 'rooms',
                'label' => 'Rooms',
                'value' => $roomsCount,
                'href' => '/admin/rooms',
            ];
        }

        if ($user->hasAnyRole(['super_admin', 'test_administrator'])) {
            $activeGrading = GradingSession::query()
                ->whereIn('status', [GradingSession::STATUS_OPEN, GradingSession::STATUS_IN_PROGRESS])
                ->count();
            $stats[] = [
                'key' => 'grading_sessions_active',
                'label' => 'Grading sessions active',
                'value' => $activeGrading,
                'href' => '/grading',
            ];
        }

        if ($user->hasAnyRole(['super_admin', 'test_administrator'])) {
            $consultationPending = ConsultationSummary::query()->where('status', ConsultationSummary::STATUS_PENDING)->count();
            $stats[] = [
                'key' => 'consultation_pending',
                'label' => 'Consultation pending',
                'value' => $consultationPending,
                'href' => '/consultation',
            ];
        }

        return $stats;
    }

    private function getExamSessionsCount(?int $seasonId = null): int
    {
        if (! Schema::hasTable('exam_sessions')) {
            return 0;
        }

        $query = DB::table('exam_sessions');
        if ($seasonId !== null) {
            $query->where('season_id', $seasonId);
        }
        return (int) $query->count();
    }
}
