<?php

namespace App\Services;

use App\ValueObjects\DocxValidationResult;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Settings;
use PhpOffice\PhpWord\TemplateProcessor;

class ResultSheetDocxService
{
    /**
     * Render a DOCX from a storage-relative path with placeholder replacements.
     */
    public function renderDocxFromStoragePath(?string $docxPath, array $replacements): string
    {
        if (! $docxPath) {
            return '<p class="text-muted-foreground">No DOCX template.</p>';
        }

        return $this->renderDocxFromFullPath(Storage::path($docxPath), $replacements);
    }

    /**
     * Render a DOCX from an absolute filesystem path with placeholder replacements.
     */
    public function renderDocxFromFullPath(string $fullPath, array $replacements): string
    {
        if (! is_file($fullPath)) {
            return '<p class="text-destructive">DOCX file not found.</p>';
        }

        $tempDir = storage_path('app/temp/phpword');
        if (! is_dir($tempDir) && ! mkdir($tempDir, 0755, true)) {
            // fall back to system temp
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

    /**
     * Validate that a DOCX template contains all required placeholders.
     *
     * @param  string[]  $requiredPlaceholders
     */
    public function validateDocxTemplate(string $fullPath, array $requiredPlaceholders, bool $isCrosswise): DocxValidationResult
    {
        if (! is_file($fullPath)) {
            return new DocxValidationResult(
                valid: false,
                found: [],
                missing: $requiredPlaceholders,
                extra: [],
            );
        }

        $processor = new TemplateProcessor($fullPath);
        $processor->setMacroChars('{{', '}}');
        $docxPlaceholders = $processor->getVariables();

        // Filter _2 placeholders when not crosswise
        if (! $isCrosswise) {
            $requiredPlaceholders = array_values(
                array_filter($requiredPlaceholders, fn (string $p) => ! str_ends_with($p, '_2'))
            );
        }

        $found = array_values(array_intersect($requiredPlaceholders, $docxPlaceholders));
        $missing = array_values(array_diff($requiredPlaceholders, $docxPlaceholders));
        $extra = array_values(array_diff($docxPlaceholders, $requiredPlaceholders));

        return new DocxValidationResult(
            valid: empty($missing),
            found: $found,
            missing: $missing,
            extra: $extra,
        );
    }
}
