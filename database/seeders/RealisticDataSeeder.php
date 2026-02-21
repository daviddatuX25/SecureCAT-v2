<?php

namespace Database\Seeders;

use App\Models\Application;
use App\Models\Applicant;
use App\Models\Course;
use App\Models\ExamSession;
use App\Models\GradingSession;
use App\Models\Room;
use App\Models\User;
use App\Services\GradingSessionService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeds realistic demo data for wide-range testing: rooms, applications in various statuses,
 * exam sessions (draft, published, in_progress, completed), grading sessions, and mixes
 * of attendance/submission statuses.
 */
class RealisticDataSeeder extends Seeder
{
    private array $courses = [];

    private array $rooms = [];

    private \Illuminate\Support\Collection $proctors;

    private User $admin;

    public function run(): void
    {
        $this->courses = Course::pluck('id')->take(3)->values()->all();
        if (count($this->courses) < 3) {
            $this->command?->warn('RealisticDataSeeder: Need at least 3 courses. Run CourseSeeder first.');

            return;
        }

        $this->admin = User::whereHas('roles', fn ($q) => $q->where('name', 'super_admin'))->first()
            ?? User::whereHas('roles', fn ($q) => $q->where('name', 'admin'))->first()
            ?? User::first();
        $this->proctors = User::whereHas('roles', fn ($q) => $q->where('name', 'proctor'))->get();
        if ($this->proctors->isEmpty()) {
            $this->command?->warn('RealisticDataSeeder: No proctor users found. Run DatabaseSeeder fully first.');

            return;
        }

        $this->seedRooms();
        $applicants = $this->seedApplicationsAndApplicants();
        $this->seedExamSessions($applicants);
    }

    private function seedRooms(): void
    {
        $roomDefs = [
            ['name' => 'IT 101', 'building' => 'IT Building', 'floor' => '1st', 'capacity' => 40],
            ['name' => 'IT 202', 'building' => 'IT Building', 'floor' => '2nd', 'capacity' => 35],
            ['name' => 'Main 301', 'building' => 'Main', 'floor' => '3rd', 'capacity' => 50],
            ['name' => 'Science Lab A', 'building' => 'Science', 'floor' => '1st', 'capacity' => 30],
            ['name' => 'AVR 1', 'building' => 'Main', 'floor' => '2nd', 'capacity' => 60],
        ];

        foreach ($roomDefs as $r) {
            $room = Room::firstOrCreate(
                ['building' => $r['building'], 'name' => $r['name']],
                [
                    'floor' => $r['floor'],
                    'capacity' => $r['capacity'],
                    'facilities' => ['projector' => true, 'ac' => true, 'whiteboard' => true],
                    'is_active' => true,
                ]
            );
            $this->rooms[] = $room;
        }
    }

    /** @return \Illuminate\Support\Collection<int, Applicant> */
    private function seedApplicationsAndApplicants(): \Illuminate\Support\Collection
    {
        $firstNames = [
            'male' => ['Juan', 'Miguel', 'Carlos', 'Rafael', 'Antonio', 'Jose', 'Luis', 'Pedro', 'Francisco', 'Marco'],
            'female' => ['Maria', 'Ana', 'Carmen', 'Rosa', 'Elena', 'Sofia', 'Isabella', 'Gabriela', 'Patricia', 'Lucia'],
        ];
        $lastNames = ['Dela Cruz', 'Santos', 'Reyes', 'Garcia', 'Ramos', 'Mendoza', 'Villanueva', 'Torres', 'Flores', 'Castro'];

        $year = now()->format('Y');
        $baseRef = Application::where('reference_number', 'like', "APP-{$year}-%")->count();
        $applicants = collect();

        // 15 pending
        for ($i = 0; $i < 15; $i++) {
            $sex = $i % 2 === 0 ? 'male' : 'female';
            $first = $firstNames[$sex][$i % 10];
            $last = $lastNames[$i % 10];
            $ref = sprintf('APP-%s-%05d', $year, $baseRef + $i + 1);
            $email = 'pending-' . ($baseRef + $i + 1) . '@applicant.test';
            if (Application::where('reference_number', $ref)->exists()) {
                continue;
            }
            $app = $this->createApplication($ref, $first, $last, $email, $sex, 'pending');
            // No applicant for pending
        }

        // 20 accepted + applicants
        for ($i = 15; $i < 35; $i++) {
            $sex = $i % 2 === 0 ? 'male' : 'female';
            $first = $firstNames[$sex][($i - 15) % 10];
            $last = $lastNames[($i - 15) % 10];
            $ref = sprintf('APP-%s-%05d', $year, $baseRef + $i + 1);
            $email = 'accepted-' . ($baseRef + $i + 1) . '@applicant.test';
            if (Application::where('reference_number', $ref)->exists()) {
                continue;
            }
            $app = $this->createApplication($ref, $first, $last, $email, $sex, 'accepted');
            $applicant = Applicant::create([
                'application_id' => $app->id,
                'email' => $email,
                'password' => null,
                'setup_token' => null,
                'setup_token_expires_at' => null,
            ]);
            $applicants->push($applicant);
        }

        // 5 rejected
        for ($i = 35; $i < 40; $i++) {
            $sex = $i % 2 === 0 ? 'male' : 'female';
            $first = $firstNames[$sex][$i % 10];
            $last = $lastNames[$i % 10];
            $ref = sprintf('APP-%s-%05d', $year, $baseRef + $i + 1);
            $email = 'rejected-' . ($baseRef + $i + 1) . '@applicant.test';
            if (Application::where('reference_number', $ref)->exists()) {
                continue;
            }
            $this->createApplication($ref, $first, $last, $email, $sex, 'rejected', $this->admin->id);
        }

        return $applicants;
    }

    private function createApplication(
        string $ref,
        string $first,
        string $last,
        string $email,
        string $sex,
        string $status,
        ?int $processedBy = null
    ): Application {
        return Application::create([
            'reference_number' => $ref,
            'first_name' => $first,
            'middle_name' => rand(0, 2) === 0 ? 'M.' : null,
            'last_name' => $last,
            'suffix' => null,
            'birthdate' => now()->subYears(18)->subDays(rand(0, 365)),
            'age' => rand(18, 22),
            'sex' => $sex,
            'email' => $email,
            'phone' => $status === 'accepted' ? '09' . rand(100000000, 999999999) : null,
            'address_line' => $status === 'accepted' ? '123 Sample St' : null,
            'city' => $status === 'accepted' ? 'Manila' : null,
            'province' => $status === 'accepted' ? 'Metro Manila' : null,
            'zip_code' => $status === 'accepted' ? '1000' : null,
            'course_preference_1' => $this->courses[0],
            'course_preference_2' => $this->courses[1],
            'course_preference_3' => $this->courses[2],
            'status' => $status,
            'processed_by' => $processedBy,
            'processed_at' => in_array($status, ['accepted', 'rejected']) ? now() : null,
            'rejection_reason' => $status === 'rejected' ? 'Capacity reached.' : null,
            'appointment_id' => null,
            'submitted_at' => now()->subDays(rand(1, 14)),
        ]);
    }

    private function seedExamSessions(\Illuminate\Support\Collection $applicants): void
    {
        $room = $this->rooms[0] ?? Room::first();
        $proctorIds = $this->proctors->pluck('id')->take(2)->all();

        // 1. Draft – future, no applicants
        ExamSession::firstOrCreate(
            [
                'room_id' => $room->id,
                'date' => now()->addDays(14),
                'start_time' => '14:00',
            ],
            [
                'end_time' => '17:00',
                'status' => ExamSession::STATUS_DRAFT,
                'published_at' => null,
                'started_at' => null,
                'closed_at' => null,
                'score_release_date' => null,
                'created_by' => $this->admin->id,
            ]
        );

        // 2. Published – future, with applicants (first 8)
        $publishedApplicants = $applicants->take(8)->values();
        $published = ExamSession::firstOrCreate(
            [
                'room_id' => $room->id,
                'date' => now()->addDays(7),
                'start_time' => '09:00',
            ],
            [
                'end_time' => '12:00',
                'status' => ExamSession::STATUS_PUBLISHED,
                'published_at' => now(),
                'started_at' => null,
                'closed_at' => null,
                'score_release_date' => now()->addDays(21),
                'created_by' => $this->admin->id,
            ]
        );
        $published->proctors()->sync($proctorIds);
        foreach ($publishedApplicants as $a) {
            if (! DB::table('exam_session_applicant')->where('applicant_id', $a->id)->exists()) {
                $published->applicants()->attach($a->id);
            }
        }

        // 3. In progress – today within window, mixed attendance/submission (next 10, excluding already assigned)
        $assignedIds = $published->applicants()->pluck('applicants.id')->all();
        $inProgressApplicants = $applicants->reject(fn ($a) => in_array($a->id, $assignedIds))->take(10)->values();
        $inProgress = ExamSession::firstOrCreate(
            [
                'room_id' => $room->id,
                'date' => now()->format('Y-m-d'),
                'start_time' => '08:00',
            ],
            [
                'end_time' => '11:00',
                'status' => ExamSession::STATUS_IN_PROGRESS,
                'published_at' => now()->subDay(),
                'started_at' => now()->subHours(1),
                'closed_at' => null,
                'score_release_date' => now()->addDays(14),
                'created_by' => $this->admin->id,
            ]
        );
        $inProgress->proctors()->sync($proctorIds);
        $proctorId = $this->proctors->first()->id;
        foreach ($inProgressApplicants as $a) {
            if (! DB::table('exam_session_applicant')->where('applicant_id', $a->id)->exists()) {
                $inProgress->applicants()->attach($a->id);
            }
        }
        $this->seedAttendanceAndSubmissionForSession($inProgress, $proctorId);

        // 4. Completed – past, with grading session (remaining applicants not yet assigned)
        $assignedIds = array_merge(
            $published->applicants()->pluck('applicants.id')->all(),
            $inProgress->applicants()->pluck('applicants.id')->all()
        );
        $completedApplicants = $applicants->reject(fn ($a) => in_array($a->id, $assignedIds))->take(8)->values();
        $completedRoomId = isset($this->rooms[1]) ? $this->rooms[1]->id : $room->id;
        $completed = ExamSession::firstOrCreate(
            [
                'room_id' => $completedRoomId,
                'date' => now()->subDays(3)->format('Y-m-d'),
                'start_time' => '09:00',
            ],
            [
                'end_time' => '12:00',
                'status' => ExamSession::STATUS_COMPLETED,
                'published_at' => now()->subDays(5),
                'started_at' => now()->subDays(3)->setTime(9, 0),
                'closed_at' => now()->subDays(3)->setTime(12, 30),
                'score_release_date' => now()->addDays(7),
                'created_by' => $this->admin->id,
            ]
        );
        $completed->proctors()->sync($proctorIds);
        foreach ($completedApplicants as $a) {
            if (! DB::table('exam_session_applicant')->where('applicant_id', $a->id)->exists()) {
                $completed->applicants()->attach($a->id);
            }
        }
        $this->seedAttendanceAndSubmissionForSession($completed, $proctorId);

        $grader = User::whereHas('roles', fn ($q) => $q->where('name', 'grader'))->first();
        if ($grader && ! $completed->gradingSession) {
            app(GradingSessionService::class)->openForExamSession($completed, $grader);
        }
    }

    private function seedAttendanceAndSubmissionForSession(ExamSession $session, int $proctorId): void
    {
        $pivots = DB::table('exam_session_applicant')
            ->where('exam_session_id', $session->id)
            ->where('attendance_status', 'pending')
            ->get();

        if ($pivots->isEmpty()) {
            return;
        }

        $now = now();
        $take = (int) ceil($pivots->count() * 0.7);
        $presentIds = $pivots->take($take)->pluck('id')->all();
        $absentCount = min(2, $pivots->count() - $take);
        $absentIds = $pivots->skip($take)->take($absentCount)->pluck('id')->all();

        foreach (array_merge($presentIds, $absentIds) as $pivotId) {
            $status = in_array($pivotId, $absentIds, true) ? 'absent' : 'present';
            DB::table('exam_session_applicant')->where('id', $pivotId)->update([
                'attendance_status' => $status,
                'attendance_marked_at' => $now,
                'attendance_marked_by' => $proctorId,
                'updated_at' => $now,
            ]);
        }

        $submittedCount = max(1, (int) floor(count($presentIds) * 0.6));
        $submittedPivotIds = array_slice($presentIds, 0, $submittedCount);
        foreach ($submittedPivotIds as $pivotId) {
            DB::table('exam_session_applicant')->where('id', $pivotId)->update([
                'submission_status' => 'submitted',
                'submitted_at' => $now,
                'submitted_to' => $proctorId,
                'updated_at' => $now,
            ]);
        }
    }
}
