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
            'notify_on_publish' => SystemSetting::notifyOnPublish(),
            'release_mode' => SystemSetting::releaseMode(),
            'allow_direct_assessment' => SystemSetting::allowDirectAssessment(),
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

        if (array_key_exists('notify_on_publish', $validated)) {
            SystemSetting::set('notify_on_publish', (bool) $validated['notify_on_publish']);
        }

        if (array_key_exists('ai_companion_persona', $validated)) {
            SystemSetting::set('ai_companion_persona', preg_replace('/<script[^>]*>.*?<\/script>/is', '', $validated['ai_companion_persona'] ?? ''));
        }

        if (array_key_exists('consultation_enabled', $validated)) {
            SystemSetting::set('consultation_enabled', (bool) $validated['consultation_enabled']);
        }

        if (array_key_exists('release_mode', $validated)) {
            SystemSetting::set('release_mode', $validated['release_mode']);
        }

        if (array_key_exists('allow_direct_assessment', $validated)) {
            SystemSetting::set('allow_direct_assessment', (bool) $validated['allow_direct_assessment']);
        }

        return redirect()->route('admin.settings.index')->with('success', 'Settings saved.');
    }
}
