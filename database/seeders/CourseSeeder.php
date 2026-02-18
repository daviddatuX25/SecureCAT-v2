<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Department;
use Illuminate\Database\Seeder;

class CourseSeeder extends Seeder
{
    public function run(): void
    {
        if (Course::exists()) {
            return;
        }

        $dept = Department::firstOrCreate(
            ['code' => 'CIT'],
            ['name' => 'College of Information Technology']
        );

        $courses = [
            ['name' => 'Bachelor of Science in Information Technology', 'code' => 'BSIT'],
            ['name' => 'Bachelor of Science in Computer Science', 'code' => 'BSCS'],
            ['name' => 'Bachelor of Science in Data Science', 'code' => 'BSDS'],
        ];

        foreach ($courses as $c) {
            Course::firstOrCreate(
                ['code' => $c['code']],
                ['department_id' => $dept->id, 'name' => $c['name'], 'is_active' => true]
            );
        }
    }
}
