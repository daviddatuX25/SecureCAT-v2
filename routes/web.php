<?php

use App\Http\Controllers\Admin\AcademicYearController;
use App\Http\Controllers\Admin\AdmissionSlipTemplateController;
use App\Http\Controllers\Admin\AiCompanionAdminController;
use App\Http\Controllers\Admin\AptitudeAreaController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\CourseController;
use App\Http\Controllers\Admin\ExamSchedulingAssistantController;
use App\Http\Controllers\Admin\ExamSessionController;
use App\Http\Controllers\Admin\KnowledgeDocumentController;
use App\Http\Controllers\Admin\ResultSheetTemplateController;
use App\Http\Controllers\Admin\RoomController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Grading\GradingController;
use App\Http\Controllers\Grading\GradingPrintController;
use App\Http\Controllers\Grading\GradingScoreController;
use App\Http\Controllers\Grading\GradingSessionController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Portal\AiCompanionController;
use App\Http\Controllers\Portal\NotificationController as PortalNotificationController;
use App\Http\Controllers\PortalAuthController;
use App\Http\Controllers\Proctor\SessionRosterController;
use App\Http\Controllers\ReleaseController;
use App\Support\GoogleOAuthConfig;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/about', [HomeController::class, 'about'])->name('about');

if (GoogleOAuthConfig::isConfigured()) {
    Route::middleware('guest')->group(function () {
        Route::get('/auth/google', [GoogleAuthController::class, 'redirect'])
            ->name('auth.google.redirect');
    });
    Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])
        ->name('auth.google.callback');
}

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store'])->middleware('throttle:login');
});

Route::get('/apply', [ApplicationController::class, 'create'])->name('applications.create');
Route::post('/applications', [ApplicationController::class, 'store'])->name('applications.store');
Route::get('/apply/success', [ApplicationController::class, 'success'])->name('applications.success');

// Applicant portal (guest: login, setup, forgot/reset password)
Route::middleware(['web', 'portal.guest'])->prefix('portal')->name('portal.')->group(function () {
    Route::get('login', [PortalAuthController::class, 'create'])->name('login');
    Route::post('login', [PortalAuthController::class, 'store'])->middleware('throttle:5,15');
    Route::get('setup/{token}', [PortalAuthController::class, 'setupShow'])->name('setup.show');
    Route::post('setup/{token}', [PortalAuthController::class, 'setupStore'])->name('setup.store');
    Route::get('forgot-password', [PortalAuthController::class, 'forgotPasswordCreate'])->name('forgot-password');
    Route::post('forgot-password', [PortalAuthController::class, 'forgotPasswordStore'])->middleware('throttle:3,15');
    Route::get('reset/{token}', [PortalAuthController::class, 'resetShow'])->name('reset.show');
    Route::post('reset/{token}', [PortalAuthController::class, 'resetStore'])->name('reset.store');
});

// Applicant portal (authenticated)
Route::middleware(['web', 'auth:applicant'])->prefix('portal')->name('portal.')->group(function () {
    Route::get('/', [PortalAuthController::class, 'dashboard'])->name('dashboard');
    Route::post('logout', [PortalAuthController::class, 'destroy'])->name('logout');
    Route::get('notifications', [PortalNotificationController::class, 'index'])->name('notifications.index');
    Route::post('notifications/{id}/read', [PortalNotificationController::class, 'markRead'])->name('notifications.read');
    Route::get('ai-companion', [AiCompanionController::class, 'index'])->name('ai-companion.index');
    Route::post('ai-companion/chat', [AiCompanionController::class, 'chat'])->name('ai-companion.chat');
    Route::post('ai-companion/clear-history', [AiCompanionController::class, 'clearHistory'])->name('ai-companion.clear-history');
});

Route::middleware(['auth'])->group(function () {
    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::middleware('role:super_admin')->prefix('admin')->name('admin.')->group(function () {
        Route::resource('users', UserController::class)->except('show')->parameters(['users' => 'user']);
        Route::get('settings', [SettingsController::class, 'index'])->name('settings.index');
        Route::put('settings', [SettingsController::class, 'update'])->name('settings.update');
        Route::get('logs', [AuditLogController::class, 'index'])->name('logs.index');
        Route::get('logs/export', [AuditLogController::class, 'export'])->name('logs.export');
        Route::resource('admission-slip-templates', AdmissionSlipTemplateController::class)->except('show')->parameters(['admission_slip_templates' => 'admission_slip_template']);
        // AI Companion hub (replaces knowledge-documents index)
        Route::get('ai-companion', [AiCompanionAdminController::class, 'index'])->name('ai-companion.index');
        Route::put('ai-companion/persona', [AiCompanionAdminController::class, 'updatePersona'])->name('ai-companion.persona.update');
        // Redirect old knowledge-documents index → new hub
        Route::get('knowledge-documents', fn () => redirect()->route('admin.ai-companion.index'))->name('knowledge-documents.index');
        Route::get('knowledge-documents/create', [KnowledgeDocumentController::class, 'create'])->name('knowledge-documents.create');
        Route::post('knowledge-documents', [KnowledgeDocumentController::class, 'store'])->name('knowledge-documents.store');
        Route::get('knowledge-documents/import', [KnowledgeDocumentController::class, 'importForm'])->name('knowledge-documents.import');
        Route::post('knowledge-documents/import', [KnowledgeDocumentController::class, 'import'])->name('knowledge-documents.import.store');
        Route::get('knowledge-documents/{knowledge_document}/edit', [KnowledgeDocumentController::class, 'edit'])->name('knowledge-documents.edit');
        Route::put('knowledge-documents/{knowledge_document}', [KnowledgeDocumentController::class, 'update'])->name('knowledge-documents.update');
        Route::delete('knowledge-documents/{knowledge_document}', [KnowledgeDocumentController::class, 'destroy'])->name('knowledge-documents.destroy');
    });

    // Test scheduling: index & show for proctors too (proctor view = assigned only)
    // Create must be registered before {exam_session} so /create is not matched as an id
    Route::middleware('role:super_admin,admin,proctor')->prefix('admin')->name('admin.')->group(function () {
        Route::get('test-scheduling', [ExamSessionController::class, 'index'])->name('test-scheduling.index');
        Route::get('test-scheduling/create', [ExamSessionController::class, 'create'])->name('test-scheduling.create');
        Route::get('test-scheduling/schedule-assistant', fn () => redirect()->route('admin.test-scheduling.index'))->name('test-scheduling.schedule-assistant.index');
        Route::get('test-scheduling/{exam_session}', [ExamSessionController::class, 'show'])->name('test-scheduling.show');
    });

    // Monitoring — standalone route so registrar_administrator can access it
    Route::get('admin/test-scheduling/monitoring', [ExamSessionController::class, 'monitoring'])
        ->name('admin.test-scheduling.monitoring')
        ->middleware(['web', 'auth', 'role:super_admin,admin,proctor,registrar_administrator']);

    Route::middleware('role:super_admin,admin,registrar_administrator')->prefix('admin')->name('admin.')->group(function () {
        Route::post('test-scheduling/schedule-assistant/chat', [ExamSchedulingAssistantController::class, 'chat'])->name('test-scheduling.schedule-assistant.chat');
        Route::post('test-scheduling/schedule-assistant/apply-schedule', [ExamSchedulingAssistantController::class, 'applySchedule'])->name('test-scheduling.schedule-assistant.apply');
        Route::post('test-scheduling/{exam_session}/assign-applicants', [ExamSessionController::class, 'assignApplicants'])->name('test-scheduling.assign-applicants');
        Route::post('test-scheduling/{exam_session}/remove-applicant', [ExamSessionController::class, 'removeApplicant'])->name('test-scheduling.remove-applicant');
        Route::post('test-scheduling/{exam_session}/publish', [ExamSessionController::class, 'publish'])->name('test-scheduling.publish');
        Route::post('test-scheduling/{exam_session}/unpublish', [ExamSessionController::class, 'unpublish'])->name('test-scheduling.unpublish');
        Route::post('test-scheduling/{exam_session}/reopen', [ExamSessionController::class, 'reopen'])->name('test-scheduling.reopen');
        Route::post('test-scheduling', [ExamSessionController::class, 'store'])->name('test-scheduling.store');
        Route::get('test-scheduling/{exam_session}/edit', [ExamSessionController::class, 'edit'])->name('test-scheduling.edit');
        Route::put('test-scheduling/{exam_session}', [ExamSessionController::class, 'update'])->name('test-scheduling.update');
        Route::resource('academic-years', AcademicYearController::class)->except('show', 'destroy')->parameters(['academic_years' => 'academic_year']);
        Route::post('academic-years/{academic_year}/activate', [AcademicYearController::class, 'activate'])->name('academic-years.activate');
        Route::resource('aptitude-areas', AptitudeAreaController::class)->except('show', 'destroy')->parameters(['aptitude_areas' => 'aptitude_area']);
        Route::resource('courses', CourseController::class)->except('show')->parameters(['courses' => 'course']);
        Route::post('courses/{course}/activate', [CourseController::class, 'activate'])->name('courses.activate');
        Route::post('courses/{course}/deactivate', [CourseController::class, 'deactivate'])->name('courses.deactivate');
        Route::post('courses/{course}/restore', [CourseController::class, 'restore'])->name('courses.restore');
        Route::resource('rooms', RoomController::class)->except('show')->parameters(['rooms' => 'room']);
        Route::post('rooms/{room}/activate', [RoomController::class, 'activate'])->name('rooms.activate');
        Route::post('rooms/{room}/deactivate', [RoomController::class, 'deactivate'])->name('rooms.deactivate');
        Route::post('rooms/{room}/restore', [RoomController::class, 'restore'])->name('rooms.restore');
        Route::post('result-sheet-templates/preview', [ResultSheetTemplateController::class, 'preview'])->name('result-sheet-templates.preview');
        Route::resource('result-sheet-templates', ResultSheetTemplateController::class)->except('show')->parameters(['result_sheet_templates' => 'result_sheet_template']);
    });

    Route::get('/applications', [ApplicationController::class, 'index'])->name('applications.index')->middleware('role:super_admin,staff,admin,registrar_administrator');
    Route::get('/applications/{application}', [ApplicationController::class, 'show'])->name('applications.show')->middleware('role:super_admin,staff,admin,registrar_administrator');
    Route::put('/applications/{application}/accept', [ApplicationController::class, 'accept'])->name('applications.accept')->middleware('role:super_admin,staff,admin');
    Route::post('/applications/{application}/resend-setup-email', [ApplicationController::class, 'resendSetupEmail'])->name('applications.resend-setup-email')->middleware('role:super_admin,staff,admin');
    Route::put('/applications/{application}/dismiss', [ApplicationController::class, 'dismiss'])->name('applications.dismiss')->middleware('role:super_admin,staff,admin');
    Route::post('/applications/bulk-accept', [ApplicationController::class, 'bulkAccept'])->name('applications.bulk-accept')->middleware('role:super_admin,staff,admin');
    Route::post('/applications/bulk-dismiss', [ApplicationController::class, 'bulkDismiss'])->name('applications.bulk-dismiss')->middleware('role:super_admin,staff,admin');
    Route::put('/applications/{application}/reopen', [ApplicationController::class, 'reopen'])->name('applications.reopen')->middleware('role:super_admin,staff,admin');
    Route::delete('/applications/{application}', [ApplicationController::class, 'destroy'])->name('applications.destroy')->middleware('role:super_admin,admin');
    Route::get('/applications/{application}/admission-slip', [ApplicationController::class, 'admissionSlip'])->name('applications.admission-slip')->middleware('role:super_admin,staff,admin,registrar_administrator');
    Route::get('/proctor', fn () => redirect()->route('admin.test-scheduling.index'))->middleware('role:super_admin,proctor');
    Route::middleware('role:super_admin,admin,proctor,registrar_administrator')->prefix('proctor')->name('proctor.')->group(function () {
        Route::get('sessions/{exam_session}', [SessionRosterController::class, 'show'])->name('sessions.show');
        Route::post('sessions/{exam_session}/attendance', [SessionRosterController::class, 'storeAttendance'])->name('sessions.attendance');
        Route::post('sessions/{exam_session}/submission', [SessionRosterController::class, 'storeSubmission'])->name('sessions.submission');
        Route::post('sessions/{exam_session}/submission-bulk', [SessionRosterController::class, 'storeSubmissionBulk'])->name('sessions.submission-bulk');
        Route::post('sessions/{exam_session}/start', [SessionRosterController::class, 'start'])->name('sessions.start');
        Route::post('sessions/{exam_session}/close', [SessionRosterController::class, 'close'])->name('sessions.close');
        Route::post('sessions/{exam_session}/bulk-attendance', [SessionRosterController::class, 'bulkAttendance'])->name('sessions.bulk-attendance');
    });

    // Test Admin session management — dedicated index + roster with full permissions
    Route::middleware('role:super_admin,admin,registrar_administrator')->prefix('admin/test-admin')->name('admin.test-admin.')->group(function () {
        Route::get('sessions', [ExamSessionController::class, 'testAdminIndex'])->name('sessions.index');
        Route::get('sessions/{exam_session}/roster', [ExamSessionController::class, 'testAdminRoster'])->name('sessions.roster');
    });
    // Grading
    Route::middleware('role:super_admin,registrar_administrator')->prefix('grading')->name('grading.')->group(function () {
        Route::get('/', [GradingController::class, 'index']);
        Route::post('/', [GradingController::class, 'store']);
        Route::get('/sessions/{grading_session}', [GradingSessionController::class, 'show'])->name('sessions.show');
        Route::put('/sessions/{grading_session}/workflow', [GradingSessionController::class, 'updateWorkflowStatus'])->name('sessions.workflow');
        Route::get('/sessions/{grading_session}/applicants/{applicant}', [GradingScoreController::class, 'show'])->name('sessions.applicants.scores');
        Route::put('/sessions/{grading_session}/applicants/{applicant}/scores', [GradingScoreController::class, 'update'])->name('sessions.applicants.scores.update');
        Route::get('/sessions/{grading_session}/print', [GradingPrintController::class, 'index'])->name('sessions.print');
        Route::post('/sessions/{grading_session}/mark-printed', [GradingPrintController::class, 'markPrinted'])->name('sessions.mark-printed');
        Route::get('/sessions/{grading_session}/print-bulk', [GradingPrintController::class, 'printBulk'])->name('sessions.print-bulk');
        Route::get('/sessions/{grading_session}/applicants/{applicant}/result-sheet', [GradingPrintController::class, 'resultSheet'])->name('sessions.result-sheet');
    });

    // Release Management
    Route::middleware('role:super_admin,registrar_administrator')
        ->prefix('release')
        ->name('release.')
        ->group(function () {
            Route::get('/', [ReleaseController::class, 'index'])->name('index');
            Route::post('/summaries/{summary}/release', [ReleaseController::class, 'release'])->name('summaries.release');
            Route::post('/summaries/bulk-release', [ReleaseController::class, 'releaseBulk'])->name('summaries.bulk-release');
            Route::put('/summaries/by-applicant/{applicantId}', [ReleaseController::class, 'storeOrUpdateByApplicant'])->name('summaries.storeOrUpdate');
        });
});
