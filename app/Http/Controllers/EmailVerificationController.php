<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EmailVerificationController extends Controller
{
    /**
     * Display the email verification prompt.
     */
    public function notice(Request $request)
    {
        return $request->user()->hasVerifiedEmail()
            ? redirect()->intended(route('dashboard'))
            : Inertia::render('Auth/VerifyEmail', ['status' => session('status')]);
    }

    /**
     * Mark the authenticated user's email address as verified.
     */
    public function verify(EmailVerificationRequest $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended(route('dashboard') . '?verified=1');
        }

        if ($request->user()->markEmailAsVerified()) {
            event(new \Illuminate\Auth\Events\Verified($request->user()));
        }

        return redirect()->intended(route('dashboard') . '?verified=1');
    }

    /**
     * Send a new email verification notification.
     */
    public function resend(Request $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended(route('dashboard'));
        }

        try {
            $request->user()->sendEmailVerificationNotification();
        } catch (\Exception $e) {
            \Log::error('Resend Mail Error: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Failed to send verification email. Please try again later.']);
        }

        return back()->with('status', 'verification-link-sent');
    }
}
