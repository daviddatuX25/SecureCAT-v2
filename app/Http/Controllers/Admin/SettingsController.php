<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateSystemSettingsRequest;
use App\Models\SystemSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    /**
     * Show system settings (super_admin only). AI companion toggle and future persona/config.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', SystemSetting::class);

        return Inertia::render('Admin/Settings/Index', [
            'ai_exam_companion_enabled' => SystemSetting::aiCompanionEnabled(),
        ]);
    }

    /**
     * Update system settings (e.g. AI exam companion enabled).
     */
    public function update(UpdateSystemSettingsRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        if (array_key_exists('ai_exam_companion_enabled', $validated)) {
            SystemSetting::set('ai_exam_companion_enabled', (bool) $validated['ai_exam_companion_enabled']);
        }

        if (array_key_exists('ai_companion_persona', $validated)) {
            SystemSetting::set('ai_companion_persona', $validated['ai_companion_persona'] ?? '');
        }

        if (array_key_exists('consultation_enabled', $validated)) {
            SystemSetting::set('consultation_enabled', (bool) $validated['consultation_enabled']);
        }

        return redirect()->route('admin.settings.index')->with('success', 'Settings saved.');
    }
}
