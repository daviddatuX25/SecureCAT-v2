<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\Application;
use App\Models\Course;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;

class ApplicantImportService
{
    public function __construct(
        private readonly SpreadsheetParser $parser,
    ) {}

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
        'applicant_type',
        'last_school_enrolled',
        'strand',
        'phone',
        'address_line',
        'city',
        'province',
        'zip_code',
        'course_preference_1',
        'course_preference_2',
        'course_preference_3',
        'gwa',
    ];

    /**
     * Normalize a header string: lowercase, replace spaces/dashes/dots with underscores,
     * strip non-alphanumeric/underscore characters, and collapse multiple underscores.
     * This allows CSVs with headers like "First Name" or "course preference 1" to work.
     */
    private function normalizeHeader(string $header): string
    {
        $header = strtolower(trim($header));
        // Replace common separators with underscore
        $header = preg_replace('/[\s\-\.]+/', '_', $header);
        // Strip anything that isn't alphanumeric or underscore
        $header = preg_replace('/[^a-z0-9_]/', '', $header);
        // Collapse multiple underscores
        $header = preg_replace('/_+/', '_', $header);

        return trim($header, '_');
    }

    /** Cached course code→id map for resolving preferences */
    private ?array $courseCodeMap = null;

    /**
     * Build or return cached map of course code (lowercase) → course ID.
     *
     * @return array<string, int>
     */
    private function courseCodeMap(): array
    {
        if ($this->courseCodeMap !== null) {
            return $this->courseCodeMap;
        }

        $this->courseCodeMap = Course::query()
            ->pluck('id', 'code')
            ->mapWithKeys(fn ($id, $code) => [strtolower($code) => $id])
            ->toArray();

        return $this->courseCodeMap;
    }

    /**
     * Resolve a course preference value to a course ID.
     * Accepts either a course code (e.g. "BSCS") or a numeric course ID.
     *
     * @return array{resolved: int|null, error: string|null}
     */
    private function resolveCoursePreference(?string $value, string $fieldName): array
    {
        if (empty($value)) {
            return ['resolved' => null, 'error' => null];
        }

        $trimmed = trim($value);

        // If numeric, treat as a course ID — validate it exists
        if (is_numeric($trimmed)) {
            $id = (int) $trimmed;
            if (Course::where('id', $id)->exists()) {
                return ['resolved' => $id, 'error' => null];
            }

            return ['resolved' => null, 'error' => "{$fieldName}: course ID {$id} does not exist"];
        }

        // Otherwise treat as a course code — resolve to ID
        $codeLower = strtolower($trimmed);
        $map = $this->courseCodeMap();

        if (isset($map[$codeLower])) {
            return ['resolved' => $map[$codeLower], 'error' => null];
        }

        return ['resolved' => null, 'error' => "{$fieldName}: unknown course code \"{$trimmed}\""];
    }

    /**
     * Parse spreadsheet file (CSV, XLSX, XLS) into array of records.
     * Delegates to the shared SpreadsheetParser.
     *
     * @return array<int, array<string, mixed>>
     */
    public function parseSpreadsheet(UploadedFile $file): array
    {
        $records = $this->parser->parse($file);

        if (! empty($records)) {
            $this->validateHeaders(array_keys($records[0]));
        }

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

        // Required by DB: birthdate
        $birthdate = $record['birthdate'] ?? null;
        if (empty($birthdate)) {
            $errors[] = 'Birthdate is required';
        } else {
            try {
                Carbon::parse($birthdate);
            } catch (\Exception $e) {
                $errors[] = 'Invalid birthdate format (use YYYY-MM-DD or any standard date format)';
            }
        }

        // Required by DB: sex
        $sex = strtolower(trim($record['sex'] ?? ''));
        if (empty($sex)) {
            $errors[] = 'Sex is required (male or female)';
        } elseif (! in_array($sex, ['male', 'female'], true)) {
            $errors[] = "Sex must be 'male' or 'female', got '{$sex}'";
        }

        // Required by DB: course_preference_1
        $pref1Value = $record['course_preference_1'] ?? null;
        if (empty($pref1Value)) {
            $errors[] = 'Course preference 1 is required';
        }

        // Validate course preferences: accept course code or numeric ID
        $resolvedPrefs = [];
        foreach ([1, 2, 3] as $prefNum) {
            $key = "course_preference_{$prefNum}";
            $result = $this->resolveCoursePreference($record[$key] ?? null, $key);
            if ($result['error'] !== null) {
                $errors[] = $result['error'];
            } else {
                $resolvedPrefs[$key] = $result['resolved'];
            }
        }

        // Check mutual exclusion: preferences must differ from each other
        $prefValues = array_filter($resolvedPrefs);
        if (count($prefValues) !== count(array_unique($prefValues))) {
            $errors[] = 'Course preferences must be different from each other';
        }

        // Validate GWA if present
        $gwa = $record['gwa'] ?? null;
        if (! empty($gwa) && ! is_numeric($gwa)) {
            $errors[] = 'GWA must be a number';
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

            // Resolve course preferences (code or ID → ID)
            $coursePref1 = $this->resolveCoursePreference($record['course_preference_1'] ?? null, 'course_preference_1');
            $coursePref2 = $this->resolveCoursePreference($record['course_preference_2'] ?? null, 'course_preference_2');
            $coursePref3 = $this->resolveCoursePreference($record['course_preference_3'] ?? null, 'course_preference_3');

            // Generate reference number
            $referenceNumber = $this->generateReferenceNumber($academicYearId);

            // Parse birthdate and calculate age
            $birthdate = $this->parseDate($record['birthdate'] ?? null);
            $age = $birthdate ? Carbon::parse($birthdate)->age : 0;

            // Normalize sex value
            $sex = strtolower(trim($record['sex'] ?? 'male'));
            if (! in_array($sex, ['male', 'female'], true)) {
                $sex = 'male'; // fallback, should be caught by validation
            }

            Application::create([
                'academic_year_id' => $academicYearId,
                'reference_number' => $referenceNumber,
                'first_name' => $record['first_name'],
                'middle_name' => $record['middle_name'] ?? null,
                'last_name' => $record['last_name'],
                'suffix' => $record['suffix'] ?? null,
                'birthdate' => $birthdate,
                'age' => $age,
                'sex' => $sex,
                'applicant_type' => in_array(strtolower(trim($record['applicant_type'] ?? 'new')), ['new', 'transferee'], true) ? strtolower(trim($record['applicant_type'] ?? 'new')) : 'new',
                'last_school_enrolled' => $record['last_school_enrolled'] ?? null,
                'strand' => $record['strand'] ?? null,
                'email' => $record['email'],
                'phone' => $record['phone'] ?? null,
                'address_line' => $record['address_line'] ?? null,
                'city' => $record['city'] ?? null,
                'province' => $record['province'] ?? null,
                'zip_code' => $record['zip_code'] ?? null,
                'gwa' => isset($record['gwa']) && is_numeric($record['gwa']) ? (float) $record['gwa'] : null,
                'course_preference_1' => $coursePref1['resolved'],
                'course_preference_2' => $coursePref2['resolved'],
                'course_preference_3' => $coursePref3['resolved'],
                'status' => 'pending',
                'pipeline_status' => 'pending',
                'processed_by' => $importerId,
                'processed_at' => now(),
                'submitted_at' => now(),
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
