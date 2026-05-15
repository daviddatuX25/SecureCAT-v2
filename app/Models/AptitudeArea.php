<?php

namespace App\Models;

use App\Services\FormulaEvaluator;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AptitudeArea extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'description',
        'max_items',
        'formula',
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
        return $this->hasMany(ApplicantScore::class, 'aptitude_area_id');
    }

    public function computeNormalizedScore(float $rawScore): ?float
    {
        if (! $this->formula) {
            return null;
        }

        return app(FormulaEvaluator::class)->evaluate($this->formula, [
            'x' => $rawScore,
            'max_items' => $this->max_items,
        ]);
    }
}
