<?php

namespace Tests\Feature\Admin;

use App\Models\AcademicYear;
use App\Models\Applicant;
use App\Models\Application;
use App\Models\ExamSession;
use App\Models\Role;
use App\Models\Room;
use App\Models\User;
use App\Notifications\ExamSessionPublished;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ExamSessionAssignNotificationTest extends TestCase
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

    private function createSession(array $overrides = []): ExamSession
    {
        $room = Room::factory()->create();
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

    private function createApplicant(): Applicant
    {
        $application = Application::factory()->create(['status' => 'accepted']);

        return Applicant::factory()->create(['application_id' => $application->id]);
    }

    // --- Notification on assign to published session ---

    public function test_assign_to_published_session_sends_notification(): void
    {
        Notification::fake();

        $session = $this->createSession(['status' => ExamSession::STATUS_PUBLISHED]);
        $applicant = $this->createApplicant();

        $this->actingAs($this->admin())
            ->post(route('admin.exam-scheduling.assign-applicants', $session), [
                'applicant_ids' => [$applicant->id],
            ]);

        Notification::assertSentTo($applicant, ExamSessionPublished::class);
    }

    public function test_assign_to_in_progress_session_sends_notification(): void
    {
        Notification::fake();

        $session = $this->createSession(['status' => ExamSession::STATUS_IN_PROGRESS]);
        $applicant = $this->createApplicant();

        $this->actingAs($this->admin())
            ->post(route('admin.exam-scheduling.assign-applicants', $session), [
                'applicant_ids' => [$applicant->id],
            ]);

        Notification::assertSentTo($applicant, ExamSessionPublished::class);
    }

    public function test_assign_to_draft_session_does_not_send_notification(): void
    {
        Notification::fake();

        $session = $this->createSession(['status' => ExamSession::STATUS_DRAFT]);
        $applicant = $this->createApplicant();

        $this->actingAs($this->admin())
            ->post(route('admin.exam-scheduling.assign-applicants', $session), [
                'applicant_ids' => [$applicant->id],
            ]);

        Notification::assertNotSentTo($applicant, ExamSessionPublished::class);
    }

    public function test_assign_to_completed_session_is_rejected(): void
    {
        $session = $this->createSession(['status' => ExamSession::STATUS_COMPLETED]);
        $applicant = $this->createApplicant();

        $response = $this->actingAs($this->admin())
            ->post(route('admin.exam-scheduling.assign-applicants', $session), [
                'applicant_ids' => [$applicant->id],
            ]);

        $response->assertSessionHas('error');
    }
}
