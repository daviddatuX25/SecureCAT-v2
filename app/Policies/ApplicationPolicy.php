<?php

namespace App\Policies;

use App\Models\Application;
use App\Models\User;

class ApplicationPolicy
{
    /**
     * Roles that can view applications list and details.
     * Per 05-SECURITY-CONTROLS: staff, admin, test_administrator, super_admin.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'staff', 'admin', 'test_administrator']);
    }

    public function view(User $user, Application $application): bool
    {
        return $user->hasAnyRole(['super_admin', 'staff', 'admin', 'test_administrator']);
    }

    /**
     * Per 08-API-SPEC-PHASE1: staff can accept.
     */
    public function accept(User $user, Application $application): bool
    {
        return $user->hasAnyRole(['super_admin', 'staff', 'admin']);
    }

    /**
     * Staff can dismiss an application (within application window enforced in controller).
     */
    public function dismiss(User $user, Application $application): bool
    {
        return $user->hasAnyRole(['super_admin', 'staff', 'admin']);
    }

    /**
     * Staff can set application to incomplete documents (within window enforced in controller).
     */
    public function setIncompleteDocuments(User $user, Application $application): bool
    {
        return $user->hasAnyRole(['super_admin', 'staff', 'admin']);
    }

    /**
     * Resend portal setup email (same roles as accept).
     */
    public function resendSetupEmail(User $user, Application $application): bool
    {
        return $user->hasAnyRole(['super_admin', 'staff', 'admin']);
    }

    /**
     * Per 08-API-SPEC-PHASE1: staff, admin, applicant (own).
     * Applicant (own) is handled in controller since we use staff auth guard.
     */
    public function admissionSlip(User $user, Application $application): bool
    {
        return $user->hasAnyRole(['super_admin', 'staff', 'admin', 'test_administrator']);
    }
}
