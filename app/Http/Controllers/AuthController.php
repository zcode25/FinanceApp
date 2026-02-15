<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Illuminate\Auth\Events\Registered;

class AuthController extends Controller
{
    /**
     * Display the login view.
     */
    public function showLogin()
    {
        return Inertia::render('Auth/Login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ], [
            'email.required' => __('login_email_required'),
            'email.email' => __('email_invalid'),
            'password.required' => __('login_password_required'),
        ]);

        if (Auth::attempt(array_merge($credentials, ['is_active' => true]), $request->boolean('remember'))) {
            $request->session()->regenerate();

            $user = Auth::user();
            $user->update(['last_login_at' => now()]);

            // Hard Gate for Starter users (non-premium, unverified)
            if (!$user->is_premium && !$user->hasVerifiedEmail()) {
                return redirect()->route('verification.notice');
            }

            return redirect()->intended(route('dashboard'));
        }

        // Check if user exists but is inactive
        $user = User::where('email', $credentials['email'])->first();
        if ($user && !$user->is_active) {
            return back()->withErrors([
                'email' => __('account_deactivated'),
            ])->onlyInput('email');
        }

        return back()->withErrors([
            'email' => __('login_failed'),
        ])->onlyInput('email');
    }

    /**
     * Display the registration view.
     */
    public function showRegister()
    {
        $plans = \App\Models\Plan::all();
        return Inertia::render('Auth/Register', [
            'plans' => $plans,
        ]);
    }

    /**
     * Handle an incoming registration request.
     */
    public function register(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:' . User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ], [
            'name.required' => __('auth_name_required'),
            'email.required' => __('email_required'),
            'email.email' => __('email_invalid'),
            'email.unique' => __('email_unique'),
            'password.required' => __('password_required'),
            'password.confirmed' => __('password_confirmed_mismatch'),
            'password.min' => __('password_min_length'),
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        try {
            event(new Registered($user));
        } catch (\Exception $e) {
            \Log::error('Mail Error: ' . $e->getMessage());
        }

        Auth::login($user);

        // Update last login timestamp
        $user->update(['last_login_at' => now()]);

        // Optimized Journey: Directly to checkout for premium plans, bypass verification gate
        if ($request->plan && (int) $request->plan !== 1) {
            return redirect(route('subscription.checkout.index', ['plan' => $request->plan]));
        }

        // Starter users (plan ID 1) must verify email first (Hard Gate)
        return redirect(route('verification.notice'));
    }

    /**
     * Destroy an authenticated session.
     */
    public function logout(Request $request)
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
