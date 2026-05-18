<?php

namespace App\Services;

use App\Models\Applicant;
use App\Models\ApplicantScore;
use App\Models\AptitudeArea;
use App\Models\GradingSession;
use App\Models\RatingScale;
use App\Models\ResultSheetTemplate;
use App\Models\SystemSetting;
use App\ValueObjects\DocxValidationResult;
use App\ValueObjects\RenderResult;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ResultSheetTemplateService
{
    public function __construct(
        protected PrintTemplateCssService $cssService,
        protected ResultSheetDocxService $docxService,
    ) {}

    public const PLACEHOLDERS = [
        'applicant_name', 'applicant_reference',
        'family_name', 'first_name', 'middle_name', 'suffix',
        'sex', 'gwa', 'course_applied', 'strand', 'applicant_type',
        'exam_date', 'exam_time', 'room_name',
        'scores_rows', 'overall_pct',
        'recommended_course', 'counselor_comments', 'counselor_name',
        'applicant_name_2', 'applicant_reference_2',
        'family_name_2', 'first_name_2', 'middle_name_2', 'suffix_2',
        'sex_2', 'gwa_2', 'course_applied_2', 'strand_2', 'applicant_type_2',
        'exam_date_2', 'exam_time_2', 'room_name_2',
        'scores_rows_2', 'overall_pct_2',
        'recommended_course_2', 'counselor_comments_2', 'counselor_name_2',
        'institution_name', 'institution_campus', 'institution_address',
        'institution_contact', 'institution_email', 'institution_website',
        'institution_exam_name', 'institution_exam_acronym',
    ];

    /**
     * Render template HTML for one or two applicants.
     *
     * @param  array<int, array{name: string, reference: string, exam_date: string, room_name: string, scores: array<array{domain: string, raw: int, max: int, pct: int}>, overall_pct: int}>  $applicants
     */
    public function render(ResultSheetTemplate $template, array $applicants, bool $useSampleData = false, bool $forPdf = false): RenderResult
    {
        $applicants = array_values($applicants);
        $replacements = $this->buildReplacements($applicants, $useSampleData);
        $paperSize = $template->paper_size ?? ResultSheetTemplate::PAPER_A4;
        $orientation = $template->orientation ?? ResultSheetTemplate::ORIENTATION_PORTRAIT;

        if ($template->mode === ResultSheetTemplate::MODE_HTML) {
            $rawHtml = $this->renderRaw($template->content ?: '', $replacements);
            $html = $forPdf
                ? $this->cssService->wrapForPdf($rawHtml, $paperSize, $orientation)
                : $this->cssService->wrap($rawHtml);
        } else {
            $html = $this->docxService->renderDocxFromStoragePath($template->docx_path, $replacements);
        }

        return new RenderResult(
            html: $html,
            mode: $template->mode,
            paperSize: $paperSize,
            orientation: $orientation,
            logicalUnit: $template->logical_unit ?? ResultSheetTemplate::LOGICAL_FULL,
            watermarkText: $template->watermark_text,
        );
    }

    /**
     * Render two applicants in a dual (crosswise/half-page) layout on one sheet.
     *
     * @param  array{name: string, reference: string, exam_date: string, room_name: string, scores: array<array{domain: string, raw: int, max: int, pct: int}>, overall_pct: int}  $applicant1
     * @param  array{name: string, reference: string, exam_date: string, room_name: string, scores: array<array{domain: string, raw: int, max: int, pct: int}>, overall_pct: int}  $applicant2
     */
    public function renderDual(ResultSheetTemplate $template, array $applicant1, array $applicant2, bool $useSampleData = false, bool $forPdf = false): RenderResult
    {
        $replacements1 = $this->buildReplacements([$applicant1], $useSampleData);
        $replacements2 = $this->buildReplacements([$applicant2], $useSampleData);
        $paperSize = $template->paper_size ?? ResultSheetTemplate::PAPER_A4;
        $orientation = $template->orientation ?? ResultSheetTemplate::ORIENTATION_PORTRAIT;

        if ($template->mode === ResultSheetTemplate::MODE_HTML) {
            $html1 = $this->renderRaw($template->content ?: '', $replacements1);
            $html2 = $this->renderRaw($template->content ?: '', $replacements2);
            $html = $forPdf
                ? $this->cssService->wrapDualForPdf($html1, $html2, $paperSize, $orientation)
                : $this->cssService->wrapDual($html1, $html2);
        } else {
            $html1 = $this->docxService->renderDocxFromStoragePath($template->docx_path, $replacements1);
            $html2 = $this->docxService->renderDocxFromStoragePath($template->docx_path, $replacements2);
            $html = $forPdf
                ? $this->cssService->wrapDualForPdf($html1, $html2, $paperSize, $orientation)
                : $this->cssService->wrapDual($html1, $html2);
        }

        return new RenderResult(
            html: $html,
            mode: $template->mode,
            paperSize: $paperSize,
            orientation: $orientation,
            logicalUnit: $template->logical_unit ?? ResultSheetTemplate::LOGICAL_FULL,
            watermarkText: $template->watermark_text,
        );
    }

    /**
     * Fetch applicants + scores for given IDs, render into sheet HTML blobs.
     *
     * @param  int[]  $applicantIds
     * @return string[]
     */
    public function buildSheetsForApplicantIds(
        array $applicantIds,
        ResultSheetTemplate $template,
        ?int $gradingSessionId = null,
    ): array {
        $applicantsWithScores = $this->fetchApplicantsWithScores($applicantIds, $gradingSessionId);

        app(AuditService::class)->log('result_sheet.rendered', ResultSheetTemplate::class, $template->id, [], [
            'applicant_ids' => $applicantIds,
            'mode' => $template->mode,
            'count' => count($applicantIds),
        ]);

        return $this->buildSheetsFromApplicantData($applicantsWithScores, $template);
    }

    /**
     * Build sheet HTML from pre-built applicant data arrays.
     *
     * @param  array<int, array<string, mixed>>  $applicantsWithScores
     * @return string[]
     */
    public function buildSheetsFromApplicantData(array $applicantsWithScores, ResultSheetTemplate $template, bool $forPdf = false): array
    {
        $logicalUnit = $template->logical_unit ?? ResultSheetTemplate::LOGICAL_FULL;
        $chunkSize = in_array($logicalUnit, [ResultSheetTemplate::LOGICAL_HALF_A4, ResultSheetTemplate::LOGICAL_HALF_LEGAL, ResultSheetTemplate::LOGICAL_HALF_LETTER], true) ? 2 : 1;

        $sheetsHtml = [];
        foreach (array_chunk($applicantsWithScores, $chunkSize) as $chunk) {
            if (count($chunk) === 2) {
                $result = $this->renderDual($template, $chunk[0], $chunk[1], false, $forPdf);
                $sheetsHtml[] = $result->html;
            } else {
                $result = $this->render($template, $chunk, false, $forPdf);
                $sheetsHtml[] = $result->html;
            }
        }

        return $sheetsHtml;
    }

    /**
     * Build raw (unwrapped) HTML fragments for each sheet — for use in bulk PDF assembly.
     *
     * Unlike buildSheetsFromApplicantData(), these fragments have NO CSS wrapping and no
     * full HTML document shell. The caller (ResultSheetPdfService) is responsible for
     * combining them and producing a single valid HTML document.
     *
     * @param  array<int, array<string, mixed>>  $applicantsWithScores
     * @return string[]
     */
    public function buildRawSheetsFromApplicantData(array $applicantsWithScores, ResultSheetTemplate $template): array
    {
        $logicalUnit = $template->logical_unit ?? ResultSheetTemplate::LOGICAL_FULL;
        $chunkSize = in_array($logicalUnit, [ResultSheetTemplate::LOGICAL_HALF_A4, ResultSheetTemplate::LOGICAL_HALF_LEGAL, ResultSheetTemplate::LOGICAL_HALF_LETTER], true) ? 2 : 1;

        $sheetsHtml = [];
        foreach (array_chunk($applicantsWithScores, $chunkSize) as $chunk) {
            if (count($chunk) === 2) {
                $sheetsHtml[] = $this->buildRawDualFragment($template, $chunk[0], $chunk[1]);
            } else {
                $sheetsHtml[] = $this->buildRawFragment($template, $chunk);
            }
        }

        return $sheetsHtml;
    }

    /**
     * Fetch applicants + scores for given IDs and return raw HTML fragments for bulk PDF.
     *
     * @param  int[]  $applicantIds
     * @return string[]
     */
    public function buildRawSheetsForApplicantIds(
        array $applicantIds,
        ResultSheetTemplate $template,
        ?int $gradingSessionId = null,
    ): array {
        $applicantsWithScores = $this->fetchApplicantsWithScores($applicantIds, $gradingSessionId);

        app(AuditService::class)->log('result_sheet.rendered', ResultSheetTemplate::class, $template->id, [], [
            'applicant_ids' => $applicantIds,
            'mode' => $template->mode,
            'count' => count($applicantIds),
        ]);

        return $this->buildRawSheetsFromApplicantData($applicantsWithScores, $template);
    }

    /**
     * @param  array<int, array{name: string, reference: string, exam_date: string, room_name: string, scores: array<array{domain: string, raw: int, max: int, pct: int}>, overall_pct: int}>  $applicants
     */
    public function buildRawFragment(ResultSheetTemplate $template, array $applicants, bool $useSampleData = false): string
    {
        $applicants = array_values($applicants);
        $replacements = $this->buildReplacements($applicants, $useSampleData);

        if ($template->mode === ResultSheetTemplate::MODE_HTML) {
            $html = $this->renderRaw($template->content ?: '', $replacements);
        } else {
            $html = $this->docxService->renderDocxFromStoragePath($template->docx_path, $replacements);
        }

        return "<div class=\"print-template\">{$html}</div>";
    }

    /**
     * @param  array{name: string, reference: string, exam_date: string, room_name: string, scores: array<array{domain: string, raw: int, max: int, pct: int}>, overall_pct: int}  $applicant1
     * @param  array{name: string, reference: string, exam_date: string, room_name: string, scores: array<array{domain: string, raw: int, max: int, pct: int}>, overall_pct: int}  $applicant2
     */
    public function buildRawDualFragment(ResultSheetTemplate $template, array $applicant1, array $applicant2, bool $useSampleData = false): string
    {
        $replacements1 = $this->buildReplacements([$applicant1], $useSampleData);
        $replacements2 = $this->buildReplacements([$applicant2], $useSampleData);

        if ($template->mode === ResultSheetTemplate::MODE_HTML) {
            $html1 = $this->renderRaw($template->content ?: '', $replacements1);
            $html2 = $this->renderRaw($template->content ?: '', $replacements2);
        } else {
            $html1 = $this->docxService->renderDocxFromStoragePath($template->docx_path, $replacements1);
            $html2 = $this->docxService->renderDocxFromStoragePath($template->docx_path, $replacements2);
        }

        return "<div class=\"print-template print-template--dual\">\n"
            ."  <div class=\"print-template--half\">{$html1}</div>\n"
            ."  <div class=\"print-template--half\">{$html2}</div>\n"
            .'</div>';
    }

    /**
     * Render from raw HTML content (for preview before template is saved).
     */
    public function renderHtmlContent(
        string $content,
        array $applicants = [],
        bool $useSampleData = true,
        string $paperSize = ResultSheetTemplate::PAPER_A4,
        string $orientation = ResultSheetTemplate::ORIENTATION_PORTRAIT,
        string $logicalUnit = ResultSheetTemplate::LOGICAL_FULL
    ): RenderResult {
        $applicants = array_values($applicants);
        $replacements = $this->buildReplacements($applicants, $useSampleData);

        return new RenderResult(
            html: $this->cssService->wrap($this->renderRaw($content, $replacements)),
            mode: ResultSheetTemplate::MODE_HTML,
            paperSize: $paperSize,
            orientation: $orientation,
            logicalUnit: $logicalUnit,
            watermarkText: null,
        );
    }

    /**
     * Render DOCX file to HTML (for preview).
     *
     * @param  array<string, string>  $replacements
     */
    public function renderDocxFile(
        string $path,
        array $replacements = [],
        bool $useSampleIfEmpty = true,
        string $paperSize = ResultSheetTemplate::PAPER_A4,
        string $orientation = ResultSheetTemplate::ORIENTATION_PORTRAIT,
        string $logicalUnit = ResultSheetTemplate::LOGICAL_FULL
    ): RenderResult {
        if (empty($replacements) && $useSampleIfEmpty) {
            $sample = $this->sampleApplicantData();
            $replacements = [
                'applicant_name' => $sample['name'],
                'applicant_reference' => $sample['reference'],
                'exam_date' => $sample['exam_date'],
                'room_name' => $sample['room_name'],
                'scores_rows' => $this->buildScoresRows($sample['scores']),
                'overall_pct' => (string) $sample['overall_pct'],
                'applicant_name_2' => $sample['name_2'] ?? '—',
                'applicant_reference_2' => $sample['reference_2'] ?? '—',
                'room_name_2' => $sample['room_name_2'] ?? '—',
                'scores_rows_2' => $this->buildScoresRows($sample['scores_2'] ?? []),
                'overall_pct_2' => (string) ($sample['overall_pct_2'] ?? 0),
                'family_name' => $sample['family_name'],
                'first_name' => $sample['first_name'],
                'middle_name' => $sample['middle_name'],
                'suffix' => $sample['suffix'],
                'sex' => $sample['sex'],
                'gwa' => $sample['gwa'],
                'course_applied' => $sample['course_applied'],
                'strand' => $sample['strand'],
                'applicant_type' => $sample['applicant_type'],
                'exam_time' => $sample['exam_time'],
                'recommended_course' => $sample['recommended_course'],
                'counselor_comments' => $sample['counselor_comments'],
                'counselor_name' => $sample['counselor_name'],
                'family_name_2' => $sample['family_name_2'] ?? '—',
                'first_name_2' => $sample['first_name_2'] ?? '—',
                'middle_name_2' => $sample['middle_name_2'] ?? '—',
                'suffix_2' => $sample['suffix_2'] ?? '',
                'sex_2' => $sample['sex_2'] ?? '—',
                'gwa_2' => $sample['gwa_2'] ?? '—',
                'course_applied_2' => $sample['course_applied_2'] ?? '—',
                'strand_2' => $sample['strand_2'] ?? '—',
                'applicant_type_2' => $sample['applicant_type_2'] ?? '—',
                'exam_time_2' => $sample['exam_time_2'] ?? '—',
                'recommended_course_2' => $sample['recommended_course_2'] ?? '—',
                'counselor_comments_2' => $sample['counselor_comments_2'] ?? '—',
                'counselor_name_2' => $sample['counselor_name_2'] ?? '—',
                'institution_name' => SystemSetting::institution('name', '—'),
                'institution_campus' => SystemSetting::institution('campus', '—'),
                'institution_address' => SystemSetting::institution('address', '—'),
                'institution_contact' => SystemSetting::institution('contact_number', '—'),
                'institution_email' => SystemSetting::institution('email', '—'),
                'institution_website' => SystemSetting::institution('website', '—'),
                'institution_exam_name' => SystemSetting::institution('exam_name', '—'),
                'institution_exam_acronym' => SystemSetting::institution('exam_acronym', '—'),
            ];
            $this->addPerDomainReplacements($replacements, [], $sample, true, RatingScale::default());
        }

        $html = $this->docxService->renderDocxFromFullPath($path, $replacements);

        return new RenderResult(
            html: $html,
            mode: ResultSheetTemplate::MODE_DOCX,
            paperSize: $paperSize,
            orientation: $orientation,
            logicalUnit: $logicalUnit,
            watermarkText: null,
        );
    }

    /**
     * Slugify domain name for placeholder key (lowercase, underscores).
     */
    public function aptitudeAreaSlug(string $name): string
    {
        return str_replace('-', '_', Str::slug($name, '_'));
    }

    /**
     * Build replacement map for applicant data (slot 1 and slot 2).
     *
     * @param  array<int, array{name: string, reference: string, exam_date: string, room_name: string, scores: array<array{domain: string, raw: int, max: int, pct: int}>, overall_pct: int}>  $applicants
     * @return array<string, string>
     */
    public function buildReplacements(array $applicants, bool $useSampleData): array
    {
        $sample = $this->sampleApplicantData();
        $replacements = [];

        foreach ([1 => 0, 2 => 1] as $slot => $idx) {
            $app = $applicants[$idx] ?? null;
            $data = $app ?? ($useSampleData ? $sample : null);
            $suffix = $slot === 1 ? '' : '_2';
            if ($data) {
                $replacements["applicant_name{$suffix}"] = $data['name'] ?? '—';
                $replacements["applicant_reference{$suffix}"] = $data['reference'] ?? '—';
                $replacements["exam_date{$suffix}"] = $data['exam_date'] ?? '—';
                $replacements["room_name{$suffix}"] = $data['room_name'] ?? '—';
                $replacements["scores_rows{$suffix}"] = $this->buildScoresRows($data['scores'] ?? []);
                $replacements["overall_pct{$suffix}"] = (string) ($data['overall_pct'] ?? 0);

                $newFields = [
                    'family_name', 'first_name', 'middle_name', 'suffix',
                    'sex', 'gwa', 'course_applied', 'strand', 'applicant_type',
                    'exam_time',
                    'recommended_course', 'counselor_comments', 'counselor_name',
                ];
                foreach ($newFields as $field) {
                    $replacements["{$field}{$suffix}"] = (string) ($data[$field] ?? '—');
                }
            } else {
                $replacements["applicant_name{$suffix}"] = '—';
                $replacements["applicant_reference{$suffix}"] = '—';
                $replacements["exam_date{$suffix}"] = '—';
                $replacements["room_name{$suffix}"] = '—';
                $replacements["scores_rows{$suffix}"] = '';
                $replacements["overall_pct{$suffix}"] = '—';

                foreach (['family_name', 'first_name', 'middle_name', 'suffix', 'sex', 'gwa', 'course_applied', 'strand', 'applicant_type', 'exam_time', 'recommended_course', 'counselor_comments', 'counselor_name'] as $field) {
                    $replacements["{$field}{$suffix}"] = '—';
                }
            }
        }

        $replacements['exam_date'] = $replacements['exam_date'] ?? $replacements['exam_date_2'] ?? '—';
        $replacements['room_name'] = $replacements['room_name'] ?? $replacements['room_name_2'] ?? '—';

        $replacements['institution_name'] = SystemSetting::institution('name', '—');
        $replacements['institution_campus'] = SystemSetting::institution('campus', '—');
        $replacements['institution_address'] = SystemSetting::institution('address', '—');
        $replacements['institution_contact'] = SystemSetting::institution('contact_number', '—');
        $replacements['institution_email'] = SystemSetting::institution('email', '—');
        $replacements['institution_website'] = SystemSetting::institution('website', '—');
        $replacements['institution_exam_name'] = SystemSetting::institution('exam_name', '—');
        $replacements['institution_exam_acronym'] = SystemSetting::institution('exam_acronym', '—');

        $personnel = config('institution.personnel', []);
        foreach ($personnel as $role => $defaults) {
            foreach (['name', 'title', 'credentials'] as $field) {
                $dotKey = "personnel.{$role}.{$field}";
                $val = SystemSetting::institution($dotKey) ?? ($defaults[$field] ?? '');
                $replacements["personnel_{$role}_{$field}"] = $val ?: '—';
            }
        }

        $this->addPerDomainReplacements($replacements, $applicants, $sample, $useSampleData, RatingScale::default());

        return $replacements;
    }

    /**
     * Add per-domain replacements (e.g. {{spatial_awareness}}, {{spatial_awareness_raw}}) for DOCX strict binding.
     *
     * @param  array<string, string>  $replacements
     * @param  array<int, array{name?: string, reference?: string, scores?: array<array{domain: string, raw: int, max: int, pct: int}>}>  $applicants
     * @param  array{name?: string, reference?: string, scores?: array<array{domain: string, raw: int, max: int, pct: int}>}  $sample
     */
    protected function addPerDomainReplacements(array &$replacements, array $applicants, array $sample, bool $useSampleData, ?RatingScale $ratingScale = null): void
    {
        $domains = AptitudeArea::where('is_active', true)->orderBy('display_order')->get(['id', 'name']);

        foreach ([1 => 0, 2 => 1] as $slot => $idx) {
            $suffix = $slot === 1 ? '' : '_2';
            $data = $applicants[$idx] ?? null;
            $data = $data ?? ($useSampleData ? $sample : null);
            $scores = $data['scores'] ?? [];
            $scoresByDomain = collect($scores)->keyBy(fn (array $s) => $s['domain'] ?? '');

            foreach ($domains as $domain) {
                $slug = $this->aptitudeAreaSlug($domain->name);
                $score = $scoresByDomain->get($domain->name);
                $pct = $score !== null ? (string) ((int) ($score['pct'] ?? 0)) : '—';
                $raw = $score !== null
                    ? ($score['max'] > 0 ? sprintf('%d / %d', (int) ($score['raw'] ?? 0), (int) ($score['max'])) : (string) ($score['raw'] ?? '—'))
                    : '—';
                $replacements[$slug.$suffix] = $pct;
                $replacements[$slug.'_raw'.$suffix] = $raw;

                $rating = $this->percentileToRating((int) $pct, $ratingScale);
                $replacements[$slug.'_rating'.$suffix] = $rating;
            }
        }
    }

    /**
     * @param  int[]  $applicantIds
     * @return array<int, array<string, mixed>>
     */
    public function fetchApplicantsWithScores(array $applicantIds, ?int $gradingSessionId = null): array
    {
        if ($gradingSessionId !== null) {
            return $this->fetchApplicantsForSession($applicantIds, $gradingSessionId);
        }

        return $this->fetchApplicantsAgnostic($applicantIds);
    }

    /**
     * @param  int[]  $applicantIds
     * @return array<int, array<string, mixed>>
     */
    protected function fetchApplicantsForSession(array $applicantIds, int $gradingSessionId): array
    {
        $session = GradingSession::with('examSession.room')->findOrFail($gradingSessionId);
        $applicants = $session->applicants()
            ->whereIn('applicants.id', $applicantIds)
            ->with([
                'application.coursePreference1',
                'consultationSummary.recommendedCourse',
                'consultationSummary.counselor',
            ])
            ->get();

        $scoresByApplicant = $session->applicantScores()
            ->whereIn('applicant_id', $applicantIds)
            ->with('aptitudeArea')
            ->get()
            ->groupBy('applicant_id');

        return $applicants->map(function ($a) use ($scoresByApplicant) {
            $scores = $this->mapScoresFromCollection($scoresByApplicant->get($a->id, collect()));

            return $this->buildApplicantDataArray($a, null, $scores);
        })->values()->all();
    }

    /**
     * @param  int[]  $applicantIds
     * @return array<int, array<string, mixed>>
     */
    protected function fetchApplicantsAgnostic(array $applicantIds): array
    {
        $applicants = Applicant::whereIn('id', $applicantIds)
            ->with([
                'application.coursePreference1',
                'consultationSummary.recommendedCourse',
                'consultationSummary.counselor',
                'gradingSessions.examSession.room',
            ])
            ->get();

        $applicantSessionMap = [];
        foreach ($applicants as $applicant) {
            $gs = $applicant->gradingSessions->where('status', GradingSession::STATUS_FINALIZED)->first()
                ?? $applicant->gradingSessions->first();
            if ($gs) {
                $applicantSessionMap[$applicant->id] = $gs->id;
            }
        }

        $allScores = ApplicantScore::whereIn('applicant_id', array_keys($applicantSessionMap))
            ->whereIn('grading_session_id', array_unique(array_values($applicantSessionMap)))
            ->with('aptitudeArea')
            ->get()
            ->groupBy('applicant_id');

        return $applicants->map(function ($a) use ($allScores) {
            $gs = $a->gradingSessions->where('status', GradingSession::STATUS_FINALIZED)->first()
                ?? $a->gradingSessions->first();
            $scores = $this->mapScoresFromCollection(
                $allScores->get($a->id, collect())
                    ->filter(fn ($s) => $gs && $s->grading_session_id === $gs->id)
            );

            return $this->buildApplicantDataArray($a, $gs, $scores);
        })->values()->all();
    }

    /**
     * @param  Collection<int, ApplicantScore>  $scores
     * @return array<int, array{domain: string, raw: int|float|null, max: int|float|null, pct: int}>
     */
    protected function mapScoresFromCollection(Collection $scores): array
    {
        return $scores->map(fn ($s) => [
            'domain' => $s->aptitudeArea?->name ?? '—',
            'raw' => $s->normalized_score ?? $s->raw_score,
            'max' => $s->max_score,
            'pct' => $s->normalized_score ?? ($s->max_score > 0 ? (int) round(($s->raw_score / $s->max_score) * 100) : 0),
        ])->values()->all();
    }

    /**
     * @param  array<int, array{domain: string, raw: int|float|null, max: int|float|null, pct: int}>  $scores
     * @return array<string, mixed>
     */
    protected function buildApplicantDataArray(Applicant $applicant, ?GradingSession $session, array $scores): array
    {
        $overallPct = count($scores) > 0
            ? (int) round(collect($scores)->avg('pct'))
            : 0;

        $name = '—';
        if ($applicant->application) {
            $name = trim(implode(' ', array_filter([
                $applicant->application->first_name,
                $applicant->application->middle_name,
                $applicant->application->last_name,
                $applicant->application->suffix,
            ])));
        }

        $app = $applicant->application;
        $summary = $applicant->consultationSummary;

        return [
            'id' => $applicant->id,
            'name' => $name,
            'family_name' => $app?->last_name ?? '—',
            'first_name' => $app?->first_name ?? '—',
            'middle_name' => $app?->middle_name ?? '—',
            'suffix' => $app?->suffix ?? '',
            'sex' => $app?->sex ?? '—',
            'gwa' => $app?->gwa ?? '—',
            'course_applied' => $app?->coursePreference1?->name ?? '—',
            'strand' => $app?->strand ?? $app?->last_school_enrolled ?? '—',
            'applicant_type' => $app?->applicant_type ?? '—',
            'reference' => $app?->reference_number ?? '—',
            'exam_date' => $session?->examSession?->date?->format('F j, Y') ?? '—',
            'exam_time' => $session?->examSession?->start_time
                                     ? Carbon::parse($session->examSession->start_time)->format('g:i A')
                                     : '—',
            'room_name' => $session?->examSession?->room?->name ?? '—',
            'scores' => $scores,
            'overall_pct' => $overallPct,
            'recommended_course' => $summary?->recommendedCourse?->name ?? '—',
            'counselor_comments' => $summary?->counselor_comments ?? '—',
            'counselor_name' => $summary?->counselor?->name ?? '—',
        ];
    }

    /**
     * Render raw HTML content with placeholder replacements (no CSS wrapping).
     */
    private function renderRaw(string $content, array $replacements): string
    {
        foreach ($replacements as $key => $value) {
            $content = str_replace('{{'.$key.'}}', $value, $content);
        }

        foreach (['scores_rows_2' => 'scores-rows-placeholder-2', 'scores_rows' => 'scores-rows-placeholder'] as $key => $class) {
            $rows = $replacements[$key] ?? '';

            // Try matching TR first
            $content = preg_replace_callback(
                '/<\s*tr\s+class\s*=\s*["\']'.preg_quote($class, '/').'["\'][^>]*>.*?<\s*\/\s*tr\s*>/s',
                fn () => $rows ?: '<tr class="'.$class.'"><td colspan="3"></td></tr>',
                $content,
                1,
                $count
            );

            // If no TR matched, try matching TBODY
            if ($count === 0) {
                $content = preg_replace_callback(
                    '/(<\s*tbody\s+class\s*=\s*["\']'.preg_quote($class, '/').'["\'][^>]*>)(.*?)(<\s*\/\s*tbody\s*>)/s',
                    fn ($matches) => $matches[1]."\n".($rows ?: '<tr><td colspan="3"></td></tr>')."\n".$matches[3],
                    $content,
                    1
                );
            }
        }

        return $content;
    }

    public function getDocxValidation(string $fullPath, bool $isCrosswise): DocxValidationResult
    {
        return $this->docxService->validateDocxTemplate(
            $fullPath,
            $this->buildAllKnownPlaceholders(),
            $isCrosswise,
        );
    }

    protected function buildAllKnownPlaceholders(): array
    {
        $placeholders = self::PLACEHOLDERS;
        $domains = AptitudeArea::where('is_active', true)->orderBy('display_order')->get(['name']);
        foreach ($domains as $domain) {
            $slug = $this->aptitudeAreaSlug($domain->name);
            $placeholders[] = $slug;
            $placeholders[] = $slug.'_raw';
            $placeholders[] = $slug.'_rating';
            $placeholders[] = $slug.'_2';
            $placeholders[] = $slug.'_raw_2';
            $placeholders[] = $slug.'_rating_2';
        }

        $personnelRoles = array_keys(config('institution.personnel', []));
        foreach ($personnelRoles as $role) {
            foreach (['name', 'title', 'credentials'] as $field) {
                $placeholders[] = "personnel_{$role}_{$field}";
            }
        }

        return array_unique($placeholders);
    }

    protected function buildScoresRows(array $scores): string
    {
        $rows = [];
        foreach ($scores as $s) {
            $rawFormatted = $s['max'] > 0
                ? sprintf('%d / %d', (int) ($s['raw'] ?? 0), (int) ($s['max']))
                : (string) ($s['raw'] ?? '—');

            $rows[] = sprintf(
                '<tr class="border-b border-border/50"><td class="py-1.5">%s</td><td class="text-right py-1.5">%s</td><td class="text-right py-1.5 font-medium">%d%%</td></tr>',
                htmlspecialchars($s['domain'] ?? '—'),
                $rawFormatted,
                (int) ($s['pct'] ?? 0)
            );
        }

        return implode("\n", $rows);
    }

    protected function sampleApplicantData(): array
    {
        return [
            'name' => 'Juan M. Dela Cruz',
            'family_name' => 'Dela Cruz',
            'first_name' => 'Juan',
            'middle_name' => 'M.',
            'suffix' => '',
            'sex' => 'Male',
            'gwa' => '1.50',
            'course_applied' => 'BS Information Technology',
            'strand' => 'STEM',
            'applicant_type' => 'Freshman',
            'reference' => 'ICAT-2026-00042',
            'exam_date' => now()->format('F j, Y'),
            'exam_time' => '8:00 AM',
            'room_name' => 'Conference Hall A - Seat 12',
            'recommended_course' => 'BS Information Technology',
            'counselor_comments' => 'Strong aptitude in numerical and logical reasoning. Recommended for IT/CS programs.',
            'counselor_name' => 'Maria Santos',
            'scores' => [
                ['domain' => 'Spatial Awareness', 'raw' => 20, 'max' => 25, 'pct' => 80],
                ['domain' => 'Numerical Ability', 'raw' => 22, 'max' => 25, 'pct' => 88],
                ['domain' => 'Verbal Reasoning', 'raw' => 19, 'max' => 25, 'pct' => 76],
                ['domain' => 'Abstract Reasoning', 'raw' => 16, 'max' => 20, 'pct' => 80],
                ['domain' => 'Logical Reasoning', 'raw' => 21, 'max' => 25, 'pct' => 84],
                ['domain' => 'Perceptual Speed & Accuracy', 'raw' => 17, 'max' => 20, 'pct' => 85],
            ],
            'overall_pct' => 82,
            'name_2' => 'Maria L. Santos',
            'family_name_2' => 'Santos',
            'first_name_2' => 'Maria',
            'middle_name_2' => 'L.',
            'suffix_2' => '',
            'sex_2' => 'Female',
            'gwa_2' => '1.75',
            'course_applied_2' => 'BS Accountancy',
            'strand_2' => 'ABM',
            'applicant_type_2' => 'Freshman',
            'reference_2' => 'ICAT-2026-00043',
            'exam_time_2' => '8:00 AM',
            'room_name_2' => 'Conference Hall A - Seat 13',
            'recommended_course_2' => 'BS Accountancy',
            'counselor_comments_2' => 'Excellent numerical aptitude. Well-suited for business programs.',
            'counselor_name_2' => 'Maria Santos',
            'scores_2' => [
                ['domain' => 'Spatial Awareness', 'raw' => 18, 'max' => 25, 'pct' => 72],
                ['domain' => 'Numerical Ability', 'raw' => 24, 'max' => 25, 'pct' => 96],
                ['domain' => 'Verbal Reasoning', 'raw' => 21, 'max' => 25, 'pct' => 84],
                ['domain' => 'Abstract Reasoning', 'raw' => 14, 'max' => 20, 'pct' => 70],
                ['domain' => 'Logical Reasoning', 'raw' => 19, 'max' => 25, 'pct' => 76],
                ['domain' => 'Perceptual Speed & Accuracy', 'raw' => 15, 'max' => 20, 'pct' => 75],
            ],
            'overall_pct_2' => 79,
        ];
    }

    private function percentileToRating(int $pct, ?RatingScale $ratingScale = null): string
    {
        if ($ratingScale) {
            return $ratingScale->ratingFor($pct);
        }

        return match (true) {
            $pct >= 90 => 'Outstanding',
            $pct >= 75 => 'Above Average',
            $pct >= 50 => 'Average',
            $pct >= 25 => 'Below Average',
            default => 'Needs Improvement',
        };
    }
}
