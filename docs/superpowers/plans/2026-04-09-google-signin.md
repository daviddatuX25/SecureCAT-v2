# Google Sign-In Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add Google Sign-In for staff accounts on the web guard, silently disabled when env vars are absent.

**Architecture:** Socialite handles the OAuth redirect/callback. A `GoogleOAuthUserResolver` service matches the returning Google user to an existing `users` row by email, auto-creating a `user_credentials` row on first match. `GoogleOAuthConfig::isConfigured()` gates route registration and UI rendering so the feature is invisible in local dev without credentials.

**Spec:** `docs/superpowers/specs/2026-04-09-google-signin-architecture.md`

**Tech Stack:** Laravel 12, Laravel Socialite, Inertia.js, Svelte 5, PHPUnit

---

## File Map

| Action | File | Responsibility |
|---|---|---|
| Create | `app/Support/GoogleOAuthConfig.php` | Single `isConfigured()` gate |
| Create | `app/Models/UserCredential.php` | OAuth credential record per user/provider |
| Create | `app/Services/GoogleOAuthUserResolver.php` | Match Google user → staff account |
| Create | `app/Http/Controllers/Auth/GoogleAuthController.php` | Redirect + callback routes |
| Create | `database/migrations/XXXX_create_user_credentials_table.php` | New table |
| Create | `tests/Feature/Auth/GoogleSignInTest.php` | Controller integration tests |
| Create | `tests/Unit/Services/GoogleOAuthUserResolverTest.php` | Resolver unit tests |
| Modify | `app/Models/User.php` | Add `credentials()`, `hasGoogleLinked()` |
| Modify | `app/Http/Middleware/HandleInertiaRequests.php` | Share `googleOAuthEnabled` prop |
| Modify | `app/Http/Controllers/Admin/UserController.php` | Delete credential on email change; pass `googleLinked` to edit |
| Modify | `config/services.php` | Add `google` driver config |
| Modify | `routes/web.php` | Conditional Google OAuth routes |
| Modify | `resources/js/Pages/Auth/Login.svelte` | Google button in staff tab |
| Modify | `resources/js/Pages/Admin/Users/Edit.svelte` | Google linked badge |

---

## Task 1: Install Socialite and Add Google Config

**Files:**
- Modify: `composer.json` (via composer)
- Modify: `config/services.php`

- [ ] **Step 1: Install Socialite**

```bash
cd D:/Projects/SecureCAT-v2
composer require laravel/socialite
```

Expected: `laravel/socialite` appears in `composer.json` requires, no errors.

- [ ] **Step 2: Add Google service config**

In `config/services.php`, add after the `slack` block:

```php
'google' => [
    'client_id'     => env('GOOGLE_CLIENT_ID'),
    'client_secret' => env('GOOGLE_CLIENT_SECRET'),
    'redirect'      => env('GOOGLE_REDIRECT_URI'),
],
```

- [ ] **Step 3: Commit**

```bash
git add composer.json composer.lock config/services.php
git commit -m "chore: install laravel/socialite, add google service config"
```

---

## Task 2: Migration — `user_credentials` Table

**Files:**
- Create: `database/migrations/XXXX_create_user_credentials_table.php`

- [ ] **Step 1: Generate the migration**

```bash
php artisan make:migration create_user_credentials_table
```

Expected: new file in `database/migrations/` with today's timestamp.

- [ ] **Step 2: Write the migration**

Open the generated file and replace the `up()` and `down()` methods:

```php
public function up(): void
{
    Schema::create('user_credentials', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained()->cascadeOnDelete();
        $table->string('provider');
        $table->string('identifier');
        $table->string('secret')->nullable();
        $table->timestamps();

        $table->unique(['provider', 'identifier']);
        $table->unique(['user_id', 'provider']);
    });
}

public function down(): void
{
    Schema::dropIfExists('user_credentials');
}
```

- [ ] **Step 3: Run the migration**

```bash
php artisan migrate
```

Expected: `user_credentials` table created, no errors.

- [ ] **Step 4: Commit**

```bash
git add database/migrations/
git commit -m "feat: create user_credentials table migration"
```

---

## Task 3: `UserCredential` Model

**Files:**
- Create: `app/Models/UserCredential.php`

- [ ] **Step 1: Create the model**

Create `app/Models/UserCredential.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserCredential extends Model
{
    const PROVIDER_GOOGLE = 'google';

    protected $fillable = ['user_id', 'provider', 'identifier', 'secret'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
```

- [ ] **Step 2: Commit**

```bash
git add app/Models/UserCredential.php
git commit -m "feat: add UserCredential model"
```

---

## Task 4: Update `User` Model

**Files:**
- Modify: `app/Models/User.php`

- [ ] **Step 1: Add the `credentials` relationship and `hasGoogleLinked` method**

In `app/Models/User.php`, add these imports at the top:

```php
use App\Models\UserCredential;
use Illuminate\Database\Eloquent\Relations\HasMany;
```

Then add these two methods inside the `User` class, after the existing `hasAnyRole` method:

```php
public function credentials(): HasMany
{
    return $this->hasMany(UserCredential::class);
}

public function hasGoogleLinked(): bool
{
    return $this->credentials()
        ->where('provider', UserCredential::PROVIDER_GOOGLE)
        ->exists();
}
```

- [ ] **Step 2: Verify no test regressions**

```bash
php artisan test tests/Feature/Auth/LoginTest.php
```

Expected: all tests PASS.

- [ ] **Step 3: Commit**

```bash
git add app/Models/User.php
git commit -m "feat: add credentials relationship and hasGoogleLinked to User model"
```

---

## Task 5: `GoogleOAuthConfig` Support Class

**Files:**
- Create: `app/Support/GoogleOAuthConfig.php`

- [ ] **Step 1: Create the support class**

Create `app/Support/GoogleOAuthConfig.php`:

```php
<?php

namespace App\Support;

class GoogleOAuthConfig
{
    /**
     * Returns true only when Google OAuth credentials are fully configured.
     * Used to gate route registration and UI rendering.
     */
    public static function isConfigured(): bool
    {
        return ! empty(config('services.google.client_id'))
            && ! empty(config('services.google.client_secret'));
    }
}
```

- [ ] **Step 2: Commit**

```bash
git add app/Support/GoogleOAuthConfig.php
git commit -m "feat: add GoogleOAuthConfig support class"
```

---

## Task 6: `GoogleOAuthUserResolver` Service (TDD)

**Files:**
- Create: `app/Services/GoogleOAuthUserResolver.php`
- Create: `tests/Unit/Services/GoogleOAuthUserResolverTest.php`

- [ ] **Step 1: Write the failing tests**

Create `tests/Unit/Services/GoogleOAuthUserResolverTest.php`:

```php
<?php

namespace Tests\Unit\Services;

use App\Models\User;
use App\Models\UserCredential;
use App\Services\GoogleOAuthUserResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Mockery;
use Tests\TestCase;

class GoogleOAuthUserResolverTest extends TestCase
{
    use RefreshDatabase;

    private function makeSocialiteUser(string $sub, string $email): SocialiteUser
    {
        $mock = Mockery::mock(SocialiteUser::class);
        $mock->shouldReceive('getId')->andReturn($sub);
        $mock->shouldReceive('getEmail')->andReturn($email);
        return $mock;
    }

    public function test_returns_user_when_credential_already_exists(): void
    {
        $user = User::factory()->create(['email' => 'john@gmail.com']);
        UserCredential::create([
            'user_id'    => $user->id,
            'provider'   => UserCredential::PROVIDER_GOOGLE,
            'identifier' => 'sub-abc-123',
        ]);

        $socialUser = $this->makeSocialiteUser('sub-abc-123', 'john@gmail.com');

        $resolver = new GoogleOAuthUserResolver();
        $result = $resolver->findOrAttachUser($socialUser);

        $this->assertNotNull($result);
        $this->assertEquals($user->id, $result->id);
        // No new credential should be created (still just 1)
        $this->assertCount(1, UserCredential::all());
    }

    public function test_creates_credential_and_returns_user_on_first_google_signin(): void
    {
        $user = User::factory()->create(['email' => 'jane@gmail.com']);

        $socialUser = $this->makeSocialiteUser('sub-xyz-456', 'jane@gmail.com');

        $resolver = new GoogleOAuthUserResolver();
        $result = $resolver->findOrAttachUser($socialUser);

        $this->assertNotNull($result);
        $this->assertEquals($user->id, $result->id);

        $credential = UserCredential::where('user_id', $user->id)
            ->where('provider', UserCredential::PROVIDER_GOOGLE)
            ->first();
        $this->assertNotNull($credential);
        $this->assertEquals('sub-xyz-456', $credential->identifier);
    }

    public function test_email_match_is_case_insensitive(): void
    {
        $user = User::factory()->create(['email' => 'Staff@Gmail.com']);

        $socialUser = $this->makeSocialiteUser('sub-999', 'staff@gmail.com');

        $resolver = new GoogleOAuthUserResolver();
        $result = $resolver->findOrAttachUser($socialUser);

        $this->assertNotNull($result);
        $this->assertEquals($user->id, $result->id);
    }

    public function test_returns_null_when_no_matching_user(): void
    {
        $socialUser = $this->makeSocialiteUser('sub-unknown', 'unknown@gmail.com');

        $resolver = new GoogleOAuthUserResolver();
        $result = $resolver->findOrAttachUser($socialUser);

        $this->assertNull($result);
        $this->assertCount(0, UserCredential::all());
    }
}
```

- [ ] **Step 2: Run tests to verify they fail**

```bash
php artisan test tests/Unit/Services/GoogleOAuthUserResolverTest.php
```

Expected: 4 failures — `GoogleOAuthUserResolver` class not found.

- [ ] **Step 3: Create the service**

Create `app/Services/GoogleOAuthUserResolver.php`:

```php
<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserCredential;
use Laravel\Socialite\Contracts\User as SocialiteUser;

class GoogleOAuthUserResolver
{
    /**
     * Find or attach a staff User for the given Google account.
     * Returns null if no matching user exists.
     */
    public function findOrAttachUser(SocialiteUser $socialUser): ?User
    {
        // Fast path: already linked — find by Google sub
        $credential = UserCredential::where('provider', UserCredential::PROVIDER_GOOGLE)
            ->where('identifier', $socialUser->getId())
            ->first();

        if ($credential) {
            return $credential->user;
        }

        // First-time sign-in: match by email, auto-create credential
        $googleEmail = strtolower(trim($socialUser->getEmail()));

        $user = User::whereRaw('LOWER(TRIM(email)) = ?', [$googleEmail])->first();

        if (! $user) {
            return null;
        }

        UserCredential::create([
            'user_id'    => $user->id,
            'provider'   => UserCredential::PROVIDER_GOOGLE,
            'identifier' => $socialUser->getId(),
        ]);

        return $user;
    }
}
```

- [ ] **Step 4: Run tests to verify they pass**

```bash
php artisan test tests/Unit/Services/GoogleOAuthUserResolverTest.php
```

Expected: 4 tests PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Services/GoogleOAuthUserResolver.php tests/Unit/Services/GoogleOAuthUserResolverTest.php
git commit -m "feat: add GoogleOAuthUserResolver service with tests"
```

---

## Task 7: `GoogleAuthController` (TDD)

**Files:**
- Create: `app/Http/Controllers/Auth/GoogleAuthController.php`
- Create: `tests/Feature/Auth/GoogleSignInTest.php`

- [ ] **Step 1: Write the failing tests**

Create `tests/Feature/Auth/GoogleSignInTest.php`:

```php
<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Models\UserCredential;
use App\Support\GoogleOAuthConfig;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;
use Mockery;
use Tests\TestCase;

class GoogleSignInTest extends TestCase
{
    use RefreshDatabase;

    private function mockSocialite(string $sub, string $email): void
    {
        $socialUser = Mockery::mock(SocialiteUser::class);
        $socialUser->shouldReceive('getId')->andReturn($sub);
        $socialUser->shouldReceive('getEmail')->andReturn($email);

        $provider = Mockery::mock(\Laravel\Socialite\Contracts\Provider::class);
        $provider->shouldReceive('user')->andReturn($socialUser);
        $provider->shouldReceive('redirect')->andReturn(
            redirect('https://accounts.google.com/o/oauth2/auth?fake=1')
        );

        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);
    }

    private function configureGoogle(): void
    {
        config([
            'services.google.client_id'     => 'fake-client-id',
            'services.google.client_secret' => 'fake-client-secret',
            'services.google.redirect'      => 'http://localhost/auth/google/callback',
        ]);
    }

    public function test_redirect_sends_guest_to_google(): void
    {
        $this->configureGoogle();
        $this->mockSocialite('sub-1', 'test@gmail.com');

        $response = $this->get(route('auth.google.redirect'));

        $response->assertRedirect();
        $this->assertStringContainsString('google.com', $response->headers->get('Location'));
    }

    public function test_callback_logs_in_existing_linked_user(): void
    {
        $this->configureGoogle();

        $user = User::factory()->create(['email' => 'linked@gmail.com']);
        UserCredential::create([
            'user_id'    => $user->id,
            'provider'   => UserCredential::PROVIDER_GOOGLE,
            'identifier' => 'sub-linked-123',
        ]);

        $this->mockSocialite('sub-linked-123', 'linked@gmail.com');

        $response = $this->get(route('auth.google.callback'));

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_callback_auto_links_and_logs_in_user_on_first_signin(): void
    {
        $this->configureGoogle();

        $user = User::factory()->create(['email' => 'newlink@gmail.com']);
        $this->mockSocialite('sub-new-456', 'newlink@gmail.com');

        $response = $this->get(route('auth.google.callback'));

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);

        $this->assertDatabaseHas('user_credentials', [
            'user_id'    => $user->id,
            'provider'   => 'google',
            'identifier' => 'sub-new-456',
        ]);
    }

    public function test_callback_redirects_to_login_when_no_matching_user(): void
    {
        $this->configureGoogle();

        $this->mockSocialite('sub-orphan', 'orphan@gmail.com');

        $response = $this->get(route('auth.google.callback'));

        $response->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_google_routes_not_registered_without_config(): void
    {
        // Ensure env vars are empty
        config(['services.google.client_id' => '', 'services.google.client_secret' => '']);

        $this->assertFalse(GoogleOAuthConfig::isConfigured());
    }
}
```

- [ ] **Step 2: Run tests to verify they fail**

```bash
php artisan test tests/Feature/Auth/GoogleSignInTest.php
```

Expected: failures due to missing `GoogleAuthController` and unregistered routes.

- [ ] **Step 3: Create the controller**

Create `app/Http/Controllers/Auth/GoogleAuthController.php`:

```php
<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\GoogleOAuthUserResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    public function redirect(): RedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback(Request $request, GoogleOAuthUserResolver $resolver): RedirectResponse
    {
        try {
            $socialUser = Socialite::driver('google')->user();
        } catch (\Throwable) {
            return redirect()->route('login')
                ->withErrors(['google' => 'Google sign-in was cancelled or failed.']);
        }

        $user = $resolver->findOrAttachUser($socialUser);

        if (! $user) {
            return redirect()->route('login')
                ->withErrors(['google' => 'No staff account found for this Google address. Contact your administrator.']);
        }

        Auth::login($user, remember: true);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }
}
```

- [ ] **Step 4: Register the routes** (needed before tests can pass)

In `routes/web.php`, add these imports at the top with the other `use` statements:

```php
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Support\GoogleOAuthConfig;
```

Then add this block anywhere before the `Route::middleware('guest')` group (e.g. right after the home/about routes):

```php
if (GoogleOAuthConfig::isConfigured()) {
    Route::middleware('guest')->group(function () {
        Route::get('/auth/google', [GoogleAuthController::class, 'redirect'])
            ->name('auth.google.redirect');
    });

    Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])
        ->name('auth.google.callback');
}
```

- [ ] **Step 5: Run tests to verify they pass**

```bash
php artisan test tests/Feature/Auth/GoogleSignInTest.php
```

Expected: 5 tests PASS.

- [ ] **Step 6: Run the full test suite to check for regressions**

```bash
php artisan test
```

Expected: all tests PASS.

- [ ] **Step 7: Commit**

```bash
git add app/Http/Controllers/Auth/GoogleAuthController.php tests/Feature/Auth/GoogleSignInTest.php routes/web.php
git commit -m "feat: add GoogleAuthController, register Google OAuth routes"
```

---

## Task 8: Share `googleOAuthEnabled` via Inertia

**Files:**
- Modify: `app/Http/Middleware/HandleInertiaRequests.php`

- [ ] **Step 1: Add the import**

In `app/Http/Middleware/HandleInertiaRequests.php`, add this import at the top with the other `use` statements:

```php
use App\Support\GoogleOAuthConfig;
```

- [ ] **Step 2: Add the shared prop**

In the `share()` method, add `googleOAuthEnabled` to the `array_merge` return:

```php
return array_merge(parent::share($request), [
    'auth' => [
        'user' => $authUser,
    ],
    'csrf_token'                  => $request->session()->token(),
    'ai_exam_companion_enabled'   => SystemSetting::aiCompanionEnabled(),
    'release_mode'                => SystemSetting::releaseMode(),
    'pageTitle'                   => $this->defaultPageTitle($request),
    'googleOAuthEnabled'          => GoogleOAuthConfig::isConfigured(),
]);
```

- [ ] **Step 3: Verify existing tests still pass**

```bash
php artisan test
```

Expected: all tests PASS.

- [ ] **Step 4: Commit**

```bash
git add app/Http/Middleware/HandleInertiaRequests.php
git commit -m "feat: share googleOAuthEnabled prop via Inertia middleware"
```

---

## Task 9: Delete Google Credential on Email Change + Pass `googleLinked` to Edit View

**Files:**
- Modify: `app/Http/Controllers/Admin/UserController.php`

- [ ] **Step 1: Write the failing test**

Add this test to `tests/Feature/Admin/` — create `tests/Feature/Admin/UserGoogleCredentialTest.php`:

```php
<?php

namespace Tests\Feature\Admin;

use App\Models\Role;
use App\Models\User;
use App\Models\UserCredential;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserGoogleCredentialTest extends TestCase
{
    use RefreshDatabase;

    private function superAdmin(): User
    {
        $user = User::factory()->create();
        $role = Role::firstOrCreate(['name' => 'super_admin'], ['display_name' => 'Super Admin']);
        $user->roles()->attach($role);
        return $user;
    }

    public function test_updating_user_email_deletes_existing_google_credential(): void
    {
        $admin = $this->superAdmin();
        $staff = User::factory()->create(['email' => 'old@gmail.com']);

        UserCredential::create([
            'user_id'    => $staff->id,
            'provider'   => UserCredential::PROVIDER_GOOGLE,
            'identifier' => 'sub-old-123',
        ]);

        $this->actingAs($admin)->put(route('admin.users.update', $staff), [
            'name'  => $staff->name,
            'email' => 'new@gmail.com',
            'roles' => [],
        ]);

        $this->assertDatabaseMissing('user_credentials', [
            'user_id'  => $staff->id,
            'provider' => 'google',
        ]);
    }

    public function test_updating_user_email_to_same_value_keeps_credential(): void
    {
        $admin = $this->superAdmin();
        $staff = User::factory()->create(['email' => 'same@gmail.com']);

        UserCredential::create([
            'user_id'    => $staff->id,
            'provider'   => UserCredential::PROVIDER_GOOGLE,
            'identifier' => 'sub-same-456',
        ]);

        $this->actingAs($admin)->put(route('admin.users.update', $staff), [
            'name'  => $staff->name,
            'email' => 'same@gmail.com',
            'roles' => [],
        ]);

        $this->assertDatabaseHas('user_credentials', [
            'user_id'    => $staff->id,
            'provider'   => 'google',
            'identifier' => 'sub-same-456',
        ]);
    }

    public function test_edit_view_receives_google_linked_prop(): void
    {
        $admin = $this->superAdmin();
        $staff = User::factory()->create();

        UserCredential::create([
            'user_id'    => $staff->id,
            'provider'   => UserCredential::PROVIDER_GOOGLE,
            'identifier' => 'sub-edit-789',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.users.edit', $staff));

        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Users/Edit')
            ->where('googleLinked', true)
        );
    }
}
```

- [ ] **Step 2: Run tests to verify they fail**

```bash
php artisan test tests/Feature/Admin/UserGoogleCredentialTest.php
```

Expected: 3 failures.

- [ ] **Step 3: Update `UserController`**

In `app/Http/Controllers/Admin/UserController.php`, add this import:

```php
use App\Models\UserCredential;
```

In the `edit()` method, update the return to pass `googleLinked`:

```php
public function edit(User $user): Response
{
    $user->load('roles:id,name,display_name');

    return Inertia::render('Admin/Users/Edit', [
        'user'         => $user,
        'roles'        => Role::orderBy('name')->get(['id', 'name', 'display_name']),
        'googleLinked' => $user->hasGoogleLinked(),
    ]);
}
```

In the `update()` method, add the credential deletion when email changes. Add this block **before** `$user->save()`:

```php
if (isset($validated['email']) && $validated['email'] !== $user->email) {
    $user->credentials()
        ->where('provider', UserCredential::PROVIDER_GOOGLE)
        ->delete();
}
```

- [ ] **Step 4: Run tests to verify they pass**

```bash
php artisan test tests/Feature/Admin/UserGoogleCredentialTest.php
```

Expected: 3 tests PASS.

- [ ] **Step 5: Run full suite**

```bash
php artisan test
```

Expected: all tests PASS.

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/Admin/UserController.php tests/Feature/Admin/UserGoogleCredentialTest.php
git commit -m "feat: delete google credential on email change, pass googleLinked to edit view"
```

---

## Task 10: Login Page — Google Button on Staff Tab

**Files:**
- Modify: `resources/js/Pages/Auth/Login.svelte`

- [ ] **Step 1: Read `googleOAuthEnabled` from page props**

In the `<script>` block of `Login.svelte`, add after the `const flash = ...` line:

```js
const googleOAuthEnabled = $derived($page.props.googleOAuthEnabled ?? false);
```

- [ ] **Step 2: Add the Google error display and button inside the staff form**

Inside the `{#if activeTab === 'staff'}` form block, add the following **after** the "Remember my computer" checkbox block and **before** the existing `<Button>` submit button:

```svelte
{#if errors?.google}
  <p class="text-sm text-destructive font-bold">{errors.google}</p>
{/if}

{#if googleOAuthEnabled}
  <div class="relative">
    <div class="absolute inset-0 flex items-center">
      <span class="w-full border-t border-border/60"></span>
    </div>
    <div class="relative flex justify-center text-xs">
      <span class="bg-card px-3 text-muted-foreground font-semibold uppercase tracking-wide">or</span>
    </div>
  </div>

  <a
    href="/auth/google"
    class="flex items-center justify-center gap-3 w-full h-12 rounded-xl border border-border/60 bg-background hover:bg-muted transition-colors text-sm font-bold shadow-sm"
  >
    <svg class="h-5 w-5" viewBox="0 0 24 24" aria-hidden="true">
      <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
      <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
      <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l3.66-2.84z" fill="#FBBC05"/>
      <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
    </svg>
    Sign in with Google
  </a>
{/if}
```

The existing `<Button variant="secondary" ...>Sign in as Staff</Button>` stays in place — it should come after the Google button block.

- [ ] **Step 3: Verify the page loads**

```bash
php artisan serve
```

Open `http://localhost:8000/login`, switch to Staff tab. Without Google env vars set, the button should **not** appear. No JS errors in the console.

- [ ] **Step 4: Commit**

```bash
git add resources/js/Pages/Auth/Login.svelte
git commit -m "feat: add Google Sign-In button to staff login tab"
```

---

## Task 11: Admin Users Edit — Google Linked Badge

**Files:**
- Modify: `resources/js/Pages/Admin/Users/Edit.svelte`

- [ ] **Step 1: Accept the new prop**

In the `<script>` block of `Edit.svelte`, update the `$props()` destructure:

```js
let { user, roles, googleLinked } = $props();
```

- [ ] **Step 2: Add the badge below the email field**

After the email `<div class="space-y-2">` block (the one with `id="email"`), add:

```svelte
{#if googleLinked}
  <div class="flex items-center gap-2 text-sm text-emerald-600 font-semibold">
    <svg class="h-4 w-4" viewBox="0 0 24 24" aria-hidden="true">
      <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
      <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
      <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l3.66-2.84z" fill="#FBBC05"/>
      <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
    </svg>
    Google account linked — changing the email above will remove this link
  </div>
{:else}
  <p class="text-xs text-muted-foreground">No Google account linked.</p>
{/if}
```

- [ ] **Step 3: Run full test suite**

```bash
php artisan test
```

Expected: all tests PASS.

- [ ] **Step 4: Commit**

```bash
git add resources/js/Pages/Admin/Users/Edit.svelte
git commit -m "feat: show Google linked status badge on user edit screen"
```

---

## Task 12: Final Verification

- [ ] **Step 1: Run the full test suite**

```bash
php artisan test
```

Expected: all tests PASS, no skipped.

- [ ] **Step 2: Smoke-test locally (without Google credentials)**

```bash
php artisan serve
```

- Visit `/login` — staff tab should show **no** Google button (env vars empty)
- Visit `/admin/users/{id}/edit` — should show "No Google account linked."
- Email/password login still works normally

- [ ] **Step 3: Smoke-test with credentials (optional, if you have a Google OAuth app)**

Add to `.env`:
```ini
GOOGLE_CLIENT_ID=your-client-id
GOOGLE_CLIENT_SECRET=your-client-secret
GOOGLE_REDIRECT_URI=http://localhost:8000/auth/google/callback
```

Restart the server (`php artisan serve`). Staff tab should now show the Google Sign-In button. Clicking it should redirect to Google's OAuth consent screen.

- [ ] **Step 4: Final commit**

```bash
git add .
git commit -m "feat: Google Sign-In for staff accounts (web guard)"
```
