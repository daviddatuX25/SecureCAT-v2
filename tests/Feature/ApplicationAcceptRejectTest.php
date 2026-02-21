<?php

namespace Tests\Feature;

use App\Jobs\SendApplicantSetupEmail;
use App\Models\Applicant;
use App\Models\Appointment;
use App\Models\Application;
use App\Models\Course;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ApplicationAcceptRejectTest extends TestCase
{
    use RefreshDatabase;

    protected User $staff;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RoleSeeder::class);
        $this->seed(\Database\Seeders\CourseSeeder::class);
        $this->staff = User::factory()->create();
        $this->staff->roles()->attach(Role::where('name', 'staff')->first());
    }

    protected function createApplicationRecord(array $overrides = []): Application
    {
        $courses = Course::orderBy('id')->pluck('id')->all();
        if (count($courses) < 3) {
            $this->markTestSkipped('Need courses seeded.');
        }

        return Application::create(array_merge([
            'reference_number' => Application::nextReferenceNumber(),
            'first_name' => 'Juan',
            'middle_name' => 'Santos',
            'last_name' => 'Dela Cruz',
            'suffix' => null,
            'birthdate' => '2005-01-15',
            'age' => 19,
            'sex' => 'male',
            'email' => 'juan.delacruz@example.com',
            'phone' => '09171234567',
            'address_line' => '123 Main St',
            'city' => 'Manila',
            'province' => 'NCR',
            'zip_code' => '1000',
            'course_preference_1' => $courses[0],
            'course_preference_2' => $courses[1],
            'course_preference_3' => $courses[2],
            'status' => 'pending',
            'appointment_id' => null,
            'submitted_at' => now(),
        ], $overrides));
    }

    public function test_staff_can_accept_pending_application(): void
    {
        Queue::fake();

        $app = $this->createApplicationRecord();

        $response = $this->actingAs($this->staff)->put("/applications/{$app->id}/accept");

        $response->assertRedirect(route('applications.show', $app));
        $response->assertSessionHas('success');

        $app->refresh();
        $this->assertSame('accepted', $app->status);
        $this->assertSame($this->staff->id, $app->processed_by);
        $this->assertNotNull($app->processed_at);

        $applicant = Applicant::where('application_id', $app->id)->first();
        $this->assertNotNull($applicant);
        $this->assertSame($app->email, $applicant->email);
        $this->assertNotNull($applicant->setup_token);
        $this->assertNotNull($applicant->setup_token_expires_at);

        Queue::assertPushed(SendApplicantSetupEmail::class);
    }

    public function test_accept_returns_error_when_application_not_pending(): void
    {
        $app = $this->createApplicationRecord(['status' => 'accepted']);

        $response = $this->actingAs($this->staff)->put("/applications/{$app->id}/accept");

        $response->assertRedirect();
        $response->assertSessionHas('error');

        $app->refresh();
        $this->assertSame('accepted', $app->status);
    }

    public function test_staff_can_reject_pending_application_with_reason(): void
    {
        $app = $this->createApplicationRecord();

        $response = $this->actingAs($this->staff)->put("/applications/{$app->id}/reject", [
            'reason' => 'Incomplete requirements.',
        ]);

        $response->assertRedirect(route('applications.show', $app));
        $response->assertSessionHas('success');

        $app->refresh();
        $this->assertSame('rejected', $app->status);
        $this->assertSame('Incomplete requirements.', $app->rejection_reason);
        $this->assertSame($this->staff->id, $app->processed_by);
        $this->assertNotNull($app->processed_at);
    }

    public function test_reject_validates_reason_required(): void
    {
        $app = $this->createApplicationRecord();

        $response = $this->actingAs($this->staff)->put("/applications/{$app->id}/reject", [
            'reason' => '',
        ]);

        $response->assertSessionHasErrors('reason');

        $app->refresh();
        $this->assertSame('pending', $app->status);
    }

    public function test_reject_returns_error_when_application_not_pending(): void
    {
        $app = $this->createApplicationRecord(['status' => 'rejected']);

        $response = $this->actingAs($this->staff)->put("/applications/{$app->id}/reject", [
            'reason' => 'Some reason.',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');

        $app->refresh();
        $this->assertSame('rejected', $app->status);
    }

    /** E-032: Rejecting application with appointment decrements booked_count. */
    public function test_reject_decrements_appointment_booked_count(): void
    {
        $apt = Appointment::create([
            'date' => now()->addDays(7),
            'time_slot' => '09:00:00',
            'duration_minutes' => 30,
            'max_slots' => 10,
            'booked_count' => 1,
            'is_active' => true,
        ]);
        $app = $this->createApplicationRecord(['appointment_id' => $apt->id]);

        $this->actingAs($this->staff)->put("/applications/{$app->id}/reject", [
            'reason' => 'Test.',
        ]);

        $apt->refresh();
        $this->assertSame(0, $apt->booked_count);
    }

    public function test_admission_slip_returns_pdf_for_accepted_application(): void
    {
        $app = $this->createApplicationRecord(['status' => 'accepted']);

        $response = $this->actingAs($this->staff)->get("/applications/{$app->id}/admission-slip");

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_admission_slip_returns_403_for_pending_application(): void
    {
        $app = $this->createApplicationRecord(['status' => 'pending']);

        $response = $this->actingAs($this->staff)->get("/applications/{$app->id}/admission-slip");

        $response->assertStatus(403);
    }

    public function test_admission_slip_returns_403_for_rejected_application(): void
    {
        $app = $this->createApplicationRecord(['status' => 'rejected']);

        $response = $this->actingAs($this->staff)->get("/applications/{$app->id}/admission-slip");

        $response->assertStatus(403);
    }

    public function test_staff_can_resend_setup_email_for_accepted_application(): void
    {
        Queue::fake();

        $app = $this->createApplicationRecord(['status' => 'accepted']);
        $applicant = Applicant::create([
            'application_id' => $app->id,
            'email' => $app->email,
            'setup_token' => Applicant::generateSetupToken(),
            'setup_token_expires_at' => now()->addHours(72),
        ]);

        $response = $this->actingAs($this->staff)->post("/applications/{$app->id}/resend-setup-email");

        $response->assertRedirect(route('applications.show', $app));
        $response->assertSessionHas('success');

        $applicant->refresh();
        $this->assertNotNull($applicant->setup_token);
        $this->assertTrue($applicant->setup_token_expires_at->isFuture());

        Queue::assertPushed(SendApplicantSetupEmail::class);
    }

    public function test_resend_setup_email_returns_error_for_pending_application(): void
    {
        $app = $this->createApplicationRecord(['status' => 'pending']);

        $response = $this->actingAs($this->staff)->post("/applications/{$app->id}/resend-setup-email");

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public function test_resend_setup_email_when_applicant_already_set_up_returns_info_message(): void
    {
        $app = $this->createApplicationRecord(['status' => 'accepted']);
        Applicant::create([
            'application_id' => $app->id,
            'email' => $app->email,
            'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
            'setup_token' => null,
            'setup_token_expires_at' => null,
        ]);

        $response = $this->actingAs($this->staff)->post("/applications/{$app->id}/resend-setup-email");

        $response->assertRedirect(route('applications.show', $app));
        $response->assertSessionHas('success');
    }
}
