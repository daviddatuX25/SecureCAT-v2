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

        $staffEmail = env('STAFF_USER_EMAIL', 'test@example.com');
        if ($staffEmail !== $superAdminEmail) {
            $staff = User::firstOrCreate(
                ['email' => $staffEmail],
                [
                    'name' => env('STAFF_USER_NAME', 'Test User'),
                    'password' => Hash::make(env('STAFF_USER_PASSWORD', 'password')),
                ]
            );
            $staffRole = Role::where('name', 'staff')->first();
            if ($staffRole && ! $staff->roles()->where('role_id', $staffRole->id)->exists()) {
                $staff->roles()->attach($staffRole);
            }
        }

        $sampleUsers = [
            ['email' => 'registrar@example.com', 'name' => 'Registrar Admin', 'role' => 'admin'],
            ['email' => 'proctor@example.com', 'name' => 'Proctor User', 'role' => 'proctor'],
            ['email' => 'grader@example.com', 'name' => 'Grader User', 'role' => 'grader'],
            ['email' => 'counselor@example.com', 'name' => 'Counselor User', 'role' => 'counselor'],
        ];
        $defaultPassword = Hash::make('password');
        foreach ($sampleUsers as $data) {
            $user = User::firstOrCreate(
                ['email' => $data['email']],
                ['name' => $data['name'], 'password' => $defaultPassword]
            );
            $role = Role::where('name', $data['role'])->first();
            if ($role && ! $user->roles()->where('role_id', $role->id)->exists()) {
                $user->roles()->attach($role);
            }
        }

        // Multi-role user: admin + proctor (registrar who also proctors)
        $multiRoleUser = User::firstOrCreate(
            ['email' => 'registrar-proctor@example.com'],
            ['name' => 'Registrar & Proctor', 'password' => $defaultPassword]
        );
        foreach (['admin', 'proctor'] as $roleName) {
            $role = Role::where('name', $roleName)->first();
            if ($role && ! $multiRoleUser->roles()->where('role_id', $role->id)->exists()) {
                $multiRoleUser->roles()->attach($role);
            }
        }
    }
}
