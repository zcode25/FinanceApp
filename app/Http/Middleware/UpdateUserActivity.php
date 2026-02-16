<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class UpdateUserActivity
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $user = Auth::user();

            // Throttle: Update only if last_login_at is null or older than 24 hours
            if (!$user->last_login_at || $user->last_login_at->diffInHours(now()) >= 24) {
                $user->update([
                    'last_login_at' => now(),
                ]);
            }
        }

        return $next($request);
    }
}
