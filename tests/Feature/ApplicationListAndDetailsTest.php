<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\Course;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApplicationListAndDetailsTest extends TestCase
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

    public function test_authorized_role_can_view_applications_list(): void
    {
        $this->createApplicationRecord();

        $response = $this->actingAs($this->staff)->get('/applications');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Applications/Index')
            ->has('applications')
            ->has('filters')
            ->has('statuses')
            ->where('statuses.0.value', 'pending')
        );
        $props = $response->original->getData()['page']['props'];
        $this->assertNotEmpty($props['applications']['data']);
        $this->assertSame('Juan Santos Dela Cruz', $props['applications']['data'][0]['full_name']);
        $this->assertSame('pending', $props['applications']['data'][0]['status']);
    }

    public function test_applications_list_respects_search_filter(): void
    {
        $this->createApplicationRecord(['first_name' => 'Maria', 'last_name' => 'Clara', 'email' => 'maria@example.com']);

        $response = $this->actingAs($this->staff)->get('/applications?search=Maria');

        $response->assertStatus(200);
        $props = $response->original->getData()['page']['props'];
        $this->assertCount(1, $props['applications']['data']);
        $this->assertStringContainsString('Maria', $props['applications']['data'][0]['full_name']);
    }

    public function test_applications_list_respects_status_filter(): void
    {
        $this->createApplicationRecord(['status' => 'pending']);
        $this->createApplicationRecord(['status' => 'accepted', 'email' => 'accepted@example.com']);

        $response = $this->actingAs($this->staff)->get('/applications?status=accepted');

        $response->assertStatus(200);
        $props = $response->original->getData()['page']['props'];
        $this->assertCount(1, $props['applications']['data']);
        $this->assertSame('accepted', $props['applications']['data'][0]['status']);
    }

    public function test_authorized_role_can_view_application_details(): void
    {
        $app = $this->createApplicationRecord();

        $response = $this->actingAs($this->staff)->get("/applications/{$app->id}");

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Applications/Show')
            ->has('application')
            ->has('courses')
            ->where('application.reference_number', $app->reference_number)
            ->where('application.first_name', 'Juan')
            ->where('application.last_name', 'Dela Cruz')
        );
    }

    public function test_show_returns_404_for_missing_application(): void
    {
        $response = $this->actingAs($this->staff)->get('/applications/99999');

        $response->assertStatus(404);
    }

    public function test_proctor_cannot_view_applications_list(): void
    {
        $proctor = User::factory()->create();
        $proctor->roles()->attach(Role::where('name', 'proctor')->first());

        $response = $this->actingAs($proctor)->get('/applications');

        $response->assertStatus(403);
    }

    public function test_proctor_cannot_view_application_details(): void
    {
        $app = $this->createApplicationRecord();
        $proctor = User::factory()->create();
        $proctor->roles()->attach(Role::where('name', 'proctor')->first());

        $response = $this->actingAs($proctor)->get("/applications/{$app->id}");

        $response->assertStatus(403);
    }

    public function test_unauthenticated_user_redirected_to_login_for_list(): void
    {
        $response = $this->get('/applications');

        $response->assertRedirect(route('login'));
    }

    public function test_unauthenticated_user_redirected_to_login_for_show(): void
    {
        $app = $this->createApplicationRecord();
        $response = $this->get("/applications/{$app->id}");

        $response->assertRedirect(route('login'));
    }
}
