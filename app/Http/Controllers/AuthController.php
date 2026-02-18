<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class AuthController extends Controller
{
    /**
     * Show the login form.
     */
    public function create(): Response|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return Inertia::render('Auth/Login');
    }

    /**
     * Handle a login request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $remember = (bool) ($validated['remember'] ?? false);

        if (Auth::attempt(
            ['email' => $validated['email'], 'password' => $validated['password']],
            $remember
        )) {
            $request->session()->regenerate();

            Log::info('User login', [
                'user_id' => Auth::id(),
                'ip' => $request->ip(),
                'success' => true,
            ]);

            return redirect()->intended(route('dashboard'));
        }

        Log::info('User login failed', [
            'email' => $validated['email'],
            'ip' => $request->ip(),
            'success' => false,
        ]);

        return back()->withErrors([
            'email' => 'These credentials do not match our records.',
        ])->onlyInput('email');
    }

    /**
     * Handle a logout request.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $userId = Auth::id();

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        Log::info('User logout', ['user_id' => $userId]);

        return redirect()->route('login');
    }
}
