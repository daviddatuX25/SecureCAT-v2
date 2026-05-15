<?php

namespace App\Services;

use App\Models\AptitudeArea;
use App\Models\ResultSheetTemplate;
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
    public function render(ResultSheetTemplate $template, array $applicants, bool $useSampleData = false): string
    {
        $applicants = array_values($applicants);
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

        if ($template->mode === ResultSheetTemplate::MODE_HTML) {
            return $this->cssService->wrap(
                $this->renderHtml($template->content ?: '', $replacements)
            );
        }

        return $this->renderDocx($template->docx_path, $replacements);
    }

    /**
     * Render from raw HTML content (for preview before template is saved).
     */
    public function renderHtmlContent(string $content, array $applicants = [], bool $useSampleData = true): string
    {
        $applicants = array_values($applicants);
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
        $replacements['exam_date'] = $replacements['exam_date'] ?? '—';
        $replacements['room_name'] = $replacements['room_name'] ?? '—';

        $this->addPerDomainReplacements($replacements, $applicants, $sample, $useSampleData);

        return $this->cssService->wrap(
            $this->renderHtml($content, $replacements)
        );
    }

    /**
     * Render DOCX file to HTML (for preview).
     *
     * @param  array<string, string>  $replacements
     */
    public function renderDocxFile(string $path, array $replacements = [], bool $useSampleIfEmpty = true): string
    {
        if (empty($replacements) && $useSampleIfEmpty) {
            $sample = $this->sampleApplicantData();
            $replacements = [
                'applicant_name' => $sample['name'],
                'applicant_reference' => $sample['reference'],
                'exam_date' => $sample['exam_date'],
                'room_name' => $sample['room_name'],
                'scores_rows' => $this->buildScoresRows($sample['scores']),
                'overall_pct' => (string) $sample['overall_pct'],
                // Applicant 2 (dual layout)
                'applicant_name_2' => $sample['name_2'] ?? '—',
                'applicant_reference_2' => $sample['reference_2'] ?? '—',
                'room_name_2' => $sample['room_name_2'] ?? '—',
                'scores_rows_2' => $this->buildScoresRows($sample['scores_2'] ?? []),
                'overall_pct_2' => (string) ($sample['overall_pct_2'] ?? 0),
            ];
            $this->addPerDomainReplacements($replacements, [], $sample, true);
        }

        return $this->renderDocxFromFullPath($path, $replacements);
    }

    /**
     * Slugify domain name for placeholder key (lowercase, underscores).
     */
    public function aptitudeAreaSlug(string $name): string
    {
        return str_replace('-', '_', Str::slug($name, '_'));
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

    protected function renderHtml(string $content, array $replacements): string
    {
        foreach ($replacements as $key => $value) {
            $content = str_replace('{{'.$key.'}}', $value, $content);
        }

        // Replace structural placeholders for scores_rows (survive HTML Purifier; raw {{scores_rows}} in tbody gets stripped)
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

        // Use a dedicated temp directory to avoid Windows permission issues
        $tempDir = storage_path('app/temp/phpword');
        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }
        Settings::setTempDir($tempDir);

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
            // Sample for applicant 2 (dual layout)
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

    /**
     * Get CSS dimensions for preview container.
     */
    public function previewDimensions(string $paperSize, string $orientation, string $logicalUnit): array
    {
        $sizes = [
            'a4' => ['portrait' => [210, 297], 'landscape' => [297, 210]],
            'legal' => ['portrait' => [216, 356], 'landscape' => [356, 216]],
            'letter' => ['portrait' => [216, 279], 'landscape' => [279, 216]],
        ];
        [$w, $h] = $sizes[$paperSize][$orientation] ?? [210, 297];

        if (str_starts_with($logicalUnit, 'half_')) {
            $h = (int) ($h / 2);
        }

        return ['width' => "{$w}mm", 'height' => "{$h}mm"];
    }
}
