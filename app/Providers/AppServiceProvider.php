<?php

namespace App\Providers;

use App\Models\ExamSession;
use App\Policies\ExamSessionPolicy;
use Illuminate\Auth\Middleware\RedirectIfAuthenticated;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(ExamSession::class, ExamSessionPolicy::class);

        RedirectIfAuthenticated::redirectUsing(fn () => route('dashboard'));

        RateLimiter::for('login', function (Request $request) {
            if (! config('auth.login_throttle_enabled', true)) {
                return Limit::none();
            }

            $attempts = (int) config('auth.login_throttle_attempts', 5);
            $decayMinutes = (int) config('auth.login_throttle_decay_minutes', 15);

            return Limit::perMinutes($decayMinutes, $attempts)
                ->by($request->ip())
                ->response(fn () => back()->withErrors([
                    'email' => "Too many login attempts. Please try again in {$decayMinutes} minutes.",
                ]));
        });
    }
}
