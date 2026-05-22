<?php

namespace Tests\Feature\Grading;

use App\Models\AcademicYear;
use App\Models\Applicant;
use App\Models\ApplicantScore;
use App\Models\Application;
use App\Models\AptitudeArea;
use App\Models\ExamSession;
use App\Models\GradingSession;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ScoreImportControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private AcademicYear $academicYear;

    private AptitudeArea $areaSa;

    private AptitudeArea $areaVr;

    private ExamSession $examSession;

    private GradingSession $gradingSession;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create();
        $this->admin->assignRole('super_admin');

        $this->academicYear = AcademicYear::factory()->create();
        $this->areaSa = AptitudeArea::factory()->create(['code' => 'SA', 'max_items' => 50, 'formula' => '(x / max_items) * 100']);
        $this->areaVr = AptitudeArea::factory()->create(['code' => 'VR', 'max_items' => 50]);

        $this->examSession = ExamSession::factory()->create([
            'academic_year_id' => $this->academicYear->id,
            'status' => ExamSession::STATUS_COMPLETED,
        ]);
        $this->gradingSession = GradingSession::factory()->create([
            'exam_session_id' => $this->examSession->id,
            'status' => GradingSession::STATUS_OPEN,
        ]);
    }

    public function test_preview_shows_area_code_columns_and_resolved_session()
    {
        $application = Application::factory()->create([
            'reference_number' => 'APP-2026-00001',
            'academic_year_id' => $this->academicYear->id,
        ]);
        $applicant = Applicant::factory()->create(['application_id' => $application->id]);
        $this->examSession->applicants()->attach($applicant);

        $csv = "reference_number,SA,VR\nAPP-2026-00001,20,30\n";
        $file = UploadedFile::fake()->createWithContent('scores.csv', $csv);

        $response = $this->actingAs($this->admin)
            ->post('/admin/grading/import/preview', ['file' => $file]);

        $response->assertInertia(fn ($page) => $page
            ->component('Grading/ImportPreview')
            ->has('records', 1)
            ->where('records.0.reference_number', 'APP-2026-00001')
            ->where('records.0.is_valid', true)
            ->where('records.0.grading_session_id', $this->gradingSession->id)
            ->where('records.0.scores', fn ($scores) => count($scores) === 2)
        );
    }

    public function test_preview_rejects_row_with_no_completed_exam_session()
    {
        $application = Application::factory()->create([
            'reference_number' => 'APP-2026-00002',
        ]);
        Applicant::factory()->create(['application_id' => $application->id]);

        $csv = "reference_number,SA\nAPP-2026-00002,20\n";
        $file = UploadedFile::fake()->createWithContent('scores.csv', $csv);

        $response = $this->actingAs($this->admin)
            ->post('/admin/grading/import/preview', ['file' => $file]);

        $response->assertInertia(fn ($page) => $page
            ->where('records.0.is_valid', false)
            ->where('records.0.errors.0', 'No open grading session found for this applicant')
        );
    }

    public function test_preview_rejects_duplicate_scores_in_same_academic_year()
    {
        $application = Application::factory()->create([
            'reference_number' => 'APP-2026-00003',
            'academic_year_id' => $this->academicYear->id,
        ]);
        $applicant = Applicant::factory()->create(['application_id' => $application->id]);
        $this->examSession->applicants()->attach($applicant);

        ApplicantScore::factory()->create([
            'grading_session_id' => $this->gradingSession->id,
            'applicant_id' => $applicant->id,
            'aptitude_area_id' => $this->areaSa->id,
        ]);

        $csv = "reference_number,SA\nAPP-2026-00003,25\n";
        $file = UploadedFile::fake()->createWithContent('scores.csv', $csv);

        $response = $this->actingAs($this->admin)
            ->post('/admin/grading/import/preview', ['file' => $file]);

        $response->assertInertia(fn ($page) => $page
            ->where('records.0.is_valid', false)
            ->where('records.0.errors.0', 'Applicant already has scores for this aptitude area in the current academic year')
        );
    }

    public function test_confirm_imports_selected_rows()
    {
        SystemSetting::set('enable_normalized_scores', true);

        $application = Application::factory()->create([
            'reference_number' => 'APP-2026-00004',
            'academic_year_id' => $this->academicYear->id,
        ]);
        $applicant = Applicant::factory()->create(['application_id' => $application->id]);
        $this->examSession->applicants()->attach($applicant);

        $csv = "reference_number,SA,VR\nAPP-2026-00004,25,40\n";
        $file = UploadedFile::fake()->createWithContent('scores.csv', $csv);

        $client = $this->actingAs($this->admin);
        $client->post('/admin/grading/import/preview', ['file' => $file]);

        $response = $client->post('/admin/grading/import/confirm', ['selected_ids' => [0]]);

        $response->assertRedirect('/admin/grading/import');

        $this->assertDatabaseHas('applicant_scores', [
            'applicant_id' => $applicant->id,
            'aptitude_area_id' => $this->areaSa->id,
            'raw_score' => 25,
            'max_score' => 50,
            'normalized_score' => 50.0,
            'scored_by' => $this->admin->id,
        ]);

        $this->assertDatabaseHas('applicant_scores', [
            'applicant_id' => $applicant->id,
            'aptitude_area_id' => $this->areaVr->id,
            'raw_score' => 40,
            'max_score' => 50,
            'normalized_score' => null,
        ]);
    }

    public function test_confirm_in_manual_mode_imports_normalized_scores()
    {
        SystemSetting::set('enable_normalized_scores', false);

        $application = Application::factory()->create([
            'reference_number' => 'APP-2026-00005',
            'academic_year_id' => $this->academicYear->id,
        ]);
        $applicant = Applicant::factory()->create(['application_id' => $application->id]);
        $this->examSession->applicants()->attach($applicant);

        $csv = "reference_number,SA\nAPP-2026-00005,88.5\n";
        $file = UploadedFile::fake()->createWithContent('scores.csv', $csv);

        $client = $this->actingAs($this->admin);
        $client->post('/admin/grading/import/preview', ['file' => $file]);

        $response = $client->post('/admin/grading/import/confirm', ['selected_ids' => [0]]);

        $response->assertRedirect('/admin/grading/import');

        $this->assertDatabaseHas('applicant_scores', [
            'applicant_id' => $applicant->id,
            'aptitude_area_id' => $this->areaSa->id,
            'raw_score' => null,
            'max_score' => null,
            'normalized_score' => 88.5,
        ]);
    }

    public function test_analyze_returns_column_analysis_for_valid_csv()
    {
        $csv = "reference_number,SA,VR\nAPP-2026-00001,20,30\n";
        $file = UploadedFile::fake()->createWithContent('scores.csv', $csv);

        $response = $this->actingAs($this->admin)
            ->post('/admin/grading/import/analyze', ['file' => $file]);

        $response->assertOk()
            ->assertJsonStructure(['checks', 'column_analysis', 'row_count']);
    }

    public function test_analyze_detects_missing_required_columns()
    {
        $csv = "SA,VR\n20,30\n";
        $file = UploadedFile::fake()->createWithContent('scores.csv', $csv);

        $response = $this->actingAs($this->admin)
            ->post('/admin/grading/import/analyze', ['file' => $file]);

        $response->assertOk();
        $data = $response->json();
        $failedChecks = collect($data['checks'])->where('status', 'fail');
        $this->assertGreaterThan(0, $failedChecks->count());
    }

    public function test_analyze_rejects_invalid_file_type()
    {
        $file = UploadedFile::fake()->create('scores.pdf', 100, 'application/pdf');

        $response = $this->actingAs($this->admin)
            ->post('/admin/grading/import/analyze', ['file' => $file]);

        $response->assertSessionHasErrors('file');
    }

    public function test_template_download_returns_csv()
    {
        $response = $this->actingAs($this->admin)
            ->get('/admin/grading/import/template');

        $response->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8')
            ->assertDownload('score_import_template.csv');

        // Should contain reference_number and aptitude area codes
        $content = $response->streamedContent();
        $this->assertStringContainsString('reference_number', $content);
        $this->assertStringContainsString('SA', $content);
        $this->assertStringContainsString('VR', $content);
    }

    public function test_preview_rejects_unmatched_reference_number()
    {
        $csv = "reference_number,SA\nNONEXISTENT-REF,20\n";
        $file = UploadedFile::fake()->createWithContent('scores.csv', $csv);

        $response = $this->actingAs($this->admin)
            ->post('/admin/grading/import/preview', ['file' => $file]);

        $response->assertInertia(fn ($page) => $page
            ->where('records.0.is_valid', false)
            ->where('records.0.errors.0', 'Application not found')
        );
    }

    public function test_preview_rejects_missing_reference_number()
    {
        $csv = "reference_number,SA\n,20\n";
        $file = UploadedFile::fake()->createWithContent('scores.csv', $csv);

        $response = $this->actingAs($this->admin)
            ->post('/admin/grading/import/preview', ['file' => $file]);

        $response->assertInertia(fn ($page) => $page
            ->where('records.0.is_valid', false)
            ->where('records.0.errors.0', 'Reference number is required')
        );
    }
}
