<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfStarterAndUnverified
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // 1. Bypass if premium (Soft Gate)
        if ($user && $user->is_premium) {
            return $next($request);
        }

        // 2. Bypass if just came from successful checkout (Race condition protection)
        if ($request->has('upgrade_success') || session('premium_bypass')) {
            session(['premium_bypass' => true]);
            return $next($request);
        }

        // 3. Hard Gate for Starter users or unverified
        if ($user && !$user->hasVerifiedEmail()) {
            return redirect()->route('verification.notice');
        }

        return $next($request);
    }
}
