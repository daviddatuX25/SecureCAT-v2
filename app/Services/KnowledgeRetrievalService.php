<?php

namespace App\Services;

use App\Models\Applicant;
use App\Models\KnowledgeDocument;
use Illuminate\Support\Facades\DB;

class KnowledgeRetrievalService
{
    public const DEFAULT_MAX_DOCS = 10;

    public const DEFAULT_MAX_TOTAL_CHARS = 8000;

    /**
     * Retrieve institutional knowledge text for an applicant: active docs optionally filtered by
     * course preferences (metadata.category), limited by doc count and total length.
     * Deterministic order (updated_at desc, id desc) for reproducible truncation (T5.3).
     *
     * @return string "No institutional data available." or concatenated content with source labels
     */
    public function retrieveForApplicant(
        Applicant $applicant,
        int $maxDocs = self::DEFAULT_MAX_DOCS,
        int $maxTotalChars = self::DEFAULT_MAX_TOTAL_CHARS
    ): string {
        $applicant->load('application');
        $courseNames = $this->getApplicantCoursePreferenceNames($applicant);

        $docs = KnowledgeDocument::query()
            ->active()
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->get();

        $filtered = $this->filterByCategory($docs, $courseNames);

        $chunks = [];
        $total = 0;

        foreach ($filtered as $doc) {
            if (count($chunks) >= $maxDocs) {
                break;
            }
            $content = trim($doc->content ?? '');
            if ($content === '') {
                continue;
            }
            $label = "Source: {$doc->title}";
            $block = "{$label}\n{$content}";
            $blockLen = strlen($block);

            if ($total + $blockLen > $maxTotalChars) {
                $remaining = $maxTotalChars - $total - (int) strlen("\n\n") - (int) strlen($label) - 1;
                if ($remaining > 0) {
                    $truncated = mb_substr($content, 0, $remaining);
                    if ($truncated !== '') {
                        $lastChunk = "{$label}\n{$truncated}";
                        $chunks[] = $lastChunk;
                        $total += strlen($lastChunk);
                    }
                }
                break;
            }

            $chunks[] = $block;
            $total += $blockLen;
        }

        if ($chunks === []) {
            return 'No institutional data available.';
        }

        return implode("\n\n", $chunks);
    }

    /**
     * Retrieve with explicit filters (e.g. for year). Used when caller wants to filter by year (T5.5).
     *
     * @param  array{category?: string, year?: string}  $filters
     */
    public function retrieveWithFilters(
        array $filters = [],
        int $maxDocs = self::DEFAULT_MAX_DOCS,
        int $maxTotalChars = self::DEFAULT_MAX_TOTAL_CHARS
    ): string {
        $docs = KnowledgeDocument::query()
            ->active()
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->get();

        $filtered = $this->filterByCategoryAndYear($docs, $filters);

        $chunks = [];
        $total = 0;

        foreach ($filtered as $doc) {
            if (count($chunks) >= $maxDocs) {
                break;
            }
            $content = trim($doc->content ?? '');
            if ($content === '') {
                continue;
            }
            $label = "Source: {$doc->title}";
            $block = "{$label}\n{$content}";
            $blockLen = strlen($block);

            if ($total + $blockLen > $maxTotalChars) {
                $remaining = $maxTotalChars - $total - (int) strlen("\n\n") - (int) strlen($label) - 1;
                if ($remaining > 0) {
                    $truncated = mb_substr($content, 0, $remaining);
                    if ($truncated !== '') {
                        $lastChunk = "{$label}\n{$truncated}";
                        $chunks[] = $lastChunk;
                        $total += strlen($lastChunk);
                    }
                }
                break;
            }

            $chunks[] = $block;
            $total += $blockLen;
        }

        if ($chunks === []) {
            return 'No institutional data available.';
        }

        return implode("\n\n", $chunks);
    }

    /**
     * Get course preference names for the applicant (from application course IDs).
     *
     * @return array<int, string>
     */
    private function getApplicantCoursePreferenceNames(Applicant $applicant): array
    {
        $application = $applicant->application;
        if (! $application) {
            return [];
        }

        $ids = [];
        foreach (['course_preference_1', 'course_preference_2', 'course_preference_3'] as $key) {
            $id = $application->{$key} ?? null;
            if (! empty($id) && is_numeric($id)) {
                $ids[] = (int) $id;
            }
        }

        if ($ids === []) {
            return [];
        }

        $names = DB::table('courses')->whereIn('id', array_unique($ids))->pluck('name')->all();

        return array_values(array_filter(array_map('trim', $names)));
    }

    /**
     * Filter docs by category: when course names exist, include docs whose metadata category
     * matches any (case-insensitive) or is empty (T5.4). When no course names, include all.
     *
     * @param  \Illuminate\Support\Collection<int, KnowledgeDocument>  $docs
     * @param  array<int, string>  $courseNames
     * @return \Illuminate\Support\Collection<int, KnowledgeDocument>
     */
    private function filterByCategory($docs, array $courseNames)
    {
        if ($courseNames === []) {
            return $docs;
        }

        $lowerNames = array_map('strtolower', $courseNames);

        return $docs->filter(function (KnowledgeDocument $doc) use ($lowerNames) {
            $category = isset($doc->metadata['category']) ? (string) $doc->metadata['category'] : '';
            $categoryLower = strtolower(trim($category));

            if ($categoryLower === '') {
                return true;
            }

            return in_array($categoryLower, $lowerNames, true)
                || collect($lowerNames)->contains(fn ($name) => str_contains($name, $categoryLower) || str_contains($categoryLower, $name));
        })->values();
    }

    /**
     * Filter by optional category and year (T5.5).
     *
     * @param  \Illuminate\Support\Collection<int, KnowledgeDocument>  $docs
     * @param  array{category?: string, year?: string}  $filters
     * @return \Illuminate\Support\Collection<int, KnowledgeDocument>
     */
    private function filterByCategoryAndYear($docs, array $filters)
    {
        $year = isset($filters['year']) ? trim((string) $filters['year']) : null;
        $category = isset($filters['category']) ? trim((string) $filters['category']) : null;

        return $docs->filter(function (KnowledgeDocument $doc) use ($year, $category) {
            if ($year !== null && $year !== '') {
                $docYear = isset($doc->metadata['year']) ? trim((string) $doc->metadata['year']) : '';
                if ($docYear !== '' && $docYear !== $year) {
                    return false;
                }
            }
            if ($category !== null && $category !== '') {
                $docCat = isset($doc->metadata['category']) ? trim((string) $doc->metadata['category']) : '';
                if ($docCat !== '' && strtolower($docCat) !== strtolower($category)) {
                    return false;
                }
            }

            return true;
        })->values();
    }
}
