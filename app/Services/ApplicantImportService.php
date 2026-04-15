<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\Application;
use App\Models\Course;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ApplicantImportService
{
    public const MAX_FILE_SIZE_BYTES = 10 * 1024 * 1024; // 10MB

    public const REQUIRED_COLUMNS = [
        'first_name',
        'last_name',
        'email',
    ];

    public const OPTIONAL_COLUMNS = [
        'middle_name',
        'suffix',
        'birthdate',
        'sex',
        'phone',
        'address_line',
        'city',
        'province',
        'zip_code',
        'course_preference_1',
        'course_preference_2',
        'course_preference_3',
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
    public function validateRecords(array $records): array
    {
        $valid = [];
        $errors = [];

        foreach ($records as $index => $record) {
            $rowNum = $index + 2; // +2 for 1-based index and header row
            $recordErrors = $this->validateSingleRecord($record, $rowNum);

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
    public function validateRecordsWithDetails(array $records): array
    {
        $results = [];

        foreach ($records as $index => $record) {
            $rowNum = $index + 2;
            $recordErrors = $this->validateSingleRecord($record, $rowNum);

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
     * @return array<int, string>
     */
    private function validateSingleRecord(array $record, int $rowNum): array
    {
        $errors = [];

        // Required: first_name
        if (empty($record['first_name'])) {
            $errors[] = 'First name is required';
        }

        // Required: last_name
        if (empty($record['last_name'])) {
            $errors[] = 'Last name is required';
        }

        // Required: email
        if (empty($record['email'])) {
            $errors[] = 'Email is required';
        } elseif (! filter_var($record['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email format';
        }

        // Optional: validate course preference IDs if provided
        foreach ([1, 2, 3] as $prefNum) {
            $key = "course_preference_{$prefNum}";
            if (! empty($record[$key]) && ! is_numeric($record[$key])) {
                $errors[] = "{$key} must be a number";
            }
        }

        return $errors;
    }

    /**
     * Import validated records into database.
     *
     * @param  array<int, array<string, mixed>>  $records
     * @return array{imported: int, skipped: int, errors: array<int, string>}
     */
    public function importRecords(
        array $records,
        int $academicYearId,
        int $importerId
    ): array {
        $imported = 0;
        $skipped = 0;
        $errors = [];

        foreach ($records as $index => $record) {
            $rowNum = $index + 2;
            $result = $this->importSingleRecord($record, $academicYearId, $importerId);

            if ($result['success']) {
                $imported++;
            } else {
                $skipped++;
                $errors[] = "Row {$rowNum}: {$result['error']}";
            }
        }

        return ['imported' => $imported, 'skipped' => $skipped, 'errors' => $errors];
    }

    /**
     * Import selected records only (for selective import).
     *
     * @param  array<int, array<string, mixed>>  $records
     * @param  array<int>  $selectedIds  Array of record IDs to import
     * @return array{imported: int, skipped: int, errors: array<int, string>}
     */
    public function importSelectedRecords(
        array $records,
        array $selectedIds,
        int $academicYearId,
        int $importerId
    ): array {
        $imported = 0;
        $skipped = 0;
        $errors = [];

        $selectedSet = array_flip($selectedIds);

        foreach ($records as $index => $record) {
            if (! isset($selectedSet[$index])) {
                $skipped++;

                continue;
            }

            $rowNum = $index + 2;
            $result = $this->importSingleRecord($record, $academicYearId, $importerId);

            if ($result['success']) {
                $imported++;
            } else {
                $skipped++;
                $errors[] = "Row {$rowNum}: {$result['error']}";
            }
        }

        return ['imported' => $imported, 'skipped' => $skipped, 'errors' => $errors];
    }

    /**
     * Import a single record into database.
     *
     * @param  array<string, mixed>  $record
     * @return array{success: bool, error?: string}
     */
    private function importSingleRecord(
        array $record,
        int $academicYearId,
        int $importerId
    ): array {
        try {
            // Check for duplicate email in same academic year
            $exists = Application::where('email', $record['email'])
                ->where('academic_year_id', $academicYearId)
                ->exists();

            if ($exists) {
                return ['success' => false, 'error' => 'Duplicate email for this academic year'];
            }

            // Generate reference number
            $referenceNumber = $this->generateReferenceNumber($academicYearId);

            Application::create([
                'academic_year_id' => $academicYearId,
                'reference_number' => $referenceNumber,
                'first_name' => $record['first_name'],
                'middle_name' => $record['middle_name'] ?? null,
                'last_name' => $record['last_name'],
                'suffix' => $record['suffix'] ?? null,
                'birthdate' => $this->parseDate($record['birthdate'] ?? null),
                'sex' => $record['sex'] ?? null,
                'email' => $record['email'],
                'phone' => $record['phone'] ?? null,
                'address_line' => $record['address_line'] ?? null,
                'city' => $record['city'] ?? null,
                'province' => $record['province'] ?? null,
                'zip_code' => $record['zip_code'] ?? null,
                'course_preference_1' => $record['course_preference_1'] ?? null,
                'course_preference_2' => $record['course_preference_2'] ?? null,
                'course_preference_3' => $record['course_preference_3'] ?? null,
                'status' => 'pending',
                'processed_by' => $importerId,
                'processed_at' => now(),
            ]);

            return ['success' => true];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Generate unique reference number.
     */
    private function generateReferenceNumber(int $academicYearId): string
    {
        $year = AcademicYear::find($academicYearId);
        $yearCode = $year?->code ?? date('Y');

        $lastApplication = Application::where('reference_number', 'like', "{$yearCode}%")
            ->orderByDesc('id')
            ->first();

        $sequence = 1;
        if ($lastApplication) {
            $lastSeq = (int) substr($lastApplication->reference_number, -4);
            $sequence = $lastSeq + 1;
        }

        return $yearCode.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Parse date string to Y-m-d format.
     */
    private function parseDate(?string $date): ?string
    {
        if (empty($date)) {
            return null;
        }

        try {
            return Carbon::parse($date)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }
}
