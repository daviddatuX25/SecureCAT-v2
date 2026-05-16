<?php

namespace App\Services;

use App\Models\AptitudeArea;
use App\Models\ResultSheetTemplate;
use App\ValueObjects\RenderResult;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Settings;
use PhpOffice\PhpWord\TemplateProcessor;

class ResultSheetTemplateService
{
    public function __construct(
        protected PrintTemplateCssService $cssService,
    ) {}

    public const PLACEHOLDERS = [
        'applicant_name', 'applicant_reference', 'exam_date', 'room_name',
        'scores_rows', 'overall_pct',
        'applicant_name_2', 'applicant_reference_2', 'scores_rows_2', 'overall_pct_2',
    ];

    /**
     * Render template HTML for one or two applicants.
     *
     * @param  array<int, array{name: string, reference: string, exam_date: string, room_name: string, scores: array<array{domain: string, raw: int, max: int, pct: int}>, overall_pct: int}>  $applicants
     */
    public function render(ResultSheetTemplate $template, array $applicants, bool $useSampleData = false): RenderResult
    {
        $applicants = array_values($applicants);
        $replacements = $this->buildReplacements($applicants, $useSampleData);

        if ($template->mode === ResultSheetTemplate::MODE_HTML) {
            $html = $this->cssService->wrap(
                $this->renderRaw($template->content ?: '', $replacements)
            );
        } else {
            $html = $this->renderDocx($template->docx_path, $replacements);
        }

        return new RenderResult(
            html: $html,
            mode: $template->mode,
            paperSize: $template->paper_size ?? ResultSheetTemplate::PAPER_A4,
            orientation: $template->orientation ?? ResultSheetTemplate::ORIENTATION_PORTRAIT,
            logicalUnit: $template->logical_unit ?? ResultSheetTemplate::LOGICAL_FULL,
        );
    }

    /**
     * Render two applicants in a dual (crosswise/half-page) layout on one sheet.
     *
     * @param  array{name: string, reference: string, exam_date: string, room_name: string, scores: array<array{domain: string, raw: int, max: int, pct: int}>, overall_pct: int}  $applicant1
     * @param  array{name: string, reference: string, exam_date: string, room_name: string, scores: array<array{domain: string, raw: int, max: int, pct: int}>, overall_pct: int}  $applicant2
     */
    public function renderDual(ResultSheetTemplate $template, array $applicant1, array $applicant2, bool $useSampleData = false): RenderResult
    {
        $replacements1 = $this->buildReplacements([$applicant1], $useSampleData);
        $replacements2 = $this->buildReplacements([$applicant2], $useSampleData);

        if ($template->mode === ResultSheetTemplate::MODE_HTML) {
            $html1 = $this->renderRaw($template->content ?: '', $replacements1);
            $html2 = $this->renderRaw($template->content ?: '', $replacements2);
            $html = $this->cssService->wrapDual($html1, $html2);
        } else {
            $html1 = $this->renderDocx($template->docx_path, $replacements1);
            $html2 = $this->renderDocx($template->docx_path, $replacements2);
            $html = $this->cssService->wrapDual($html1, $html2);
        }

        return new RenderResult(
            html: $html,
            mode: $template->mode,
            paperSize: $template->paper_size ?? ResultSheetTemplate::PAPER_A4,
            orientation: $template->orientation ?? ResultSheetTemplate::ORIENTATION_PORTRAIT,
            logicalUnit: $template->logical_unit ?? ResultSheetTemplate::LOGICAL_FULL,
        );
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
            ];
            $this->addPerDomainReplacements($replacements, [], $sample, true);
        }

        $html = $this->renderDocxFromFullPath($path, $replacements);

        return new RenderResult(
            html: $html,
            mode: ResultSheetTemplate::MODE_DOCX,
            paperSize: $paperSize,
            orientation: $orientation,
            logicalUnit: $logicalUnit,
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
    protected function buildReplacements(array $applicants, bool $useSampleData): array
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
            } else {
                $replacements["applicant_name{$suffix}"] = '—';
                $replacements["applicant_reference{$suffix}"] = '—';
                $replacements["exam_date{$suffix}"] = '—';
                $replacements["room_name{$suffix}"] = '—';
                $replacements["scores_rows{$suffix}"] = '';
                $replacements["overall_pct{$suffix}"] = '—';
            }
        }

        $replacements['exam_date'] = $replacements['exam_date'] ?? $replacements['exam_date_2'] ?? '—';
        $replacements['room_name'] = $replacements['room_name'] ?? $replacements['room_name_2'] ?? '—';

        $this->addPerDomainReplacements($replacements, $applicants, $sample, $useSampleData);

        return $replacements;
    }

    /**
     * Add per-domain replacements (e.g. {{spatial_awareness}}, {{spatial_awareness_raw}}) for DOCX strict binding.
     *
     * @param  array<string, string>  $replacements
     * @param  array<int, array{name?: string, reference?: string, scores?: array<array{domain: string, raw: int, max: int, pct: int}>}>  $applicants
     * @param  array{name?: string, reference?: string, scores?: array<array{domain: string, raw: int, max: int, pct: int}>}  $sample
     */
    protected function addPerDomainReplacements(array &$replacements, array $applicants, array $sample, bool $useSampleData): void
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
                $raw = $score !== null ? sprintf('%d / %d', (int) ($score['raw'] ?? 0), (int) ($score['max'] ?? 0)) : '—';
                $replacements[$slug.$suffix] = $pct;
                $replacements[$slug.'_raw'.$suffix] = $raw;
            }
        }
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
            $content = preg_replace_callback(
                '/<\s*tr\s+class\s*=\s*["\']'.preg_quote($class, '/').'["\'][^>]*>.*?<\s*\/\s*tr\s*>/s',
                fn () => $rows ?: '<tr class="'.$class.'"><td colspan="3"></td></tr>',
                $content,
                1
            );
        }

        return $content;
    }

    protected function renderDocx(?string $docxPath, array $replacements): string
    {
        if (! $docxPath) {
            return '<p class="text-muted-foreground">No DOCX template.</p>';
        }

        return $this->renderDocxFromFullPath(Storage::path($docxPath), $replacements);
    }

    protected function renderDocxFromFullPath(string $fullPath, array $replacements): string
    {
        if (! is_file($fullPath)) {
            return '<p class="text-destructive">DOCX file not found.</p>';
        }

        $tempDir = storage_path('app/temp/phpword');
        if (! is_dir($tempDir) && ! mkdir($tempDir, 0755, true)) {
            // fall back to system temp — don't override Settings
        } else {
            Settings::setTempDir($tempDir);
        }

        $processor = new TemplateProcessor($fullPath);
        $processor->setMacroChars('{{', '}}');
        foreach ($replacements as $key => $value) {
            $processor->setValue($key, $value);
        }

        $tempDocx = tempnam($tempDir, 'rst_').'.docx';
        $processor->saveAs($tempDocx);

        try {
            $phpWord = IOFactory::load($tempDocx);
            $tempHtml = tempnam($tempDir, 'rst_').'.html';
            $writer = IOFactory::createWriter($phpWord, 'HTML');
            $writer->save($tempHtml);
            $html = file_get_contents($tempHtml);
        } finally {
            @unlink($tempDocx);
            if (isset($tempHtml) && is_file($tempHtml)) {
                @unlink($tempHtml);
            }
        }

        return $html ?: '<p class="text-muted-foreground">Failed to convert DOCX to HTML.</p>';
    }

    protected function buildScoresRows(array $scores): string
    {
        $rows = [];
        foreach ($scores as $s) {
            $rows[] = sprintf(
                '<tr class="border-b border-border/50"><td class="py-1.5">%s</td><td class="text-right py-1.5">%d / %d</td><td class="text-right py-1.5 font-medium">%d%%</td></tr>',
                htmlspecialchars($s['domain'] ?? '—'),
                (int) ($s['raw'] ?? 0),
                (int) ($s['max'] ?? 0),
                (int) ($s['pct'] ?? 0)
            );
        }

        return implode("\n", $rows);
    }

    protected function sampleApplicantData(): array
    {
        return [
            'name' => 'Juan M. Dela Cruz',
            'reference' => 'EXAM-2026-00042',
            'exam_date' => now()->format('F j, Y'),
            'room_name' => 'Conference Hall A - Seat 12',
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
            'reference_2' => 'EXAM-2026-00043',
            'room_name_2' => 'Conference Hall A - Seat 13',
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
}
