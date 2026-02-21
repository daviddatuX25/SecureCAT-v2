<?php

namespace Tests\Feature;

use App\Models\Applicant;
use App\Models\Application;
use App\Models\Course;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PortalAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function createApplicantWithSetup(bool $completedSetup = true): Applicant
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
            'password' => $completedSetup ? Hash::make('Password1') : null,
            'setup_token' => $completedSetup ? null : 'valid-token',
            'setup_token_expires_at' => $completedSetup ? null : now()->addHours(72),
        ]);

        return $applicant;
    }

    public function test_portal_login_page_renders_for_guest(): void
    {
        $response = $this->get(route('portal.login'));
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('Portal/Login'));
    }

    public function test_portal_login_redirects_to_dashboard_when_applicant_authenticated(): void
    {
        $applicant = $this->createApplicantWithSetup(true);
        $response = $this->actingAs($applicant, 'applicant')->get(route('portal.login'));
        $response->assertRedirect(route('portal.dashboard'));
    }

    public function test_applicant_with_completed_setup_can_log_in(): void
    {
        $this->createApplicantWithSetup(true);
        $response = $this->post(route('portal.login'), [
            'email' => 'maria@example.com',
            'password' => 'Password1',
        ]);
        $response->assertRedirect(route('portal.dashboard'));
        $this->assertNotNull(Auth::guard('applicant')->user());
    }

    public function test_applicant_without_setup_cannot_log_in(): void
    {
        $this->createApplicantWithSetup(false);
        $response = $this->post(route('portal.login'), [
            'email' => 'maria@example.com',
            'password' => 'wrongpassword',
        ]);
        $response->assertSessionHasErrors('email');
        $this->assertNull(Auth::guard('applicant')->user());
    }

    public function test_unauthenticated_visitor_redirected_to_portal_login_from_dashboard(): void
    {
        $response = $this->get(route('portal.dashboard'));
        $response->assertRedirect(route('portal.login'));
    }

    public function test_portal_dashboard_renders_for_authenticated_applicant(): void
    {
        $applicant = $this->createApplicantWithSetup(true);
        $response = $this->actingAs($applicant, 'applicant')->get(route('portal.dashboard'));
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('Portal/Dashboard'));
    }
}
