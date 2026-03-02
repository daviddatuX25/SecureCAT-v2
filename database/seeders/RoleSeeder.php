<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['name' => 'super_admin', 'display_name' => 'Super Admin', 'description' => 'System administrator, manages users and roles'],
            ['name' => 'staff', 'display_name' => 'Staff', 'description' => 'Registrar staff, processes applications'],
            ['name' => 'admin', 'display_name' => 'Admin', 'description' => 'Registrar admin, manages scheduling'],
            ['name' => 'proctor', 'display_name' => 'Proctor', 'description' => 'Guidance office, monitors exams'],
            ['name' => 'test_administrator', 'display_name' => 'Test Administrator', 'description' => 'Guidance office, inputs scores and releases consultations'],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(
                ['name' => $role['name']],
                $role
            );
        }
    }
}
