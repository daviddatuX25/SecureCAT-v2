<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Auth;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $applicant = Auth::guard('applicant')->user();
        $applicantPayload = null;
        $notificationsUnreadCount = 0;
        $notificationsRecent = [];

        if ($applicant) {
            $applicant->load('application');
            $app = $applicant->application;
            $name = $app
                ? trim(($app->first_name ?? '') . ' ' . ($app->middle_name ?? '') . ' ' . ($app->last_name ?? ''))
                : $applicant->email;

            $notificationsUnreadCount = $applicant->unreadNotifications()->count();
            $notificationsRecent = $applicant->notifications()
                ->orderBy('created_at', 'desc')
                ->limit(10)
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

            $applicantPayload = [
                'name' => $name,
                'email' => $applicant->email,
                'reference_number' => $app?->reference_number ?? '—',
            ];
        }

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user() && $request->user() instanceof \App\Models\User
                    ? $request->user()->load('roles:id,name,display_name')
                    : null,
                'applicant' => $applicantPayload,
                'notifications_unread_count' => $notificationsUnreadCount,
                'notifications_recent' => $notificationsRecent,
            ],
            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
            ],
            'csrf_token' => $request->session()->token(),
        ];
    }
}
