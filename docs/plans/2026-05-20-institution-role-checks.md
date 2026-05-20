# Institution Setup Role-Based Visibility Control Implementation Plan

> **For Antigravity:** REQUIRED WORKFLOW: Use `.agent/workflows/execute-plan.md` to execute this plan in single-flow mode.

**Goal:** Restrict visibility and editing of institution configuration options based on the user's role: `test_administrator` can only see/edit guidance personnel (Guidance Counselor and Testing Coordinator), while `super_admin` and `registrar_administrator` have access to all profile and personnel fields.

**Architecture:** Check roles in the backend controller (`InstitutionController.php`) to filter what data is returned, and wrap the frontend Svelte cards in conditional blocks. Also, validate and restrict edits strictly on the backend `update` and `resetDefaults` methods.

**Tech Stack:** Laravel 12 (PHP), Svelte 5, Inertia.js v2.

---

### Task 1: Backend Controller Filtering and Access Checks

**Files:**
- Modify: `app/Http/Controllers/Admin/InstitutionController.php`
- Test: `tests/Feature/Admin/InstitutionControllerTest.php`

**Step 1: Write the failing tests**

Add these tests to `tests/Feature/Admin/InstitutionControllerTest.php` to verify backend role filtering:

```php
    public function test_institution_page_loads_for_test_administrator_with_filtered_data(): void
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('name', 'test_administrator')->first());

        $response = $this->actingAs($user)->get(route('admin.setup.institution.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Institution/Index')
            ->where('profile', [])
            ->has('personnel.guidance_counselor')
            ->has('personnel.testing_coordinator')
            ->missing('personnel.registrar')
            ->where('personnelRoles', ['guidance_counselor', 'testing_coordinator'])
        );
    }

    public function test_test_administrator_cannot_update_profile_or_unauthorized_personnel(): void
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('name', 'test_administrator')->first());

        // Setup some overrides first
        SystemSetting::set('institution.name', 'Initial Name');
        SystemSetting::set('institution.personnel.registrar.name', 'Initial Registrar');
        SystemSetting::set('institution.personnel.guidance_counselor.name', 'Initial Guidance');

        $this->actingAs($user)->put(route('admin.setup.institution.update'), [
            'profile' => [
                'name' => 'Attempted Change',
            ],
            'personnel' => [
                'registrar' => [
                    'name' => 'Attempted Registrar Change',
                ],
                'guidance_counselor' => [
                    'name' => 'Changed Guidance Counselor',
                ]
            ]
        ]);

        // Assert guidance counselor was updated
        $this->assertSame('Changed Guidance Counselor', SystemSetting::institution('personnel.guidance_counselor.name'));
        // Assert other values remain unchanged
        $this->assertSame('Initial Name', SystemSetting::institution('name'));
        $this->assertSame('Initial Registrar', SystemSetting::institution('personnel.registrar.name'));
    }

    public function test_test_administrator_reset_only_clears_allowed_personnel(): void
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('name', 'test_administrator')->first());

        SystemSetting::set('institution.name', 'Override Name');
        SystemSetting::set('institution.personnel.registrar.name', 'Override Registrar');
        SystemSetting::set('institution.personnel.guidance_counselor.name', 'Override Guidance');

        $this->actingAs($user)->post(route('admin.setup.institution.reset'));

        // Guidance counselor should be reset
        $this->assertNull(SystemSetting::get('institution.personnel.guidance_counselor.name'));
        // Name and registrar should NOT be reset
        $this->assertSame('Override Name', SystemSetting::get('institution.name'));
        $this->assertSame('Override Registrar', SystemSetting::get('institution.personnel.registrar.name'));
    }
```

**Step 2: Run test to verify it fails**

Run: `php artisan test --filter=InstitutionControllerTest`
Expected: FAIL

**Step 3: Implement minimal controller logic**

Modify `app/Http/Controllers/Admin/InstitutionController.php` to filter fields and restrict updates:

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InstitutionController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $isSuperOrRegistrar = $user->hasAnyRole(['super_admin', 'registrar_administrator']);

        $profileKeys = ['name', 'campus', 'address', 'contact_number', 'email', 'website', 'exam_name', 'exam_acronym'];
        $profile = [];
        if ($isSuperOrRegistrar) {
            foreach ($profileKeys as $key) {
                $profile[$key] = [
                    'value' => SystemSetting::institution($key, ''),
                    'env_default' => config("institution.{$key}", ''),
                    'overridden' => SystemSetting::get("institution.{$key}") !== null,
                ];
            }
        }

        $personnelRoles = array_keys(config('institution.personnel', []));
        if (! $isSuperOrRegistrar) {
            $personnelRoles = array_intersect($personnelRoles, ['guidance_counselor', 'testing_coordinator']);
            // Convert to a zero-indexed array to serialize correctly as list in JSON
            $personnelRoles = array_values($personnelRoles);
        }

        $personnel = [];
        foreach ($personnelRoles as $role) {
            foreach (['name', 'title', 'credentials'] as $field) {
                $dotKey = "personnel.{$role}.{$field}";
                $personnel[$role][$field] = [
                    'value' => SystemSetting::institution($dotKey, ''),
                    'env_default' => config("institution.{$dotKey}", ''),
                    'overridden' => SystemSetting::get("institution.{$dotKey}") !== null,
                ];
            }
        }

        return Inertia::render('Admin/Institution/Index', [
            'profile' => $profile,
            'personnel' => $personnel,
            'personnelRoles' => $personnelRoles,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'profile' => ['nullable', 'array'],
            'profile.*' => ['nullable', 'string', 'max:500'],
            'personnel' => ['nullable', 'array'],
            'personnel.*.*' => ['nullable', 'string', 'max:500'],
        ]);

        $changed = 0;
        $user = $request->user();
        $isSuperOrRegistrar = $user->hasAnyRole(['super_admin', 'registrar_administrator']);

        if ($isSuperOrRegistrar) {
            foreach ($request->input('profile', []) as $key => $value) {
                $settingKey = "institution.{$key}";
                $envDefault = config("institution.{$key}", '');
                if ((string) $value !== (string) $envDefault) {
                    SystemSetting::set($settingKey, $value);
                    $changed++;
                } else {
                    SystemSetting::where('key', $settingKey)->delete();
                }
            }
        }

        $allowedPersonnelRoles = array_keys(config('institution.personnel', []));
        if (! $isSuperOrRegistrar) {
            $allowedPersonnelRoles = array_intersect($allowedPersonnelRoles, ['guidance_counselor', 'testing_coordinator']);
        }

        foreach ($request->input('personnel', []) as $role => $fields) {
            if (! in_array($role, $allowedPersonnelRoles, true)) {
                continue;
            }
            foreach ($fields as $field => $value) {
                $settingKey = "institution.personnel.{$role}.{$field}";
                $envDefault = config("institution.personnel.{$role}.{$field}", '');
                if ((string) $value !== (string) $envDefault) {
                    SystemSetting::set($settingKey, $value);
                    $changed++;
                } else {
                    SystemSetting::where('key', $settingKey)->delete();
                }
            }
        }

        app(AuditService::class)->log('institution.updated', SystemSetting::class, null, [], [
            'fields_changed' => $changed,
        ]);

        return back()->with('success', "Institution settings saved ({$changed} override(s) updated).");
    }

    public function resetDefaults(Request $request): RedirectResponse
    {
        $user = $request->user();
        $isSuperOrRegistrar = $user->hasAnyRole(['super_admin', 'registrar_administrator']);

        if ($isSuperOrRegistrar) {
            $deleted = SystemSetting::where('key', 'like', 'institution.%')->delete();
        } else {
            $keysToDelete = [];
            foreach (['guidance_counselor', 'testing_coordinator'] as $role) {
                foreach (['name', 'title', 'credentials'] as $field) {
                    $keysToDelete[] = "institution.personnel.{$role}.{$field}";
                }
            }
            $deleted = SystemSetting::whereIn('key', $keysToDelete)->delete();
        }

        app(AuditService::class)->log('institution.reset', SystemSetting::class, null, [], [
            'overrides_deleted' => $deleted,
        ]);

        return back()->with('success', "All institution overrides cleared ({$deleted} removed). Using .env defaults.");
    }
}
```

**Step 4: Run test to verify it passes**

Run: `php artisan test --filter=InstitutionControllerTest`
Expected: PASS

**Step 5: Format code and commit**

Run: `vendor/bin/pint --dirty --format agent`
Run git commands:
```bash
git add app/Http/Controllers/Admin/InstitutionController.php tests/Feature/Admin/InstitutionControllerTest.php
git commit -m "feat(institution): implement role-based visibility and access control on backend"
```

---

### Task 2: Frontend Conditional Rendering

**Files:**
- Modify: `resources/js/Pages/Admin/Institution/Index.svelte`

**Step 1: Wrap profile card in checking condition**

In `resources/js/Pages/Admin/Institution/Index.svelte`, wrap the Institution Profile Card:

```svelte
      <!-- Institution Profile Card -->
      {#if Object.keys(profile).length > 0}
      <Card>
        ...
      </Card>
      {/if}
```

**Step 2: Commit**

Run git commands:
```bash
git add resources/js/Pages/Admin/Institution/Index.svelte
git commit -m "fe(institution): conditionally render profile card based on role data"
```
