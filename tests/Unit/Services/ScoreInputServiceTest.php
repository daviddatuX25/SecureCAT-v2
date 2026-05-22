<?php

namespace Tests\Unit\Services;

use App\Models\Applicant;
use App\Models\ApplicantScore;
use App\Models\AptitudeArea;
use App\Models\GradingSession;
use App\Models\User;
use App\Services\ScoreInputService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScoreInputServiceTest extends TestCase
{
    use RefreshDatabase;

    private ScoreInputService $service;

    private GradingSession $gradingSession;

    private Applicant $applicant;

    private User $scorer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ScoreInputService;
        $this->scorer = User::factory()->create();
        $this->applicant = Applicant::factory()->create();
        $this->gradingSession = GradingSession::factory()->create();
    }

    public function test_save_scores_upserts_single_aptitude_area_score_with_auto_compute()
    {
        $area = AptitudeArea::factory()->create(['formula' => '(x / max_items) * 100', 'max_items' => 100]);
        $scores = [
            ['aptitude_area_id' => $area->id, 'raw_score' => 85, 'max_score' => 100],
        ];

        $this->service->saveScores(
            $this->gradingSession,
            $this->applicant->id,
            $scores,
            $this->scorer,
            true
        );

        $this->assertDatabaseHas('applicant_scores', [
            'grading_session_id' => $this->gradingSession->id,
            'applicant_id' => $this->applicant->id,
            'aptitude_area_id' => $area->id,
            'raw_score' => 85,
            'max_score' => 100,
            'normalized_score' => 85.0,
            'scored_by' => $this->scorer->id,
        ]);
    }

    public function test_save_scores_upserts_multiple_aptitude_area_scores()
    {
        $area1 = AptitudeArea::factory()->create(['formula' => '(x / max_items) * 100', 'max_items' => 100]);
        $area2 = AptitudeArea::factory()->create(['formula' => '(x / max_items) * 100', 'max_items' => 100]);
        $scores = [
            ['aptitude_area_id' => $area1->id, 'raw_score' => 85, 'max_score' => 100],
            ['aptitude_area_id' => $area2->id, 'raw_score' => 92, 'max_score' => 100],
        ];

        $this->service->saveScores(
            $this->gradingSession,
            $this->applicant->id,
            $scores,
            $this->scorer,
            true
        );

        $this->assertDatabaseHas('applicant_scores', [
            'grading_session_id' => $this->gradingSession->id,
            'applicant_id' => $this->applicant->id,
            'aptitude_area_id' => $area1->id,
            'raw_score' => 85,
        ]);
        $this->assertDatabaseHas('applicant_scores', [
            'grading_session_id' => $this->gradingSession->id,
            'applicant_id' => $this->applicant->id,
            'aptitude_area_id' => $area2->id,
            'raw_score' => 92,
        ]);
    }

    public function test_save_scores_updates_existing_score()
    {
        $area = AptitudeArea::factory()->create(['formula' => '(x / max_items) * 100', 'max_items' => 100]);
        ApplicantScore::create([
            'grading_session_id' => $this->gradingSession->id,
            'applicant_id' => $this->applicant->id,
            'aptitude_area_id' => $area->id,
            'raw_score' => 70,
            'max_score' => 100,
            'normalized_score' => 70.0,
            'scored_by' => $this->scorer->id,
            'scored_at' => now()->subHours(2),
        ]);

        $scores = [
            ['aptitude_area_id' => $area->id, 'raw_score' => 88, 'max_score' => 100],
        ];

        $this->service->saveScores(
            $this->gradingSession,
            $this->applicant->id,
            $scores,
            $this->scorer,
            true
        );

        $this->assertDatabaseHas('applicant_scores', [
            'grading_session_id' => $this->gradingSession->id,
            'applicant_id' => $this->applicant->id,
            'aptitude_area_id' => $area->id,
            'raw_score' => 88,
        ]);
        $this->assertEquals(1, ApplicantScore::where(
            'grading_session_id',
            $this->gradingSession->id
        )->where('applicant_id', $this->applicant->id)->count());
    }

    public function test_save_scores_records_scored_by_and_scored_at()
    {
        $area = AptitudeArea::factory()->create(['formula' => '(x / max_items) * 100', 'max_items' => 100]);
        $scores = [
            ['aptitude_area_id' => $area->id, 'raw_score' => 80, 'max_score' => 100],
        ];

        $beforeTime = now();
        $this->service->saveScores(
            $this->gradingSession,
            $this->applicant->id,
            $scores,
            $this->scorer,
            true
        );
        $afterTime = now();

        $score = ApplicantScore::where(
            'grading_session_id',
            $this->gradingSession->id
        )->where('applicant_id', $this->applicant->id)->first();

        $this->assertEquals($this->scorer->id, $score->scored_by);
        $this->assertNotNull($score->scored_at);
        $this->assertTrue($score->scored_at->diffInSeconds($beforeTime) >= 0);
        $this->assertTrue($score->scored_at->diffInSeconds($afterTime) <= 5);
    }

    public function test_save_scores_manual_mode_stores_normalized_score_directly()
    {
        $area = AptitudeArea::factory()->create();
        $scores = [
            ['aptitude_area_id' => $area->id, 'normalized_score' => 92.50],
        ];

        $this->service->saveScores(
            $this->gradingSession,
            $this->applicant->id,
            $scores,
            $this->scorer,
            false
        );

        $this->assertDatabaseHas('applicant_scores', [
            'grading_session_id' => $this->gradingSession->id,
            'applicant_id' => $this->applicant->id,
            'aptitude_area_id' => $area->id,
            'raw_score' => null,
            'max_score' => null,
            'normalized_score' => 92.50,
        ]);
    }

    public function test_save_scores_with_conversion_table_stores_percentile_string(): void
    {
        $area = AptitudeArea::factory()->create([
            'scoring_method' => 'conversion_table',
            'formula' => null,
            'max_items' => 50,
        ]);
        $area->percentileConversions()->create(['raw_score' => 10, 'percentile_output' => '85th']);
        $area->percentileConversions()->create(['raw_score' => 20, 'percentile_output' => '99+']);
        $scores = [
            ['aptitude_area_id' => $area->id, 'raw_score' => 10, 'max_score' => 50],
        ];

        $this->service->saveScores(
            $this->gradingSession,
            $this->applicant->id,
            $scores,
            $this->scorer,
            true
        );

        $this->assertDatabaseHas('applicant_scores', [
            'grading_session_id' => $this->gradingSession->id,
            'applicant_id' => $this->applicant->id,
            'aptitude_area_id' => $area->id,
            'raw_score' => 10,
            'max_score' => 50,
            'normalized_score' => null,
            'percentile_string' => '85th',
            'scored_by' => $this->scorer->id,
        ]);
    }

    public function test_save_scores_with_conversion_table_returns_na_for_unmapped_raw_score(): void
    {
        $area = AptitudeArea::factory()->create([
            'scoring_method' => 'conversion_table',
            'formula' => null,
            'max_items' => 50,
        ]);
        $area->percentileConversions()->create(['raw_score' => 10, 'percentile_output' => '85th']);
        $scores = [
            ['aptitude_area_id' => $area->id, 'raw_score' => 99, 'max_score' => 50],
        ];

        $this->service->saveScores(
            $this->gradingSession,
            $this->applicant->id,
            $scores,
            $this->scorer,
            true
        );

        $this->assertDatabaseHas('applicant_scores', [
            'grading_session_id' => $this->gradingSession->id,
            'aptitude_area_id' => $area->id,
            'raw_score' => 99,
            'percentile_string' => 'N/A',
            'normalized_score' => null,
        ]);
    }

    public function test_save_scores_auto_compute_without_formula_stores_null_normalized(): void
    {
        $area = AptitudeArea::factory()->create(['formula' => null, 'max_items' => 50]);
        $scores = [
            ['aptitude_area_id' => $area->id, 'raw_score' => 30, 'max_score' => 50],
        ];

        $this->service->saveScores(
            $this->gradingSession,
            $this->applicant->id,
            $scores,
            $this->scorer,
            true
        );

        $this->assertDatabaseHas('applicant_scores', [
            'grading_session_id' => $this->gradingSession->id,
            'applicant_id' => $this->applicant->id,
            'aptitude_area_id' => $area->id,
            'raw_score' => 30,
            'max_score' => 50,
            'normalized_score' => null,
        ]);
    }
}
