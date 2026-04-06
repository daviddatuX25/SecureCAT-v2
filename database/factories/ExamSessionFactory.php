<?php

namespace Database\Factories;

use App\Models\ExamSession;
use App\Models\Room;
use App\Models\Season;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ExamSessionFactory extends Factory
{
    protected $model = ExamSession::class;

    public function definition(): array
    {
        return [
            'season_id' => Season::factory(),
            'room_id' => Room::factory(),
            'date' => $this->faker->dateTimeBetween('+1 days', '+30 days')->format('Y-m-d'),
            'start_time' => '09:00',
            'end_time' => '12:00',
            'status' => 'draft',
            'created_by' => User::factory(),
        ];
    }
}
