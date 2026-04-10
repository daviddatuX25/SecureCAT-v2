<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

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
        $count = static::where('reference_number', 'like', $prefix.'%')->count();

        return $prefix.str_pad((string) ($count + 1), 5, '0', STR_PAD_LEFT);
    }
}
