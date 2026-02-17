<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Test', [
        'message' => 'Welcome to SecureCAT! Inertia + Svelte 5 is working!',
    ]);
});
