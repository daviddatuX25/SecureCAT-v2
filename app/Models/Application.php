<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Application extends Model
{
    protected $fillable = [
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
    ];

    protected $casts = [
        'birthdate' => 'date',
        'processed_at' => 'datetime',
        'submitted_at' => 'datetime',
    ];

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
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

    public static function nextReferenceNumber(): string
    {
        $year = date('Y');
        $prefix = "APP-{$year}-";
        $count = static::where('reference_number', 'like', $prefix . '%')->count();

        return $prefix . str_pad((string) ($count + 1), 5, '0', STR_PAD_LEFT);
    }
}
