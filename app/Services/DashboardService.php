<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\Application;
use App\Models\ConsultationSummary;
use App\Models\ExamSession;
use App\Models\GradingSession;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DashboardService
{
    /**
     * Application-level KPI stats (admin / super_admin only).
     *
     * @return array<int, array{key: string, label: string, value: int|string, href?: string}>
     */
    public function getApplicationStats(User $user): array
    {
        if (! $user->hasAnyRole(['super_admin', 'admin'])) {
            return [];
        }

        $activeAcademicYear = AcademicYear::active();

        $base = Application::query();
        if ($activeAcademicYear !== null) {
            $base->forAcademicYear($activeAcademicYear);
        }

        return [
            [
                'key' => 'applications_pending',
                'label' => 'Pending',
                'value' => (clone $base)->where('status', 'pending')->count(),
                'href' => '/applications',
            ],
            [
                'key' => 'applications_accepted',
                'label' => 'Accepted',
                'value' => (clone $base)->where('status', 'accepted')->count(),
                'href' => '/applications',
            ],
            [
                'key' => 'applications_dismissed',
                'label' => 'Dismissed',
                'value' => (clone $base)->where('status', 'dismissed')->count(),
                'href' => '/applications',
            ],
        ];
    }

    /**
     * Session-level KPI stats (proctor / registrar_administrator / super_admin).
     *
     * @return array<int, array{key: string, label: string, value: int|string, href?: string}>
     */
    public function getSessionStats(User $user): array
    {
        if (! $user->hasAnyRole(['super_admin', 'proctor', 'registrar_administrator'])) {
            return [];
        }

        if (! Schema::hasTable('exam_sessions')) {
            return [];
        }

        $activeAcademicYear = AcademicYear::active();

        $upcomingQuery = ExamSession::query()
            ->whereIn('status', [ExamSession::STATUS_PUBLISHED, ExamSession::STATUS_IN_PROGRESS]);

        if ($activeAcademicYear !== null) {
            $upcomingQuery->where('academic_year_id', $activeAcademicYear->id);
        }

        $upcoming = $upcomingQuery->count();

        $attendanceDue = DB::table('exam_session_applicant')
            ->join('exam_sessions', 'exam_sessions.id', '=', 'exam_session_applicant.exam_session_id')
            ->whereIn('exam_sessions.status', [ExamSession::STATUS_IN_PROGRESS, ExamSession::STATUS_COMPLETED])
            ->where('exam_session_applicant.attendance_status', 'pending')
            ->count();

        $submissionsDue = DB::table('exam_session_applicant')
            ->join('exam_sessions', 'exam_sessions.id', '=', 'exam_session_applicant.exam_session_id')
            ->whereIn('exam_sessions.status', [ExamSession::STATUS_IN_PROGRESS, ExamSession::STATUS_COMPLETED])
            ->where('exam_session_applicant.submission_status', 'pending')
            ->count();

        return [
            [
                'key' => 'sessions_upcoming',
                'label' => 'Upcoming Sessions',
                'value' => $upcoming,
                'href' => '/admin/test-scheduling',
            ],
            [
                'key' => 'attendance_due',
                'label' => 'Attendance Due',
                'value' => $attendanceDue,
                'href' => '/admin/test-scheduling',
            ],
            [
                'key' => 'submissions_due',
                'label' => 'Submissions Due',
                'value' => $submissionsDue,
                'href' => '/admin/test-scheduling',
            ],
        ];
    }

    /**
     * Grading + release KPI stats (registrar_administrator / super_admin only).
     *
     * @return array<int, array{key: string, label: string, value: int|string, href?: string}>
     */
    public function getGradingStats(User $user): array
    {
        if (! $user->hasAnyRole(['super_admin', 'registrar_administrator'])) {
            return [];
        }

        $pendingGrading = GradingSession::query()
            ->whereIn('status', [GradingSession::STATUS_OPEN, GradingSession::STATUS_IN_PROGRESS, GradingSession::STATUS_REVIEW])
            ->count();

        $pendingRelease = ConsultationSummary::query()
            ->where('status', ConsultationSummary::STATUS_DRAFT)
            ->count();

        return [
            [
                'key' => 'grading_pending',
                'label' => 'Pending Grading',
                'value' => $pendingGrading,
                'href' => '/grading',
            ],
            [
                'key' => 'release_pending',
                'label' => 'Pending Release',
                'value' => $pendingRelease,
                'href' => '/release',
            ],
        ];
    }
}
