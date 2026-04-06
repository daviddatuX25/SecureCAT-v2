<?php

namespace Database\Factories;

use App\Models\Season;
use Illuminate\Database\Eloquent\Factories\Factory;

class SeasonFactory extends Factory
{
    protected $model = Season::class;

    public function definition(): array
    {
        $appStart = $this->faker->dateTimeBetween('-30 days', 'now');
        $appEnd = $this->faker->dateTimeBetween($appStart, '+30 days');

        return [
            'academic_year' => $this->faker->year(),
            'semester' => $this->faker->randomElement(['First', 'Second', 'Summer']),
            'is_active' => false,
            'application_start_date' => $appStart->format('Y-m-d'),
            'application_end_date' => $appEnd->format('Y-m-d'),
        ];
    }
}
