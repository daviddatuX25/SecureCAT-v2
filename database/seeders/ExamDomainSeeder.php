<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ExamDomainSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $pillars = [
            ['name' => 'Spatial Awareness', 'code' => 'SA', 'max_items' => 25, 'display_order' => 1],
            ['name' => 'Numerical Ability', 'code' => 'NA', 'max_items' => 25, 'display_order' => 2],
            ['name' => 'Verbal Reasoning', 'code' => 'VR', 'max_items' => 25, 'display_order' => 3],
            ['name' => 'Abstract Reasoning', 'code' => 'AR', 'max_items' => 20, 'display_order' => 4],
            ['name' => 'Logical Reasoning', 'code' => 'LR', 'max_items' => 25, 'display_order' => 5],
            ['name' => 'Perceptual Speed & Accuracy', 'code' => 'PSA', 'max_items' => 20, 'display_order' => 6],
        ];
        foreach ($pillars as $p) {
            \App\Models\ExamDomain::firstOrCreate(
                ['code' => $p['code']],
                array_merge($p, ['description' => null, 'is_active' => true])
            );
        }
    }
}
