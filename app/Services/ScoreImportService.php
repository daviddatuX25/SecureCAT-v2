<?php

namespace App\Services;

use App\Models\Applicant;
use App\Models\ApplicantScore;
use App\Models\GradingSession;
use Illuminate\Http\UploadedFile;
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
    public function parseSpreadsheet(UploadedFile $file): array
    {
        $extension = strtolower($file->getClientOriginalExtension());

        $records = match ($extension) {
            'xlsx', 'xls' => $this->parseExcel($file),
            'csv' => $this->parseCsv($file),
            default => throw new \InvalidArgumentException(
                'Unsupported file format. Please upload CSV or Excel file (XLSX/XLS).'
            ),
        };

        // Validate required columns after parsing
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
    protected function parseExcel(UploadedFile $file): array
    {
        $this->validateFile($file);

        try {
            $spreadsheet = IOFactory::load($file->getRealPath());
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();

            if (empty($rows)) {
                throw new \InvalidArgumentException('Excel file is empty.');
            }

            // First row is headers
            $headers = array_map('strtolower', array_map('trim', array_shift($rows)));

            $records = [];
            foreach ($rows as $rowIndex => $row) {
                // Skip completely empty rows
                if (empty(array_filter($row, fn ($v) => $v !== null && $v !== ''))) {
                    continue;
                }

                // Pad row to match header count
                $row = array_pad($row, count($headers), null);
                $record = array_combine($headers, $row);

                if ($record !== false) {
                    $records[] = array_map(fn ($v) => is_string($v) ? trim($v) : $v, $record);
                }
            }

            return $records;
        } catch (\PhpOffice\PhpSpreadsheet\Exception $e) {
            throw new \InvalidArgumentException('Unable to parse Excel file: '.$e->getMessage());
        }
    }

    /**
     * Parse CSV file into array of records.
     *
     * @return array<int, array<string, mixed>>
     */
    public function parseCsv(UploadedFile $file): array
    {
        $this->validateFile($file);

        $handle = fopen($file->getRealPath(), 'r');
        if ($handle === false) {
            throw new \RuntimeException('Unable to read uploaded file.');
        }

        $headers = array_map('strtolower', array_map('trim', fgetcsv($handle)));

        // Validate required columns exist
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
     * Validate parsed records and return errors.
     *
     * @param  array<int, array<string, mixed>>  $records
     * @return array{valid: array<int, array<string, mixed>>, errors: array<int, string>}
     */
    public function validateRecords(array $records, int $gradingSessionId): array
    {
        $valid = [];
        $errors = [];

        $gradingSession = GradingSession::with('aptitudeAreas')
            ->findOrFail($gradingSessionId);
        $aptitudeAreaIds = $gradingSession->aptitudeAreas->pluck('id')->toArray();

        foreach ($records as $index => $record) {
            $rowNum = $index + 2; // +2 for 1-based index and header row
            $recordErrors = $this->validateSingleRecord(
                $record,
                $rowNum,
                $aptitudeAreaIds
            );

            if (empty($recordErrors)) {
                $valid[] = $record;
            } else {
                $errors[] = "Row {$rowNum}: ".implode('; ', $recordErrors);
            }
        }

        return ['valid' => $valid, 'errors' => $errors];
    }

    /**
     * Validate all records with per-row details for preview.
     *
     * @param  array<int, array<string, mixed>>  $records
     * @return array{records: array<int, array{id: int, row: int, data: array, errors: array<int, string>, is_valid: bool>}
     */
    public function validateRecordsWithDetails(array $records, int $gradingSessionId): array
    {
        $results = [];

        $gradingSession = GradingSession::with('aptitudeAreas')
            ->findOrFail($gradingSessionId);
        $aptitudeAreaIds = $gradingSession->aptitudeAreas->pluck('id')->toArray();

        foreach ($records as $index => $record) {
            $rowNum = $index + 2;
            $recordErrors = $this->validateSingleRecord(
                $record,
                $rowNum,
                $aptitudeAreaIds
            );

            $results[] = [
                'id' => $index,
                'row' => $rowNum,
                'data' => $record,
                'errors' => $recordErrors,
                'is_valid' => empty($recordErrors),
            ];
        }

        return $results;
    }

    /**
     * Validate a single record.
     *
     * @param  array<string, mixed>  $record
     * @param  array<int, int>  $aptitudeAreaIds
     * @return array<int, string>
     */
    private function validateSingleRecord(
        array $record,
        int $rowNum,
        array $aptitudeAreaIds
    ): array {
        $errors = [];

        // Required: reference_number
        if (empty($record['reference_number'])) {
            $errors[] = 'Reference number is required';
        }

        // Validate applicant exists
        $applicant = Application::where('reference_number', $record['reference_number'])
            ->first();
        if (! $applicant) {
            $errors[] = 'Applicant not found';
        }

        // Validate aptitude_area_id if provided
        if (! empty($record['aptitude_area_id'])) {
            if (! in_array((int) $record['aptitude_area_id'], $aptitudeAreaIds)) {
                $errors[] = 'Invalid aptitude area for this session';
            }
        }

        // Validate numeric score fields
        foreach (['raw_score', 'max_score', 'normalized_score'] as $field) {
            if (isset($record[$field]) && $record[$field] !== '') {
                if (! is_numeric($record[$field])) {
                    "{$field} must be a number";
                }
            }
        }

        return $errors;
    }

    /**
     * Import validated records into database.
     *
     * @param  array<int, array<string, mixed>>  $records
     * @return array{imported: int, updated: int, skipped: int, errors: array<int, string>}
     */
    public function importScores(
        array $records,
        int $gradingSessionId,
        int $importerId
    ): array {
        $imported = 0;
        $updated = 0;
        $skipped = 0;
        $errors = [];

        foreach ($records as $index => $record) {
            $rowNum = $index + 2;
            $result = $this->importSingleScore(
                $record,
                $gradingSessionId,
                $importerId
            );

            if ($result['success']) {
                if ($result['created']) {
                    $imported++;
                } else {
                    $updated++;
                }
            } else {
                $skipped++;
                $errors[] = "Row {$rowNum}: {$result['error']}";
            }
        }

        return [
            'imported' => $imported,
            'updated' => $updated,
            'skipped' => $skipped,
            'errors' => $errors,
        ];
    }

    /**
     * Import selected records only (for selective import).
     *
     * @param  array<int, array<string, mixed>>  $records
     * @param  array<int>  $selectedIds
     * @return array{imported: int, updated: int, skipped: int, errors: array<int, string>}
     */
    public function importSelectedScores(
        array $records,
        array $selectedIds,
        int $gradingSessionId,
        int $importerId
    ): array {
        $imported = 0;
        $updated = 0;
        $skipped = 0;
        $errors = [];

        $selectedSet = array_flip($selectedIds);

        foreach ($records as $index => $record) {
            if (! isset($selectedSet[$index])) {
                $skipped++;

                continue;
            }

            $rowNum = $index + 2;
            $result = $this->importSingleScore(
                $record,
                $gradingSessionId,
                $importerId
            );

            if ($result['success']) {
                if ($result['created']) {
                    $imported++;
                } else {
                    $updated++;
                }
            } else {
                $skipped++;
                $errors[] = "Row {$rowNum}: {$result['error']}";
            }
        }

        return [
            'imported' => $imported,
            'updated' => $updated,
            'skipped' => $skipped,
            'errors' => $errors,
        ];
    }

    /**
     * Import a single score record into database.
     *
     * @param  array<string, mixed>  $record
     * @return array{success: bool, created: bool, error?: string}
     */
    private function importSingleScore(
        array $record,
        int $gradingSessionId,
        int $importerId
    ): array {
        try {
            // Find application by reference number
            $application = Application::where(
                'reference_number',
                $record['reference_number']
            )->first();

            if (! $application) {
                return ['success' => false, 'created' => false, 'error' => 'Applicant not found'];
            }

            // Find or create applicant
            $applicant = Applicant::firstOrCreate(
                ['application_id' => $application->id],
                ['email' => $application->email]
            );

            // Determine aptitude_area_id
            $aptitudeAreaId = ! empty($record['aptitude_area_id'])
                ? (int) $record['aptitude_area_id']
                : null;

            // Check if score already exists
            $existingScore = ApplicantScore::where('grading_session_id', $gradingSessionId)
                ->where('applicant_id', $applicant->id)
                ->when($aptitudeAreaId, fn ($q) => $q->where('aptitude_area_id', $aptitudeAreaId))
                ->first();

            if ($existingScore) {
                // Update existing score
                $existingScore->update([
                    'raw_score' => $record['raw_score'] ?? $existingScore->raw_score,
                    'max_score' => $record['max_score'] ?? $existingScore->max_score,
                    'normalized_score' => $record['normalized_score'] ?? $existingScore->normalized_score,
                    'scored_by' => $importerId,
                    'scored_at' => now(),
                ]);

                return ['success' => true, 'created' => false];
            }

            // Create new score
            ApplicantScore::create([
                'grading_session_id' => $gradingSessionId,
                'applicant_id' => $applicant->id,
                'aptitude_area_id' => $aptitudeAreaId,
                'raw_score' => $record['raw_score'] ?? null,
                'max_score' => $record['max_score'] ?? null,
                'normalized_score' => $record['normalized_score'] ?? null,
                'scored_by' => $importerId,
                'scored_at' => now(),
            ]);

            return ['success' => true, 'created' => true];
        } catch (\Exception $e) {
            return ['success' => false, 'created' => false, 'error' => $e->getMessage()];
        }
    }
}
