<?php

namespace App\Http\Controllers;

use App\Http\Requests\PortalForgotPasswordRequest;
use App\Http\Requests\PortalLoginRequest;
use App\Http\Requests\PortalSetupRequest;
use App\Mail\ApplicantResetPasswordMail;
use App\Models\Applicant;
use App\Models\SystemSetting;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class PortalAuthController extends Controller
{
    /**
     * Show portal login form.
     */
    public function create(): Response|RedirectResponse
    {
        if (Auth::guard('applicant')->check()) {
            return redirect()->route('portal.dashboard');
        }

        return Inertia::render('Portal/Login', [
            'errors' => session()->get('errors')?->getBag('default')?->getMessages() ?? [],
        ]);
    }

    /**
     * Handle portal login. Per 08-API-SPEC: account must have password set.
     */
    public function store(PortalLoginRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $applicant = Applicant::where('email', $validated['email'])->first();

        if (! $applicant || ! $applicant->hasCompletedSetup()) {
            Log::info('Applicant login failed', [
                'email' => $validated['email'],
                'ip' => $request->ip(),
                'reason' => $applicant ? 'setup_not_complete' : 'not_found',
            ]);
            app(AuditService::class)->log('applicant.login_failed', null, null, [], [
                'email' => $validated['email'],
                'ip' => $request->ip(),
                'reason' => $applicant ? 'setup_not_complete' : 'not_found',
            ], 'Applicant login failed');

            return back()->withErrors([
                'email' => 'These credentials do not match our records or your account is not yet set up.',
            ])->onlyInput('email');
        }

        if (Auth::guard('applicant')->attempt(
            ['email' => $validated['email'], 'password' => $validated['password']],
            true
        )) {
            $request->session()->regenerate();
            Log::info('Applicant login', [
                'applicant_id' => Auth::guard('applicant')->id(),
                'ip' => $request->ip(),
            ]);
            app(AuditService::class)->log('applicant.login', Applicant::class, Auth::guard('applicant')->id(), [], [
                'applicant_id' => Auth::guard('applicant')->id(),
                'ip' => $request->ip(),
            ], 'Applicant login');

            return redirect()->intended(route('portal.dashboard'));
        }

        Log::info('Applicant login failed', [
            'email' => $validated['email'],
            'ip' => $request->ip(),
        ]);
        app(AuditService::class)->log('applicant.login_failed', null, null, [], [
            'email' => $validated['email'],
            'ip' => $request->ip(),
        ], 'Applicant login failed');

        return back()->withErrors([
            'email' => 'These credentials do not match our records.',
        ])->onlyInput('email');
    }

    /**
     * Show password setup form (initial setup via token from acceptance email).
     */
    public function setupShow(string $token): Response|RedirectResponse
    {
        $applicant = Applicant::where('setup_token', $token)->first();

        if (! $applicant || ! $applicant->isSetupTokenValid()) {
            return redirect()->route('portal.login')->with('error', 'This setup link is invalid or has expired.');
        }

        return Inertia::render('Portal/Setup', [
            'token' => $token,
            'email' => $applicant->email,
        ]);
    }

    /**
     * Set initial password and invalidate setup token.
     */
    public function setupStore(PortalSetupRequest $request, string $token): RedirectResponse
    {
        $applicant = Applicant::where('setup_token', $token)->first();

        if (! $applicant || ! $applicant->isSetupTokenValid()) {
            return redirect()->route('portal.login')->with('error', 'This setup link is invalid or has expired.');
        }

        $applicant->password = Hash::make($request->validated('password'));
        $applicant->setup_token = null;
        $applicant->setup_token_expires_at = null;
        $applicant->save();

        Log::info('Applicant account activated', ['applicant_id' => $applicant->id]);
        app(AuditService::class)->log('applicant.setup_completed', Applicant::class, $applicant->id, [], [
            'applicant_id' => $applicant->id,
        ], 'Applicant setup completed');

        return redirect()->route('portal.login')->with('success', 'Your password has been set. You can now sign in.');
    }

    /**
     * Show forgot password form.
     */
    public function forgotPasswordCreate(): Response
    {
        return Inertia::render('Portal/ForgotPassword');
    }

    /**
     * Send password reset link. Always return 200/success to prevent email enumeration.
     */
    public function forgotPasswordStore(PortalForgotPasswordRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $applicant = Applicant::where('email', $validated['email'])->first();

        if ($applicant && $applicant->hasCompletedSetup()) {
            $token = Str::random(64);
            $expiresAt = now()->addMinutes(15);

            DB::table('applicant_password_reset_tokens')->upsert(
                [
                    'email' => $applicant->email,
                    'token' => $token,
                    'expires_at' => $expiresAt,
                ],
                ['email'],
                ['token', 'expires_at']
            );

            \Illuminate\Support\Facades\Mail::to($applicant->email)->send(new ApplicantResetPasswordMail($applicant, $token));
        }

        return back()->with('success', 'If an account exists for that email, we have sent password reset instructions.');
    }

    /**
     * Show reset password form (from forgot-password email link).
     */
    public function resetShow(string $token): Response|RedirectResponse
    {
        $record = DB::table('applicant_password_reset_tokens')
            ->where('token', $token)
            ->where('expires_at', '>', now())
            ->first();

        if (! $record) {
            return redirect()->route('portal.forgot-password')->with('error', 'This reset link is invalid or has expired.');
        }

        return Inertia::render('Portal/Reset', [
            'token' => $token,
            'email' => $record->email,
        ]);
    }

    /**
     * Reset password and invalidate token.
     */
    public function resetStore(PortalSetupRequest $request, string $token): RedirectResponse
    {
        $record = DB::table('applicant_password_reset_tokens')
            ->where('token', $token)
            ->where('expires_at', '>', now())
            ->first();

        if (! $record) {
            return redirect()->route('portal.forgot-password')->with('error', 'This reset link is invalid or has expired.');
        }

        $applicant = Applicant::where('email', $record->email)->first();
        if (! $applicant) {
            return redirect()->route('portal.forgot-password')->with('error', 'Account not found.');
        }

        $applicant->password = Hash::make($request->validated('password'));
        $applicant->save();

        DB::table('applicant_password_reset_tokens')->where('email', $applicant->email)->delete();

        Log::info('Applicant password reset', ['applicant_id' => $applicant->id]);
        app(AuditService::class)->log('applicant.password_reset', Applicant::class, $applicant->id, [], [
            'applicant_id' => $applicant->id,
        ], 'Applicant password reset');

        return redirect()->route('portal.login')->with('success', 'Your password has been reset. You can now sign in.');
    }

    /**
     * Portal dashboard (placeholder until BD-2b3 full dashboard).
     */
    public function dashboard(): Response
    {
        $applicant = Auth::guard('applicant')->user();
        $applicant->load(['application', 'consultationSummary']);

        $application = $applicant->application;
        $name = $application
            ? trim(($application->first_name ?? '') . ' ' . ($application->middle_name ?? '') . ' ' . ($application->last_name ?? ''))
            : $applicant->email;
        $referenceNumber = $application?->reference_number ?? '—';

        $notifications = $applicant->notifications()
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get()
            ->map(fn (DatabaseNotification $n) => [
                'id' => $n->id,
                'type' => $n->type,
                'message' => ($n->data['message'] ?? $n->data['title'] ?? class_basename($n->type)),
                'read' => $n->read_at !== null,
                'created_at' => $n->created_at?->toIso8601String(),
            ])
            ->values()
            ->all();

        $summary = $applicant->consultationSummary;
        $consultation = [
            'status' => $summary?->status ?? 'pending',
            'summary' => $summary && $summary->status === 'released' ? [
                'recommended_course_id' => $summary->recommended_course_id,
                'counselor_comments' => $summary->counselor_comments,
            ] : null,
        ];

        return Inertia::render('Portal/Dashboard', [
            'applicant' => [
                'name' => $name,
                'reference_number' => $referenceNumber,
                'email' => $applicant->email,
            ],
            'status_tracker' => [],
            'exam_schedule' => null,
            'score_release' => null,
            'consultation' => $consultation,
            'ai_companion_enabled' => SystemSetting::aiCompanionEnabled(),
            'notifications' => $notifications,
        ]);
    }

    /**
     * Portal logout.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $applicantId = Auth::guard('applicant')->id();
        if ($applicantId) {
            app(AuditService::class)->log('applicant.logout', Applicant::class, $applicantId, [], [
                'applicant_id' => $applicantId,
            ], 'Applicant logout');
        }
        Auth::guard('applicant')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('portal.login');
    }
}
