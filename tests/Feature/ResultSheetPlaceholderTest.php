<?php

namespace Tests\Feature;

use App\Models\AptitudeArea;
use App\Models\Course;
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

    public function test_build_replacements_includes_institution_aliases(): void
    {
        $service = app(ResultSheetTemplateService::class);
        $replacements = $service->buildReplacements([], true);

        $this->assertArrayHasKey('institution_contact_number', $replacements);
        $this->assertArrayHasKey('examination_name', $replacements);
        $this->assertArrayHasKey('examination_acronym', $replacements);
        $this->assertEquals($replacements['institution_contact'], $replacements['institution_contact_number']);
        $this->assertEquals($replacements['institution_exam_name'], $replacements['examination_name']);
        $this->assertEquals($replacements['institution_exam_acronym'], $replacements['examination_acronym']);
    }

    public function test_build_replacements_includes_personnel_aliases(): void
    {
        $service = app(ResultSheetTemplateService::class);
        $replacements = $service->buildReplacements([], true);

        $personnelRoles = array_keys(config('institution.personnel', []));
        foreach ($personnelRoles as $role) {
            foreach (['name', 'title', 'credentials'] as $field) {
                $prefixed = "personnel_{$role}_{$field}";
                $short = "{$role}_{$field}";
                $this->assertArrayHasKey($prefixed, $replacements, "Missing prefixed key: {$prefixed}");
                $this->assertArrayHasKey($short, $replacements, "Missing short alias: {$short}");
                $this->assertEquals($replacements[$prefixed], $replacements[$short], "Alias mismatch: {$prefixed} vs {$short}");
            }
        }
    }

    public function test_categorized_placeholders_includes_aliases(): void
    {
        $service = app(ResultSheetTemplateService::class);
        $method = new \ReflectionMethod($service, 'buildCategorizedPlaceholders');
        $method->setAccessible(true);
        $categorized = $method->invoke($service);

        $this->assertContains('institution_contact_number', $categorized['institution']);
        $this->assertContains('examination_name', $categorized['institution']);
        $this->assertContains('examination_acronym', $categorized['institution']);

        $personnelRoles = array_keys(config('institution.personnel', []));
        foreach ($personnelRoles as $role) {
            foreach (['name', 'title', 'credentials'] as $field) {
                $this->assertContains("personnel_{$role}_{$field}", $categorized['personnel']);
                $this->assertContains("{$role}_{$field}", $categorized['personnel']);
            }
        }
    }

    public function test_course_checkmark_placeholders_included_and_resolved(): void
    {
        // 1. Create test courses
        Course::factory()->create(['code' => 'BSIT', 'name' => 'BS Information Technology', 'is_active' => true]);
        Course::factory()->create(['code' => 'BSCS', 'name' => 'BS Computer Science', 'is_active' => true]);
        // inactive course should NOT be included
        Course::factory()->create(['code' => 'BSDS', 'name' => 'BS Data Science', 'is_active' => false]);

        $service = app(ResultSheetTemplateService::class);

        // 2. Assert getPlaceholderGroups includes the checks
        $groups = $service->getPlaceholderGroups();

        $applicant1Placeholders = array_column($groups['applicant1'], 'placeholder');
        $applicant2Placeholders = array_column($groups['applicant2'], 'placeholder');

        $this->assertContains('{{BSIT_check}}', $applicant1Placeholders);
        $this->assertContains('{{BSCS_check}}', $applicant1Placeholders);
        $this->assertNotContains('{{BSDS_check}}', $applicant1Placeholders);

        $this->assertContains('{{BSIT_check_2}}', $applicant2Placeholders);
        $this->assertContains('{{BSCS_check_2}}', $applicant2Placeholders);
        $this->assertNotContains('{{BSDS_check_2}}', $applicant2Placeholders);

        // 3. Assert categorized placeholders includes the checks
        $method = new \ReflectionMethod($service, 'buildCategorizedPlaceholders');
        $method->setAccessible(true);
        $categorized = $method->invoke($service);

        $this->assertContains('BSIT_check', $categorized['optional']);
        $this->assertContains('BSCS_check', $categorized['optional']);
        $this->assertNotContains('BSDS_check', $categorized['optional']);

        $this->assertContains('BSIT_check_2', $categorized['applicant2']);
        $this->assertContains('BSCS_check_2', $categorized['applicant2']);
        $this->assertNotContains('BSDS_check_2', $categorized['applicant2']);

        // 4. Assert buildReplacements resolves them correctly for actual applicants
        $applicants = [
            [
                'name' => 'John',
                'reference' => 'REF-01',
                'recommended_course_code' => 'BSIT',
                'scores' => [],
                'overall_pct' => 80,
            ],
            [
                'name' => 'Jane',
                'reference' => 'REF-02',
                'recommended_course_code' => 'BSCS',
                'scores' => [],
                'overall_pct' => 85,
            ],
        ];

        $replacements = $service->buildReplacements($applicants, false);

        $this->assertEquals('✔', $replacements['BSIT_check']);
        $this->assertEquals('', $replacements['BSCS_check']);

        $this->assertEquals('', $replacements['BSIT_check_2']);
        $this->assertEquals('✔', $replacements['BSCS_check_2']);
    }
}
