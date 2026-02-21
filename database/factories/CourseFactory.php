<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\Department;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Course>
 */
class CourseFactory extends Factory
{
    protected $model = Course::class;

    public function definition(): array
    {
        $code = strtoupper(fake()->unique()->lexify('C???'));

        return [
            'department_id' => Department::factory(),
            'name' => fake()->sentence(3),
            'code' => $code,
            'quota' => fake()->optional(0.6)->numberBetween(10, 500),
            'score_cutoff' => fake()->optional(0.6)->randomFloat(2, 0, 100),
            'is_active' => true,
        ];
    }
}

