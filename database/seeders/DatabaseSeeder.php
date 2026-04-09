<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Seed order (foundation data — run first):
        //  1. RoleSeeder          → roles (super_admin, admin, staff, proctor, test_administrator)
        //  2. CourseSeeder        → courses (BSIT, BSCS, BSDS, etc.)
        //  3. ExamDomainSeeder    → exam domains (SA, NA, VR, AR, LR, PSA)
        //  4. AdmissionSlipTemplateSeeder → print templates
        //  5. ResultSheetTemplateSeeder   → print templates
        //  6. DemoAccountSeeder   → demo/admin accounts (admin@example.com)
        //  7. SeasonSeeder        → academic seasons
        //
        // Defense demo data (run after foundation):
        //  - DefenseDemoSeeder     → ISPSC Tagudin defense-ready data (applications, sessions, scores)
        //    Invoke separately: php artisan db:seed --class=DefenseDemoSeeder
        $this->call([
            RoleSeeder::class,
            CourseSeeder::class,
            ExamDomainSeeder::class,
            AdmissionSlipTemplateSeeder::class,
            ResultSheetTemplateSeeder::class,
            DemoAccountSeeder::class,
            SeasonSeeder::class,
        ]);
    }
}

