<?php

namespace Database\Seeders;

use App\Models\Season;
use Illuminate\Database\Seeder;

class SeasonSeeder extends Seeder
{
    public function run(): void
    {
        $season = Season::updateOrCreate(
            ['academic_year' => '2025-2026', 'semester' => '1'],
            [
                'is_active' => true,
                'application_start_date' => now()->subDays(7)->toDateString(),
                'application_end_date' => now()->addDays(30)->toDateString(),
            ]
        );

        if (Season::where('is_active', true)->count() > 1) {
            Season::where('id', '!=', $season->id)->update(['is_active' => false]);
        }
    }
}
