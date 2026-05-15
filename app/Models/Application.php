<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;

class Application extends Model
{
    use HasFactory;

    protected $fillable = [
        'academic_year_id',
        'reference_number',
        'first_name',
        'middle_name',
        'last_name',
        'suffix',
        'birthdate',
        'age',
        'sex',
        'email',
        'phone',
        'address_line',
        'city',
        'province',
        'zip_code',
        'course_preference_1',
        'course_preference_2',
        'course_preference_3',
        'status',
        'processed_by',
        'processed_at',
        'rejection_reason',
        'appointment_id',
        'submitted_at',
        'gwa',
        'admission_slip_printed_at',
    ];

    protected $casts = [
        'birthdate' => 'date',
        'processed_at' => 'datetime',
        'submitted_at' => 'datetime',
        'admission_slip_printed_at' => 'datetime',
    ];

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'academic_year_id');
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function scopeForAcademicYear($query, $academicYear): void
    {
        if ($academicYear instanceof AcademicYear) {
            $query->where('academic_year_id', $academicYear->id);
        } elseif ($academicYear !== null) {
            $query->where('academic_year_id', $academicYear);
        }
    }

    public function coursePreference1(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'course_preference_1');
    }

    public function coursePreference2(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'course_preference_2');
    }

    public function coursePreference3(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'course_preference_3');
    }

    public function applicant(): HasOne
    {
        return $this->hasOne(Applicant::class);
    }

    public static function nextReferenceNumber(): string
    {
        $year = date('Y');
        $prefix = "APP-{$year}-";

        return DB::transaction(function () use ($prefix) {
            $count = static::where('reference_number', 'like', $prefix.'%')->count();

            return $prefix.str_pad((string) ($count + 1), 5, '0', STR_PAD_LEFT);
        });
    }

    /**
     * Get the exam session status if this application has an assigned session.
     * Returns null if no session assigned.
     */
    public function assignedSessionStatus(): ?string
    {
        $applicant = $this->applicant;
        if (! $applicant) {
            return null;
        }

        $examSession = $applicant->examSessions()->first();

        return $examSession?->status;
    }

    /**
     * Check if the applicant can edit this application.
     * Rules:
     * - Application status must be 'accepted'
     * - No exam session assigned OR assigned session is 'draft'
     */
    /**
     * Get the applicant's pipeline status for display in admin lists.
     * Returns the most advanced milestone reached.
     *
     * Pipeline order:
     *   f2f:  pending → accepted → draft_scheduled → scheduled → printed → attended → submitted → graded → released → dismissed
     *   direct: pending → accepted → scored → graded → released → dismissed
     */
    public function pipelineStatus(): string
    {
        // Dismissed overrides everything
        if ($this->status === 'dismissed') {
            return 'dismissed';
        }

        if ($this->status === 'pending') {
            return 'pending';
        }

        // status === 'accepted'
        $applicant = $this->applicant;
        if (! $applicant) {
            return 'accepted';
        }

        $examSessions = $applicant->relationLoaded('examSessions')
            ? $applicant->examSessions
            : $applicant->examSessions()->get();

        if ($examSessions->isEmpty()) {
            return 'accepted';
        }

        // Filter out cancelled sessions — they don't block progression
        $activeSessions = $examSessions->reject(
            fn (ExamSession $s) => $s->status === ExamSession::STATUS_CANCELLED
        );

        if ($activeSessions->isEmpty()) {
            return 'accepted';
        }

        // Sort by status priority (most advanced first)
        $statusPriority = [
            ExamSession::STATUS_COMPLETED => 4,
            ExamSession::STATUS_IN_PROGRESS => 3,
            ExamSession::STATUS_PUBLISHED => 2,
            ExamSession::STATUS_DRAFT => 1,
        ];

        $sortedSessions = $activeSessions->sortByDesc(
            fn (ExamSession $s) => $statusPriority[$s->status] ?? 0
        );

        // Examine each session to find the most advanced pipeline state
        $bestStatus = 'accepted';
        foreach ($sortedSessions as $session) {
            $pivot = $session->pivot;

            // Direct assessment: skip scheduling milestones, go straight to scored/graded/released
            if ($session->type === ExamSession::TYPE_DIRECT) {
                $hasScores = $applicant->relationLoaded('applicantScores')
                    ? $applicant->applicantScores->isNotEmpty()
                    : $applicant->applicantScores()->exists();

                if ($hasScores) {
                    $summary = $applicant->relationLoaded('consultationSummary')
                        ? $applicant->consultationSummary
                        : $applicant->consultationSummary;
                    if ($summary && $summary->status === ConsultationSummary::STATUS_RELEASED) {
                        return 'released';
                    }

                    return 'graded';
                }

                if ($bestStatus === 'accepted') {
                    $bestStatus = 'scored';
                }

                continue;
            }

            // Scheduled (f2f) session — full pipeline
            if ($session->status === ExamSession::STATUS_DRAFT) {
                if ($this->admission_slip_printed_at) {
                    return 'printed';
                }

                return 'draft_scheduled';
            }

            if (in_array($session->status, [ExamSession::STATUS_PUBLISHED, ExamSession::STATUS_IN_PROGRESS, ExamSession::STATUS_COMPLETED], true)) {
                if ($this->admission_slip_printed_at && $bestStatus === 'accepted') {
                    $bestStatus = 'printed';
                }

                if ($pivot && $pivot->attendance_status === 'present') {
                    if ($pivot->submission_status === 'submitted') {
                        $hasScores = $applicant->relationLoaded('applicantScores')
                            ? $applicant->applicantScores->isNotEmpty()
                            : $applicant->applicantScores()->exists();

                        if ($hasScores) {
                            $summary = $applicant->relationLoaded('consultationSummary')
                                ? $applicant->consultationSummary
                                : $applicant->consultationSummary;
                            if ($summary && $summary->status === ConsultationSummary::STATUS_RELEASED) {
                                return 'released';
                            }

                            return 'graded';
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

        return $bestStatus;
    }

    /**
     * Get structured pipeline details with milestone timestamps.
     */
    public function pipelineDetails(): array
    {
        $status = $this->pipelineStatus();

        $milestones = [];
        $isF2f = false;
        $isDirect = false;

        // Accepted milestone
        if ($this->status !== 'pending') {
            $milestones['accepted'] = ['at' => $this->processed_at?->toIso8601String()];
        }

        // Session-related milestones
        $applicant = $this->applicant;
        if ($applicant) {
            $examSessions = $applicant->relationLoaded('examSessions')
                ? $applicant->examSessions
                : $applicant->examSessions()->get();

            $activeSessions = $examSessions->reject(
                fn (ExamSession $s) => $s->status === ExamSession::STATUS_CANCELLED
            );

            $statusPriority = [
                ExamSession::STATUS_COMPLETED => 4,
                ExamSession::STATUS_IN_PROGRESS => 3,
                ExamSession::STATUS_PUBLISHED => 2,
                ExamSession::STATUS_DRAFT => 1,
            ];

            $sortedSessions = $activeSessions->sortByDesc(
                fn (ExamSession $s) => $statusPriority[$s->status] ?? 0
            );

            foreach ($sortedSessions as $session) {
                if ($session->type === ExamSession::TYPE_DIRECT) {
                    $isDirect = true;

                    // Direct assessment: skip scheduling milestones, only include scored/graded/released
                    $milestones['scored'] = [
                        'at' => $session->created_at?->toIso8601String(),
                        'session_label' => 'Direct Assessment #'.$session->id,
                    ];

                    $hasScores = $applicant->relationLoaded('applicantScores')
                        ? $applicant->applicantScores->isNotEmpty()
                        : $applicant->applicantScores()->exists();

                    if ($hasScores) {
                        $milestones['graded'] = ['at' => null];

                        $summary = $applicant->relationLoaded('consultationSummary')
                            ? $applicant->consultationSummary
                            : $applicant->consultationSummary;
                        if ($summary && $summary->status === ConsultationSummary::STATUS_RELEASED) {
                            $milestones['released'] = ['at' => $summary->released_at?->toIso8601String()];
                        }
                    }

                    break;
                }

                // Scheduled (f2f) session — full pipeline
                $isF2f = true;

                $milestones['scheduled'] = [
                    'at' => $session->created_at?->toIso8601String(),
                    'session_date' => $session->date,
                    'session_label' => 'Session #'.$session->id,
                ];

                // Printed milestone (f2f/scheduled type only)
                if ($isF2f && $this->admission_slip_printed_at) {
                    $milestones['printed'] = ['at' => $this->admission_slip_printed_at?->toIso8601String()];
                }

                $pivot = $session->pivot;
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

                            // Released milestone
                            $summary = $applicant->relationLoaded('consultationSummary')
                                ? $applicant->consultationSummary
                                : $applicant->consultationSummary;
                            if ($summary && $summary->status === ConsultationSummary::STATUS_RELEASED) {
                                $milestones['released'] = ['at' => $summary->released_at?->toIso8601String()];
                            }
                        }
                    }
                }

                break; // Only record milestones from the most advanced session
            }
        }

        return [
            'status' => $status,
            'milestones' => $milestones,
            'is_f2f' => $isF2f,
            'is_direct' => $isDirect,
        ];
    }

    public function isEditableByApplicant(): bool
    {
        // Must be accepted first
        if ($this->status !== 'accepted') {
            return false;
        }

        // Must have an applicant record
        if (! $this->applicant) {
            return false;
        }

        $sessionStatus = $this->assignedSessionStatus();

        // No session assigned → editable
        if ($sessionStatus === null) {
            return true;
        }

        // Session is draft → editable
        if ($sessionStatus === ExamSession::STATUS_DRAFT) {
            return true;
        }

        // Session is published/in_progress/completed/cancelled → locked
        return false;
    }
}
