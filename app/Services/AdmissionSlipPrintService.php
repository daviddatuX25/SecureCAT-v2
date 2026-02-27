<?php

namespace App\Services;

use App\Models\Application;

class AdmissionSlipPrintService
{
    public function markSlipPrinted(array $applicationIds, bool $printed): void
    {
        $ts = $printed ? now() : null;
        Application::whereIn('id', $applicationIds)->update(['admission_slip_printed_at' => $ts]);
    }
}
