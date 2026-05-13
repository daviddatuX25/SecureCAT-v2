<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\ExamSession;
use App\Models\GradingSession;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DirectAssessmentService
{
    public function __construct(
        private GradingSessionService $gradingService
    ) {}

    public function create(
        AcademicYear $academicYear,
        array $applicantIds,
        User $openedBy,
        ?string $label = null
    ): GradingSession {
        return DB::transaction(function () use ($academicYear, $applicantIds, $openedBy, $label) {
            $examSession = ExamSession::create([
                'academic_year_id' => $academicYear->id,
                'type' => ExamSession::TYPE_DIRECT,
                'label' => $label,
                'status' => ExamSession::STATUS_IN_PROGRESS,
                'room_id' => null,
                'date' => now()->format('Y-m-d'),
                'start_time' => now()->format('H:i:s'),
                'end_time' => null,
                'created_by' => $openedBy->id,
            ]);

            foreach ($applicantIds as $id) {
                $examSession->applicants()->attach($id, [
                    'attendance_status' => 'present',
                    'attendance_marked_at' => now(),
                    'attendance_marked_by' => $openedBy->id,
                ]);
            }

            return $this->gradingService->openForExamSession($examSession, $openedBy);
        });
    }
}
