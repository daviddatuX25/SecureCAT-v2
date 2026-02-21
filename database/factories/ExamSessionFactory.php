<?php

namespace Database\Factories;

use App\Models\ExamSession;
use App\Models\Room;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ExamSession>
 */
class ExamSessionFactory extends Factory
{
    protected $model = ExamSession::class;

    public function definition(): array
    {
        $date = fake()->dateTimeBetween('+1 week', '+3 months');
        $start = fake()->time('H:i');
        $end = fake()->optional(0.7)->time('H:i');

        return [
            'room_id' => Room::factory(),
            'date' => $date->format('Y-m-d'),
            'start_time' => $start,
            'end_time' => $end,
            'status' => ExamSession::STATUS_DRAFT,
            'published_at' => null,
            'started_at' => null,
            'closed_at' => null,
            'score_release_date' => null,
            'created_by' => User::factory(),
        ];
    }
}
