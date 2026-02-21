<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RoleSeeder::class);
        $this->call(CourseSeeder::class);
        $this->call(ExamDomainSeeder::class);
        $this->call(ResultSheetTemplateSeeder::class);

        $superAdminEmail = env('SUPER_ADMIN_EMAIL', 'admin@example.com');
        $superAdminPassword = env('SUPER_ADMIN_PASSWORD', 'Password1!');

        $superAdmin = User::firstOrCreate(
            ['email' => $superAdminEmail],
            [
                'name' => env('SUPER_ADMIN_NAME', 'Super Admin'),
                'password' => Hash::make($superAdminPassword),
            ]
        );
        $superAdminRole = Role::where('name', 'super_admin')->first();
        if ($superAdminRole && ! $superAdmin->roles()->where('role_id', $superAdminRole->id)->exists()) {
            $superAdmin->roles()->attach($superAdminRole);
        }

        $defaultPassword = Hash::make('password');

        // Registrar: 4 staff (1 admin, 3 staff)
        $registrarUsers = [
            ['email' => 'lorna.santos@example.com', 'name' => 'Lorna Santos', 'roles' => ['admin']],
            ['email' => 'juan.delacruz@example.com', 'name' => 'Juan Dela Cruz', 'roles' => ['staff']],
            ['email' => 'ana.garcia@example.com', 'name' => 'Ana Garcia', 'roles' => ['staff']],
            ['email' => 'pedro.ramos@example.com', 'name' => 'Pedro Ramos', 'roles' => ['staff']],
        ];
        foreach ($registrarUsers as $data) {
            $user = User::firstOrCreate(
                ['email' => $data['email']],
                ['name' => $data['name'], 'password' => $defaultPassword]
            );
            foreach ($data['roles'] as $roleName) {
                $role = Role::where('name', $roleName)->first();
                if ($role && ! $user->roles()->where('role_id', $role->id)->exists()) {
                    $user->roles()->attach($role);
                }
            }
        }

        // Guidance office: 3 staff (all grader+proctor; 2 also counselor — Sonny Jalorina, Jalorina Reyes)
        $guidanceUsers = [
            ['email' => 'sonny.jalorina@example.com', 'name' => 'Sonny Jalorina', 'roles' => ['grader', 'proctor', 'counselor']],
            ['email' => 'jalorina.reyes@example.com', 'name' => 'Jalorina Reyes', 'roles' => ['grader', 'proctor', 'counselor']],
            ['email' => 'miguel.reyes@example.com', 'name' => 'Miguel Reyes', 'roles' => ['grader', 'proctor']],
        ];
        foreach ($guidanceUsers as $data) {
            $user = User::firstOrCreate(
                ['email' => $data['email']],
                ['name' => $data['name'], 'password' => $defaultPassword]
            );
            foreach ($data['roles'] as $roleName) {
                $role = Role::where('name', $roleName)->first();
                if ($role && ! $user->roles()->where('role_id', $role->id)->exists()) {
                    $user->roles()->attach($role);
                }
            }
        }

        $this->call(RealisticDataSeeder::class);
        $this->call(ProctorDemoSeeder::class);
    }
}
