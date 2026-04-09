<?php

namespace Database\Seeders;

use App\Models\Applicant;
use App\Models\ApplicantScore;
use App\Models\Application;
use App\Models\ConsultationSummary;
use App\Models\Course;
use App\Models\ExamDomain;
use App\Models\ExamSession;
use App\Models\GradingSession;
use App\Models\Role;
use App\Models\Room;
use App\Models\Season;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DefenseDemoSeeder extends Seeder
{
    public function run(): void
    {
        $today = CarbonImmutable::today();

        $courses = Course::query()->where('is_active', true)->orderBy('id')->take(3)->get();
        if ($courses->count() < 3) {
            $this->command?->warn('DefenseDemoSeeder: need at least 3 active courses. Run DatabaseSeeder first.');
            return;
        }

        $domains = ExamDomain::query()->where('is_active', true)->orderBy('display_order')->get();
        if ($domains->count() < 3) {
            $this->command?->warn('DefenseDemoSeeder: need at least 3 active exam domains. Run DatabaseSeeder first.');
            return;
        }

        DB::transaction(function () use ($today, $courses, $domains) {
            $season  = $this->seedSeason($today);
            $users   = $this->seedUsers();
            $rooms   = $this->seedRooms();
            $appMap  = $this->seedApplications($today, $season, $courses, $users);
            $this->seedSessionA($today, $season, $rooms, $appMap, $users, $domains);
            $this->seedSessionB($today, $season, $rooms, $appMap, $users, $domains);
            $this->seedSessionC($today, $season, $rooms, $appMap, $users);
            $this->seedSessionD($today, $season, $rooms);
        });
    }

    private function seedSeason(CarbonImmutable $today): Season
    {
        $year = $today->month >= 6 ? $today->year : $today->year - 1;
        $academicYear = $year . '-' . ($year + 1);

        $season = Season::query()->updateOrCreate(
            ['academic_year' => $academicYear, 'semester' => '1'],
            [
                'is_active'               => true,
                'application_start_date'  => $today->subDays(45)->toDateString(),
                'application_end_date'    => $today->addDays(14)->toDateString(),
            ]
        );

        // Deactivate all other seasons
        Season::query()->where('id', '!=', $season->id)->update(['is_active' => false]);

        return $season;
    }

    private function seedUsers(): array
    {
        return [
            'super_admin'     => $this->upsertUserWithRole('admin@securecat.local',    'Ricardo Dela Cruz', 'super_admin'),
            'admin'           => $this->upsertUserWithRole('josefina@securecat.local', 'Josefina Gaerlan',  'admin'),
            'staff'           => $this->upsertUserWithRole('maria@securecat.local',    'Maria Corpuz',      'staff'),
            'proctor'         => $this->upsertUserWithRole('eduardo@securecat.local',  'Eduardo Fariñas',   'proctor'),
            'test_admin'      => $this->upsertUserWithRole('analiza@securecat.local',  'Analiza Barroga',   'test_administrator'),
        ];
    }

    private function seedRooms(): \Illuminate\Support\Collection
    {
        $specs = [
            ['building' => 'Main Building',       'name' => 'Room 101',  'floor' => '1st Floor',    'capacity' => 30],
            ['building' => 'Main Building',       'name' => 'Room 102',  'floor' => '1st Floor',    'capacity' => 30],
            ['building' => 'Academic Building',   'name' => 'Room 201',  'floor' => '2nd Floor',    'capacity' => 40],
            ['building' => 'Vocational Building', 'name' => 'Lab Room 1','floor' => 'Ground Floor','capacity' => 25],
        ];

        return collect($specs)->map(fn ($r) =>
            Room::query()->updateOrCreate(
                ['building' => $r['building'], 'name' => $r['name']],
                ['floor' => $r['floor'], 'capacity' => $r['capacity'], 'is_active' => true]
            )
        );
    }

    private function seedApplications(CarbonImmutable $today, Season $season, $courses, array $users): array
    {
        return []; // stub
    }

    private function seedSessionA(CarbonImmutable $today, Season $season, $rooms, array $appMap, array $users, $domains): void {}

    private function seedSessionB(CarbonImmutable $today, Season $season, $rooms, array $appMap, array $users, $domains): void {}

    private function seedSessionC(CarbonImmutable $today, Season $season, $rooms, array $appMap, array $users): void {}

    private function seedSessionD(CarbonImmutable $today, Season $season, $rooms): void {}

    private function upsertUserWithRole(string $email, string $name, string $roleName): User
    {
        $user = User::query()->updateOrCreate(
            ['email' => $email],
            ['name' => $name, 'password' => Hash::make('password')]
        );

        $role = Role::query()->where('name', $roleName)->first();
        if ($role) {
            $user->roles()->syncWithoutDetaching([$role->id]);
        }

        return $user;
    }

    private function attachApplicant(ExamSession $es, Applicant $applicant, array $pivot = []): void
    {
        DB::table('exam_session_applicant')->updateOrInsert(
            ['exam_session_id' => $es->id, 'applicant_id' => $applicant->id],
            array_merge([
                'attendance_status'  => 'pending',
                'submission_status'  => 'pending',
                'created_at'        => now(),
                'updated_at'        => now(),
            ], $pivot)
        );
    }
}
