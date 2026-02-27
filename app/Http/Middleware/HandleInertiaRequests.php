<?php

namespace App\Http\Middleware;

use App\Models\SystemSetting;
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
        if ($user) {
            $user->load('roles');
            $authUser = [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->roles->map(fn ($r) => ['name' => $r->name])->values()->toArray(),
            ];
        }

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $authUser,
            ],
            'consultation_enabled' => SystemSetting::consultationEnabled(),
        ];
    }
}
