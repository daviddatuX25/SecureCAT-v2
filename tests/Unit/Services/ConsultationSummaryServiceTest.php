<?php

namespace Tests\Unit\Services;

use App\Models\Applicant;
use App\Models\ConsultationSummary;
use App\Models\User;
use App\Services\ConsultationSummaryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConsultationSummaryServiceTest extends TestCase
{
    use RefreshDatabase;

    private ConsultationSummaryService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ConsultationSummaryService();
    }

    public function test_getOrCreateForApplicant_creates_summary_when_none_exists(): void
    {
        $applicant = Applicant::factory()->create();

        $summary = $this->service->getOrCreateForApplicant($applicant->id);

        $this->assertInstanceOf(ConsultationSummary::class, $summary);
        $this->assertEquals($applicant->id, $summary->applicant_id);
        $this->assertEquals(ConsultationSummary::STATUS_PENDING, $summary->status);
    }

    public function test_getOrCreateForApplicant_returns_existing_summary(): void
    {
        $applicant = Applicant::factory()->create();
        $existing = ConsultationSummary::create([
            'applicant_id' => $applicant->id,
            'status' => ConsultationSummary::STATUS_PENDING,
        ]);

        $summary = $this->service->getOrCreateForApplicant($applicant->id);

        $this->assertEquals($existing->id, $summary->id);
    }

    public function test_release_sets_status_and_released_at(): void
    {
        $applicant = Applicant::factory()->create();
        $user = User::factory()->create();
        $summary = ConsultationSummary::create([
            'applicant_id' => $applicant->id,
            'status' => ConsultationSummary::STATUS_PENDING,
        ]);

        $this->service->release($summary, $user);

        $this->assertEquals(ConsultationSummary::STATUS_RELEASED, $summary->status);
        $this->assertNotNull($summary->released_at);
        $this->assertEquals($user->id, $summary->released_by);
    }
}
