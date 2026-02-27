<?php

namespace Tests\Feature\Services;

use App\Models\Applicant;
use App\Models\KnowledgeDocument;
use App\Services\KnowledgeRetrievalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KnowledgeRetrievalServiceTest extends TestCase
{
    use RefreshDatabase;

    private KnowledgeRetrievalService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(KnowledgeRetrievalService::class);
    }

    /** T5.2: No knowledge docs → "No institutional data available." */
    public function test_returns_no_institutional_data_when_no_docs(): void
    {
        $applicant = Applicant::factory()->create(['application_id' => null]);

        $result = $this->service->retrieveForApplicant($applicant);

        $this->assertSame('No institutional data available.', $result);
    }

    /** T5.2: Same for retrieveWithFilters when no docs. */
    public function test_retrieve_with_filters_returns_no_data_when_no_docs(): void
    {
        $result = $this->service->retrieveWithFilters(['year' => '2024']);

        $this->assertSame('No institutional data available.', $result);
    }

    /** Applicant with no application gets all active docs (no category filter). */
    public function test_applicant_without_application_gets_all_active_docs(): void
    {
        $applicant = Applicant::factory()->create(['application_id' => null]);
        KnowledgeDocument::create([
            'title' => 'General info',
            'content' => 'Some institutional text.',
            'metadata' => ['category' => 'General'],
            'source' => 'manual',
            'is_active' => true,
        ]);

        $result = $this->service->retrieveForApplicant($applicant);

        $this->assertStringContainsString('General info', $result);
        $this->assertStringContainsString('Some institutional text.', $result);
    }

    /** T5.4: Doc with empty metadata is included when no filter (all docs). */
    public function test_doc_with_empty_metadata_included_when_no_filter(): void
    {
        $applicant = Applicant::factory()->create(['application_id' => null]);
        KnowledgeDocument::create([
            'title' => 'No metadata doc',
            'content' => 'Content here.',
            'metadata' => [],
            'source' => 'manual',
            'is_active' => true,
        ]);

        $result = $this->service->retrieveForApplicant($applicant);

        $this->assertStringContainsString('No metadata doc', $result);
        $this->assertStringContainsString('Content here.', $result);
    }

    /** Inactive docs are excluded from retrieval. */
    public function test_inactive_docs_excluded(): void
    {
        $applicant = Applicant::factory()->create(['application_id' => null]);
        KnowledgeDocument::create([
            'title' => 'Inactive',
            'content' => 'Should not appear.',
            'metadata' => [],
            'source' => 'manual',
            'is_active' => false,
        ]);

        $result = $this->service->retrieveForApplicant($applicant);

        $this->assertSame('No institutional data available.', $result);
    }

    /** T5.5: Filter by year "2024" → only docs with metadata.year = 2024 (and docs with no year). */
    public function test_retrieve_with_year_filter_includes_only_matching_year(): void
    {
        KnowledgeDocument::create([
            'title' => 'Doc 2024',
            'content' => 'Year 2024 data.',
            'metadata' => ['year' => '2024'],
            'source' => 'manual',
            'is_active' => true,
        ]);
        KnowledgeDocument::create([
            'title' => 'Doc 2023',
            'content' => 'Year 2023 data.',
            'metadata' => ['year' => '2023'],
            'source' => 'manual',
            'is_active' => true,
        ]);

        $result = $this->service->retrieveWithFilters(['year' => '2024']);

        $this->assertStringContainsString('Doc 2024', $result);
        $this->assertStringContainsString('Year 2024 data.', $result);
        $this->assertStringNotContainsString('Doc 2023', $result);
        $this->assertStringNotContainsString('Year 2023 data.', $result);
    }

    /** T5.5: Docs with no year are included when filtering by year (optional metadata). */
    public function test_retrieve_with_year_filter_includes_docs_with_no_year(): void
    {
        KnowledgeDocument::create([
            'title' => 'No year',
            'content' => 'Generic content.',
            'metadata' => [],
            'source' => 'manual',
            'is_active' => true,
        ]);

        $result = $this->service->retrieveWithFilters(['year' => '2024']);

        $this->assertStringContainsString('No year', $result);
        $this->assertStringContainsString('Generic content.', $result);
    }

    /** T5.3: Many docs; total content > max chars → truncate deterministically, stay under limit. */
    public function test_truncates_when_total_content_exceeds_max_chars(): void
    {
        $applicant = Applicant::factory()->create(['application_id' => null]);
        $longContent = str_repeat('x', 5000);
        KnowledgeDocument::create([
            'title' => 'First',
            'content' => $longContent,
            'metadata' => [],
            'source' => 'manual',
            'is_active' => true,
        ]);
        KnowledgeDocument::create([
            'title' => 'Second',
            'content' => 'Second doc content.',
            'metadata' => [],
            'source' => 'manual',
            'is_active' => true,
        ]);

        $maxChars = 6000;
        $result = $this->service->retrieveForApplicant($applicant, 10, $maxChars);

        $this->assertLessThanOrEqual($maxChars + 200, strlen($result)); // allow small overhead for labels
        $this->assertStringContainsString('Source: First', $result);
        $this->assertStringContainsString('Source: Second', $result);
    }

    /** T5.3: Respects max docs limit (order: updated_at desc, id desc, so first 3 are highest ids). */
    public function test_respects_max_docs_limit(): void
    {
        $applicant = Applicant::factory()->create(['application_id' => null]);
        foreach (range(1, 15) as $i) {
            KnowledgeDocument::create([
                'title' => "Doc {$i}",
                'content' => "Content {$i}.",
                'metadata' => [],
                'source' => 'manual',
                'is_active' => true,
            ]);
        }

        $result = $this->service->retrieveForApplicant($applicant, 3, 10000);

        $this->assertStringContainsString('Doc 15', $result);
        $this->assertStringContainsString('Doc 14', $result);
        $this->assertStringContainsString('Doc 13', $result);
        $this->assertStringNotContainsString('Doc 12', $result);
    }

    /** Category filter: only docs matching category (or empty category) included. */
    public function test_retrieve_with_category_filter_includes_matching_docs(): void
    {
        KnowledgeDocument::create([
            'title' => 'Civil',
            'content' => 'Civil Engineering info.',
            'metadata' => ['category' => 'Civil Engineering'],
            'source' => 'manual',
            'is_active' => true,
        ]);
        KnowledgeDocument::create([
            'title' => 'Other',
            'content' => 'Other category.',
            'metadata' => ['category' => 'Medicine'],
            'source' => 'manual',
            'is_active' => true,
        ]);

        $result = $this->service->retrieveWithFilters(['category' => 'Civil Engineering']);

        $this->assertStringContainsString('Civil', $result);
        $this->assertStringContainsString('Civil Engineering info.', $result);
        $this->assertStringNotContainsString('Other', $result);
    }

    /** Empty content docs are skipped. */
    public function test_skips_docs_with_empty_content(): void
    {
        $applicant = Applicant::factory()->create(['application_id' => null]);
        KnowledgeDocument::create([
            'title' => 'Empty',
            'content' => '',
            'metadata' => [],
            'source' => 'manual',
            'is_active' => true,
        ]);
        KnowledgeDocument::create([
            'title' => 'Has content',
            'content' => 'Real content.',
            'metadata' => [],
            'source' => 'manual',
            'is_active' => true,
        ]);

        $result = $this->service->retrieveForApplicant($applicant);

        $this->assertStringNotContainsString('Empty', $result);
        $this->assertStringContainsString('Has content', $result);
        $this->assertStringContainsString('Real content.', $result);
    }
}
