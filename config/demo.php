<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Demo Mode
    |--------------------------------------------------------------------------
    |
    | When DEMO=true, the DatabaseSeeder automatically runs DefenseDemoSeeder
    | after the foundation seeders, so a single `php artisan db:seed` gives
    | you a fully populated defense-ready database.
    |
    | Use `php artisan demo:setup` for a one-command fresh install.
    |
    */

    'enabled' => env('DEMO', false),

    /*
    |--------------------------------------------------------------------------
    | Demo Throttle Decay (seconds)
    |--------------------------------------------------------------------------
    |
    | In demo mode you may want to show the rate-limiter tripping quickly.
    | Set DEMO_THROTTLE_DECAY_SECONDS=15 to use a 15-second window instead
    | of the normal 15-minute window. Leave null to keep normal behaviour.
    |
    | Only applies when demo.enabled is true.
    |
    */

    'throttle_decay_seconds' => env('DEMO_THROTTLE_DECAY_SECONDS', null),

    /*
    |--------------------------------------------------------------------------
    | Demo Throttle Max Attempts
    |--------------------------------------------------------------------------
    |
    | Override the number of allowed attempts for demo throttle.
    | Defaults to the normal auth.login_throttle_attempts value.
    |
    */

    'throttle_attempts' => env('DEMO_THROTTLE_ATTEMPTS', null),

];
