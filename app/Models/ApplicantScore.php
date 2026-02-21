<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApplicantScore extends Model
{
    protected $fillable = [
        'grading_session_id',
        'applicant_id',
        'domain_id',
        'raw_score',
        'max_score',
        'normalized_score',
        'scored_by',
        'scored_at',
    ];

    protected function casts(): array
    {
        return [
            'scored_at' => 'datetime',
        ];
    }

    public function gradingSession(): BelongsTo
    {
        return $this->belongsTo(GradingSession::class);
    }

    public function applicant(): BelongsTo
    {
        return $this->belongsTo(Applicant::class);
    }

    public function domain(): BelongsTo
    {
        return $this->belongsTo(ExamDomain::class, 'domain_id');
    }

    public function scoredByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'scored_by');
    }
}
