<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class PrintJob extends Model
{
    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'user_id',
        'grading_session_id',
        'applicant_ids',
        'copies',
        'status',
        'progress',
        'pdf_path',
        'error_message',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $job) {
            if (empty($job->id)) {
                $job->id = (string) Str::uuid();
            }
        });
    }

    protected function casts(): array
    {
        return [
            'applicant_ids' => 'array',
            'copies' => 'integer',
            'progress' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function gradingSession(): BelongsTo
    {
        return $this->belongsTo(GradingSession::class);
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }
}
