<?php

namespace Tests\Unit\Services;

use App\Models\ApplicantScore;
use App\Models\Applicant;
use App\Models\ExamDomain;
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
        $this->service = new ScoreInputService();
        $this->scorer = User::factory()->create();
        $this->applicant = Applicant::factory()->create();
        $this->gradingSession = GradingSession::factory()->create();
    }

    public function test_save_scores_upserts_single_domain_score()
    {
        // Arrange
        $domain = ExamDomain::factory()->create();
        $scores = [
            [
                'domain_id' => $domain->id,
                'raw_score' => 85,
                'max_score' => 100,
            ]
        ];

        // Act
        $this->service->saveScores(
            $this->gradingSession,
            $this->applicant->id,
            $scores,
            $this->scorer
        );

        // Assert
        $this->assertDatabaseHas('applicant_scores', [
            'grading_session_id' => $this->gradingSession->id,
            'applicant_id' => $this->applicant->id,
            'domain_id' => $domain->id,
            'raw_score' => 85,
            'max_score' => 100,
            'scored_by' => $this->scorer->id,
        ]);
    }

    public function test_save_scores_upserts_multiple_domain_scores()
    {
        // Arrange
        $domain1 = ExamDomain::factory()->create();
        $domain2 = ExamDomain::factory()->create();
        $scores = [
            ['domain_id' => $domain1->id, 'raw_score' => 85, 'max_score' => 100],
            ['domain_id' => $domain2->id, 'raw_score' => 92, 'max_score' => 100],
        ];

        // Act
        $this->service->saveScores(
            $this->gradingSession,
            $this->applicant->id,
            $scores,
            $this->scorer
        );

        // Assert
        $this->assertDatabaseHas('applicant_scores', [
            'grading_session_id' => $this->gradingSession->id,
            'applicant_id' => $this->applicant->id,
            'domain_id' => $domain1->id,
            'raw_score' => 85,
        ]);
        $this->assertDatabaseHas('applicant_scores', [
            'grading_session_id' => $this->gradingSession->id,
            'applicant_id' => $this->applicant->id,
            'domain_id' => $domain2->id,
            'raw_score' => 92,
        ]);
    }

    public function test_save_scores_updates_existing_score()
    {
        // Arrange
        $domain = ExamDomain::factory()->create();
        ApplicantScore::create([
            'grading_session_id' => $this->gradingSession->id,
            'applicant_id' => $this->applicant->id,
            'domain_id' => $domain->id,
            'raw_score' => 70,
            'max_score' => 100,
            'scored_by' => $this->scorer->id,
            'scored_at' => now()->subHours(2),
        ]);

        $scores = [
            ['domain_id' => $domain->id, 'raw_score' => 88, 'max_score' => 100]
        ];

        // Act
        $this->service->saveScores(
            $this->gradingSession,
            $this->applicant->id,
            $scores,
            $this->scorer
        );

        // Assert
        $this->assertDatabaseHas('applicant_scores', [
            'grading_session_id' => $this->gradingSession->id,
            'applicant_id' => $this->applicant->id,
            'domain_id' => $domain->id,
            'raw_score' => 88,
        ]);
        // Only one record should exist
        $this->assertEquals(1, ApplicantScore::where(
            'grading_session_id',
            $this->gradingSession->id
        )->where('applicant_id', $this->applicant->id)->count());
    }

    public function test_save_scores_records_scored_by_and_scored_at()
    {
        // Arrange
        $domain = ExamDomain::factory()->create();
        $scores = [
            ['domain_id' => $domain->id, 'raw_score' => 80, 'max_score' => 100]
        ];

        // Act
        $beforeTime = now();
        $this->service->saveScores(
            $this->gradingSession,
            $this->applicant->id,
            $scores,
            $this->scorer
        );
        $afterTime = now();

        // Assert
        $score = ApplicantScore::where(
            'grading_session_id',
            $this->gradingSession->id
        )->where('applicant_id', $this->applicant->id)->first();

        $this->assertEquals($this->scorer->id, $score->scored_by);
        $this->assertNotNull($score->scored_at);
        // Timestamps should be approximately equal (within 5 seconds to account for test execution time)
        $this->assertTrue($score->scored_at->diffInSeconds($beforeTime) >= 0);
        $this->assertTrue($score->scored_at->diffInSeconds($afterTime) <= 5);
    }
}
