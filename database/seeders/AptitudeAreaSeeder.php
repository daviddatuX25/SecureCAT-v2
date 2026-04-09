<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class AptitudeAreaSeeder extends Seeder
{
    public function run(): void
    {
        $areas = [
            ['name' => 'Spatial Awareness',          'code' => 'SA',  'max_items' => 25, 'display_order' => 1],
            ['name' => 'Numerical Ability',           'code' => 'NA',  'max_items' => 25, 'display_order' => 2],
            ['name' => 'Verbal Reasoning',            'code' => 'VR',  'max_items' => 25, 'display_order' => 3],
            ['name' => 'Abstract Reasoning',          'code' => 'AR',  'max_items' => 20, 'display_order' => 4],
            ['name' => 'Logical Reasoning',           'code' => 'LR',  'max_items' => 25, 'display_order' => 5],
            ['name' => 'Perceptual Speed & Accuracy', 'code' => 'PSA', 'max_items' => 20, 'display_order' => 6],
        ];

        foreach ($areas as $area) {
            \App\Models\AptitudeArea::firstOrCreate(
                ['code' => $area['code']],
                array_merge($area, ['description' => null, 'is_active' => true])
            );
        }
    }
}
