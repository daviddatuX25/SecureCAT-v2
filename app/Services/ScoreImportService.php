<?php

namespace App\Services;

use App\Models\Applicant;
use App\Models\ApplicantScore;
use App\Models\Application;
use App\Models\AptitudeArea;
use App\Models\ExamSession;
use App\Models\GradingSession;
use App\Models\SystemSetting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Exception;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ScoreImportService
{
    public const MAX_FILE_SIZE_BYTES = 10 * 1024 * 1024; // 10MB

    public const REQUIRED_COLUMNS = [
        'reference_number',
    ];

    public function validateFile(UploadedFile $file): void
    {
        if ($file->getSize() > self::MAX_FILE_SIZE_BYTES) {
            throw new \InvalidArgumentException('File too large. Maximum size is 10MB.');
        }
    }

    /**
     * Parse spreadsheet file (CSV, XLSX, XLS) into array of records.
     *
     * @return array<int, array<string, mixed>>
     */
    public function parseSpreadsheet(UploadedFile|string $file): array
    {
        $extension = is_string($file)
            ? strtolower(pathinfo($file, PATHINFO_EXTENSION))
            : strtolower($file->getClientOriginalExtension());

        $realPath = is_string($file) ? $file : $file->getRealPath();

        if (is_string($file)) {
            if (! file_exists($file)) {
                throw new \InvalidArgumentException('Import file not found. Please upload again.');
            }
        } else {
            $this->validateFile($file);
        }

        $records = match ($extension) {
            'xlsx', 'xls' => $this->parseExcel($realPath),
            'csv' => $this->parseCsv($realPath),
            default => throw new \InvalidArgumentException(
                'Unsupported file format. Please upload CSV or Excel file (XLSX/XLS).'
            ),
        };

        if (! empty($records)) {
            $this->validateHeaders(array_keys($records[0]));
        }

        return $records;
    }

    /**
     * Parse Excel file (XLSX/XLS) into array of records.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function parseExcel(string $path): array
    {
        try {
            $spreadsheet = IOFactory::load($path);
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();

            if (empty($rows)) {
                throw new \InvalidArgumentException('Excel file is empty.');
            }

            $headers = array_map('strtolower', array_map('trim', array_shift($rows)));

            $records = [];
            foreach ($rows as $row) {
                if (empty(array_filter($row, fn ($v) => $v !== null && $v !== ''))) {
                    continue;
                }

                $row = array_pad($row, count($headers), null);
                $record = array_combine($headers, $row);

                if ($record !== false) {
                    $records[] = array_map(fn ($v) => is_string($v) ? trim($v) : $v, $record);
                }
            }

            return $records;
        } catch (Exception $e) {
            throw new \InvalidArgumentException('Unable to parse Excel file: '.$e->getMessage());
        }
    }

    /**
     * Parse CSV file into array of records.
     *
     * @return array<int, array<string, mixed>>
     */
    public function parseCsv(string $path): array
    {
        $handle = fopen($path, 'r');
        if ($handle === false) {
            throw new \RuntimeException('Unable to read uploaded file.');
        }

        $headers = array_map('strtolower', array_map('trim', fgetcsv($handle)));
        $this->validateHeaders($headers);

        $records = [];
        while (($row = fgetcsv($handle)) !== false) {
            $record = array_combine($headers, $row);
            if ($record !== false) {
                $records[] = array_map('trim', $record);
            }
        }

        fclose($handle);

        return $records;
    }

    /**
     * Validate CSV headers contain required fields.
     *
     * @param  array<int, string>  $headers
     */
    public function validateHeaders(array $headers): void
    {
        $headersLower = array_map('strtolower', $headers);
        $missing = array_diff(self::REQUIRED_COLUMNS, $headersLower);

        if (! empty($missing)) {
            throw new \InvalidArgumentException(
                'Missing required columns: '.implode(', ', $missing)
            );
        }
    }

    /**
     * Validate parsed records and return per-row details for preview.
     *
     * @param  array<int, array<string, mixed>>  $records
     * @return array{records: array<int, array>, summary: array{total: int, valid: int, invalid: int}}
     */
    public function validateRecords(array $records): array
    {
        $activeAreas = AptitudeArea::where('is_active', true)->get(['id', 'code', 'max_items', 'formula']);
        $areaCodeToId = $activeAreas->mapWithKeys(fn ($a) => [strtolower($a->code) => $a->id])->toArray();

        $referenceNumbers = array_filter(array_map(fn ($r) => $r['reference_number'] ?? null, $records));
        $applicationMap = Application::whereIn('reference_number', $referenceNumbers)
            ->with('applicant.examSessions.gradingSession.examSession')
            ->get()
            ->keyBy('reference_number');

        $results = [];
        $validCount = 0;
        $invalidCount = 0;

        foreach ($records as $index => $record) {
            $rowNum = $index + 2;
            $resolution = $this->resolveRow($record, $applicationMap);
            $recordErrors = $resolution['errors'];

            $application = $resolution['application'];
            $applicant = $resolution['applicant'];
            $gradingSession = $resolution['gradingSession'];

            $areaScores = [];
            foreach ($record as $key => $value) {
                $lowerKey = strtolower($key);
                if (isset($areaCodeToId[$lowerKey])) {
                    if ($value !== '' && $value !== null && ! is_numeric($value)) {
                        $recordErrors[] = "{$key} must be a number";
                    }
                    $areaScores[] = [
                        'area_code' => strtoupper($key),
                        'score' => $value,
                    ];
                }
            }

            if (empty($recordErrors) && $applicant && $gradingSession && ! empty($areaScores)) {
                $areaIds = array_map(fn ($s) => $areaCodeToId[strtolower($s['area_code'])], $areaScores);
                $duplicates = $this->checkDuplicateScores(
                    $applicant->id,
                    $gradingSession->examSession->academic_year_id,
                    $areaIds
                );

                if (! empty($duplicates)) {
                    $recordErrors[] = 'Applicant already has scores for this aptitude area in the current academic year';
                }
            }

            $isValid = empty($recordErrors);
            if ($isValid) {
                $validCount++;
            } else {
                $invalidCount++;
            }

            $results[] = [
                'id' => $index,
                'row' => $rowNum,
                'reference_number' => $record['reference_number'] ?? null,
                'applicant_name' => $applicant ? trim("{$application->first_name} {$application->last_name}") : '—',
                'grading_session_id' => $gradingSession?->id,
                'grading_session_label' => $gradingSession ? "Session #{$gradingSession->id}" : '—',
                'scores' => $areaScores,
                'errors' => $recordErrors,
                'is_valid' => $isValid,
            ];
        }

        return [
            'records' => $results,
            'summary' => [
                'total' => count($records),
                'valid' => $validCount,
                'invalid' => $invalidCount,
            ],
        ];
    }

    /**
     * @param  Collection<string, Application>  $applicationMap
     * @return array{application: ?Application, applicant: ?Applicant, gradingSession: ?GradingSession, errors: array<int, string>}
     */
    private function resolveRow(array $record, Collection $applicationMap): array
    {
        if (empty($record['reference_number'])) {
            return ['application' => null, 'applicant' => null, 'gradingSession' => null, 'errors' => ['Reference number is required']];
        }

        $ref = $record['reference_number'];
        $application = $applicationMap[$ref] ?? null;
        if (! $application) {
            return ['application' => null, 'applicant' => null, 'gradingSession' => null, 'errors' => ['Application not found']];
        }

        $applicant = $application->applicant;
        if (! $applicant) {
            return ['application' => $application, 'applicant' => null, 'gradingSession' => null, 'errors' => ['Applicant record not found']];
        }

        $gradingSession = $this->resolveGradingSession($application);
        if (! $gradingSession) {
            return ['application' => $application, 'applicant' => $applicant, 'gradingSession' => null, 'errors' => ['No open grading session found for this applicant']];
        }

        return ['application' => $application, 'applicant' => $applicant, 'gradingSession' => $gradingSession, 'errors' => []];
    }

    private function resolveGradingSession(Application $application): ?GradingSession
    {
        $applicant = $application->applicant;
        if (! $applicant) {
            return null;
        }

        $completedSessions = $applicant->examSessions()
            ->where('status', ExamSession::STATUS_COMPLETED)
            ->with(['gradingSession.examSession'])
            ->get();

        $eligible = $completedSessions->filter(
            fn ($session) => $session->gradingSession && in_array($session->gradingSession->status, [GradingSession::STATUS_OPEN, GradingSession::STATUS_IN_PROGRESS], true)
        );

        return $eligible->count() === 1 ? $eligible->first()->gradingSession : null;
    }

    private function checkDuplicateScores(int $applicantId, int $academicYearId, array $aptitudeAreaIds): array
    {
        if (empty($aptitudeAreaIds)) {
            return [];
        }

        return ApplicantScore::query()
            ->join('grading_sessions', 'applicant_scores.grading_session_id', '=', 'grading_sessions.id')
            ->join('exam_sessions', 'grading_sessions.exam_session_id', '=', 'exam_sessions.id')
            ->where('applicant_scores.applicant_id', $applicantId)
            ->where('exam_sessions.academic_year_id', $academicYearId)
            ->whereIn('applicant_scores.aptitude_area_id', $aptitudeAreaIds)
            ->pluck('applicant_scores.aptitude_area_id')
            ->toArray();
    }

    /**
     * Import selected records. If $selectedIndices is empty, imports all valid rows.
     *
     * @param  array<int, array<string, mixed>>  $records
     * @param  array<int>  $selectedIndices
     * @return array{imported: int, skipped: int, errors: array<int, string>}
     */
    public function importSelectedScores(array $records, array $selectedIndices, int $importerId): array
    {
        $imported = 0;
        $skipped = 0;
        $errors = [];
        $enableNormalizedScores = SystemSetting::enableNormalizedScores();

        $activeAreas = AptitudeArea::where('is_active', true)->get(['id', 'code', 'max_items', 'formula']);
        $areaCodeToId = $activeAreas->mapWithKeys(fn ($a) => [strtolower($a->code) => $a])->all();

        $referenceNumbers = array_filter(array_map(fn ($r) => $r['reference_number'] ?? null, $records));
        $applicationMap = Application::whereIn('reference_number', $referenceNumbers)
            ->with('applicant.examSessions.gradingSession.examSession')
            ->get()
            ->keyBy('reference_number');

        $selectedIndices = array_map('intval', $selectedIndices);
        foreach ($records as $index => $record) {
            if (! empty($selectedIndices) && ! in_array($index, $selectedIndices, true)) {
                continue;
            }

            $rowNum = $index + 2;
            $resolution = $this->resolveRow($record, $applicationMap);
            if (! empty($resolution['errors'])) {
                $skipped++;
                $errors[] = "Row {$rowNum}: {$resolution['errors'][0]}";

                continue;
            }

            $applicant = $resolution['applicant'];
            $gradingSession = $resolution['gradingSession'];

            $areaScores = [];
            foreach ($record as $key => $value) {
                $lowerKey = strtolower($key);
                if (isset($areaCodeToId[$lowerKey]) && $value !== '' && $value !== null) {
                    if (! is_numeric($value)) {
                        $skipped++;
                        $errors[] = "Row {$rowNum}: {$key} must be a number";

                        continue 2;
                    }
                    $areaScores[$lowerKey] = (float) $value;
                }
            }

            if (empty($areaScores)) {
                continue;
            }

            $aptitudeAreaIds = array_map(fn ($code) => $areaCodeToId[$code]->id, array_keys($areaScores));
            $duplicates = $this->checkDuplicateScores(
                $applicant->id,
                $gradingSession->examSession->academic_year_id,
                $aptitudeAreaIds
            );

            if (! empty($duplicates)) {
                $skipped++;
                $errors[] = "Row {$rowNum}: Applicant already has scores for this aptitude area in the current academic year";

                continue;
            }

            DB::transaction(function () use ($areaScores, $areaCodeToId, $enableNormalizedScores, $gradingSession, $applicant, $importerId, &$imported) {
                foreach ($areaScores as $code => $value) {
                    $area = $areaCodeToId[$code];

                    if ($enableNormalizedScores) {
                        $rawScore = $value;
                        $maxScore = $area->max_items;
                        $normalizedScore = $area->computeNormalizedScore($value);
                    } else {
                        $rawScore = null;
                        $maxScore = null;
                        $normalizedScore = $value;
                    }

                    ApplicantScore::create([
                        'grading_session_id' => $gradingSession->id,
                        'applicant_id' => $applicant->id,
                        'aptitude_area_id' => $area->id,
                        'raw_score' => $rawScore,
                        'max_score' => $maxScore,
                        'normalized_score' => $normalizedScore,
                        'scored_by' => $importerId,
                        'scored_at' => now(),
                    ]);

                    $imported++;
                }
            });
        }

        return ['imported' => $imported, 'skipped' => $skipped, 'errors' => $errors];
    }

    public function importScores(array $records, int $importerId): array
    {
        return $this->importSelectedScores($records, [], $importerId);
    }
}
