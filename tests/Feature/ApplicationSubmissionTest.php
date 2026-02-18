<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ApplicationSubmissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedCourses();
    }

    private function seedCourses(): void
    {
        if (! DB::getSchemaBuilder()->hasTable('departments')) {
            return;
        }
        $deptId = DB::table('departments')->insertGetId([
            'name' => 'College of IT',
            'code' => 'CIT',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        foreach (
            [
                ['name' => 'Bachelor of Science in Information Technology', 'code' => 'BSIT'],
                ['name' => 'Bachelor of Science in Computer Science', 'code' => 'BSCS'],
                ['name' => 'Bachelor of Science in Data Science', 'code' => 'BSDS'],
            ] as $i => $c
        ) {
            DB::table('courses')->insert([
                'department_id' => $deptId,
                'name' => $c['name'],
                'code' => $c['code'],
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function test_apply_page_renders_with_courses(): void
    {
        $response = $this->get('/apply');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Applications/Apply')
            ->has('courses')
        );
    }

    public function test_valid_application_submission_creates_record_and_redirects(): void
    {
        $courses = DB::table('courses')->orderBy('id')->pluck('id')->all();
        if (count($courses) < 3) {
            $this->markTestSkipped('Applications table requires courses; migrations may not be run.');
        }

        $payload = [
            'first_name' => 'Juan',
            'middle_name' => 'Santos',
            'last_name' => 'Dela Cruz',
            'suffix' => '',
            'birthdate' => '2005-01-15',
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
            'appointment_id' => '',
        ];

        $response = $this->post('/applications', $payload);

        $response->assertRedirect(route('applications.success'));
        $response->assertSessionHas('reference_number');

        $this->assertDatabaseHas('applications', [
            'email' => 'juan.delacruz@example.com',
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'status' => 'pending',
        ]);
        $app = DB::table('applications')->where('email', 'juan.delacruz@example.com')->first();
        $this->assertNotNull($app);
        $this->assertMatchesRegularExpression('/^APP-\d{4}-\d+$/', $app->reference_number);
    }

    public function test_application_submission_validates_required_fields(): void
    {
        $response = $this->post('/applications', [
            'first_name' => '',
            'last_name' => '',
            'email' => 'invalid',
            'birthdate' => '2010-01-01',
            'sex' => 'male',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors(['first_name', 'last_name', 'email', 'course_preference_1', 'course_preference_2', 'course_preference_3']);
    }

    public function test_application_submission_rejects_duplicate_course_preferences(): void
    {
        $courses = DB::table('courses')->orderBy('id')->pluck('id')->all();
        if (count($courses) < 2) {
            $this->markTestSkipped('Need at least 2 courses.');
        }

        $response = $this->post('/applications', [
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'birthdate' => '2005-01-15',
            'sex' => 'male',
            'email' => 'juan@example.com',
            'course_preference_1' => $courses[0],
            'course_preference_2' => $courses[0],
            'course_preference_3' => $courses[1],
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors(['course_preference_2']);
    }
}
