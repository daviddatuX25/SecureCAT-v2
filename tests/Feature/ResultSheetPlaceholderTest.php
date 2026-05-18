<?php

namespace Tests\Feature;

use App\Models\AptitudeArea;
use App\Models\RatingScale;
use App\Services\ResultSheetTemplateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResultSheetPlaceholderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        AptitudeArea::factory()->create(['name' => 'Spatial Awareness', 'is_active' => true, 'display_order' => 1]);
    }

    public function test_sample_data_includes_all_new_fields(): void
    {
        $service = app(ResultSheetTemplateService::class);
        $method = new \ReflectionMethod($service, 'sampleApplicantData');
        $method->setAccessible(true);
        $sample = $method->invoke($service);

        $requiredFields = [
            'family_name', 'first_name', 'middle_name', 'suffix',
            'sex', 'gwa', 'course_applied', 'strand', 'applicant_type',
            'exam_time', 'recommended_course', 'counselor_comments', 'counselor_name',
        ];
        foreach ($requiredFields as $field) {
            $this->assertArrayHasKey($field, $sample, "Missing field: {$field}");
        }
    }

    public function test_build_replacements_includes_institution_fields(): void
    {
        $service = app(ResultSheetTemplateService::class);
        $replacements = $service->buildReplacements([], true);

        $institutionKeys = [
            'institution_name', 'institution_campus', 'institution_address',
            'institution_contact', 'institution_email', 'institution_website',
            'institution_exam_name', 'institution_exam_acronym',
        ];
        foreach ($institutionKeys as $key) {
            $this->assertArrayHasKey($key, $replacements, "Missing institution key: {$key}");
        }
    }

    public function test_build_replacements_includes_personnel_fields(): void
    {
        $service = app(ResultSheetTemplateService::class);
        $replacements = $service->buildReplacements([], true);

        $personnelRoles = array_keys(config('institution.personnel', []));
        foreach ($personnelRoles as $role) {
            foreach (['name', 'title', 'credentials'] as $field) {
                $key = "personnel_{$role}_{$field}";
                $this->assertArrayHasKey($key, $replacements, "Missing personnel key: {$key}");
            }
        }
    }

    public function test_per_domain_rating_included(): void
    {
        RatingScale::create([
            'name' => 'Test',
            'ranges' => [['min' => 0, 'max' => 100, 'label' => 'All']],
            'is_default' => true,
        ]);

        $service = app(ResultSheetTemplateService::class);
        $replacements = $service->buildReplacements([], true);

        $this->assertArrayHasKey('spatial_awareness_rating', $replacements);
        $this->assertArrayHasKey('spatial_awareness_rating_2', $replacements);
    }

    public function test_placeholders_constant_includes_all_fields(): void
    {
        $expected = [
            'applicant_name', 'applicant_reference',
            'family_name', 'first_name', 'middle_name', 'suffix',
            'sex', 'gwa', 'course_applied', 'strand', 'applicant_type',
            'institution_name', 'institution_campus',
        ];
        $placeholders = ResultSheetTemplateService::PLACEHOLDERS;

        foreach ($expected as $key) {
            $this->assertContains($key, $placeholders, "PLACEHOLDERS constant missing: {$key}");
        }
    }
}
