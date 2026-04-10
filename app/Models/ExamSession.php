<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ExamSession extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'season_id',
        'room_id',
        'date',
        'start_time',
        'end_time',
        'status',
        'published_at',
        'started_at',
        'closed_at',
        'score_release_date',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'published_at' => 'datetime',
            'started_at' => 'datetime',
            'closed_at' => 'datetime',
            'score_release_date' => 'date',
        ];
    }

    public function season(): BelongsTo
    {
        return $this->belongsTo(Season::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function scopeForSeason($query, $season): void
    {
        if ($season instanceof Season) {
            $query->where('season_id', $season->id);
        } elseif ($season !== null) {
            $query->where('season_id', $season);
        }
    }

    /** Users assigned as proctors for this session (proctor role). */
    public function proctors(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'exam_session_user')
            ->withTimestamps();
    }

    /** Applicants assigned to this exam session. Per 08-API-SPEC-PHASE1: one applicant per session globally. */
    public function applicants(): BelongsToMany
    {
        return $this->belongsToMany(Applicant::class, 'exam_session_applicant')
            ->withPivot([
                'id',
                'attendance_status',
                'attendance_marked_at',
                'attendance_marked_by',
                'submission_status',
                'submitted_at',
                'submitted_to',
            ])
            ->withTimestamps();
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function gradingSession(): HasOne
    {
        return $this->hasOne(GradingSession::class);
    }

    /**
     * Check if another session exists in the same room on the same date with overlapping time.
     * Exclude given session ID when updating.
     */
    public static function hasRoomConflict(int $roomId, string $date, string $startTime, ?string $endTime, ?int $excludeSessionId = null): bool
    {
        $query = self::query()
            ->where('room_id', $roomId)
            ->whereDate('date', $date);

        if ($excludeSessionId !== null) {
            $query->where('id', '!=', $excludeSessionId);
        }

        $sessions = $query->get(['id', 'start_time', 'end_time']);

        $start = Carbon::parse($startTime)->format('H:i:s');
        $end = $endTime ? Carbon::parse($endTime)->format('H:i:s') : '23:59:59';

        foreach ($sessions as $session) {
            $otherStart = Carbon::parse($session->start_time)->format('H:i:s');
            $otherEnd = $session->end_time
                ? Carbon::parse($session->end_time)->format('H:i:s')
                : '23:59:59';
            if ($start < $otherEnd && $end > $otherStart) {
                return true;
            }
        }

        return false;
    }

    /**
     * Whether the given time (or now) falls within the allowed window to start this session.
     * Window: (date + start_time - grace_before) through (date + end_time + grace_after).
     * Grace values are fixed for now; will be dynamic from admin settings in the future (task deferred).
     */
    public function isWithinStartWindow(?Carbon $now = null): bool
    {
        $now = ($now ?? now())->copy()->setTimezone(config('app.timezone', 'UTC'));

        // Fixed grace minutes; will be dynamic from admin settings in the future (task deferred).
        $graceMinutesBeforeStart = 15;
        $graceMinutesAfterEnd = 30;

        $dateStr = $this->date->format('Y-m-d');
        $windowStart = Carbon::parse($dateStr.' '.$this->start_time, config('app.timezone', 'UTC'))->subMinutes($graceMinutesBeforeStart);
        $windowEnd = Carbon::parse($dateStr.' '.($this->end_time ?? '23:59'), config('app.timezone', 'UTC'))->addMinutes($graceMinutesAfterEnd);

        return $now->between($windowStart, $windowEnd);
    }

    /**
     * True when the current time is between the session's start_time and end_time.
     * If no end_time is set, any time after start is considered within window.
     */
    public function isWithinExamWindow(?Carbon $now = null): bool
    {
        $tz = config('app.timezone', 'UTC');
        $now ??= Carbon::now($tz);
        $sessionDate = Carbon::parse($this->date)->tz($tz);
        $start = $sessionDate->copy()->setTimeFromTimeString($this->start_time);

        if ($now->lt($start)) {
            return false;
        }

        if (! $this->end_time) {
            return true;
        }

        $end = $sessionDate->copy()->setTimeFromTimeString($this->end_time);

        return $now->lte($end);
    }

    /**
     * True when the current time is past the session's end_time.
     * Returns false when end_time is not set (open-ended sessions never expire).
     */
    public function isPastEndTime(?Carbon $now = null): bool
    {
        if (! $this->end_time) {
            return false;
        }

        $tz = config('app.timezone', 'UTC');
        $now ??= Carbon::now($tz);
        $sessionDate = Carbon::parse($this->date)->tz($tz);
        $end = $sessionDate->copy()->setTimeFromTimeString($this->end_time);

        return $now->gt($end);
    }
}
