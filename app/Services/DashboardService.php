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
        if (! $user->hasAnyRole(['super_admin', 'registrar_administrator'])) {
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
        if (! $user->hasAnyRole(['super_admin', 'proctor', 'test_administrator'])) {
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

        $attendanceQuery = DB::table('exam_session_applicant')
            ->join('exam_sessions', 'exam_sessions.id', '=', 'exam_session_applicant.exam_session_id')
            ->whereIn('exam_sessions.status', [ExamSession::STATUS_IN_PROGRESS, ExamSession::STATUS_COMPLETED])
            ->where('exam_session_applicant.attendance_status', 'pending');

        if ($activeAcademicYear !== null) {
            $attendanceQuery->where('exam_sessions.academic_year_id', $activeAcademicYear->id);
        }

        $attendanceDue = $attendanceQuery->count();

        $submissionsQuery = DB::table('exam_session_applicant')
            ->join('exam_sessions', 'exam_sessions.id', '=', 'exam_session_applicant.exam_session_id')
            ->whereIn('exam_sessions.status', [ExamSession::STATUS_IN_PROGRESS, ExamSession::STATUS_COMPLETED])
            ->where('exam_session_applicant.submission_status', 'pending');

        if ($activeAcademicYear !== null) {
            $submissionsQuery->where('exam_sessions.academic_year_id', $activeAcademicYear->id);
        }

        $submissionsDue = $submissionsQuery->count();

        return [
            [
                'key' => 'sessions_upcoming',
                'label' => 'Upcoming Sessions',
                'value' => $upcoming,
                'href' => '/admin/exam-scheduling',
            ],
            [
                'key' => 'attendance_due',
                'label' => 'Attendance Due',
                'value' => $attendanceDue,
                'href' => '/admin/exam-scheduling',
            ],
            [
                'key' => 'submissions_due',
                'label' => 'Submissions Due',
                'value' => $submissionsDue,
                'href' => '/admin/exam-scheduling',
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
        if (! $user->hasAnyRole(['super_admin', 'test_administrator'])) {
            return [];
        }

        $activeAcademicYear = AcademicYear::active();

        $gradingQuery = GradingSession::query()
            ->whereIn('status', [GradingSession::STATUS_OPEN, GradingSession::STATUS_IN_PROGRESS, GradingSession::STATUS_REVIEW]);

        if ($activeAcademicYear !== null) {
            $gradingQuery->whereHas('examSession', fn ($q) => $q->forAcademicYear($activeAcademicYear));
        }

        $pendingGrading = $gradingQuery->count();

        $releaseQuery = ConsultationSummary::query()
            ->where('status', ConsultationSummary::STATUS_DRAFT);

        if ($activeAcademicYear !== null) {
            $releaseQuery->whereHas('applicant.application', fn ($q) => $q->forAcademicYear($activeAcademicYear));
        }

        $pendingRelease = $releaseQuery->count();

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
