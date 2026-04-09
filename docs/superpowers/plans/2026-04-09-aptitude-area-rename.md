# Aptitude Area Rename Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Rename every `ExamDomain` / `exam_domain` / "Exam pillar" reference to `AptitudeArea` / `aptitude_area` / "Aptitude Area" across the full stack — DB, models, controllers, requests, policies, seeders, services, Svelte pages, routes, and nav.

**Architecture:** Fresh-start strategy — new migrations create `aptitude_areas` and rename FK columns; old `exam_domains` is dropped at the end. New backend files are created alongside the old ones and routes are switched atomically. Old files are deleted only after full verification.

**Tech Stack:** Laravel 12, Eloquent, Inertia.js, Svelte 5, PHPUnit

---

## File Map

### Create
| Path | Purpose |
|------|---------|
| `database/migrations/2026_04_09_000001_create_aptitude_areas_table.php` | New table (same schema as `exam_domains`) |
| `database/migrations/2026_04_09_000002_rename_domain_id_in_applicant_scores.php` | FK column rename |
| `database/migrations/2026_04_09_000003_rename_domain_id_in_decision_rules.php` | FK column rename |
| `database/migrations/2026_04_09_000004_drop_exam_domains_table.php` | Drop legacy table |
| `app/Models/AptitudeArea.php` | Eloquent model |
| `app/Http/Controllers/Admin/AptitudeAreaController.php` | CRUD controller |
| `app/Http/Requests/StoreAptitudeAreaRequest.php` | Create form request |
| `app/Http/Requests/UpdateAptitudeAreaRequest.php` | Update form request |
| `app/Policies/AptitudeAreaPolicy.php` | Authorization policy |
| `database/seeders/AptitudeAreaSeeder.php` | Seed 6 default areas |
| `resources/js/Pages/Admin/AptitudeAreas/Index.svelte` | List page |
| `resources/js/Pages/Admin/AptitudeAreas/Create.svelte` | Create form page |
| `resources/js/Pages/Admin/AptitudeAreas/Edit.svelte` | Edit form page |
| `tests/Feature/Admin/AptitudeAreaControllerTest.php` | Feature tests |

### Modify
| Path | What Changes |
|------|-------------|
| `routes/web.php` | Add new `aptitude-areas` resource route; keep old as rollback |
| `app/Http/Middleware/HandleInertiaRequests.php` | Nav label: `'Exam pillars'` → `'Aptitude Areas'`, key → `admin.aptitude-areas.index` |
| `app/Services/GradingSessionService.php` | `ExamDomain` → `AptitudeArea`, `domain_id` → `aptitude_area_id` |
| `app/Services/ResultSheetTemplateService.php` | `ExamDomain` → `AptitudeArea`, `domainSlug()` → `aptitudeAreaSlug()` |
| `app/Services/ScoreInputService.php` | `domain_id` → `aptitude_area_id` |
| `database/seeders/DatabaseSeeder.php` | `ExamDomainSeeder` → `AptitudeAreaSeeder` |
| `database/seeders/DefenseDemoSeeder.php` | `ExamDomain` → `AptitudeArea` |
| `database/seeders/DemoDashboardSeeder.php` | `ExamDomain` → `AptitudeArea` |
| `resources/js/lib/domains.js` | Rename export `EXAM_PILLARS` → `APTITUDE_AREAS` |

### Delete (Task 13 — after verification)
- `app/Models/ExamDomain.php`
- `app/Http/Controllers/Admin/ExamDomainController.php`
- `app/Http/Requests/StoreExamDomainRequest.php`
- `app/Http/Requests/UpdateExamDomainRequest.php`
- `app/Policies/ExamDomainPolicy.php`
- `database/seeders/ExamDomainSeeder.php`
- `resources/js/Pages/Admin/ExamDomains/` (directory)
- Old `exam-domains` route and `use ExamDomainController` in `routes/web.php`
- `tests/Feature/Admin/ExamDomainControllerTest.php`

---

## Task 1: Write the failing tests

**Files:**
- Create: `tests/Feature/Admin/AptitudeAreaControllerTest.php`

- [ ] **Step 1: Create the test file**

```php
<?php

namespace Tests\Feature\Admin;

use App\Models\AptitudeArea;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AptitudeAreaControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RoleSeeder::class);
        $this->seed(\Database\Seeders\AptitudeAreaSeeder::class);
    }

    private function testAdministrator(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('name', 'test_administrator')->first());
        return $user;
    }

    private function registrarAdmin(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('name', 'admin')->first());
        return $user;
    }

    public function test_registrar_admin_cannot_view_aptitude_areas_index(): void
    {
        $response = $this->actingAs($this->registrarAdmin())
            ->get(route('admin.aptitude-areas.index'));

        $response->assertForbidden();
    }

    public function test_registrar_admin_cannot_create_aptitude_area(): void
    {
        $response = $this->actingAs($this->registrarAdmin())
            ->post(route('admin.aptitude-areas.store'), [
                'name' => 'New Area',
                'code' => 'NA2',
                'max_items' => 30,
                'display_order' => 10,
                'is_active' => true,
            ]);

        $response->assertForbidden();
    }

    public function test_test_administrator_can_view_aptitude_areas_index(): void
    {
        $response = $this->actingAs($this->testAdministrator())
            ->get(route('admin.aptitude-areas.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/AptitudeAreas/Index')
            ->has('aptitude_areas')
        );
    }

    public function test_test_administrator_can_create_aptitude_area(): void
    {
        $response = $this->actingAs($this->testAdministrator())
            ->post(route('admin.aptitude-areas.store'), [
                'name' => 'Critical Thinking',
                'code' => 'CT',
                'max_items' => 30,
                'display_order' => 7,
                'is_active' => true,
            ]);

        $response->assertRedirect(route('admin.aptitude-areas.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('aptitude_areas', [
            'code' => 'CT',
            'name' => 'Critical Thinking',
            'max_items' => 30,
        ]);
    }

    public function test_test_administrator_can_update_aptitude_area(): void
    {
        $area = AptitudeArea::first();

        $response = $this->actingAs($this->testAdministrator())
            ->put(route('admin.aptitude-areas.update', $area), [
                'name' => 'Updated Name',
                'code' => $area->code,
                'max_items' => 20,
                'display_order' => 1,
                'is_active' => false,
            ]);

        $response->assertRedirect(route('admin.aptitude-areas.index'));
        $response->assertSessionHas('success');
        $area->refresh();
        $this->assertSame('Updated Name', $area->name);
        $this->assertSame(20, $area->max_items);
        $this->assertFalse($area->is_active);
    }
}
```

- [ ] **Step 2: Run tests — expect them to fail (class not found)**

```bash
php artisan test tests/Feature/Admin/AptitudeAreaControllerTest.php
```

Expected: `ERROR` — class `AptitudeArea` not found. Tests fail at setup, not logic. That's correct.

---

## Task 2: Create migrations

**Files:**
- Create: `database/migrations/2026_04_09_000001_create_aptitude_areas_table.php`
- Create: `database/migrations/2026_04_09_000002_rename_domain_id_in_applicant_scores.php`
- Create: `database/migrations/2026_04_09_000003_rename_domain_id_in_decision_rules.php`
- Create: `database/migrations/2026_04_09_000004_drop_exam_domains_table.php`

- [ ] **Step 1: Create `aptitude_areas` table migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('aptitude_areas', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('code', 20)->unique();
            $table->text('description')->nullable();
            $table->integer('max_items')->default(25);
            $table->integer('display_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aptitude_areas');
    }
};
```

- [ ] **Step 2: Create FK rename migration for `applicant_scores`**

The existing `applicant_scores` table has `domain_id` (non-nullable FK to `exam_domains`) and unique constraint `app_scores_gs_app_dom_unique`.

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applicant_scores', function (Blueprint $table) {
            $table->dropUnique('app_scores_gs_app_dom_unique');
            $table->dropForeign(['domain_id']);
            $table->renameColumn('domain_id', 'aptitude_area_id');
            $table->foreign('aptitude_area_id')
                ->references('id')->on('aptitude_areas')
                ->cascadeOnDelete();
            $table->unique(
                ['grading_session_id', 'applicant_id', 'aptitude_area_id'],
                'app_scores_gs_app_area_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('applicant_scores', function (Blueprint $table) {
            $table->dropUnique('app_scores_gs_app_area_unique');
            $table->dropForeign(['aptitude_area_id']);
            $table->renameColumn('aptitude_area_id', 'domain_id');
            $table->foreign('domain_id')
                ->references('id')->on('exam_domains')
                ->cascadeOnDelete();
            $table->unique(
                ['grading_session_id', 'applicant_id', 'domain_id'],
                'app_scores_gs_app_dom_unique'
            );
        });
    }
};
```

- [ ] **Step 3: Create FK rename migration for `decision_rules`**

The existing `decision_rules` table has `domain_id` (nullable FK to `exam_domains`) and a plain index on it.

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('decision_rules', function (Blueprint $table) {
            $table->dropForeign(['domain_id']);
            $table->dropIndex(['domain_id']);
            $table->renameColumn('domain_id', 'aptitude_area_id');
            $table->foreign('aptitude_area_id')
                ->references('id')->on('aptitude_areas')
                ->nullOnDelete();
            $table->index('aptitude_area_id');
        });
    }

    public function down(): void
    {
        Schema::table('decision_rules', function (Blueprint $table) {
            $table->dropForeign(['aptitude_area_id']);
            $table->dropIndex(['aptitude_area_id']);
            $table->renameColumn('aptitude_area_id', 'domain_id');
            $table->foreign('domain_id')
                ->references('id')->on('exam_domains')
                ->nullOnDelete();
            $table->index('domain_id');
        });
    }
};
```

- [ ] **Step 4: Create drop-exam-domains migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('exam_domains');
    }

    public function down(): void
    {
        // Recreated by existing create_exam_domains_table migration on rollback
    }
};
```

---

## Task 3: Create the AptitudeArea model

**Files:**
- Create: `app/Models/AptitudeArea.php`

- [ ] **Step 1: Create the model**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AptitudeArea extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'description',
        'max_items',
        'display_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'max_items' => 'integer',
            'display_order' => 'integer',
        ];
    }

    public function applicantScores(): HasMany
    {
        return $this->hasMany(ApplicantScore::class, 'aptitude_area_id');
    }
}
```

---

## Task 4: Create request classes

**Files:**
- Create: `app/Http/Requests/StoreAptitudeAreaRequest.php`
- Create: `app/Http/Requests/UpdateAptitudeAreaRequest.php`

- [ ] **Step 1: Create `StoreAptitudeAreaRequest`**

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAptitudeAreaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['super_admin', 'test_administrator']) ?? false;
    }

    public function rules(): array
    {
        return [
            'name'          => ['required', 'string', 'max:100'],
            'code'          => ['required', 'string', 'max:20', 'unique:aptitude_areas,code'],
            'description'   => ['nullable', 'string', 'max:1000'],
            'max_items'     => ['required', 'integer', 'min:1', 'max:999'],
            'display_order' => ['nullable', 'integer', 'min:0'],
            'is_active'     => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('is_active') && $this->is_active === '') {
            $this->merge(['is_active' => true]);
        }
    }
}
```

- [ ] **Step 2: Create `UpdateAptitudeAreaRequest`**

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAptitudeAreaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['super_admin', 'test_administrator']) ?? false;
    }

    public function rules(): array
    {
        $aptitudeArea = $this->route('aptitude_area');

        return [
            'name'          => ['required', 'string', 'max:100'],
            'code'          => ['required', 'string', 'max:20', Rule::unique('aptitude_areas', 'code')->ignore($aptitudeArea?->id)],
            'description'   => ['nullable', 'string', 'max:1000'],
            'max_items'     => ['required', 'integer', 'min:1', 'max:999'],
            'display_order' => ['nullable', 'integer', 'min:0'],
            'is_active'     => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('is_active') && $this->is_active === '') {
            $this->merge(['is_active' => true]);
        }
    }
}
```

---

## Task 5: Create policy and register it

**Files:**
- Create: `app/Policies/AptitudeAreaPolicy.php`
- Modify: `app/Providers/AppServiceProvider.php`

- [ ] **Step 1: Create `AptitudeAreaPolicy`**

```php
<?php

namespace App\Policies;

use App\Models\AptitudeArea;
use App\Models\User;

/**
 * Aptitude areas are managed by test_administrator and super_admin only.
 */
class AptitudeAreaPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'test_administrator']);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'test_administrator']);
    }

    public function update(User $user, AptitudeArea $aptitudeArea): bool
    {
        return $user->hasAnyRole(['super_admin', 'test_administrator']);
    }
}
```

- [ ] **Step 2: Read `app/Providers/AppServiceProvider.php` to find where policies are registered**

Open the file and look for `Gate::policy(` or a `$policies` array. In Laravel 12, policies are auto-discovered by naming convention (`AptitudeArea` model → `AptitudeAreaPolicy`). If the file has explicit registrations, add one:

```php
Gate::policy(\App\Models\AptitudeArea::class, \App\Policies\AptitudeAreaPolicy::class);
```

If the file uses auto-discovery only (no explicit registrations), no change needed — naming convention handles it.

---

## Task 6: Create the controller

**Files:**
- Create: `app/Http/Controllers/Admin/AptitudeAreaController.php`

- [ ] **Step 1: Create `AptitudeAreaController`**

```php
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAptitudeAreaRequest;
use App\Http\Requests\UpdateAptitudeAreaRequest;
use App\Models\AptitudeArea;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class AptitudeAreaController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', AptitudeArea::class);

        $aptitudeAreas = AptitudeArea::query()
            ->orderBy('display_order')
            ->orderBy('id')
            ->get()
            ->map(fn (AptitudeArea $a) => [
                'id'            => $a->id,
                'name'          => $a->name,
                'code'          => $a->code,
                'description'   => $a->description,
                'max_items'     => $a->max_items,
                'display_order' => $a->display_order,
                'is_active'     => $a->is_active,
            ]);

        return Inertia::render('Admin/AptitudeAreas/Index', [
            'aptitude_areas' => $aptitudeAreas,
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', AptitudeArea::class);

        return Inertia::render('Admin/AptitudeAreas/Create');
    }

    public function store(StoreAptitudeAreaRequest $request): RedirectResponse
    {
        $data = $request->validated();

        AptitudeArea::create([
            'name'          => $data['name'],
            'code'          => $data['code'],
            'description'   => $data['description'] ?? null,
            'max_items'     => (int) $data['max_items'],
            'display_order' => isset($data['display_order']) ? (int) $data['display_order'] : 0,
            'is_active'     => $data['is_active'] ?? true,
        ]);

        return redirect()->route('admin.aptitude-areas.index')
            ->with('success', 'Aptitude area created.');
    }

    public function edit(AptitudeArea $aptitudeArea): Response
    {
        $this->authorize('update', $aptitudeArea);

        return Inertia::render('Admin/AptitudeAreas/Edit', [
            'aptitude_area' => [
                'id'            => $aptitudeArea->id,
                'name'          => $aptitudeArea->name,
                'code'          => $aptitudeArea->code,
                'description'   => $aptitudeArea->description ?? '',
                'max_items'     => $aptitudeArea->max_items,
                'display_order' => $aptitudeArea->display_order,
                'is_active'     => $aptitudeArea->is_active,
            ],
        ]);
    }

    public function update(UpdateAptitudeAreaRequest $request, AptitudeArea $aptitudeArea): RedirectResponse
    {
        $data = $request->validated();

        $aptitudeArea->update([
            'name'          => $data['name'],
            'code'          => $data['code'],
            'description'   => $data['description'] ?? null,
            'max_items'     => (int) $data['max_items'],
            'display_order' => isset($data['display_order']) ? (int) $data['display_order'] : 0,
            'is_active'     => $data['is_active'] ?? true,
        ]);

        return redirect()->route('admin.aptitude-areas.index')
            ->with('success', 'Aptitude area updated.');
    }
}
```

---

## Task 7: Add routes and update nav label

**Files:**
- Modify: `routes/web.php`
- Modify: `app/Http/Middleware/HandleInertiaRequests.php`

- [ ] **Step 1: Add new route to `routes/web.php`**

At the top of the file, add the import next to the existing ExamDomainController import:

```php
use App\Http\Controllers\Admin\AptitudeAreaController;
```

Inside the `admin` route group (near the existing exam-domains route on line 117), add:

```php
Route::resource('aptitude-areas', AptitudeAreaController::class)
    ->except('show', 'destroy')
    ->parameters(['aptitude_areas' => 'aptitude_area']);
```

Keep the old `exam-domains` route in place for now (rollback safety).

- [ ] **Step 2: Update nav label in `HandleInertiaRequests.php`**

Find line 77:
```php
'admin.exam-domains.index' => 'Exam pillars',
```

Replace with:
```php
'admin.aptitude-areas.index' => 'Aptitude Areas',
```

- [ ] **Step 3: Commit**

```bash
git add app/Http/Controllers/Admin/AptitudeAreaController.php \
        app/Http/Requests/StoreAptitudeAreaRequest.php \
        app/Http/Requests/UpdateAptitudeAreaRequest.php \
        app/Models/AptitudeArea.php \
        app/Policies/AptitudeAreaPolicy.php \
        database/migrations/2026_04_09_000001_create_aptitude_areas_table.php \
        database/migrations/2026_04_09_000002_rename_domain_id_in_applicant_scores.php \
        database/migrations/2026_04_09_000003_rename_domain_id_in_decision_rules.php \
        database/migrations/2026_04_09_000004_drop_exam_domains_table.php \
        routes/web.php \
        app/Http/Middleware/HandleInertiaRequests.php
git commit -m "feat: add AptitudeArea model, controller, requests, policy, migrations, route, nav"
```

---

## Task 8: Create Svelte pages

**Files:**
- Create: `resources/js/Pages/Admin/AptitudeAreas/Index.svelte`
- Create: `resources/js/Pages/Admin/AptitudeAreas/Create.svelte`
- Create: `resources/js/Pages/Admin/AptitudeAreas/Edit.svelte`

- [ ] **Step 1: Create `AptitudeAreas/Index.svelte`**

```svelte
<script>
  import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.svelte';
  import { Link, usePage } from '@inertiajs/svelte';
  import { Button } from '@/Components/ui/button';
  import { Badge } from '@/Components/ui/badge';
  import { Plus, Pencil } from 'lucide-svelte';

  let { aptitude_areas = [] } = $props();

  const page = usePage();
  const success = $derived($page.props.flash?.success ?? null);
  const list = $derived(Array.isArray(aptitude_areas) ? aptitude_areas : []);

  const breadcrumbs = [{ label: 'Aptitude Areas' }];
</script>

<AuthenticatedLayout {breadcrumbs}>
  <div class="space-y-6 min-w-0">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <Link href="/admin/aptitude-areas/create">
          <Button class="min-h-[44px]">
            <Plus class="mr-2 h-4 w-4" />
            Add aptitude area
          </Button>
        </Link>
      </div>
    </div>

    {#if success}
      <div class="rounded-lg bg-primary/10 px-4 py-3 text-sm text-primary">
        {success}
      </div>
    {/if}

    <div class="glass-panel rounded-2xl overflow-hidden min-w-0 max-w-full p-6">
      <div class="w-full min-w-0 overflow-x-auto">
        <table class="w-full min-w-[520px] text-sm">
          <thead class="bg-muted/50">
            <tr>
              <th class="px-4 py-3 text-left font-medium">Name</th>
              <th class="px-4 py-3 text-left font-medium">Code</th>
              <th class="px-4 py-3 text-left font-medium">Max items</th>
              <th class="px-4 py-3 text-left font-medium">Order</th>
              <th class="px-4 py-3 text-left font-medium">Status</th>
              <th class="px-4 py-3 text-right font-medium">Actions</th>
            </tr>
          </thead>
          <tbody>
            {#each list as area}
              <tr class="border-t border-border hover:bg-muted/30">
                <td class="px-4 py-3 font-medium">{area.name ?? '—'}</td>
                <td class="px-4 py-3 font-mono text-muted-foreground">{area.code ?? '—'}</td>
                <td class="px-4 py-3">{area.max_items ?? '—'}</td>
                <td class="px-4 py-3">{area.display_order ?? 0}</td>
                <td class="px-4 py-3">
                  <Badge variant={area.is_active ? 'success' : 'muted'}>
                    {area.is_active ? 'Active' : 'Inactive'}
                  </Badge>
                </td>
                <td class="px-4 py-3 text-right">
                  <Link href={`/admin/aptitude-areas/${area.id}/edit`}>
                    <Button variant="ghost" size="icon" aria-label="Edit">
                      <Pencil class="h-4 w-4" />
                    </Button>
                  </Link>
                </td>
              </tr>
            {:else}
              <tr>
                <td colspan="6" class="px-4 py-12 text-center text-muted-foreground">
                  No aptitude areas yet. Add one to use in grading and result templates.
                </td>
              </tr>
            {/each}
          </tbody>
        </table>
      </div>
    </div>
  </div>
</AuthenticatedLayout>
```

- [ ] **Step 2: Create `AptitudeAreas/Create.svelte`**

```svelte
<script>
  import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.svelte';
  import { Link, useForm } from '@inertiajs/svelte';
  import { Button } from '@/Components/ui/button';
  import { Input } from '@/Components/ui/input';

  const form = useForm({
    name: '',
    code: '',
    description: '',
    max_items: 25,
    display_order: 0,
    is_active: true,
  });

  function submitForm(e) {
    e.preventDefault();
    $form.post('/admin/aptitude-areas');
  }

  const breadcrumbs = [
    { label: 'Aptitude Areas', href: '/admin/aptitude-areas' },
    { label: 'Create' },
  ];
</script>

<AuthenticatedLayout {breadcrumbs}>
  <div class="max-w-lg space-y-6">
    <form onsubmit={submitForm} class="space-y-4 rounded-lg border border-border bg-card p-6">
      <div class="space-y-2">
        <label for="name" class="text-sm font-medium">Name</label>
        <Input id="name" bind:value={$form.name} placeholder="e.g., Spatial Awareness" required maxlength="100" />
        {#if $form.errors?.name}
          <p class="text-sm text-destructive">{$form.errors.name}</p>
        {/if}
      </div>

      <div class="space-y-2">
        <label for="code" class="text-sm font-medium">Code</label>
        <Input id="code" bind:value={$form.code} placeholder="e.g., SA" required maxlength="20" />
        {#if $form.errors?.code}
          <p class="text-sm text-destructive">{$form.errors.code}</p>
        {/if}
      </div>

      <div class="space-y-2">
        <label for="description" class="text-sm font-medium">Description (optional)</label>
        <textarea
          id="description"
          bind:value={$form.description}
          rows="2"
          class="flex min-h-[60px] w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
          placeholder="Brief description"
        ></textarea>
        {#if $form.errors?.description}
          <p class="text-sm text-destructive">{$form.errors.description}</p>
        {/if}
      </div>

      <div class="space-y-2">
        <label for="max_items" class="text-sm font-medium">Max items (score ceiling)</label>
        <Input id="max_items" type="number" bind:value={$form.max_items} min="1" max="999" required />
        {#if $form.errors?.max_items}
          <p class="text-sm text-destructive">{$form.errors.max_items}</p>
        {/if}
      </div>

      <div class="space-y-2">
        <label for="display_order" class="text-sm font-medium">Display order</label>
        <Input id="display_order" type="number" bind:value={$form.display_order} min="0" />
        {#if $form.errors?.display_order}
          <p class="text-sm text-destructive">{$form.errors.display_order}</p>
        {/if}
      </div>

      <div class="flex items-center gap-2">
        <input type="checkbox" id="is_active" bind:checked={$form.is_active} class="h-4 w-4 rounded border-input" />
        <label for="is_active" class="text-sm font-medium">Active (included in grading and templates)</label>
      </div>
      {#if $form.errors?.is_active}
        <p class="text-sm text-destructive">{$form.errors.is_active}</p>
      {/if}

      <div class="flex gap-2 pt-4">
        <Button type="submit" disabled={$form.processing}>
          {$form.processing ? 'Creating...' : 'Create aptitude area'}
        </Button>
        <Link href="/admin/aptitude-areas">
          <Button type="button" variant="outline">Cancel</Button>
        </Link>
      </div>
    </form>
  </div>
</AuthenticatedLayout>
```

- [ ] **Step 3: Create `AptitudeAreas/Edit.svelte`**

```svelte
<script>
  import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.svelte';
  import { Link, useForm } from '@inertiajs/svelte';
  import { Button } from '@/Components/ui/button';
  import { Input } from '@/Components/ui/input';

  let { aptitude_area } = $props();

  const form = useForm({
    name: aptitude_area.name,
    code: aptitude_area.code,
    description: aptitude_area.description ?? '',
    max_items: aptitude_area.max_items,
    display_order: aptitude_area.display_order ?? 0,
    is_active: aptitude_area.is_active,
  });

  function submitForm(e) {
    e.preventDefault();
    $form.put(`/admin/aptitude-areas/${aptitude_area.id}`);
  }

  const breadcrumbs = [
    { label: 'Aptitude Areas', href: '/admin/aptitude-areas' },
    { label: 'Edit' },
  ];
</script>

<AuthenticatedLayout {breadcrumbs}>
  <div class="max-w-lg space-y-6">
    <form onsubmit={submitForm} class="space-y-4 rounded-lg border border-border bg-card p-6">
      <div class="space-y-2">
        <label for="name" class="text-sm font-medium">Name</label>
        <Input id="name" bind:value={$form.name} placeholder="e.g., Spatial Awareness" required maxlength="100" />
        {#if $form.errors?.name}
          <p class="text-sm text-destructive">{$form.errors.name}</p>
        {/if}
      </div>

      <div class="space-y-2">
        <label for="code" class="text-sm font-medium">Code</label>
        <Input id="code" bind:value={$form.code} placeholder="e.g., SA" required maxlength="20" />
        {#if $form.errors?.code}
          <p class="text-sm text-destructive">{$form.errors.code}</p>
        {/if}
      </div>

      <div class="space-y-2">
        <label for="description" class="text-sm font-medium">Description (optional)</label>
        <textarea
          id="description"
          bind:value={$form.description}
          rows="2"
          class="flex min-h-[60px] w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
          placeholder="Brief description"
        ></textarea>
        {#if $form.errors?.description}
          <p class="text-sm text-destructive">{$form.errors.description}</p>
        {/if}
      </div>

      <div class="space-y-2">
        <label for="max_items" class="text-sm font-medium">Max items (score ceiling)</label>
        <Input id="max_items" type="number" bind:value={$form.max_items} min="1" max="999" required />
        {#if $form.errors?.max_items}
          <p class="text-sm text-destructive">{$form.errors.max_items}</p>
        {/if}
      </div>

      <div class="space-y-2">
        <label for="display_order" class="text-sm font-medium">Display order</label>
        <Input id="display_order" type="number" bind:value={$form.display_order} min="0" />
        {#if $form.errors?.display_order}
          <p class="text-sm text-destructive">{$form.errors.display_order}</p>
        {/if}
      </div>

      <div class="flex items-center gap-2">
        <input type="checkbox" id="is_active" bind:checked={$form.is_active} class="h-4 w-4 rounded border-input" />
        <label for="is_active" class="text-sm font-medium">Active (included in grading and templates)</label>
      </div>
      {#if $form.errors?.is_active}
        <p class="text-sm text-destructive">{$form.errors.is_active}</p>
      {/if}

      <div class="flex gap-2 pt-4">
        <Button type="submit" disabled={$form.processing}>
          {$form.processing ? 'Saving...' : 'Save'}
        </Button>
        <Link href="/admin/aptitude-areas">
          <Button type="button" variant="outline">Cancel</Button>
        </Link>
      </div>
    </form>
  </div>
</AuthenticatedLayout>
```

- [ ] **Step 4: Commit**

```bash
git add resources/js/Pages/Admin/AptitudeAreas/
git commit -m "feat: add AptitudeAreas Svelte pages (Index, Create, Edit)"
```

---

## Task 9: Update services

**Files:**
- Modify: `app/Services/GradingSessionService.php`
- Modify: `app/Services/ResultSheetTemplateService.php`
- Modify: `app/Services/ScoreInputService.php`

- [ ] **Step 1: Update `GradingSessionService.php`**

Find all occurrences of `ExamDomain` and `domain_id` in this file:

```php
// Line 5 — change import:
use App\Models\ExamDomain;
// → 
use App\Models\AptitudeArea;

// Line 59 — change query:
$activeDomainIds = ExamDomain::where('is_active', true)->pluck('id');
// →
$activeDomainIds = AptitudeArea::where('is_active', true)->pluck('id');

// Lines 65, 67 — change column:
->whereIn('domain_id', $activeDomainIds)
// →
->whereIn('aptitude_area_id', $activeDomainIds)

->count('domain_id');
// →
->count('aptitude_area_id');
```

- [ ] **Step 2: Update `ResultSheetTemplateService.php`**

```php
// Change import (line 5):
use App\Models\ExamDomain;
// →
use App\Models\AptitudeArea;

// Change query (line 146):
$domains = ExamDomain::where('is_active', true)->orderBy('display_order')->get(['id', 'name']);
// →
$domains = AptitudeArea::where('is_active', true)->orderBy('display_order')->get(['id', 'name']);

// Rename method — find the method signature:
public function domainSlug(string $name): string
// →
public function aptitudeAreaSlug(string $name): string

// Update all internal calls to domainSlug() → aptitudeAreaSlug() within this file
```

- [ ] **Step 3: Update `ScoreInputService.php`**

Find the `domain_id` references in this file (lines ~14, 21, 29):

```php
// Line 14 — update docblock:
* @param  array<array{domain_id: int, raw_score: int, max_score: int}>  $scores
// →
* @param  array<array{aptitude_area_id: int, raw_score: int, max_score: int}>  $scores

// Line 21:
$domainId = (int) $entry['domain_id'];
// →
$domainId = (int) $entry['aptitude_area_id'];

// Line 29:
'domain_id' => $domainId,
// →
'aptitude_area_id' => $domainId,
```

- [ ] **Step 4: Commit**

```bash
git add app/Services/GradingSessionService.php \
        app/Services/ResultSheetTemplateService.php \
        app/Services/ScoreInputService.php
git commit -m "feat: update services to use AptitudeArea and aptitude_area_id"
```

---

## Task 10: Create AptitudeAreaSeeder and update DatabaseSeeder + demo seeders

**Files:**
- Create: `database/seeders/AptitudeAreaSeeder.php`
- Modify: `database/seeders/DatabaseSeeder.php`
- Modify: `database/seeders/DefenseDemoSeeder.php`
- Modify: `database/seeders/DemoDashboardSeeder.php`

- [ ] **Step 1: Create `AptitudeAreaSeeder.php`**

```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class AptitudeAreaSeeder extends Seeder
{
    public function run(): void
    {
        $areas = [
            ['name' => 'Spatial Awareness',          'code' => 'SA',  'max_items' => 25, 'display_order' => 1],
            ['name' => 'Numerical Ability',           'code' => 'NA',  'max_items' => 25, 'display_order' => 2],
            ['name' => 'Verbal Reasoning',            'code' => 'VR',  'max_items' => 25, 'display_order' => 3],
            ['name' => 'Abstract Reasoning',          'code' => 'AR',  'max_items' => 20, 'display_order' => 4],
            ['name' => 'Logical Reasoning',           'code' => 'LR',  'max_items' => 25, 'display_order' => 5],
            ['name' => 'Perceptual Speed & Accuracy', 'code' => 'PSA', 'max_items' => 20, 'display_order' => 6],
        ];

        foreach ($areas as $area) {
            \App\Models\AptitudeArea::firstOrCreate(
                ['code' => $area['code']],
                array_merge($area, ['description' => null, 'is_active' => true])
            );
        }
    }
}
```

- [ ] **Step 2: Update `DatabaseSeeder.php`**

Find the line:
```php
ExamDomainSeeder::class,
```

Replace with:
```php
AptitudeAreaSeeder::class,
```

Also update the comment above it from `// 3. ExamDomainSeeder    → exam domains (SA, NA, VR, AR, LR, PSA)` to:
```php
// 3. AptitudeAreaSeeder  → aptitude areas (SA, NA, VR, AR, LR, PSA)
```

- [ ] **Step 3: Update `DefenseDemoSeeder.php`**

```php
// Change import:
use App\Models\ExamDomain;
// →
use App\Models\AptitudeArea;

// Change query (line 34):
$domains = ExamDomain::query()->where('is_active', true)->orderBy('display_order')->get();
// →
$domains = AptitudeArea::query()->where('is_active', true)->orderBy('display_order')->get();

// Change warning message (line 36):
$this->command?->warn('DefenseDemoSeeder: need at least 3 active exam domains. Run DatabaseSeeder first.');
// →
$this->command?->warn('DefenseDemoSeeder: need at least 3 active aptitude areas. Run DatabaseSeeder first.');
```

- [ ] **Step 4: Update `DemoDashboardSeeder.php`**

```php
// Change import (line 11):
use App\Models\ExamDomain;
// →
use App\Models\AptitudeArea;

// Change query (line 39):
$domains = ExamDomain::query()->where('is_active', true)->orderBy('display_order')->get();
// →
$domains = AptitudeArea::query()->where('is_active', true)->orderBy('display_order')->get();

// Change warning (line 41):
$this->command?->warn('DemoDashboardSeeder: not enough exam domains; skipping.');
// →
$this->command?->warn('DemoDashboardSeeder: not enough aptitude areas; skipping.');

// Change type hint (line 237):
$domains->take($take)->each(function (ExamDomain $d) use ($gs, $applicantId, $users) {
// →
$domains->take($take)->each(function (AptitudeArea $d) use ($gs, $applicantId, $users) {
```

- [ ] **Step 5: Commit**

```bash
git add database/seeders/AptitudeAreaSeeder.php \
        database/seeders/DatabaseSeeder.php \
        database/seeders/DefenseDemoSeeder.php \
        database/seeders/DemoDashboardSeeder.php
git commit -m "feat: add AptitudeAreaSeeder; update DatabaseSeeder and demo seeders"
```

---

## Task 11: Update frontend lib

**Files:**
- Modify: `resources/js/lib/domains.js`

- [ ] **Step 1: Rename `EXAM_PILLARS` → `APTITUDE_AREAS` and update the comment**

Open `resources/js/lib/domains.js`. Replace the entire file content with:

```js
/**
 * Legacy/fallback aptitude areas (subjects).
 * The source of truth is the backend: aptitude_areas table, managed at Admin → Aptitude Areas.
 * Grading, consultation rules, and result templates receive areas from the API.
 * Use this constant only when backend areas are not available (e.g. static fallback).
 */
export const APTITUDE_AREAS = [
  { id: 1, name: 'Spatial Awareness',          code: 'SA',  max_score: 25 },
  { id: 2, name: 'Numerical Ability',           code: 'NA',  max_score: 25 },
  { id: 3, name: 'Verbal Reasoning',            code: 'VR',  max_score: 25 },
  { id: 4, name: 'Abstract Reasoning',          code: 'AR',  max_score: 20 },
  { id: 5, name: 'Logical Reasoning',           code: 'LR',  max_score: 25 },
  { id: 6, name: 'Perceptual Speed & Accuracy', code: 'PSA', max_score: 20 },
];
```

- [ ] **Step 2: Search for any Svelte/JS files importing `EXAM_PILLARS` and update them**

```bash
grep -r "EXAM_PILLARS\|from.*domains" resources/js --include="*.svelte" --include="*.js" -l
```

For each file found, change:
```js
import { EXAM_PILLARS } from '@/lib/domains';
// →
import { APTITUDE_AREAS } from '@/lib/domains';
```

And rename usages of `EXAM_PILLARS` → `APTITUDE_AREAS` in those files.

- [ ] **Step 3: Commit**

```bash
git add resources/js/lib/domains.js
git commit -m "feat: rename EXAM_PILLARS to APTITUDE_AREAS in lib/domains.js"
```

---

## Task 12: Migrate, seed, and run tests

- [ ] **Step 1: Run fresh migration and seed**

```bash
php artisan migrate:fresh --seed
```

Expected: All migrations run in order. Tables created: `aptitude_areas` (from migration 1). `applicant_scores.domain_id` renamed to `aptitude_area_id` (migration 2). `decision_rules.domain_id` renamed to `aptitude_area_id` (migration 3). `exam_domains` dropped (migration 4). Seeders run including `AptitudeAreaSeeder`.

- [ ] **Step 2: Run all feature tests**

```bash
php artisan test tests/Feature/Admin/AptitudeAreaControllerTest.php
```

Expected: 5 tests pass.

- [ ] **Step 3: Run the full test suite to check for regressions**

```bash
php artisan test
```

Expected: All tests pass. If any test references `ExamDomain`, `exam_domains`, or `domain_id`, fix it before proceeding.

- [ ] **Step 4: Manual browser check**

1. Log in as `test_administrator`
2. Navigate to Admin → Aptitude Areas (sidebar label should read "Aptitude Areas")
3. Verify index lists 6 areas from seed
4. Create a new area — confirm redirect and flash success
5. Edit an existing area — confirm values load and save correctly
6. Navigate to Grading — confirm grading sessions still load

---

## Task 13: Delete legacy files

Only proceed after Task 12 passes completely.

- [ ] **Step 1: Delete old PHP files**

```bash
rm app/Models/ExamDomain.php
rm app/Http/Controllers/Admin/ExamDomainController.php
rm app/Http/Requests/StoreExamDomainRequest.php
rm app/Http/Requests/UpdateExamDomainRequest.php
rm app/Policies/ExamDomainPolicy.php
rm database/seeders/ExamDomainSeeder.php
rm tests/Feature/Admin/ExamDomainControllerTest.php
```

- [ ] **Step 2: Delete old Svelte pages**

```bash
rm -rf resources/js/Pages/Admin/ExamDomains/
```

- [ ] **Step 3: Remove old route from `routes/web.php`**

Remove the line:
```php
use App\Http\Controllers\Admin\ExamDomainController;
```

And remove the route:
```php
Route::resource('exam-domains', ExamDomainController::class)->except('show', 'destroy')->parameters(['exam_domains' => 'exam_domain']);
```

- [ ] **Step 4: Run full test suite one final time**

```bash
php artisan test
```

Expected: All tests pass. No references to `ExamDomain` or `exam_domains` anywhere in active code.

- [ ] **Step 5: Final commit**

```bash
git add -A
git commit -m "cleanup: remove legacy ExamDomain files after AptitudeArea rename"
```

---

## Verification Checklist

After Task 12 (before deleting legacy files):

- [ ] `aptitude_areas` table exists with 6 seeded rows
- [ ] `applicant_scores` has `aptitude_area_id` column (no `domain_id`)
- [ ] `decision_rules` has `aptitude_area_id` column (no `domain_id`)
- [ ] `exam_domains` table does NOT exist
- [ ] Sidebar nav shows "Aptitude Areas"
- [ ] Index page loads at `/admin/aptitude-areas`
- [ ] Create form POSTs to `/admin/aptitude-areas`
- [ ] Edit form PUTs to `/admin/aptitude-areas/{id}`
- [ ] Grading session service resolves active areas correctly
- [ ] Result sheet template service renders domain slugs (e.g. `{{spatial_awareness}}`)
- [ ] All `php artisan test` pass
