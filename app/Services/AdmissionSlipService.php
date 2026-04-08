<?php

namespace App\Services;

use App\Models\Application;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\View;

class AdmissionSlipService
{
    /**
     * Generate PDF admission slip.
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

        return Pdf::loadView('pdf.admission-slip', [
            'referenceNumber' => $application->reference_number,
            'fullName'        => $fullName,
            'birthdate'       => $application->birthdate->format('F j, Y'),
            'sex'             => ucfirst($application->sex),
            'courseLabels'    => $courseLabels,
        ]);
    }

    /**
     * Render admission slip as HTML for browser print.
     */
    public function renderHtml(Application $application): string
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

        return View::make('pdf.admission-slip', [
            'referenceNumber' => $application->reference_number,
            'fullName'        => $fullName,
            'birthdate'       => $application->birthdate->format('F j, Y'),
            'sex'             => ucfirst($application->sex),
            'courseLabels'    => $courseLabels,
        ])->render();
    }
}
