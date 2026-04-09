# Google Sign-In Architecture — SecureCAT-v2

**Date:** 2026-04-09  
**Status:** Approved  
**Author:** David Datu Sarmiento

---

## 1. Goals & Non-Goals

### Goals
- Allow staff/admin to sign in with Google as an alternative to email/password
- Admin-first: no orphan users — staff accounts must already exist with a matching Gmail as `email`
- Feature is silently disabled (routes not registered, button not shown) when Google OAuth env vars are not set — works fine with no config locally
- Email + password login remains as fallback, always

### Non-Goals
- No unlink UI — admin manages credential reset by updating `email` on the user account
- No self-service account creation via Google
- No applicant portal Google sign-in (Phase 2, separate spec)
- No Google Workspace domain restriction

---

## 2. Scope

Targets the **`web` guard only** (staff: super_admin, admin, proctor, test_administrator, staff).  
The `applicant` guard (`/portal`) is out of scope.

---

## 3. Data Model

### 3.1 New table: `user_credentials`

```php
Schema::create('user_credentials', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->string('provider');           // 'google' (extensible)
    $table->string('identifier');         // Google sub (unique account ID)
    $table->string('secret')->nullable(); // null for OAuth providers
    $table->timestamps();

    $table->unique(['provider', 'identifier']); // one sub globally per provider
    $table->unique(['user_id', 'provider']);     // one credential per provider per user
});
```

### 3.2 New model: `UserCredential`

```php
class UserCredential extends Model
{
    const PROVIDER_GOOGLE = 'google';
    protected $fillable = ['user_id', 'provider', 'identifier', 'secret'];
}
```

### 3.3 User model additions

```php
protected $fillable = ['name', 'email', 'password']; // unchanged — email IS the Gmail

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

### 3.4 No schema changes to `users`

The existing `email` field is the anchor. No `recovery_gmail` needed.

### 3.5 Email-change side effect

When admin updates a staff member's `email` in `UserController::update()`, any existing Google credential for that user is automatically deleted. Next Google sign-in with the new Gmail auto-creates a fresh credential link.

---

## 4. Routes

Registered **only when** `GoogleOAuthConfig::isConfigured()` returns true:

```php
if (\App\Support\GoogleOAuthConfig::isConfigured()) {
    Route::middleware('guest')->group(function () {
        Route::get('/auth/google', [GoogleAuthController::class, 'redirect'])
            ->name('auth.google.redirect');
    });

    Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])
        ->name('auth.google.callback');
}
```

| Route | Intent | Middleware |
|---|---|---|
| `GET /auth/google` | Redirect to Google OAuth | `guest` |
| `GET /auth/google/callback` | Handle Google's response | none |

No link/unlink routes needed.

---

## 5. `GoogleOAuthConfig` Support Class

```php
// app/Support/GoogleOAuthConfig.php

class GoogleOAuthConfig
{
    public static function isConfigured(): bool
    {
        return ! empty(config('services.google.client_id'))
            && ! empty(config('services.google.client_secret'));
    }
}
```

Single gate for route registration, Inertia shared data, and UI rendering.

---

## 6. `GoogleOAuthUserResolver` Service

```php
// app/Services/GoogleOAuthUserResolver.php

public function findOrAttachUser(SocialiteUser $socialUser): ?User
{
    // 1. Already linked — find by Google sub (fast path for returning users)
    $credential = UserCredential::where('provider', UserCredential::PROVIDER_GOOGLE)
        ->where('identifier', $socialUser->getId())
        ->first();

    if ($credential) {
        return $credential->user;
    }

    // 2. First-time — find user by email match, auto-create credential
    $googleEmail = strtolower(trim($socialUser->getEmail()));
    $user = User::whereRaw('LOWER(TRIM(email)) = ?', [$googleEmail])->first();

    if (! $user) {
        return null; // No matching staff account
    }

    UserCredential::create([
        'user_id'    => $user->id,
        'provider'   => UserCredential::PROVIDER_GOOGLE,
        'identifier' => $socialUser->getId(),
    ]);

    return $user;
}
```

Returns `null` for unrecognized Google accounts — controller handles the error redirect.

---

## 7. `GoogleAuthController`

```php
// app/Http/Controllers/Auth/GoogleAuthController.php

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
```

---

## 8. Shared Inertia Data

Add to `HandleInertiaRequests::share()`:

```php
'googleOAuthEnabled' => \App\Support\GoogleOAuthConfig::isConfigured(),
```

Used by the login page to conditionally render the Google button. No per-user `googleLinked` flag needed since there's no unlink UI.

---

## 9. Login Page (`Login.svelte`)

Google button appears on the **staff tab only**, only when `googleOAuthEnabled` is true:

```svelte
{#if googleOAuthEnabled}
  <!-- divider -->
  <a href="/auth/google" class="btn-outline w-full ...">
    <!-- Google G SVG icon -->
    Sign in with Google
  </a>
{/if}
```

Plain `<a>` tag (GET redirect) — no form submit, no CSRF token needed.

---

## 10. Admin Users Edit — Google Linked Badge

`Admin/Users/Edit.svelte` shows a read-only badge indicating whether the user has a Google credential linked. Useful for admin to know if a re-link is needed after an email change.

Pass from `UserController::edit()`:

```php
'googleLinked' => $user->hasGoogleLinked(),
```

---

## 11. Environment & Config

### `.env` additions (production only — leave empty locally)
```ini
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REDIRECT_URI="${APP_URL}/auth/google/callback"
```

### `config/services.php`
```php
'google' => [
    'client_id'     => env('GOOGLE_CLIENT_ID'),
    'client_secret' => env('GOOGLE_CLIENT_SECRET'),
    'redirect'      => env('GOOGLE_REDIRECT_URI'),
],
```

### Composer
```bash
composer require laravel/socialite
```

---

## 12. File Checklist

```
New files:
  app/Http/Controllers/Auth/GoogleAuthController.php
  app/Services/GoogleOAuthUserResolver.php
  app/Support/GoogleOAuthConfig.php
  app/Models/UserCredential.php
  database/migrations/XXXX_create_user_credentials_table.php

Modified files:
  app/Models/User.php                             — credentials(), hasGoogleLinked()
  app/Http/Middleware/HandleInertiaRequests.php   — googleOAuthEnabled shared prop
  app/Http/Controllers/Admin/UserController.php  — delete credential on email change; pass googleLinked to edit
  routes/web.php                                  — conditional Google OAuth routes
  config/services.php                             — google key
  resources/js/Pages/Auth/Login.svelte            — Google button on staff tab
  resources/js/Pages/Admin/Users/Edit.svelte      — Google linked badge
```

---

## 13. Security Notes

1. Email comparison uses `LOWER(TRIM())` — prevents case-sensitivity bypass
2. `UNIQUE(provider, identifier)` prevents one Google account linking to two users
3. No orphan creation — unrecognised Google accounts are rejected with a user-facing error
4. Email + password fallback always available — Google OAuth outage never locks anyone out
5. Credential auto-deleted on email change — prevents stale Google sub from accessing a reassigned account
