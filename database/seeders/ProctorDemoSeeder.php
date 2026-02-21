<?php

namespace Database\Seeders;

use App\Models\Application;
use App\Models\Applicant;
use App\Models\Course;
use App\Models\ExamSession;
use App\Models\Room;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeds demo data for the Proctor roster: one or more exam sessions (published or in_progress)
 * with assigned applicants and proctors, and a mix of attendance/submission statuses.
 */
class ProctorDemoSeeder extends Seeder
{
    public function run(): void
    {
        $proctor = User::whereHas('roles', fn ($q) => $q->where('name', 'proctor'))->first();
        if (! $proctor) {
            return;
        }

        $room = Room::where('is_active', true)->first() ?? Room::first();
        if (! $room) {
            $room = Room::create([
                'name' => 'Demo Room A',
                'building' => 'Main',
                'floor' => 1,
                'capacity' => 40,
                'is_active' => true,
            ]);
        }

        $session = ExamSession::query()
            ->whereIn('status', [ExamSession::STATUS_PUBLISHED, ExamSession::STATUS_IN_PROGRESS])
            ->whereHas('proctors')
            ->whereHas('applicants')
            ->first();

        if (! $session) {
            $session = $this->createDemoSession($room, $proctor);
        }

        $this->seedAttendanceAndSubmission($session, $proctor);
    }

    private function createDemoSession(Room $room, User $proctor): ExamSession
    {
        $creator = User::whereHas('roles', fn ($q) => $q->where('name', 'super_admin'))->first()
            ?? User::first();

        $session = ExamSession::create([
            'room_id' => $room->id,
            'date' => now()->addDays(7),
            'start_time' => '09:00',
            'end_time' => '12:00',
            'status' => ExamSession::STATUS_PUBLISHED,
            'published_at' => now(),
            'started_at' => null,
            'closed_at' => null,
            'score_release_date' => null,
            'created_by' => $creator->id,
        ]);
        $session->proctors()->attach($proctor->id);

        $applicants = Applicant::query()
            ->whereHas('application', fn ($q) => $q->where('status', 'accepted'))
            ->whereDoesntHave('examSessions')
            ->limit(8)
            ->get();

        if ($applicants->isEmpty()) {
            $applicants = $this->createDemoApplicants(5);
        } else {
            $applicants = $applicants->take(5);
        }

        foreach ($applicants as $applicant) {
            $session->applicants()->attach($applicant->id);
        }

        return $session;
    }

    /** @return \Illuminate\Support\Collection<int, Applicant> */
    private function createDemoApplicants(int $count): \Illuminate\Support\Collection
    {
        $courses = Course::pluck('id')->take(3)->values()->all();
        if (count($courses) < 3) {
            return collect();
        }

        $applicants = collect();
        $year = now()->format('Y');
        $base = Application::where('reference_number', 'like', "APP-{$year}-%")->count() + 1;

        for ($i = 0; $i < $count; $i++) {
            $ref = sprintf('APP-%s-%05d', $year, $base + $i);
            if (Application::where('reference_number', $ref)->exists()) {
                continue;
            }
            $email = 'demo-roster-' . ($base + $i) . '@example.com';
            if (Applicant::where('email', $email)->exists()) {
                continue;
            }
            $app = Application::create([
                'reference_number' => $ref,
                'first_name' => 'Demo',
                'middle_name' => null,
                'last_name' => "Applicant{$i}",
                'suffix' => null,
                'birthdate' => now()->subYears(20),
                'age' => 20,
                'sex' => 'male',
                'email' => $email,
                'phone' => null,
                'address_line' => null,
                'city' => null,
                'province' => null,
                'zip_code' => null,
                'course_preference_1' => $courses[0],
                'course_preference_2' => $courses[1],
                'course_preference_3' => $courses[2],
                'status' => 'accepted',
                'processed_by' => null,
                'processed_at' => null,
                'rejection_reason' => null,
                'appointment_id' => null,
                'submitted_at' => now(),
            ]);
            $applicant = Applicant::create([
                'application_id' => $app->id,
                'email' => $email,
                'password' => null,
                'setup_token' => null,
                'setup_token_expires_at' => null,
            ]);
            $applicants->push($applicant);
        }

        return $applicants;
    }

    private function seedAttendanceAndSubmission(ExamSession $session, User $proctor): void
    {
        $pivots = DB::table('exam_session_applicant')
            ->where('exam_session_id', $session->id)
            ->where('attendance_status', 'pending')
            ->get();

        if ($pivots->isEmpty()) {
            return;
        }

        $now = now();
        $proctorId = $proctor->id;
        $take = (int) ceil($pivots->count() * 0.6);
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
