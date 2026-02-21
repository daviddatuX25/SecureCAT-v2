<?php

namespace Tests\Feature;

use App\Models\Applicant;
use App\Models\Application;
use App\Models\Course;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PortalNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function createApplicantWithSetup(): Applicant
    {
        $this->seed(\Database\Seeders\RoleSeeder::class);
        $this->seed(\Database\Seeders\CourseSeeder::class);
        $courses = Course::orderBy('id')->limit(3)->pluck('id')->all();
        $app = Application::create([
            'reference_number' => Application::nextReferenceNumber(),
            'first_name' => 'Maria',
            'last_name' => 'Clara',
            'email' => 'maria@example.com',
            'birthdate' => '2005-06-01',
            'age' => 19,
            'sex' => 'female',
            'status' => 'accepted',
            'submitted_at' => now(),
            'course_preference_1' => $courses[0],
            'course_preference_2' => $courses[1],
            'course_preference_3' => $courses[2],
        ]);
        $applicant = Applicant::create([
            'application_id' => $app->id,
            'email' => $app->email,
            'password' => Hash::make('Password1'),
            'setup_token' => null,
            'setup_token_expires_at' => null,
        ]);

        return $applicant;
    }

    protected function createTestNotification(Applicant $applicant, string $message = 'Test message'): DatabaseNotification
    {
        $applicant->notify(new class($message) extends Notification {
            public function __construct(private readonly string $message) {}

            public function via($notifiable): array
            {
                return ['database'];
            }

            public function toArray($notifiable): array
            {
                return ['message' => $this->message];
            }
        });

        return $applicant->notifications()->first();
    }

    public function test_unauthenticated_cannot_list_notifications(): void
    {
        $response = $this->getJson(route('portal.notifications.index'));
        $response->assertUnauthorized();
    }

    public function test_applicant_can_list_own_notifications(): void
    {
        $applicant = $this->createApplicantWithSetup();
        $this->createTestNotification($applicant, 'Hello');

        $response = $this->actingAs($applicant, 'applicant')->getJson(route('portal.notifications.index'));
        $response->assertOk();
        $response->assertJsonPath('notifications.0.message', 'Hello');
        $response->assertJsonPath('notifications.0.read', false);
    }

    public function test_applicant_can_mark_notification_as_read(): void
    {
        $applicant = $this->createApplicantWithSetup();
        $notification = $this->createTestNotification($applicant);

        $response = $this->actingAs($applicant, 'applicant')->post(route('portal.notifications.read', ['id' => $notification->id]));
        $response->assertRedirect(route('portal.dashboard'));

        $notification->refresh();
        $this->assertNotNull($notification->read_at);
    }

    public function test_applicant_cannot_mark_another_applicants_notification(): void
    {
        $applicant1 = $this->createApplicantWithSetup();
        $app2 = Application::create([
            'reference_number' => Application::nextReferenceNumber(),
            'first_name' => 'Jose',
            'last_name' => 'Rizal',
            'email' => 'jose@example.com',
            'birthdate' => '2005-01-01',
            'age' => 19,
            'sex' => 'male',
            'status' => 'accepted',
            'submitted_at' => now(),
            'course_preference_1' => $applicant1->application->course_preference_1,
            'course_preference_2' => $applicant1->application->course_preference_2,
            'course_preference_3' => $applicant1->application->course_preference_3,
        ]);
        $applicant2 = Applicant::create([
            'application_id' => $app2->id,
            'email' => $app2->email,
            'password' => Hash::make('Password1'),
            'setup_token' => null,
            'setup_token_expires_at' => null,
        ]);
        $notification = $this->createTestNotification($applicant2);

        $response = $this->actingAs($applicant1, 'applicant')->post(route('portal.notifications.read', ['id' => $notification->id]));
        $response->assertForbidden();

        $notification->refresh();
        $this->assertNull($notification->read_at);
    }
}
