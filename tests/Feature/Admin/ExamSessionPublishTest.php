<?php

namespace Tests\Feature\Admin;

use App\Models\AcademicYear;
use App\Models\Applicant;
use App\Models\Application;
use App\Models\ExamSession;
use App\Models\Role;
use App\Models\Room;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExamSessionPublishTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    private function admin(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('name', 'registrar_administrator')->first());

        return $user;
    }

    private function createRoom(): Room
    {
        return Room::factory()->create();
    }

    private function createSession(array $overrides = []): ExamSession
    {
        $room = $this->createRoom();
        $academicYear = AcademicYear::factory()->create(['is_active' => true]);

        return ExamSession::create(array_merge([
            'academic_year_id' => $academicYear->id,
            'room_id' => $room->id,
            'date' => now()->addDay()->toDateString(),
            'start_time' => '09:00',
            'end_time' => '12:00',
            'status' => ExamSession::STATUS_DRAFT,
            'type' => ExamSession::TYPE_SCHEDULED,
            'created_by' => $this->admin()->id,
        ], $overrides));
    }

    private function attachApplicant(ExamSession $session): void
    {
        $application = Application::factory()->create(['status' => 'accepted']);
        $applicant = Applicant::factory()->create(['application_id' => $application->id]);
        $session->applicants()->attach($applicant->id);
    }

    // --- publishBlockReason model tests ---

    public function test_publish_block_reason_returns_message_for_past_date(): void
    {
        $session = $this->createSession(['date' => '2020-01-01']);

        $this->assertEquals('Cannot publish a session with a past date.', $session->publishBlockReason());
        $this->assertFalse($session->is_publishable);
    }

    public function test_publish_block_reason_returns_message_when_no_applicants(): void
    {
        $session = $this->createSession();

        $this->assertEquals('Assign at least one applicant before publishing.', $session->publishBlockReason());
        $this->assertFalse($session->is_publishable);
    }

    public function test_publish_block_reason_returns_message_when_missing_scheduling_fields(): void
    {
        // Test model method directly without persisting — DB NOT NULL constraints prevent null inserts
        $session = new ExamSession([
            'status' => ExamSession::STATUS_DRAFT,
            'date' => null,
            'start_time' => null,
            'room_id' => null,
        ]);

        $this->assertEquals('Set the date, start time, and room before publishing.', $session->publishBlockReason());
    }

    public function test_publish_block_reason_returns_message_for_in_progress_status(): void
    {
        $session = $this->createSession(['status' => ExamSession::STATUS_IN_PROGRESS]);

        $this->assertStringContainsString('in progress', $session->publishBlockReason());
    }

    public function test_publish_block_reason_returns_null_for_publishable_session(): void
    {
        $session = $this->createSession();
        $this->attachApplicant($session);

        $this->assertNull($session->refresh()->publishBlockReason());
        $this->assertTrue($session->refresh()->is_publishable);
    }

    public function test_publish_block_reason_returns_message_for_past_end_time_today(): void
    {
        $pastEndTime = now()->subHour()->format('H:i');
        $session = $this->createSession([
            'date' => now()->toDateString(),
            'end_time' => $pastEndTime,
        ]);
        $this->attachApplicant($session);

        $reason = $session->refresh()->publishBlockReason();
        $this->assertStringContainsString('end time has already passed', $reason);
    }

    // --- Controller publish endpoint tests ---

    public function test_publish_rejects_past_date(): void
    {
        $session = $this->createSession(['date' => '2020-01-01']);
        $this->attachApplicant($session);

        $response = $this->actingAs($this->admin())
            ->post(route('admin.exam-scheduling.publish', $session));

        $response->assertSessionHas('error');
        $this->assertEquals(ExamSession::STATUS_DRAFT, $session->refresh()->status);
    }

    public function test_publish_rejects_session_without_applicants(): void
    {
        $session = $this->createSession();

        $response = $this->actingAs($this->admin())
            ->post(route('admin.exam-scheduling.publish', $session));

        $response->assertSessionHas('error');
        $this->assertEquals(ExamSession::STATUS_DRAFT, $session->refresh()->status);
    }

    public function test_publish_rejects_past_end_time_today(): void
    {
        $pastEndTime = now()->subHour()->format('H:i');
        $session = $this->createSession([
            'date' => now()->toDateString(),
            'end_time' => $pastEndTime,
        ]);
        $this->attachApplicant($session);

        $response = $this->actingAs($this->admin())
            ->post(route('admin.exam-scheduling.publish', $session->refresh()));

        $response->assertSessionHas('error');
        $this->assertEquals(ExamSession::STATUS_DRAFT, $session->refresh()->status);
    }

    public function test_publish_rejects_completed_session(): void
    {
        $session = $this->createSession(['status' => ExamSession::STATUS_COMPLETED]);
        $this->attachApplicant($session);

        $response = $this->actingAs($this->admin())
            ->post(route('admin.exam-scheduling.publish', $session));

        $response->assertSessionHas('error');
    }

    public function test_publish_rejects_cancelled_session(): void
    {
        $session = $this->createSession(['status' => ExamSession::STATUS_CANCELLED]);
        $this->attachApplicant($session);

        $response = $this->actingAs($this->admin())
            ->post(route('admin.exam-scheduling.publish', $session));

        $response->assertSessionHas('error');
    }

    public function test_publish_succeeds_for_valid_session(): void
    {
        $session = $this->createSession();
        $this->attachApplicant($session);

        $response = $this->actingAs($this->admin())
            ->post(route('admin.exam-scheduling.publish', $session->refresh()));

        $response->assertSessionHas('success');
        $this->assertEquals(ExamSession::STATUS_PUBLISHED, $session->refresh()->status);
        $this->assertNotNull($session->refresh()->published_at);
    }
}
