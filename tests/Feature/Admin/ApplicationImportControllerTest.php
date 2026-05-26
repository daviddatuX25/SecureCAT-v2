<?php

namespace Tests\Feature\Admin;

use App\Models\AcademicYear;
use App\Models\Application;
use App\Models\Course;
use App\Models\User;
use App\Services\SpreadsheetParser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ApplicationImportControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private AcademicYear $academicYear;

    private Course $course;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create();
        $this->admin->assignRole('super_admin');

        $this->academicYear = AcademicYear::factory()->create([
            'academic_year' => '2026-2027',
            'semester' => 1,
        ]);

        $this->course = Course::factory()->create(['code' => 'BSCS', 'is_active' => true]);
    }

    /**
     * Build a complete valid record with all DB-required fields.
     */
    private function validRecord(array $overrides = []): array
    {
        return array_merge([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'birthdate' => '2000-01-15',
            'sex' => 'Male',
            'course_preference_1' => $this->course->code,
        ], $overrides);
    }

    private function makeCsv(array $headers, array $rows): UploadedFile
    {
        $content = implode(',', $headers)."\n";
        foreach ($rows as $row) {
            $content .= implode(',', $row)."\n";
        }

        $path = tempnam(sys_get_temp_dir(), 'csv');
        file_put_contents($path, $content);

        return new UploadedFile($path, 'test.csv', 'text/csv', null, true);
    }

    // -- Import form access --

    public function test_admin_can_access_import_form(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/applications/import');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Applications/Import')
            ->has('requiredColumns')
            ->has('optionalColumns')
            ->has('academicYears')
        );
    }

    // -- Analyze endpoint --

    public function test_analyze_returns_column_analysis_for_valid_csv(): void
    {
        $file = $this->makeCsv(
            ['first_name', 'last_name', 'email', 'birthdate', 'sex', 'course_preference_1'],
            [['John', 'Doe', 'john@example.com', '2000-01-15', 'Male', 'BSCS']]
        );

        $response = $this->actingAs($this->admin)
            ->postJson('/admin/applications/import/analyze', ['file' => $file]);

        $response->assertOk();
        $response->assertJsonStructure([
            'headers',
            'raw_headers',
            'row_count',
            'column_analysis',
            'missing_required',
            'checks',
        ]);
        $response->assertJsonPath('row_count', 1);
        $response->assertJsonPath('missing_required', []);
    }

    public function test_analyze_detects_missing_required_columns(): void
    {
        $file = $this->makeCsv(
            ['first_name', 'phone'],
            [['John', '1234567890']]
        );

        $response = $this->actingAs($this->admin)
            ->postJson('/admin/applications/import/analyze', ['file' => $file]);

        $response->assertOk();
        $data = $response->json();
        $this->assertContains('last_name', $data['missing_required']);

        // Should have a failed check for required columns
        $requiredCheck = collect($data['checks'])->firstWhere('label', 'Required columns');
        $this->assertEquals('fail', $requiredCheck['status']);
    }

    public function test_analyze_normalizes_headers_with_spaces(): void
    {
        $file = $this->makeCsv(
            ['first name', 'last name', 'email', 'zip code'],
            [['John', 'Doe', 'john@example.com', '1000']]
        );

        $response = $this->actingAs($this->admin)
            ->postJson('/admin/applications/import/analyze', ['file' => $file]);

        $response->assertOk();
        $data = $response->json();
        $this->assertEmpty($data['missing_required']);

        // Check column analysis shows proper normalization
        $firstNameCol = collect($data['column_analysis'])->firstWhere('raw', 'first name');
        $this->assertEquals('first_name', $firstNameCol['normalized']);
        $this->assertEquals('required', $firstNameCol['status']);
    }

    public function test_analyze_rejects_invalid_file_type(): void
    {
        $file = UploadedFile::fake()->create('test.pdf', 100, 'application/pdf');

        $response = $this->actingAs($this->admin)
            ->postJson('/admin/applications/import/analyze', ['file' => $file]);

        $response->assertUnprocessable();
    }

    // -- Preview endpoint --

    public function test_preview_parses_and_validates_csv(): void
    {
        $file = $this->makeCsv(
            ['first_name', 'last_name', 'email', 'birthdate', 'sex', 'course_preference_1'],
            [
                ['John', 'Doe', 'john@example.com', '2000-01-15', 'Male', 'BSCS'],
                ['', 'Smith', 'invalid-email', '', '', ''],
            ]
        );

        $response = $this->actingAs($this->admin)
            ->post('/admin/applications/import/preview', [
                'file' => $file,
                'academic_year_id' => $this->academicYear->id,
            ]);

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Applications/ImportPreview')
            ->has('records', 2)
            ->where('totalCount', 2)
            ->where('validCount', 1)
        );
    }

    public function test_preview_shows_validation_errors(): void
    {
        $file = $this->makeCsv(
            ['first_name', 'last_name', 'email'],
            [['John', 'Doe', 'invalid-email']]
        );

        $response = $this->actingAs($this->admin)
            ->post('/admin/applications/import/preview', [
                'file' => $file,
                'academic_year_id' => $this->academicYear->id,
            ]);

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Applications/ImportPreview')
            ->where('validCount', 0) // Invalid email
        );
    }

    public function test_preview_handles_headers_with_spaces(): void
    {
        $file = $this->makeCsv(
            ['first name', 'last name', 'email', 'birthdate', 'sex', 'course preference 1'],
            [['Maria', 'Santos', 'maria@example.com', '2000-06-15', 'female', 'BSCS']]
        );

        $response = $this->actingAs($this->admin)
            ->post('/admin/applications/import/preview', [
                'file' => $file,
                'academic_year_id' => $this->academicYear->id,
            ]);

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Applications/ImportPreview')
            ->where('validCount', 1)
        );
    }

    // -- Confirm endpoint --

    public function test_confirm_imports_valid_records(): void
    {
        $records = [
            $this->validRecord(['email' => 'john@example.com']),
            $this->validRecord(['email' => 'jane@example.com', 'first_name' => 'Jane', 'last_name' => 'Smith']),
        ];

        $response = $this->actingAs($this->admin)
            ->withSession([
                'import_records' => $records,
                'import_academic_year_id' => $this->academicYear->id,
            ])
            ->post('/admin/applications/import/confirm', [
                'selected_ids' => [0, 1],
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('message');

        $this->assertDatabaseHas('applications', [
            'email' => 'john@example.com',
            'academic_year_id' => $this->academicYear->id,
        ]);
        $this->assertDatabaseHas('applications', [
            'email' => 'jane@example.com',
            'academic_year_id' => $this->academicYear->id,
        ]);
    }

    public function test_confirm_skips_invalid_records_with_feedback(): void
    {
        $records = [
            $this->validRecord(['email' => 'good@example.com']),
            $this->validRecord(['email' => 'good@example.com']), // duplicate email
        ];

        $response = $this->actingAs($this->admin)
            ->withSession([
                'import_records' => $records,
                'import_academic_year_id' => $this->academicYear->id,
            ])
            ->post('/admin/applications/import/confirm', [
                'selected_ids' => [0, 1],
            ]);

        $response->assertRedirect();
        // Should import first, skip second as duplicate
        $this->assertEquals(1, Application::where('email', 'good@example.com')->count());
    }

    public function test_confirm_redirects_when_no_session_data(): void
    {
        $response = $this->actingAs($this->admin)
            ->post('/admin/applications/import/confirm', [
                'selected_ids' => [0],
            ]);

        $response->assertRedirect(route('admin.applications.import'));
        $response->assertSessionHas('error');
    }

    // -- Template download --

    public function test_template_download_returns_csv(): void
    {
        $response = $this->actingAs($this->admin)
            ->get('/admin/applications/import/template');

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $response->assertDownload('applicant_import_template.csv');
    }

    // -- SpreadsheetParser unit tests --

    public function test_normalize_header_converts_spaces_to_underscores(): void
    {
        $this->assertEquals('first_name', SpreadsheetParser::normalizeHeader('first name'));
        $this->assertEquals('first_name', SpreadsheetParser::normalizeHeader('First Name'));
        $this->assertEquals('first_name', SpreadsheetParser::normalizeHeader('FIRST_NAME'));
        $this->assertEquals('zip_code', SpreadsheetParser::normalizeHeader('zip code'));
        $this->assertEquals('course_preference_1', SpreadsheetParser::normalizeHeader('course preference 1'));
        $this->assertEquals('address_line', SpreadsheetParser::normalizeHeader('Address Line'));
    }

    public function test_normalize_header_handles_special_characters(): void
    {
        $this->assertEquals('first_name', SpreadsheetParser::normalizeHeader('first-name'));
        $this->assertEquals('first_name', SpreadsheetParser::normalizeHeader('first.name'));
        $this->assertEquals('first_name', SpreadsheetParser::normalizeHeader('first  name'));
        $this->assertEquals('course_preference_1', SpreadsheetParser::normalizeHeader('course preference  1'));
    }

    // -- Validation feedback tests --

    public function test_validation_rejects_invalid_sex_value(): void
    {
        $file = $this->makeCsv(
            ['first_name', 'last_name', 'email', 'birthdate', 'sex', 'course_preference_1'],
            [['John', 'Doe', 'john@example.com', '2000-01-15', 'other', 'BSCS']]
        );

        $response = $this->actingAs($this->admin)
            ->post('/admin/applications/import/preview', [
                'file' => $file,
                'academic_year_id' => $this->academicYear->id,
            ]);

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('validCount', 0)
        );
    }

    public function test_validation_rejects_invalid_email_format(): void
    {
        $file = $this->makeCsv(
            ['first_name', 'last_name', 'email', 'birthdate', 'sex', 'course_preference_1'],
            [['John', 'Doe', 'not-an-email', '2000-01-15', 'Male', 'BSCS']]
        );

        $response = $this->actingAs($this->admin)
            ->post('/admin/applications/import/preview', [
                'file' => $file,
                'academic_year_id' => $this->academicYear->id,
            ]);

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('validCount', 0)
        );
    }

    public function test_import_resolves_course_codes(): void
    {
        Course::factory()->create(['code' => 'BSIT', 'is_active' => true]);

        $records = [
            $this->validRecord([
                'email' => 'john.course@example.com',
                'course_preference_1' => 'BSCS',
                'course_preference_2' => 'BSIT',
            ]),
        ];

        $response = $this->actingAs($this->admin)
            ->withSession([
                'import_records' => $records,
                'import_academic_year_id' => $this->academicYear->id,
            ])
            ->post('/admin/applications/import/confirm', [
                'selected_ids' => [0],
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('applications', [
            'email' => 'john.course@example.com',
        ]);
    }
}
