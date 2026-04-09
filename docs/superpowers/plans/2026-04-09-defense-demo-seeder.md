# DefenseDemoSeeder Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build `DefenseDemoSeeder` — a self-contained, idempotent seeder that populates SecureCAT-v2 with realistic ISPSC Tagudin data for the defense demo, covering every workflow state across the full exam pipeline.

**Architecture:** Single seeder class with private helper methods per concern (season, users, rooms, applications, each session). All dates computed from `CarbonImmutable::today()` at seed time so re-seeding before the defense always produces a "live today" snapshot.

**Tech Stack:** Laravel 12, PHP 8.2, CarbonImmutable, PHPUnit (RefreshDatabase)

**Spec:** `docs/superpowers/specs/2026-04-09-defense-demo-data-design.md`

---

## File Map

| Action | Path | Responsibility |
|---|---|---|
| Create | `database/seeders/DefenseDemoSeeder.php` | All demo data in one idempotent seeder |
| Create | `tests/Feature/DefenseDemoSeederTest.php` | Integration assertions for all seeded states |
| Modify | `database/seeders/DatabaseSeeder.php` | Add seed-order comment block |

---

## Task 1: Scaffold + Integration Test

**Files:**
- Create: `database/seeders/DefenseDemoSeeder.php`
- Create: `tests/Feature/DefenseDemoSeederTest.php`

- [ ] **Step 1.1: Write the failing integration test**

Create `tests/Feature/DefenseDemoSeederTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\Applicant;
use App\Models\Application;
use App\Models\ConsultationSummary;
use App\Models\ExamSession;
use App\Models\GradingSession;
use App\Models\Room;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DefenseDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DefenseDemoSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->seed(DefenseDemoSeeder::class);
    }

    public function test_seeds_five_staff_users(): void
    {
        $this->assertDatabaseHas('users', ['email' => 'admin@securecat.local']);
        $this->assertDatabaseHas('users', ['email' => 'josefina@securecat.local']);
        $this->assertDatabaseHas('users', ['email' => 'maria@securecat.local']);
        $this->assertDatabaseHas('users', ['email' => 'eduardo@securecat.local']);
        $this->assertDatabaseHas('users', ['email' => 'analiza@securecat.local']);
    }

    public function test_seeds_four_rooms(): void
    {
        $this->assertGreaterThanOrEqual(4, Room::count());
        $this->assertDatabaseHas('rooms', ['building' => 'Main Building', 'name' => 'Room 101']);
        $this->assertDatabaseHas('rooms', ['building' => 'Main Building', 'name' => 'Room 102']);
    }

    public function test_seeds_twenty_applications(): void
    {
        $this->assertSame(20, Application::count());
    }

    public function test_seeds_twelve_applicant_portal_accounts(): void
    {
        $this->assertSame(12, Applicant::count());
    }

    public function test_seeds_four_exam_sessions(): void
    {
        $this->assertSame(4, ExamSession::count());
    }

    public function test_session_a_is_finalized(): void
    {
        $this->assertSame(1, GradingSession::where('status', GradingSession::STATUS_FINALIZED)->count());
    }

    public function test_session_b_grading_is_in_progress(): void
    {
        $this->assertSame(1, GradingSession::where('status', GradingSession::STATUS_IN_PROGRESS)->count());
    }

    public function test_session_a_consultation_summaries_are_released(): void
    {
        $this->assertSame(3, ConsultationSummary::where('status', ConsultationSummary::STATUS_RELEASED)->count());
    }

    public function test_session_b_consultation_summaries_are_pending(): void
    {
        $this->assertSame(2, ConsultationSummary::where('status', ConsultationSummary::STATUS_PENDING)->count());
    }

    public function test_session_c_has_one_present_and_two_pending_attendance(): void
    {
        $sessionC = ExamSession::whereDate('date', today())->first();
        $this->assertNotNull($sessionC);

        $present = DB::table('exam_session_applicant')
            ->where('exam_session_id', $sessionC->id)
            ->where('attendance_status', 'present')
            ->count();

        $pending = DB::table('exam_session_applicant')
            ->where('exam_session_id', $sessionC->id)
            ->where('attendance_status', 'pending')
            ->count();

        $this->assertSame(1, $present);
        $this->assertSame(2, $pending);
    }
}
```

- [ ] **Step 1.2: Create the seeder scaffold**

Create `database/seeders/DefenseDemoSeeder.php`:

```php
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

    // ── helpers (stubs — filled in Tasks 2-6) ──────────────────────────────

    private function seedSeason(CarbonImmutable $today): Season
    {
        return new Season(); // stub
    }

    private function seedUsers(): array
    {
        return []; // stub
    }

    private function seedRooms(): \Illuminate\Support\Collection
    {
        return collect(); // stub
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
                'created_at'         => now(),
                'updated_at'         => now(),
            ], $pivot)
        );
    }
}
```

- [ ] **Step 1.3: Run the test — expect it to fail (stubs return empty)**

```bash
cd D:\Projects\SecureCAT-v2
php artisan test tests/Feature/DefenseDemoSeederTest.php --stop-on-failure
```

Expected: several test failures — stubs return empty collections/seasons.

- [ ] **Step 1.4: Commit the scaffold**

```bash
git add database/seeders/DefenseDemoSeeder.php tests/Feature/DefenseDemoSeederTest.php
git commit -m "test: add DefenseDemoSeeder scaffold and integration test"
```

---

## Task 2: Season, Users, Rooms

**Files:**
- Modify: `database/seeders/DefenseDemoSeeder.php` — implement `seedSeason`, `seedUsers`, `seedRooms`

- [ ] **Step 2.1: Implement `seedSeason`**

Replace the stub with:

```php
private function seedSeason(CarbonImmutable $today): Season
{
    // Derive academic year: Aug–Jul cycle. Before June = second half of AY.
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

    // Deactivate any other seasons
    Season::query()->where('id', '!=', $season->id)->update(['is_active' => false]);

    return $season;
}
```

- [ ] **Step 2.2: Implement `seedUsers`**

Replace the stub with:

```php
private function seedUsers(): array
{
    return [
        'super_admin'      => $this->upsertUserWithRole('admin@securecat.local',    'Ricardo Dela Cruz', 'super_admin'),
        'admin'            => $this->upsertUserWithRole('josefina@securecat.local', 'Josefina Gaerlan',  'admin'),
        'staff'            => $this->upsertUserWithRole('maria@securecat.local',    'Maria Corpuz',      'staff'),
        'proctor'          => $this->upsertUserWithRole('eduardo@securecat.local',  'Eduardo Fariñas',   'proctor'),
        'test_admin'       => $this->upsertUserWithRole('analiza@securecat.local',  'Analiza Barroga',   'test_administrator'),
    ];
}
```

- [ ] **Step 2.3: Implement `seedRooms`**

Replace the stub with:

```php
private function seedRooms(): \Illuminate\Support\Collection
{
    $specs = [
        ['building' => 'Main Building',      'name' => 'Room 101',  'floor' => '1st Floor',    'capacity' => 30],
        ['building' => 'Main Building',      'name' => 'Room 102',  'floor' => '1st Floor',    'capacity' => 30],
        ['building' => 'Academic Building',  'name' => 'Room 201',  'floor' => '2nd Floor',    'capacity' => 40],
        ['building' => 'Vocational Building','name' => 'Lab Room 1','floor' => 'Ground Floor', 'capacity' => 25],
    ];

    return collect($specs)->map(fn ($r) =>
        Room::query()->updateOrCreate(
            ['building' => $r['building'], 'name' => $r['name']],
            ['floor' => $r['floor'], 'capacity' => $r['capacity'], 'is_active' => true]
        )
    );
}
```

- [ ] **Step 2.4: Run the user/room assertions**

```bash
php artisan test tests/Feature/DefenseDemoSeederTest.php --filter="seeds_five_staff_users|seeds_four_rooms"
```

Expected: both PASS.

- [ ] **Step 2.5: Commit**

```bash
git add database/seeders/DefenseDemoSeeder.php
git commit -m "feat(demo): seed season, users, rooms in DefenseDemoSeeder"
```

---

## Task 3: 20 Applications + 12 Applicant Portal Accounts

**Files:**
- Modify: `database/seeders/DefenseDemoSeeder.php` — implement `seedApplications`

- [ ] **Step 3.1: Implement `seedApplications`**

Replace the stub with the full method. The method returns an associative array keyed by applicant slot number (1–20) mapping to `['application' => Application, 'applicant' => Applicant|null]`.

```php
private function seedApplications(CarbonImmutable $today, Season $season, $courses, array $users): array
{
    $year = $today->year;

    // Each entry: [first, middle, last, suffix, sex, birthdate, city, province, zip, phone, status, submitted_days_ago, session_slot]
    // session_slot: 'A', 'B', 'C', 'unassigned', 'pending', 'dismissed'
    $specs = [
        // --- Accepted + Assigned ---
        [1,  'Juan Carlo', null,     'Agustin',    null,   'male',   '2006-03-12', 'Tagudin',     'Ilocos Sur', '2714', '09171001001', 'accepted', 28, 'A'],
        [2,  'Maricel',    null,     'Dacumos',    null,   'female', '2005-07-24', 'Tagudin',     'Ilocos Sur', '2714', '09171001002', 'accepted', 26, 'A'],
        [3,  'Reynaldo',   null,     'Soriano',    null,   'male',   '2006-01-08', 'Candon City', 'Ilocos Sur', '2802', '09171001003', 'accepted', 25, 'A'],
        [4,  'Rowena',     null,     'Ballesteros',null,   'female', '2005-11-15', 'Narvacan',    'Ilocos Sur', '2704', '09171001004', 'accepted', 18, 'B'],
        [5,  'Danilo',     null,     'Espiritu',   'Jr.',  'male',   '2006-05-30', 'Tagudin',     'Ilocos Sur', '2714', '09171001005', 'accepted', 17, 'B'],
        [6,  'Lorena',     null,     'Tamayo',     null,   'female', '2007-02-17', 'Santiago',    'Ilocos Sur', '2712', '09171001006', 'accepted', 10, 'C'],
        [7,  'Roberto',    null,     'Libed',      null,   'male',   '2006-09-03', 'Tagudin',     'Ilocos Sur', '2714', '09171001007', 'accepted', 9,  'C'],
        [8,  'Maribel',    null,     'Pagulayan',  null,   'female', '2005-12-21', 'Sudipen',     'La Union',   '2507', '09171001008', 'accepted', 8,  'C'],
        // --- Accepted + Unassigned ---
        [9,  'Arturo',     null,     'Madriaga',   null,   'male',   '2006-08-14', 'Tagudin',     'Ilocos Sur', '2714', '09171001009', 'accepted', 7,  'unassigned'],
        [10, 'Natividad',  null,     'Ramirez',    null,   'female', '2005-04-07', 'Candon City', 'Ilocos Sur', '2802', '09171001010', 'accepted', 6,  'unassigned'],
        [11, 'Virgilio',   null,     'Castillo',   null,   'male',   '2007-01-19', 'Vigan City',  'Ilocos Sur', '2700', '09171001011', 'accepted', 6,  'unassigned'],
        [12, 'Erlinda',    null,     'De Vera',    null,   'female', '2006-06-25', 'Tagudin',     'Ilocos Sur', '2714', '09171001012', 'accepted', 5,  'unassigned'],
        // --- Pending ---
        [13, 'Nestor',     null,     'Domingo',    null,   'male',   '2006-04-11', 'Tagudin',     'Ilocos Sur', '2714', '09171001013', 'pending',  3,  'pending'],
        [14, 'Imelda',     null,     'Gaerlan',    null,   'female', '2005-08-29', 'Candon City', 'Ilocos Sur', '2802', '09171001014', 'pending',  1,  'pending'],
        [15, 'Ferdinand',  null,     'Molina',     null,   'male',   '2007-03-05', 'Sinait',      'Ilocos Sur', '2721', '09171001015', 'pending',  5,  'pending'],
        [16, 'Rosalinda',  null,     'Aquino',     null,   'female', '2006-10-17', 'Tagudin',     'Ilocos Sur', '2714', '09171001016', 'pending',  0,  'pending'],
        // --- Dismissed / Incomplete ---
        [17, 'Carlos',     null,     'Vargas',     null,   'male',   '2005-06-14', 'Vigan City',  'Ilocos Sur', '2700', '09171001017', 'dismissed',               12, 'dismissed'],
        [18, 'Analiza',    null,     'Marcos',     null,   'female', '2006-02-28', 'Tagudin',     'Ilocos Sur', '2714', '09171001018', 'dismissed',               10, 'dismissed'],
        [19, 'Rodolfo',    null,     'Lacsamana',  null,   'male',   '2007-04-01', 'Narvacan',    'Ilocos Sur', '2704', '09171001019', 'incomplete_documents',    9,  'dismissed'],
        [20, 'Teresita',   null,     'Mirasol',    null,   'female', '2005-09-18', 'Santiago',    'Ilocos Sur', '2712', '09171001020', 'incomplete_documents',    8,  'dismissed'],
    ];

    // Pre-fetch course IDs once (avoids N queries in the loop)
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
            $rejectionReason = $idx === 19 ? 'Missing PSA birth certificate.' : 'Missing Form 138 (Report Card).';
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
                'processed_at'        => $processedAt,
                'rejection_reason'    => $rejectionReason,
                'appointment_id'      => null,
                'submitted_at'        => $submittedAt,
            ]
        );

        // Create Applicant portal account only for accepted applicants (slots 1-12)
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
```

- [ ] **Step 3.2: Run application assertions**

```bash
php artisan test tests/Feature/DefenseDemoSeederTest.php --filter="seeds_twenty_applications|seeds_twelve_applicant"
```

Expected: both PASS.

- [ ] **Step 3.3: Commit**

```bash
git add database/seeders/DefenseDemoSeeder.php
git commit -m "feat(demo): seed 20 ISPSC Tagudin applications with portal accounts"
```

---

## Task 4: Session A — Completed Pipeline

**Files:**
- Modify: `database/seeders/DefenseDemoSeeder.php` — implement `seedSessionA`

- [ ] **Step 4.1: Implement `seedSessionA`**

Replace the stub with:

```php
private function seedSessionA(CarbonImmutable $today, Season $season, $rooms, array $appMap, array $users, $domains): void
{
    $date = $today->subDays(14);
    $room = $rooms[0]; // Main Building Room 101

    $es = ExamSession::query()->updateOrCreate(
        ['season_id' => $season->id, 'room_id' => $room->id, 'date' => $date->toDateString()],
        [
            'start_time'         => '09:00:00',
            'end_time'           => '11:00:00',
            'status'             => ExamSession::STATUS_COMPLETED,
            'published_at'       => $date->subDays(5),
            'started_at'         => $date->setTimeFromTimeString('09:00:00'),
            'closed_at'          => $date->setTimeFromTimeString('11:05:00'),
            'score_release_date' => $date->addDays(7)->toDateString(),
            'created_by'         => $users['admin']->id,
        ]
    );

    $es->proctors()->syncWithoutDetaching([$users['proctor']->id]);

    // Applicants #1, #2, #3 — all attended and submitted
    $sessionApplicants = [
        $appMap[1]['applicant'],
        $appMap[2]['applicant'],
        $appMap[3]['applicant'],
    ];

    foreach ($sessionApplicants as $applicant) {
        $this->attachApplicant($es, $applicant, [
            'attendance_status'    => 'present',
            'attendance_marked_at' => $date->setTimeFromTimeString('09:05:00'),
            'attendance_marked_by' => $users['proctor']->id,
            'submission_status'    => 'submitted',
            'submitted_at'         => $date->setTimeFromTimeString('10:55:00'),
            'submitted_to'         => $users['proctor']->id,
        ]);
    }

    // Grading session — finalized
    $gs = GradingSession::query()->updateOrCreate(
        ['exam_session_id' => $es->id],
        [
            'status'       => GradingSession::STATUS_FINALIZED,
            'opened_at'    => $date->addDays(1)->setTimeFromTimeString('08:00:00'),
            'opened_by'    => $users['test_admin']->id,
            'finalized_at' => $date->addDays(3)->setTimeFromTimeString('16:00:00'),
            'finalized_by' => $users['test_admin']->id,
        ]
    );

    foreach ($sessionApplicants as $applicant) {
        $gs->applicants()->syncWithoutDetaching([$applicant->id]);
    }

    // Scores — varied for realistic results
    // Format: [applicant_index => [domain_code => raw_score]]
    $scoreMap = [
        0 => ['SA' => 22, 'NA' => 20, 'VR' => 21, 'AR' => 17, 'LR' => 20, 'PSA' => 16], // Juan — high/passing
        1 => ['SA' => 14, 'NA' => 13, 'VR' => 15, 'AR' => 10, 'LR' => 13, 'PSA' => 11], // Maricel — borderline
        2 => ['SA' =>  8, 'NA' =>  9, 'VR' =>  7, 'AR' =>  6, 'LR' =>  8, 'PSA' =>  7], // Reynaldo — low/failing
    ];

    foreach ($sessionApplicants as $i => $applicant) {
        foreach ($domains as $domain) {
            $raw = $scoreMap[$i][$domain->code] ?? (int) round($domain->max_items * 0.5);
            ApplicantScore::query()->updateOrCreate(
                ['grading_session_id' => $gs->id, 'applicant_id' => $applicant->id, 'domain_id' => $domain->id],
                [
                    'raw_score'        => $raw,
                    'max_score'        => $domain->max_items,
                    'normalized_score' => null,
                    'scored_by'        => $users['test_admin']->id,
                    'scored_at'        => $date->addDays(2)->setTimeFromTimeString('14:00:00'),
                ]
            );
        }
    }

    // Consultation summaries — released
    $consultationData = [
        0 => ['course' => 'BSIT', 'comments' => 'Excellent performance. Highly recommended for BSIT.'],
        1 => ['course' => 'BSCS', 'comments' => 'Borderline scores. Recommended to consider BSCS.'],
        2 => ['course' => 'BSIT', 'comments' => 'Low scores across domains. Advised to review and retake.'],
    ];

    foreach ($sessionApplicants as $i => $applicant) {
        $courseId = Course::query()->where('code', $consultationData[$i]['course'])->value('id');
        ConsultationSummary::query()->updateOrCreate(
            ['applicant_id' => $applicant->id],
            [
                'status'               => ConsultationSummary::STATUS_RELEASED,
                'recommended_course_id'=> $courseId,
                'counselor_comments'   => $consultationData[$i]['comments'],
                'system_notes'         => ['seed' => 'defense-demo'],
                'counselor_id'         => $users['test_admin']->id,
                'released_at'          => $date->addDays(5)->setTimeFromTimeString('10:00:00'),
                'released_by'          => $users['test_admin']->id,
            ]
        );
    }
}
```

- [ ] **Step 4.2: Run Session A assertions**

```bash
php artisan test tests/Feature/DefenseDemoSeederTest.php --filter="session_a"
```

Expected: `test_session_a_is_finalized` and `test_session_a_consultation_summaries_are_released` both PASS.

- [ ] **Step 4.3: Commit**

```bash
git add database/seeders/DefenseDemoSeeder.php
git commit -m "feat(demo): seed Session A — completed pipeline with released results"
```

---

## Task 5: Session B — Grading In Progress

**Files:**
- Modify: `database/seeders/DefenseDemoSeeder.php` — implement `seedSessionB`

- [ ] **Step 5.1: Implement `seedSessionB`**

Replace the stub with:

```php
private function seedSessionB(CarbonImmutable $today, Season $season, $rooms, array $appMap, array $users, $domains): void
{
    $date = $today->subDays(5);
    $room = $rooms[2]; // Academic Building Room 201

    $es = ExamSession::query()->updateOrCreate(
        ['season_id' => $season->id, 'room_id' => $room->id, 'date' => $date->toDateString()],
        [
            'start_time'         => '13:00:00',
            'end_time'           => '15:00:00',
            'status'             => ExamSession::STATUS_COMPLETED,
            'published_at'       => $date->subDays(5),
            'started_at'         => $date->setTimeFromTimeString('13:00:00'),
            'closed_at'          => $date->setTimeFromTimeString('15:05:00'),
            'score_release_date' => $date->addDays(7)->toDateString(),
            'created_by'         => $users['admin']->id,
        ]
    );

    $es->proctors()->syncWithoutDetaching([$users['proctor']->id]);

    $sessionApplicants = [
        $appMap[4]['applicant'], // Rowena
        $appMap[5]['applicant'], // Danilo
    ];

    foreach ($sessionApplicants as $applicant) {
        $this->attachApplicant($es, $applicant, [
            'attendance_status'    => 'present',
            'attendance_marked_at' => $date->setTimeFromTimeString('13:05:00'),
            'attendance_marked_by' => $users['proctor']->id,
            'submission_status'    => 'submitted',
            'submitted_at'         => $date->setTimeFromTimeString('14:55:00'),
            'submitted_to'         => $users['proctor']->id,
        ]);
    }

    $gs = GradingSession::query()->updateOrCreate(
        ['exam_session_id' => $es->id],
        [
            'status'    => GradingSession::STATUS_IN_PROGRESS,
            'opened_at' => $date->addDays(1)->setTimeFromTimeString('08:00:00'),
            'opened_by' => $users['test_admin']->id,
        ]
    );

    foreach ($sessionApplicants as $applicant) {
        $gs->applicants()->syncWithoutDetaching([$applicant->id]);
    }

    // Partial scores — first 3 domains only (SA, NA, VR)
    $partialScores = [
        0 => ['SA' => 18, 'NA' => 16, 'VR' => 17], // Rowena
        1 => ['SA' => 20, 'NA' => 19, 'VR' => 18], // Danilo
    ];

    foreach ($sessionApplicants as $i => $applicant) {
        foreach ($domains->take(3) as $domain) {
            $raw = $partialScores[$i][$domain->code] ?? (int) round($domain->max_items * 0.6);
            ApplicantScore::query()->updateOrCreate(
                ['grading_session_id' => $gs->id, 'applicant_id' => $applicant->id, 'domain_id' => $domain->id],
                [
                    'raw_score'        => $raw,
                    'max_score'        => $domain->max_items,
                    'normalized_score' => null,
                    'scored_by'        => $users['test_admin']->id,
                    'scored_at'        => $date->addDays(1)->setTimeFromTimeString('11:00:00'),
                ]
            );
        }
    }

    // Consultation summaries — pending (awaiting counselor action)
    $bsitId = Course::query()->where('code', 'BSIT')->value('id');
    $bscsId = Course::query()->where('code', 'BSCS')->value('id');

    foreach ($sessionApplicants as $i => $applicant) {
        $courseId = $i === 0 ? $bsitId : $bscsId;
        ConsultationSummary::query()->updateOrCreate(
            ['applicant_id' => $applicant->id],
            [
                'status'                => ConsultationSummary::STATUS_PENDING,
                'recommended_course_id' => $courseId,
                'counselor_comments'    => null,
                'system_notes'          => ['seed' => 'defense-demo'],
                'counselor_id'          => $users['test_admin']->id,
                'released_at'           => null,
                'released_by'           => null,
            ]
        );
    }
}
```

- [ ] **Step 5.2: Run Session B assertions**

```bash
php artisan test tests/Feature/DefenseDemoSeederTest.php --filter="session_b"
```

Expected: `test_session_b_grading_is_in_progress` and `test_session_b_consultation_summaries_are_pending` both PASS.

- [ ] **Step 5.3: Commit**

```bash
git add database/seeders/DefenseDemoSeeder.php
git commit -m "feat(demo): seed Session B — grading in progress with pending consultations"
```

---

## Task 6: Session C (Live Demo) + Session D (Upcoming)

**Files:**
- Modify: `database/seeders/DefenseDemoSeeder.php` — implement `seedSessionC` and `seedSessionD`

- [ ] **Step 6.1: Implement `seedSessionC`**

Replace the stub with:

```php
private function seedSessionC(CarbonImmutable $today, Season $season, $rooms, array $appMap, array $users): void
{
    $room = $rooms[1]; // Main Building Room 102

    $es = ExamSession::query()->updateOrCreate(
        ['season_id' => $season->id, 'room_id' => $room->id, 'date' => $today->toDateString()],
        [
            'start_time'         => '09:00:00',
            'end_time'           => '11:00:00',
            'status'             => ExamSession::STATUS_PUBLISHED,
            'published_at'       => $today->subDays(3),
            'score_release_date' => $today->addDays(7)->toDateString(),
            'created_by'         => $users['admin']->id,
        ]
    );

    $es->proctors()->syncWithoutDetaching([$users['proctor']->id]);

    // Lorena (#6) — already marked present (arrived early for demo purposes)
    $this->attachApplicant($es, $appMap[6]['applicant'], [
        'attendance_status'    => 'present',
        'attendance_marked_at' => $today->setTimeFromTimeString('09:03:00'),
        'attendance_marked_by' => $users['proctor']->id,
        'submission_status'    => 'pending',
    ]);

    // Roberto (#7) and Maribel (#8) — NOT yet marked (live demo marks these)
    $this->attachApplicant($es, $appMap[7]['applicant'], [
        'attendance_status' => 'pending',
        'submission_status' => 'pending',
    ]);

    $this->attachApplicant($es, $appMap[8]['applicant'], [
        'attendance_status' => 'pending',
        'submission_status' => 'pending',
    ]);

    // Open grading session — no scores yet
    GradingSession::query()->updateOrCreate(
        ['exam_session_id' => $es->id],
        [
            'status'    => GradingSession::STATUS_OPEN,
            'opened_at' => $today->setTimeFromTimeString('08:45:00'),
            'opened_by' => $users['test_admin']->id,
        ]
    );
}
```

- [ ] **Step 6.2: Implement `seedSessionD`**

Replace the stub with:

```php
private function seedSessionD(CarbonImmutable $today, Season $season, $rooms): void
{
    $date = $today->addDays(5);
    $room = $rooms[3]; // Vocational Building Lab Room 1

    // Fetch admin user for created_by
    $admin = User::query()->where('email', 'josefina@securecat.local')->first();

    ExamSession::query()->updateOrCreate(
        ['season_id' => $season->id, 'room_id' => $room->id, 'date' => $date->toDateString()],
        [
            'start_time'         => '09:00:00',
            'end_time'           => '11:00:00',
            'status'             => ExamSession::STATUS_PUBLISHED,
            'published_at'       => $today->subDay(),
            'score_release_date' => $date->addDays(7)->toDateString(),
            'created_by'         => $admin?->id,
        ]
    );
    // No applicants assigned yet — shows scheduling backlog on dashboard
}
```

- [ ] **Step 6.3: Run Session C assertion**

```bash
php artisan test tests/Feature/DefenseDemoSeederTest.php --filter="session_c"
```

Expected: `test_session_c_has_one_present_and_two_pending_attendance` PASS.

- [ ] **Step 6.4: Run the full test suite**

```bash
php artisan test tests/Feature/DefenseDemoSeederTest.php
```

Expected: all 10 tests PASS.

- [ ] **Step 6.5: Commit**

```bash
git add database/seeders/DefenseDemoSeeder.php
git commit -m "feat(demo): seed Session C (live demo slot) and Session D (upcoming)"
```

---

## Task 7: Wire Up + Smoke Test + Final Commit

**Files:**
- Modify: `database/seeders/DatabaseSeeder.php` — add seed-order comment

- [ ] **Step 7.1: Update `DatabaseSeeder` with seed order comment**

In `database/seeders/DatabaseSeeder.php`, add the comment block above the `$this->call` array:

```php
public function run(): void
{
    // Base data: roles, courses, exam domains, templates, demo account, season.
    // After this, run DefenseDemoSeeder for defense/demo presentation data:
    //   php artisan db:seed --class=DefenseDemoSeeder
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
```

- [ ] **Step 7.2: Run full defense smoke test locally**

```bash
php artisan migrate:fresh
php artisan db:seed
php artisan db:seed --class=DefenseDemoSeeder
```

Expected output (no warnings, no errors):
```
Seeding: RoleSeeder
...
Seeding: SeasonSeeder
Seeded:  SeasonSeeder (x ms)
Seeding: DefenseDemoSeeder
Seeded:  DefenseDemoSeeder (x ms)
```

- [ ] **Step 7.3: Verify key counts via tinker**

```bash
php artisan tinker --execute="
echo 'Applications: ' . App\Models\Application::count() . PHP_EOL;
echo 'Applicants: '   . App\Models\Applicant::count()   . PHP_EOL;
echo 'Exam sessions: '. App\Models\ExamSession::count()  . PHP_EOL;
echo 'Rooms: '        . App\Models\Room::count()         . PHP_EOL;
echo 'Consultations: '. App\Models\ConsultationSummary::count() . PHP_EOL;
"
```

Expected output:
```
Applications: 20
Applicants: 12
Exam sessions: 4
Rooms: 4
Consultations: 5
```

- [ ] **Step 7.4: Run full test suite**

```bash
php artisan test tests/Feature/DefenseDemoSeederTest.php
```

Expected: 10/10 PASS.

- [ ] **Step 7.5: Final commit**

```bash
git add database/seeders/DatabaseSeeder.php
git commit -m "feat(demo): complete DefenseDemoSeeder — ISPSC Tagudin defense-ready data"
```

---

## Quick Reference: Demo Login Credentials

| Role | Email | Password | Purpose |
|---|---|---|---|
| super_admin | admin@securecat.local | password | Full access |
| admin | josefina@securecat.local | password | Manage sessions/rooms |
| staff | maria@securecat.local | password | Review applications |
| proctor | eduardo@securecat.local | password | **Live demo** — mark attendance |
| test_administrator | analiza@securecat.local | password | Enter scores, release results |
| Applicant (portal) | juan.agustin@ispsc-demo.local | password | View released result |
| Applicant (portal) | lorena.tamayo@ispsc-demo.local | password | View today's exam |

## Defense Demo Sequence

1. **Staff (Maria)** — Applications list → accept a pending applicant live
2. **Admin (Josefina)** — Exam Sessions list → show all 4 sessions in different states
3. **Proctor (Eduardo)** — Session C roster → mark Roberto + Maribel as present (live)
4. **Test Admin (Analiza)** — Session B grading → enter remaining domain scores
5. **Test Admin (Analiza)** — Session A → show finalized grading, release consultation
6. **Print** — Session A result sheets (Juan, Maricel, Reynaldo)
7. **Applicant Portal (Juan)** — log in, see released result
