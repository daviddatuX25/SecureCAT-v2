<?php

namespace App\Services;

use App\Models\ExamDomain;
use App\Models\ResultSheetTemplate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\TemplateProcessor;

class ResultSheetTemplateService
{
    public const PLACEHOLDERS = [
        'applicant_name', 'applicant_reference', 'exam_date', 'room_name',
        'scores_rows', 'overall_pct', 'qr_code',
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
                $replacements["qr_code{$suffix}"] = $this->qrPlaceholder($data['reference'] ?? '');
            } else {
                $replacements["applicant_name{$suffix}"] = '—';
                $replacements["applicant_reference{$suffix}"] = '—';
                $replacements["exam_date{$suffix}"] = '—';
                $replacements["room_name{$suffix}"] = '—';
                $replacements["scores_rows{$suffix}"] = '';
                $replacements["overall_pct{$suffix}"] = '—';
                $replacements["qr_code{$suffix}"] = '';
            }
        }

        $replacements['exam_date'] = $replacements['exam_date'] ?? $replacements['exam_date_2'] ?? '—';
        $replacements['room_name'] = $replacements['room_name'] ?? $replacements['room_name_2'] ?? '—';

        $this->addPerDomainReplacements($replacements, $applicants, $sample, $useSampleData);

        if ($template->mode === ResultSheetTemplate::MODE_HTML) {
            return $this->renderHtml($template->content ?: '', $replacements);
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
                $replacements["qr_code{$suffix}"] = $this->qrPlaceholder($data['reference'] ?? '');
            } else {
                $replacements["applicant_name{$suffix}"] = '—';
                $replacements["applicant_reference{$suffix}"] = '—';
                $replacements["exam_date{$suffix}"] = '—';
                $replacements["room_name{$suffix}"] = '—';
                $replacements["scores_rows{$suffix}"] = '';
                $replacements["overall_pct{$suffix}"] = '—';
                $replacements["qr_code{$suffix}"] = '';
            }
        }
        $replacements['exam_date'] = $replacements['exam_date'] ?? '—';
        $replacements['room_name'] = $replacements['room_name'] ?? '—';

        $this->addPerDomainReplacements($replacements, $applicants, $sample, $useSampleData);

        return $this->renderHtml($content, $replacements);
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
                'qr_code' => $this->qrPlaceholder($sample['reference']),
            ];
            $this->addPerDomainReplacements($replacements, [], $sample, true);
        }

        return $this->renderDocx($path, $replacements);
    }

    /**
     * Slugify domain name for placeholder key (lowercase, underscores).
     */
    public function domainSlug(string $name): string
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
        $domains = ExamDomain::where('is_active', true)->orderBy('display_order')->get(['id', 'name']);

        foreach ([1 => 0, 2 => 1] as $slot => $idx) {
            $suffix = $slot === 1 ? '' : '_2';
            $data = $applicants[$idx] ?? null;
            $data = $data ?? ($useSampleData ? $sample : null);
            $scores = $data['scores'] ?? [];
            $scoresByDomain = collect($scores)->keyBy(fn (array $s) => $s['domain'] ?? '');

            foreach ($domains as $domain) {
                $slug = $this->domainSlug($domain->name);
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

        $fullPath = Storage::path($docxPath);
        if (! is_file($fullPath)) {
            return '<p class="text-destructive">DOCX file not found.</p>';
        }

        $processor = new TemplateProcessor($fullPath);
        $processor->setMacroChars('{{', '}}');
        foreach ($replacements as $key => $value) {
            $processor->setValue($key, $value);
        }

        $tempDocx = tempnam(sys_get_temp_dir(), 'rst_').'.docx';
        $processor->saveAs($tempDocx);

        try {
            $phpWord = IOFactory::load($tempDocx);
            $tempHtml = tempnam(sys_get_temp_dir(), 'rst_').'.html';
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

    protected function qrPlaceholder(string $reference): string
    {
        if ($reference === '' || $reference === '—') {
            return sprintf(
                '<div class="w-20 h-20 border-2 border-dashed border-muted-foreground/50 rounded flex items-center justify-center text-xs text-muted-foreground text-center px-1">QR Code<br />%s</div>',
                htmlspecialchars($reference ?: '—')
            );
        }

        $qrService = app(QrCodeService::class);
        $dataUri = $qrService->consultationDataUri($reference);

        return sprintf(
            '<img src="%s" alt="QR Code" width="80" height="80" class="inline-block align-middle rounded" />',
            htmlspecialchars($dataUri)
        );
    }

    protected function sampleApplicantData(): array
    {
        return [
            'name' => 'Juan Dela Cruz',
            'reference' => 'APP-2026-00001',
            'exam_date' => now()->format('F j, Y'),
            'room_name' => 'Room 101',
            'scores' => [
                ['domain' => 'Spatial Awareness', 'raw' => 20, 'max' => 25, 'pct' => 80],
                ['domain' => 'Numerical Ability', 'raw' => 22, 'max' => 25, 'pct' => 88],
                ['domain' => 'Verbal Reasoning', 'raw' => 19, 'max' => 25, 'pct' => 76],
                ['domain' => 'Abstract Reasoning', 'raw' => 16, 'max' => 20, 'pct' => 80],
                ['domain' => 'Logical Reasoning', 'raw' => 21, 'max' => 25, 'pct' => 84],
                ['domain' => 'Perceptual Speed & Accuracy', 'raw' => 17, 'max' => 20, 'pct' => 85],
            ],
            'overall_pct' => 82,
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
