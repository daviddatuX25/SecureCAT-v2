<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExamDomain extends Model
{
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
        ];
    }

    public function applicantScores(): HasMany
    {
        return $this->hasMany(ApplicantScore::class, 'domain_id');
    }

    public function decisionRules(): HasMany
    {
        return $this->hasMany(DecisionRule::class, 'domain_id');
    }
}
