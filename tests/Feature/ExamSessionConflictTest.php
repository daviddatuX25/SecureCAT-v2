<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Applicant;
use App\Models\ExamSession;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ExamSessionConflictTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private AcademicYear $academicYear;

    private Room $room;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create();
        $this->admin->assignRole('super_admin');

        $this->academicYear = AcademicYear::factory()->create(['is_active' => true]);
        $this->room = Room::factory()->create(['is_active' => true, 'capacity' => 30]);
    }

    private function createSession(array $overrides = []): ExamSession
    {
        return ExamSession::factory()->create(array_merge([
            'academic_year_id' => $this->academicYear->id,
            'room_id' => $this->room->id,
            'date' => now()->addDays(3)->format('Y-m-d'),
            'start_time' => '09:00',
            'end_time' => '11:00',
            'status' => ExamSession::STATUS_DRAFT,
            'type' => 'scheduled',
        ], $overrides));
    }

    /** @test */
    public function update_rejects_room_conflict_on_same_date_time(): void
    {
        $existing = $this->createSession();
        $target = $this->createSession(['start_time' => '13:00', 'end_time' => '15:00']);

        $response = $this->actingAs($this->admin)->put(
            route('admin.exam-scheduling.update', $target),
            ['start_time' => '09:30', 'end_time' => '10:30', 'room_id' => $this->room->id],
        );

        $response->assertSessionHasErrors('room_id');
    }

    /** @test */
    public function update_rejects_conflict_when_only_date_changes(): void
    {
        $room2 = Room::factory()->create(['is_active' => true]);
        $existing = $this->createSession(['room_id' => $room2->id]);
        $target = $this->createSession(['room_id' => $room2->id, 'date' => now()->addDays(5)->format('Y-m-d')]);

        $response = $this->actingAs($this->admin)->put(
            route('admin.exam-scheduling.update', $target),
            ['date' => $existing->date->format('Y-m-d')],
        );

        $response->assertSessionHasErrors('room_id');
    }

    /** @test */
    public function update_rejects_conflict_when_only_time_changes(): void
    {
        $existing = $this->createSession();
        $target = $this->createSession(['start_time' => '13:00', 'end_time' => '15:00']);

        $response = $this->actingAs($this->admin)->put(
            route('admin.exam-scheduling.update', $target),
            ['start_time' => '09:30', 'end_time' => '10:30'],
        );

        $response->assertSessionHasErrors('room_id');
    }

    /** @test */
    public function update_allows_self_overlap(): void
    {
        $target = $this->createSession();

        $response = $this->actingAs($this->admin)->put(
            route('admin.exam-scheduling.update', $target),
            ['start_time' => '08:00', 'end_time' => '12:00'],
        );

        $response->assertSessionHasNoErrors();
    }

    /** @test */
    public function update_allows_non_overlapping_time(): void
    {
        $existing = $this->createSession();
        $target = $this->createSession(['start_time' => '13:00', 'end_time' => '15:00']);

        $response = $this->actingAs($this->admin)->put(
            route('admin.exam-scheduling.update', $target),
            ['start_time' => '11:30', 'end_time' => '12:30'],
        );

        $response->assertSessionHasNoErrors();
    }

    /** @test */
    public function update_skips_conflict_check_for_direct_sessions(): void
    {
        $target = ExamSession::factory()->create([
            'academic_year_id' => $this->academicYear->id,
            'room_id' => null,
            'date' => now()->format('Y-m-d'),
            'start_time' => now()->format('H:i'),
            'end_time' => null,
            'status' => ExamSession::STATUS_COMPLETED,
            'type' => 'direct',
        ]);

        $response = $this->actingAs($this->admin)->put(
            route('admin.exam-scheduling.update', $target),
            ['type' => 'direct'],
        );

        $response->assertSessionHasNoErrors();
    }

    /** @test */
    public function store_rejects_room_conflict(): void
    {
        $existing = $this->createSession();

        $response = $this->actingAs($this->admin)->post(
            route('admin.exam-scheduling.store'),
            [
                'room_id' => $this->room->id,
                'date' => $existing->date->format('Y-m-d'),
                'start_time' => '09:30',
                'end_time' => '10:30',
                'academic_year_id' => $this->academicYear->id,
            ],
        );

        $response->assertSessionHasErrors('room_id');
    }

    /** @test */
    public function store_allows_non_conflicting_session(): void
    {
        $this->createSession();

        $response = $this->actingAs($this->admin)->post(
            route('admin.exam-scheduling.store'),
            [
                'room_id' => $this->room->id,
                'date' => now()->addDays(3)->format('Y-m-d'),
                'start_time' => '13:00',
                'end_time' => '15:00',
                'academic_year_id' => $this->academicYear->id,
            ],
        );

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('admin.exam-scheduling.index'));
    }

    /** @test */
    public function end_time_midnight_and_empty_string_normalization_and_conflict_handling(): void
    {
        // 1. Assert mutator normalizes empty string and '00:00:00' to null
        $session1 = $this->createSession(['end_time' => '']);
        $this->assertNull($session1->getRawOriginal('end_time'));

        $session2 = $this->createSession(['end_time' => '00:00:00']);
        $this->assertNull($session2->getRawOriginal('end_time'));

        $session3 = $this->createSession(['end_time' => '00:00']);
        $this->assertNull($session3->getRawOriginal('end_time'));

        // 2. Assert hasRoomConflict correctly detects conflicts against database sessions with '00:00:00' (treated as 23:59:59)
        // Let's force raw DB insert to have '00:00:00' (simulating existing faulty database records)
        DB::table('exam_sessions')->where('id', $session1->id)->update(['end_time' => '00:00:00']);

        // Now check if a new session starting at 09:30 conflicts with $session1 (which starts at 09:00 and ends at 00:00:00)
        $hasConflict = ExamSession::hasRoomConflict(
            $this->room->id,
            $session1->date->format('Y-m-d'),
            '09:30',
            '10:30'
        );
        $this->assertTrue($hasConflict);
    }

    public function test_monitoring_endpoint_returns_correct_applicant_statistics(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $activeAy = AcademicYear::active() ?: AcademicYear::factory()->create(['is_active' => true]);

        $session = $this->createSession([
            'academic_year_id' => $activeAy->id,
            'status' => ExamSession::STATUS_PUBLISHED,
            'date' => now()->toDateString(),
            'start_time' => '08:00:00',
            'end_time' => '10:00:00',
        ]);

        $applicant1 = Applicant::factory()->create();
        $applicant2 = Applicant::factory()->create();

        // Attach applicants with attendance and submission statuses
        $session->applicants()->attach($applicant1->id, [
            'attendance_status' => 'present',
            'submission_status' => 'submitted',
        ]);
        $session->applicants()->attach($applicant2->id, [
            'attendance_status' => 'present',
            'submission_status' => 'pending',
        ]);

        $response = $this->actingAs($user)
            ->get('/admin/exam-monitoring');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/TestScheduling/Monitoring')
            ->has('sessions', 1, fn ($pageSession) => $pageSession
                ->where('total_count', 2)
                ->where('present_count', 2)
                ->where('submitted_count', 1)
                ->where('absent_count', 0)
                ->etc()
            )
        );
    }
}
