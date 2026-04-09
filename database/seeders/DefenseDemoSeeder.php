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
        $year = $today->year;

        $specs = [
            // [idx, first, middle, last, suffix, sex, birthdate, city, province, zip, phone, status, daysAgo, slot]
            [1,  'Juan Carlo', null,     'Agustin',    null,   'male',   '2006-03-12', 'Tagudin',     'Ilocos Sur', '2714', '09171001001', 'accepted',             28, 'A'],
            [2,  'Maricel',    null,     'Dacumos',    null,   'female', '2005-07-24', 'Tagudin',     'Ilocos Sur', '2714', '09171001002', 'accepted',             26, 'A'],
            [3,  'Reynaldo',   null,     'Soriano',    null,   'male',   '2006-01-08', 'Candon City', 'Ilocos Sur', '2802', '09171001003', 'accepted',             25, 'A'],
            [4,  'Rowena',     null,     'Ballesteros',null,   'female', '2005-11-15', 'Narvacan',    'Ilocos Sur', '2704', '09171001004', 'accepted',             18, 'B'],
            [5,  'Danilo',     null,     'Espiritu',   'Jr.',  'male',   '2006-05-30', 'Tagudin',     'Ilocos Sur', '2714', '09171001005', 'accepted',             17, 'B'],
            [6,  'Lorena',     null,     'Tamayo',     null,   'female', '2007-02-17', 'Santiago',    'Ilocos Sur', '2712', '09171001006', 'accepted',             10, 'C'],
            [7,  'Roberto',    null,     'Libed',      null,   'male',   '2006-09-03', 'Tagudin',     'Ilocos Sur', '2714', '09171001007', 'accepted',              9, 'C'],
            [8,  'Maribel',    null,     'Pagulayan',  null,   'female', '2005-12-21', 'Sudipen',     'La Union',   '2507', '09171001008', 'accepted',              8, 'C'],
            [9,  'Arturo',     null,     'Madriaga',   null,   'male',   '2006-08-14', 'Tagudin',     'Ilocos Sur', '2714', '09171001009', 'accepted',              7, 'unassigned'],
            [10, 'Natividad',  null,     'Ramirez',    null,   'female', '2005-04-07', 'Candon City', 'Ilocos Sur', '2802', '09171001010', 'accepted',              6, 'unassigned'],
            [11, 'Virgilio',   null,     'Castillo',   null,   'male',   '2007-01-19', 'Vigan City',  'Ilocos Sur', '2700', '09171001011', 'accepted',              6, 'unassigned'],
            [12, 'Erlinda',    null,     'De Vera',    null,   'female', '2006-06-25', 'Tagudin',     'Ilocos Sur', '2714', '09171001012', 'accepted',              5, 'unassigned'],
            [13, 'Nestor',     null,     'Domingo',    null,   'male',   '2006-04-11', 'Tagudin',     'Ilocos Sur', '2714', '09171001013', 'pending',               3, 'pending'],
            [14, 'Imelda',     null,     'Gaerlan',    null,   'female', '2005-08-29', 'Candon City', 'Ilocos Sur', '2802', '09171001014', 'pending',               1, 'pending'],
            [15, 'Ferdinand',  null,     'Molina',     null,   'male',   '2007-03-05', 'Sinait',      'Ilocos Sur', '2721', '09171001015', 'pending',               5, 'pending'],
            [16, 'Rosalinda',  null,     'Aquino',     null,   'female', '2006-10-17', 'Tagudin',     'Ilocos Sur', '2714', '09171001016', 'pending',               0, 'pending'],
            [17, 'Carlos',     null,     'Vargas',     null,   'male',   '2005-06-14', 'Vigan City',  'Ilocos Sur', '2700', '09171001017', 'dismissed',             12, 'dismissed'],
            [18, 'Analiza',    null,     'Marcos',     null,   'female', '2006-02-28', 'Tagudin',     'Ilocos Sur', '2714', '09171001018', 'dismissed',             10, 'dismissed'],
            [19, 'Rodolfo',    null,     'Lacsamana',  null,   'male',   '2007-04-01', 'Narvacan',    'Ilocos Sur', '2704', '09171001019', 'incomplete_documents',  9, 'dismissed'],
            [20, 'Teresita',   null,     'Mirasol',    null,   'female', '2005-09-18', 'Santiago',    'Ilocos Sur', '2712', '09171001020', 'incomplete_documents',  8, 'dismissed'],
        ];

        $courseIdMap = Course::query()
            ->whereIn('code', ['BSIT', 'BSCS', 'BSDS'])
            ->pluck('id', 'code');

        $appMap = [];

        foreach ($specs as [$idx, $first, $middle, $last, $suffix, $sex, $birthdate, $city, $province, $zip, $phone, $status, $daysAgo, $slot]) {
            $ref         = 'ISPSC-' . $year . '-' . str_pad((string) $idx, 4, '0', STR_PAD_LEFT);
            $email       = strtolower(str_replace(' ', '.', $first) . '.' . strtolower(str_replace(' ', '', $last))) . '@ispsc-demo.local';
            $submittedAt = $today->subDays($daysAgo)->startOfDay()->addHours(9);

            $processedBy     = null;
            $processedAt     = null;
            $rejectionReason = null;

            if ($status === 'accepted') {
                $processedBy = $users['staff']->id;
                $processedAt = $submittedAt->addDays(2);
            }

            if ($status === 'dismissed') {
                $processedBy     = $users['staff']->id;
                $processedAt     = $submittedAt->addDays(2);
                $rejectionReason = 'Did not appear for scheduled appointment.';
            }

            if ($status === 'incomplete_documents') {
                $processedBy     = $users['staff']->id;
                $processedAt     = $submittedAt->addDays(1);
                $rejectionReason = $idx === 19
                    ? 'Missing PSA birth certificate.'
                    : 'Missing Form 138 (Report Card).';
            }

            $app = Application::query()->updateOrCreate(
                ['reference_number' => $ref],
                [
                    'season_id'           => $season->id,
                    'first_name'          => $first,
                    'middle_name'         => $middle,
                    'last_name'           => $last,
                    'suffix'              => $suffix,
                    'birthdate'           => $birthdate,
                    'age'                 => $today->year - (int) substr($birthdate, 0, 4),
                    'sex'                 => $sex,
                    'email'               => $email,
                    'phone'               => $phone,
                    'address_line'        => '123 Rizal St.',
                    'city'                => $city,
                    'province'            => $province,
                    'zip_code'            => $zip,
                    'course_preference_1' => $courseIdMap['BSIT'],
                    'course_preference_2' => $courseIdMap['BSCS'],
                    'course_preference_3' => $courseIdMap['BSDS'],
                    'status'              => $status,
                    'processed_by'        => $processedBy,
                    'processed_at'       => $processedAt,
                    'rejection_reason'    => $rejectionReason,
                    'appointment_id'      => null,
                    'submitted_at'        => $submittedAt,
                ]
            );

            $applicant = null;

            if ($status === 'accepted') {
                $applicant = Applicant::query()->updateOrCreate(
                    ['email' => $app->email],
                    [
                        'application_id'         => $app->id,
                        'password'               => Hash::make('password'),
                        'setup_token'            => null,
                        'setup_token_expires_at' => null,
                    ]
                );
            }

            $appMap[$idx] = ['application' => $app, 'applicant' => $applicant, 'slot' => $slot];
        }

        return $appMap;
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
