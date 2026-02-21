<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ConsultationSchedule extends Model
{
    protected $fillable = [
        'scheduled_date',
        'grading_session_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_date' => 'date',
        ];
    }

    public function gradingSession(): BelongsTo
    {
        return $this->belongsTo(GradingSession::class);
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function applicants(): BelongsToMany
    {
        return $this->belongsToMany(Applicant::class, 'consultation_schedule_applicant')
            ->withTimestamps();
    }
}
