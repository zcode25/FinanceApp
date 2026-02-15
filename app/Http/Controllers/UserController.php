<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    /**
     * Mark the product tour as completed for the authenticated user.
     */
    public function completeTour(Request $request)
    {
        $user = Auth::user();
        $user->update(['has_completed_tour' => true]);

        return back();
    }

    /**
     * Reset the product tour for the authenticated user.
     */
    public function resetTour(Request $request)
    {
        $user = Auth::user();
        $user->update(['has_completed_tour' => false]);

        return back();
    }
}
