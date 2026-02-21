<?php

namespace App\Services;

use App\Models\Application;
use Barryvdh\DomPDF\Facade\Pdf;

class AdmissionSlipService
{
    /**
     * Generate PDF admission slip. Per 08-API-SPEC-PHASE1: ref number, name, photo placeholder, QR placeholder.
     */
    public function generatePdf(Application $application): \Barryvdh\DomPDF\PDF
    {
        $application->load(['coursePreference1', 'coursePreference2', 'coursePreference3']);

        $fullName = implode(' ', array_filter([
            $application->first_name,
            $application->middle_name,
            $application->last_name,
            $application->suffix,
        ]));

        $courseLabels = [
            $application->coursePreference1?->name ?? '—',
            $application->coursePreference2?->name ?? '—',
            $application->coursePreference3?->name ?? '—',
        ];

        $pdf = Pdf::loadView('pdf.admission-slip', [
            'referenceNumber' => $application->reference_number,
            'fullName' => $fullName,
            'birthdate' => $application->birthdate->format('F j, Y'),
            'sex' => ucfirst($application->sex),
            'courseLabels' => $courseLabels,
        ]);

        return $pdf;
    }
}
