<?php

use App\Http\Controllers\Admin\AdmissionSlipTemplateController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\CourseController;
use App\Http\Controllers\Admin\ExamSessionController;
use App\Http\Controllers\Admin\KnowledgeDocumentController;
use App\Http\Controllers\Admin\ResultSheetTemplateController;
use App\Http\Controllers\Admin\RoomController;
use App\Http\Controllers\Admin\SeasonController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Consultation\ConsultationApplicantController;
use App\Http\Controllers\Consultation\ConsultationController;
use App\Http\Controllers\Consultation\ConsultationDayController;
use App\Http\Controllers\Consultation\ConsultationRulesController;
use App\Http\Controllers\Consultation\ConsultationScheduleController;
use App\Http\Controllers\Grading\GradingController;
use App\Http\Controllers\Grading\GradingPrintController;
use App\Http\Controllers\Grading\GradingScoreController;
use App\Http\Controllers\Grading\GradingSessionController;
use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Portal\AiCompanionController;
use App\Http\Controllers\Portal\NotificationController as PortalNotificationController;
use App\Http\Controllers\PortalAuthController;
use App\Http\Controllers\Proctor\SessionRosterController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Home', [
        'systemName' => 'SecureCAT',
    ]);
});

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
        Route::get('knowledge-documents', [KnowledgeDocumentController::class, 'index'])->name('knowledge-documents.index');
        Route::get('knowledge-documents/create', [KnowledgeDocumentController::class, 'create'])->name('knowledge-documents.create');
        Route::post('knowledge-documents', [KnowledgeDocumentController::class, 'store'])->name('knowledge-documents.store');
        Route::get('knowledge-documents/import', [KnowledgeDocumentController::class, 'importForm'])->name('knowledge-documents.import');
        Route::post('knowledge-documents/import', [KnowledgeDocumentController::class, 'import'])->name('knowledge-documents.import.store');
        Route::get('knowledge-documents/{knowledge_document}/edit', [KnowledgeDocumentController::class, 'edit'])->name('knowledge-documents.edit');
        Route::put('knowledge-documents/{knowledge_document}', [KnowledgeDocumentController::class, 'update'])->name('knowledge-documents.update');
        Route::delete('knowledge-documents/{knowledge_document}', [KnowledgeDocumentController::class, 'destroy'])->name('knowledge-documents.destroy');
    });

    // Exam sessions: index & show for proctors too (proctor view = assigned only)
    // Create must be registered before {exam_session} so /create is not matched as an id
    Route::middleware('role:super_admin,admin,proctor')->prefix('admin')->name('admin.')->group(function () {
        Route::get('exam-sessions', [ExamSessionController::class, 'index'])->name('exam-sessions.index');
        Route::get('exam-sessions/create', [ExamSessionController::class, 'create'])->name('exam-sessions.create');
        Route::get('exam-sessions/monitoring', [ExamSessionController::class, 'monitoring'])->name('exam-sessions.monitoring');
        Route::get('exam-sessions/schedule-assistant', fn () => redirect()->route('admin.exam-sessions.index'))->name('exam-sessions.schedule-assistant');
        Route::get('exam-sessions/{exam_session}', [ExamSessionController::class, 'show'])->name('exam-sessions.show');
    });

    Route::middleware('role:super_admin,admin,counselor')->prefix('admin')->name('admin.')->group(function () {
        Route::post('exam-sessions/{exam_session}/assign-applicants', [ExamSessionController::class, 'assignApplicants'])->name('exam-sessions.assign-applicants');
        Route::post('exam-sessions/{exam_session}/remove-applicant', [ExamSessionController::class, 'removeApplicant'])->name('exam-sessions.remove-applicant');
        Route::post('exam-sessions/{exam_session}/publish', [ExamSessionController::class, 'publish'])->name('exam-sessions.publish');
        Route::put('exam-sessions/{exam_session}/release-date', [ExamSessionController::class, 'releaseDate'])->name('exam-sessions.release-date');
        Route::post('exam-sessions/{exam_session}/reopen', [ExamSessionController::class, 'reopen'])->name('exam-sessions.reopen');
        Route::post('exam-sessions', [ExamSessionController::class, 'store'])->name('exam-sessions.store');
        Route::get('exam-sessions/{exam_session}/edit', [ExamSessionController::class, 'edit'])->name('exam-sessions.edit');
        Route::put('exam-sessions/{exam_session}', [ExamSessionController::class, 'update'])->name('exam-sessions.update');
        Route::resource('seasons', SeasonController::class)->except('show', 'destroy')->parameters(['seasons' => 'season']);
        Route::post('seasons/{season}/activate', [SeasonController::class, 'activate'])->name('seasons.activate');
        Route::resource('courses', CourseController::class)->except('show')->parameters(['courses' => 'course']);
        Route::resource('rooms', RoomController::class)->except('show')->parameters(['rooms' => 'room']);
        Route::post('result-sheet-templates/preview', [ResultSheetTemplateController::class, 'preview'])->name('result-sheet-templates.preview');
        Route::resource('result-sheet-templates', ResultSheetTemplateController::class)->except('show')->parameters(['result_sheet_templates' => 'result_sheet_template']);
    });

    Route::get('/applications', [ApplicationController::class, 'index'])->name('applications.index')->middleware('role:super_admin,staff,admin,counselor');
    Route::get('/applications/{application}', [ApplicationController::class, 'show'])->name('applications.show')->middleware('role:super_admin,staff,admin,counselor');
    Route::put('/applications/{application}/accept', [ApplicationController::class, 'accept'])->name('applications.accept')->middleware('role:super_admin,staff,admin');
    Route::post('/applications/{application}/resend-setup-email', [ApplicationController::class, 'resendSetupEmail'])->name('applications.resend-setup-email')->middleware('role:super_admin,staff,admin');
    Route::put('/applications/{application}/reject', [ApplicationController::class, 'reject'])->name('applications.reject')->middleware('role:super_admin,staff,admin');
    Route::get('/applications/{application}/admission-slip', [ApplicationController::class, 'admissionSlip'])->name('applications.admission-slip')->middleware('role:super_admin,staff,admin,counselor');
    Route::get('/proctor', fn () => redirect()->route('admin.exam-sessions.index'))->middleware('role:super_admin,proctor');
    Route::middleware('role:super_admin,admin,proctor')->prefix('proctor')->name('proctor.')->group(function () {
        Route::get('sessions/{exam_session}', [SessionRosterController::class, 'show'])->name('sessions.show');
        Route::post('sessions/{exam_session}/attendance', [SessionRosterController::class, 'storeAttendance'])->name('sessions.attendance');
        Route::post('sessions/{exam_session}/submission', [SessionRosterController::class, 'storeSubmission'])->name('sessions.submission');
        Route::post('sessions/{exam_session}/submission-bulk', [SessionRosterController::class, 'storeSubmissionBulk'])->name('sessions.submission-bulk');
        Route::post('sessions/{exam_session}/start', [SessionRosterController::class, 'start'])->name('sessions.start');
        Route::post('sessions/{exam_session}/close', [SessionRosterController::class, 'close'])->name('sessions.close');
    });
    // Grading
    Route::middleware('role:super_admin,grader')->prefix('grading')->name('grading.')->group(function () {
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
    // Consultation
    Route::middleware('role:super_admin,counselor')->prefix('consultation')->name('consultation.')->group(function () {
        Route::get('/', [ConsultationController::class, 'index'])->name('index');
        Route::get('/schedule', [ConsultationScheduleController::class, 'index'])->name('schedule.index');
        Route::post('/schedule', [ConsultationScheduleController::class, 'store'])->name('schedule.store');
        Route::get('/day', [ConsultationDayController::class, 'index'])->name('day.index');
        Route::get('/rules', [ConsultationRulesController::class, 'index'])->name('rules.index');
        Route::post('/rules', [ConsultationRulesController::class, 'store'])->name('rules.store');
        Route::put('/rules/{decision_rule}', [ConsultationRulesController::class, 'update'])->name('rules.update');
        Route::delete('/rules/{decision_rule}', [ConsultationRulesController::class, 'destroy'])->name('rules.destroy');
        Route::get('/applicants/{applicant}', [ConsultationApplicantController::class, 'show'])->name('applicants.show');
        Route::put('/applicants/{applicant}/summary', [ConsultationApplicantController::class, 'update'])->name('applicants.summary');
        Route::post('/applicants/{applicant}/release', [ConsultationApplicantController::class, 'release'])->name('applicants.release');
    });
});
