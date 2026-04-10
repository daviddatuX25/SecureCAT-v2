<?php

namespace App\Policies;

use App\Models\Application;
use App\Models\User;

class ApplicationPolicy
{
    /**
     * Roles that can view applications list and details.
     * Per 05-SECURITY-CONTROLS: registrar_administrator, staff, super_admin.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'registrar_administrator', 'staff']);
    }

    public function view(User $user, Application $application): bool
    {
        return $user->hasAnyRole(['super_admin', 'registrar_administrator', 'staff']);
    }

    /**
     * Per 08-API-SPEC-PHASE1: staff can accept.
     */
    public function accept(User $user, Application $application): bool
    {
        return $user->hasAnyRole(['super_admin', 'staff', 'registrar_administrator']);
    }

    /**
     * Staff can dismiss an application (within application window enforced in controller).
     */
    public function dismiss(User $user, Application $application): bool
    {
        return $user->hasAnyRole(['super_admin', 'staff', 'registrar_administrator']);
    }

    /**
     * Resend portal setup email (same roles as accept).
     */
    public function resendSetupEmail(User $user, Application $application): bool
    {
        return $user->hasAnyRole(['super_admin', 'staff', 'registrar_administrator']);
    }

    /**
     * Per 08-API-SPEC-PHASE1: staff, admin, applicant (own).
     * Applicant (own) is handled in controller since we use staff auth guard.
     */
    public function admissionSlip(User $user, Application $application): bool
    {
        return $user->hasAnyRole(['super_admin', 'registrar_administrator', 'staff']);
    }

    /**
     * Bulk accept/dismiss and reopen — same roles as accept.
     */
    public function update(User $user, Application $application): bool
    {
        return $user->hasAnyRole(['super_admin', 'staff', 'registrar_administrator']);
    }

    /**
     * Only super_admin and registrar_administrator can delete applications.
     */
    public function delete(User $user, Application $application): bool
    {
        return $user->hasAnyRole(['super_admin', 'registrar_administrator']);
    }
}
