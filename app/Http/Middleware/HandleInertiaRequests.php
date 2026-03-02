<?php

namespace App\Http\Middleware;

use App\Models\Applicant;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        $authUser = null;
        if ($user instanceof User) {
            $user->load('roles');
            $authUser = [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->roles->map(fn ($r) => ['name' => $r->name])->values()->toArray(),
            ];
        } elseif ($user instanceof Applicant) {
            $authUser = [
                'id' => $user->id,
                'name' => $user->email,
                'email' => $user->email,
                'roles' => [],
            ];
        }

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $authUser,
            ],
            'consultation_enabled' => SystemSetting::consultationEnabled(),
            'pageTitle' => $this->defaultPageTitle($request),
        ];
    }

    /**
     * Default page title from route name (controllers can override by passing pageTitle).
     */
    private function defaultPageTitle(Request $request): string
    {
        $name = $request->route()?->getName();
        if (! $name) {
            return 'Dashboard';
        }
        $titles = [
            'portal.dashboard' => 'Portal',
            'dashboard' => 'Overview',
            'grading.index' => 'Grading',
            'admin.exam-sessions.index' => 'Exam Sessions',
            'admin.courses.index' => 'Courses',
            'admin.rooms.index' => 'Rooms',
            'admin.users.index' => 'Users',
            'admin.settings.index' => 'Settings',
            'admin.logs.index' => 'Audit log',
            'admin.seasons.index' => 'Seasons',
            'admin.exam-domains.index' => 'Exam pillars',
            'admin.knowledge-documents.index' => 'Knowledge docs',
            'admin.admission-slip-templates.index' => 'Admission slip templates',
            'admin.result-sheet-templates.index' => 'Result templates',
            'applications.index' => 'Applications',
        ];
        return $titles[$name] ?? str_replace(['-', '.'], ' ', ucwords(implode(' ', preg_split('/[.-]/', $name))));
    }
}
