<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CheckSubscriptionStatus
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            /** @var \App\Models\User $user */
            $user = Auth::user();

            // Only check if user is currently marked as premium
            if ($user->is_premium) {
                // If subscription_until is NULL, it's a Lifetime plan (no expiry)
                if ($user->subscription_until !== null) {
                    // Check if the current time has passed the subscription end date
                    if ($user->subscription_until->isPast()) {
                        Log::info("User subscription expired. Downgrading to Starter.", [
                            'user_id' => $user->id,
                            'expired_at' => $user->subscription_until->toDateTimeString(),
                        ]);

                        // Automatically downgrade the user
                        $user->update([
                            'is_premium' => false,
                        ]);

                        // Optional: Add a flash message for the next request
                        // Since this is middleware, we might want to notify the user
                        // session()->flash('warning', __('subscription_expired_notice'));
                    }
                }
            }
        }

        return $next($request);
    }
}
