<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExamDomain extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'description',
        'max_items',
        'display_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'max_items' => 'integer',
            'display_order' => 'integer',
        ];
    }

    public function applicantScores(): HasMany
    {
        return $this->hasMany(ApplicantScore::class, 'domain_id');
    }
}
