<?php

use App\Http\Controllers\Admin\RoomController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Home', [
        'systemName' => 'SecureCAT',
    ]);
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store'])->middleware('throttle:login');
});

Route::get('/apply', [ApplicationController::class, 'create'])->name('applications.create');
Route::post('/applications', [ApplicationController::class, 'store'])->name('applications.store');
Route::get('/apply/success', [ApplicationController::class, 'success'])->name('applications.success');

Route::middleware(['auth'])->group(function () {
    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard', [
            'user' => request()->user(),
            'stats' => null,
        ]);
    })->name('dashboard');

    Route::middleware('role:super_admin')->prefix('admin')->name('admin.')->group(function () {
        Route::resource('users', UserController::class)->except('show')->parameters(['users' => 'user']);
    });

    Route::middleware('role:super_admin,admin')->prefix('admin')->name('admin.')->group(function () {
        Route::resource('rooms', RoomController::class)->except('show')->parameters(['rooms' => 'room']);
    });

    Route::get('/applications', [ApplicationController::class, 'index'])->name('applications.index')->middleware('role:super_admin,staff,admin,counselor');
    Route::get('/applications/{application}', [ApplicationController::class, 'show'])->name('applications.show')->middleware('role:super_admin,staff,admin,counselor');
    Route::get('/admin/exam-sessions', fn () => Inertia::render('Admin/ExamSessions/Index', ['title' => 'Exam Sessions', 'description' => 'Schedule and manage exam sessions.']))->middleware('role:super_admin,admin');
    Route::get('/admin/proctors', fn () => Inertia::render('Admin/Proctors/Index', ['title' => 'Proctors', 'description' => 'Manage proctors.']))->middleware('role:super_admin,admin');
    Route::get('/proctor', fn () => Inertia::render('Proctor/Dashboard', ['title' => 'My Sessions', 'description' => 'View assigned exam sessions.']))->middleware('role:super_admin,admin,proctor');
    Route::get('/grading', fn () => Inertia::render('Grading/Dashboard', ['title' => 'Grading', 'description' => 'Input and manage exam scores.']))->middleware('role:super_admin,grader');
    Route::get('/consultation', fn () => Inertia::render('Consultation/Dashboard', ['title' => 'Consultation', 'description' => 'Review scores and release consultations.']))->middleware('role:super_admin,counselor');
    Route::get('/admin/courses', fn () => Inertia::render('Admin/Courses/Index', ['title' => 'Courses', 'description' => 'Manage courses.']))->middleware('role:super_admin,admin');
    Route::get('/admin/settings', fn () => Inertia::render('Placeholder', ['title' => 'Settings', 'description' => 'System settings.']))->middleware('role:super_admin');
});
