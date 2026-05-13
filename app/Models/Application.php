<?php

namespace App\Models;

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
    ];

    protected $casts = [
        'birthdate' => 'date',
        'processed_at' => 'datetime',
        'submitted_at' => 'datetime',
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

            if ($session->status === ExamSession::STATUS_DRAFT) {
                return 'draft_scheduled';
            }

            if (in_array($session->status, [ExamSession::STATUS_PUBLISHED, ExamSession::STATUS_IN_PROGRESS, ExamSession::STATUS_COMPLETED], true)) {
                if ($pivot && $pivot->attendance_status === 'present') {
                    if ($pivot->submission_status === 'submitted') {
                        $hasScores = $applicant->relationLoaded('applicantScores')
                            ? $applicant->applicantScores->isNotEmpty()
                            : $applicant->applicantScores()->exists();

                        if ($hasScores) {
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
