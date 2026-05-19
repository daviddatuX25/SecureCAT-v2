<?php

namespace App\Services;

use App\ValueObjects\DocxValidationResult;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\Exception\Exception;
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

        $tempDocx = null;
        $tempHtml = null;

        $repairedPath = $this->getRepairedDocx($fullPath) ?: $fullPath;

        try {
            $processor = new TemplateProcessor($repairedPath);
            $processor->setMacroChars('{{', '}}');

            $sanitized = array_map(function ($value) {
                $v = (string) $value;

                return str_replace(['{{', '}}'], ['{ {', '} }'], $v);
            }, $replacements);

            foreach ($sanitized as $key => $value) {
                $processor->setValue($key, $value);
            }

            $tempDocx = tempnam($tempDir, 'rst_').'.docx';
            $processor->saveAs($tempDocx);

            $phpWord = IOFactory::load($tempDocx);
            $tempHtml = tempnam($tempDir, 'rst_').'.html';
            $writer = IOFactory::createWriter($phpWord, 'HTML');
            $writer->save($tempHtml);
            $html = file_get_contents($tempHtml);

            return $html ?: '<p class="text-muted-foreground">Failed to convert DOCX to HTML.</p>';
        } catch (Exception $e) {
            Log::error('DOCX render failed', ['path' => $fullPath, 'error' => $e->getMessage()]);

            return '<p class="text-destructive">Failed to process DOCX template: '
                   .htmlspecialchars($e->getMessage()).'</p>';
        } catch (\Throwable $e) {
            Log::error('DOCX render unexpected error', ['path' => $fullPath, 'error' => $e->getMessage()]);

            return '<p class="text-destructive">Unexpected error rendering template.</p>';
        } finally {
            if ($tempDocx && is_file($tempDocx)) {
                @unlink($tempDocx);
            }
            if ($tempHtml && is_file($tempHtml)) {
                @unlink($tempHtml);
            }
            if ($repairedPath && $repairedPath !== $fullPath && is_file($repairedPath)) {
                @unlink($repairedPath);
            }
        }
    }

    /**
     * Validate that a DOCX template contains all required placeholders.
     *
     * @param  array{required: string[], recommended: string[], optional: string[], domain: string[], personnel: string[], institution: string[], applicant2: string[]}  $categorizedPlaceholders
     */
    public function validateDocxTemplate(string $fullPath, array $categorizedPlaceholders, bool $isCrosswise): DocxValidationResult
    {
        $requiredAndRecommended = array_merge($categorizedPlaceholders['required'], $categorizedPlaceholders['recommended']);
        $optionalAll = array_merge(
            $categorizedPlaceholders['optional'],
            $categorizedPlaceholders['domain'],
            $categorizedPlaceholders['personnel'],
            $categorizedPlaceholders['institution'],
        );

        if ($isCrosswise) {
            $optionalAll = array_merge($optionalAll, $categorizedPlaceholders['applicant2']);
        }

        $allKnown = array_merge($requiredAndRecommended, $optionalAll);

        $checks = [];

        if (! is_file($fullPath)) {
            $checks[] = ['label' => 'DOCX file readable', 'detail' => 'File not found', 'status' => 'fail'];
            $checks[] = ['label' => 'Required placeholders', 'detail' => count($requiredAndRecommended).' expected', 'status' => 'fail'];
            $checks[] = ['label' => 'Optional placeholders', 'detail' => '0/'.count($optionalAll).' used', 'status' => 'warn'];

            return new DocxValidationResult(
                valid: false,
                found: [],
                missing: $requiredAndRecommended,
                missingOptional: $optionalAll,
                extra: [],
                checks: $checks,
            );
        }

        $checks[] = ['label' => 'DOCX file readable', 'detail' => 'OK', 'status' => 'pass'];

        $repairedPath = $this->getRepairedDocx($fullPath) ?: $fullPath;

        $processor = new TemplateProcessor($repairedPath);
        $processor->setMacroChars('{{', '}}');
        $docxPlaceholders = $processor->getVariables();

        if ($repairedPath !== $fullPath && is_file($repairedPath)) {
            @unlink($repairedPath);
        }

        $found = array_values(array_intersect($allKnown, $docxPlaceholders));

        // Missing required/recommended: what we care about most
        $missing = array_values(array_diff($requiredAndRecommended, $docxPlaceholders));

        // Missing optional
        $missingOptional = array_values(array_diff($optionalAll, $docxPlaceholders));

        // Extra placeholders in DOCX that we don't know about
        $extra = array_values(array_diff($docxPlaceholders, $allKnown));

        // Build checks
        $checks[] = empty($missing)
            ? ['label' => 'Required placeholders', 'detail' => count($requiredAndRecommended).' found', 'status' => 'pass']
            : ['label' => 'Required placeholders', 'detail' => count($missing).' missing', 'status' => 'fail'];

        $optUsed = count($optionalAll) - count($missingOptional);
        $checks[] = $optUsed > 0
            ? ['label' => 'Optional placeholders', 'detail' => $optUsed.'/'.count($optionalAll).' used', 'status' => 'pass']
            : ['label' => 'Optional placeholders', 'detail' => '0/'.count($optionalAll).' used', 'status' => 'warn'];

        $checks[] = empty($extra)
            ? ['label' => 'No unknown placeholders', 'detail' => 'Clean', 'status' => 'pass']
            : ['label' => 'Unknown placeholders', 'detail' => count($extra).' found', 'status' => 'warn'];

        // Valid only if there are no missing required/recommended placeholders
        return new DocxValidationResult(
            valid: empty($missing),
            found: $found,
            missing: $missing,
            missingOptional: $missingOptional,
            extra: $extra,
            checks: $checks,
        );
    }

    /**
     * Creates a temporary copy of the DOCX and repairs broken {{ }} macros
     * that are split across XML tags. Returns the path to the repaired file.
     */
    protected function getRepairedDocx(string $originalPath): ?string
    {
        $tempDir = storage_path('app/temp/phpword');
        if (! is_dir($tempDir) && ! mkdir($tempDir, 0755, true)) {
            $tempDir = sys_get_temp_dir();
        }

        $tempDocx = tempnam($tempDir, 'rst_repair_').'.docx';
        if (! copy($originalPath, $tempDocx)) {
            return null;
        }

        $zip = new \ZipArchive;
        if ($zip->open($tempDocx) === true) {
            $filesToFix = [];
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $name = $zip->getNameIndex($i);
                if (preg_match('/^word\/(document|header|footer).*\.xml$/i', $name)) {
                    $filesToFix[] = $name;
                }
            }

            // Match {{ ... }} even if separated by XML tags
            $pattern = '/\{[^{}]*\{[^{}]*\}[^{}]*\}/s';

            foreach ($filesToFix as $file) {
                $content = $zip->getFromName($file);
                if (! $content) {
                    continue;
                }

                $fixedContent = preg_replace_callback($pattern, function ($match) {
                    $stripped = strip_tags($match[0]);
                    // Clean up spaces between the double braces that might have been typed by the user
                    $stripped = preg_replace('/\{\s+/', '{', $stripped);
                    $stripped = preg_replace('/\s+\}/', '}', $stripped);

                    return $stripped;
                }, $content);

                if ($content !== $fixedContent) {
                    $zip->addFromString($file, $fixedContent);
                }
            }
            $zip->close();
        }

        return $tempDocx;
    }
}
