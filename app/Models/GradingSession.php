<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GradingSession extends Model
{
    public const STATUS_OPEN = 'open';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_REVIEW = 'review';
    public const STATUS_FINALIZED = 'finalized';

    protected $fillable = [
        'exam_session_id',
        'status',
        'opened_at',
        'opened_by',
        'finalized_at',
        'finalized_by',
    ];

    protected function casts(): array
    {
        return [
            'opened_at' => 'datetime',
            'finalized_at' => 'datetime',
        ];
    }

    public function examSession(): BelongsTo
    {
        return $this->belongsTo(ExamSession::class);
    }

    public function openedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'opened_by');
    }

    public function finalizedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'finalized_by');
    }

    public function applicants(): BelongsToMany
    {
        return $this->belongsToMany(Applicant::class, 'grading_session_applicant')
            ->withPivot('result_printed_at')
            ->withTimestamps();
    }

    public function applicantScores(): HasMany
    {
        return $this->hasMany(ApplicantScore::class);
    }

    public function consultationSchedules(): HasMany
    {
        return $this->hasMany(ConsultationSchedule::class);
    }

    public function workflowStatus(): string
    {
        return $this->status === self::STATUS_FINALIZED ? 'completed' : 'in_progress';
    }
}
