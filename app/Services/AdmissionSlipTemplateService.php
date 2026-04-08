<?php

namespace App\Services;

use App\Models\AdmissionSlipTemplate;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\TemplateProcessor;

class AdmissionSlipTemplateService
{
    public const PLACEHOLDERS = [
        'reference_number', 'full_name', 'birthdate', 'sex',
        'course_1', 'course_2', 'course_3',
        'photo_placeholder', 'qr_placeholder',
        'reference_number_2', 'full_name_2', 'birthdate_2', 'sex_2',
        'course_1_2', 'course_2_2', 'course_3_2',
        'photo_placeholder_2', 'qr_placeholder_2',
    ];

    /**
     * Render template HTML for one or two applications (admission slip data).
     *
     * @param  array<int, array{reference_number: string, full_name: string, birthdate: string, sex: string, course_1: string, course_2: string, course_3: string}>  $applications
     */
    public function render(AdmissionSlipTemplate $template, array $applications, bool $useSampleData = false): string
    {
        $applications = array_values($applications);
        $sample = $this->sampleApplicantData();

        $replacements = [];
        foreach ([1 => 0, 2 => 1] as $slot => $idx) {
            $app = $applications[$idx] ?? null;
            $data = $app ?? ($useSampleData ? $sample : null);
            $suffix = $slot === 1 ? '' : '_2';
            if ($data) {
                $replacements["reference_number{$suffix}"] = $data['reference_number'] ?? '—';
                $replacements["full_name{$suffix}"] = $data['full_name'] ?? '—';
                $replacements["birthdate{$suffix}"] = $data['birthdate'] ?? '—';
                $replacements["sex{$suffix}"] = $data['sex'] ?? '—';
                $replacements["course_1{$suffix}"] = $data['course_1'] ?? '—';
                $replacements["course_2{$suffix}"] = $data['course_2'] ?? '—';
                $replacements["course_3{$suffix}"] = $data['course_3'] ?? '—';
                $replacements["photo_placeholder{$suffix}"] = $this->photoPlaceholder();
                $replacements["qr_placeholder{$suffix}"] = $this->qrPlaceholder($data['reference_number'] ?? '');
            } else {
                $replacements["reference_number{$suffix}"] = '—';
                $replacements["full_name{$suffix}"] = '—';
                $replacements["birthdate{$suffix}"] = '—';
                $replacements["sex{$suffix}"] = '—';
                $replacements["course_1{$suffix}"] = '—';
                $replacements["course_2{$suffix}"] = '—';
                $replacements["course_3{$suffix}"] = '—';
                $replacements["photo_placeholder{$suffix}"] = '';
                $replacements["qr_placeholder{$suffix}"] = '';
            }
        }

        if ($template->mode === AdmissionSlipTemplate::MODE_HTML) {
            return $this->renderHtml($template->content ?: '', $replacements);
        }

        return $this->renderDocx($template->docx_path, $replacements);
    }

    /**
     * Render from raw HTML content (for preview before template is saved).
     */
    public function renderHtmlContent(string $content, array $applications = [], bool $useSampleData = true): string
    {
        $applications = array_values($applications);
        $sample = $this->sampleApplicantData();

        $replacements = [];
        foreach ([1 => 0, 2 => 1] as $slot => $idx) {
            $app = $applications[$idx] ?? null;
            $data = $app ?? ($useSampleData ? $sample : null);
            $suffix = $slot === 1 ? '' : '_2';
            if ($data) {
                $replacements["reference_number{$suffix}"] = $data['reference_number'] ?? '—';
                $replacements["full_name{$suffix}"] = $data['full_name'] ?? '—';
                $replacements["birthdate{$suffix}"] = $data['birthdate'] ?? '—';
                $replacements["sex{$suffix}"] = $data['sex'] ?? '—';
                $replacements["course_1{$suffix}"] = $data['course_1'] ?? '—';
                $replacements["course_2{$suffix}"] = $data['course_2'] ?? '—';
                $replacements["course_3{$suffix}"] = $data['course_3'] ?? '—';
                $replacements["photo_placeholder{$suffix}"] = $this->photoPlaceholder();
                $replacements["qr_placeholder{$suffix}"] = $this->qrPlaceholder($data['reference_number'] ?? '');
            } else {
                $replacements["reference_number{$suffix}"] = '—';
                $replacements["full_name{$suffix}"] = '—';
                $replacements["birthdate{$suffix}"] = '—';
                $replacements["sex{$suffix}"] = '—';
                $replacements["course_1{$suffix}"] = '—';
                $replacements["course_2{$suffix}"] = '—';
                $replacements["course_3{$suffix}"] = '—';
                $replacements["photo_placeholder{$suffix}"] = '';
                $replacements["qr_placeholder{$suffix}"] = '';
            }
        }

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
                'reference_number' => $sample['reference_number'],
                'full_name' => $sample['full_name'],
                'birthdate' => $sample['birthdate'],
                'sex' => $sample['sex'],
                'course_1' => $sample['course_1'],
                'course_2' => $sample['course_2'],
                'course_3' => $sample['course_3'],
                'photo_placeholder' => $this->photoPlaceholder(),
                'qr_placeholder' => $this->qrPlaceholder($sample['reference_number']),
            ];
        }

        return $this->renderDocx($path, $replacements);
    }

    protected function renderHtml(string $content, array $replacements): string
    {
        foreach ($replacements as $key => $value) {
            $content = str_replace('{{'.$key.'}}', $value, $content);
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

        $tempDocx = tempnam(sys_get_temp_dir(), 'ast_').'.docx';
        $processor->saveAs($tempDocx);

        try {
            $phpWord = IOFactory::load($tempDocx);
            $tempHtml = tempnam(sys_get_temp_dir(), 'ast_').'.html';
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

    protected function photoPlaceholder(): string
    {
        return '<div class="photo-placeholder" style="width:120px;height:150px;border:2px dashed #9ca3af;display:inline-block;text-align:center;line-height:150px;color:#9ca3af;font-size:10px;">Photo</div>';
    }

    protected function qrPlaceholder(string $reference): string
    {
        return sprintf(
            '<div class="qr-placeholder" style="width:80px;height:80px;border:2px dashed #9ca3af;display:inline-block;text-align:center;line-height:80px;color:#9ca3af;font-size:10px;">QR Code</div>'
        );
    }

    protected function sampleApplicantData(): array
    {
        return [
            'reference_number' => 'APP-2026-00001',
            'full_name' => 'Juan Dela Cruz',
            'birthdate' => 'January 15, 2005',
            'sex' => 'Male',
            'course_1' => 'Bachelor of Science in Computer Science',
            'course_2' => 'Bachelor of Science in Information Technology',
            'course_3' => 'Bachelor of Science in Data Science',
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
