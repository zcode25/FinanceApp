<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Models\Category;
use App\Models\Budget;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SettingsController extends Controller
{
    public function index()
    {
        return Inertia::render('Settings/Index', [
            'user' => Auth::user(),
            'stats' => [
                'transactions' => Transaction::where('user_id', Auth::id())->count(),
                'wallets' => Wallet::where('user_id', Auth::id())->count(),
                'budgets' => Budget::where('user_id', Auth::id())->count(),
            ]
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg|max:1024', // 1MB Max, no GIFs
        ]);

        $user->name = $validated['name'];
        $user->email = $validated['email'];

        if ($request->hasFile('avatar')) {
            // Delete old avatar if exists
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }

            $path = $request->file('avatar')->store('avatars', 'public');
            $user->avatar = $path;
        }

        $user->save();

        return back()->with('success', 'Profile updated successfully.');
    }

    public function updatePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => 'required|current_password',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        return back()->with('success', 'Password updated successfully.');
    }

    public function updatePreferences(Request $request)
    {
        $validated = $request->validate([
            'locale' => 'required|in:en,id',
        ]);

        $request->user()->update([
            'locale' => $validated['locale'],
        ]);

        // Session locale update (optional depending on middleware)
        session(['locale' => $validated['locale']]);

        return back()->with('success', 'Preferences updated successfully.');
    }

    public function resetData(Request $request)
    {
        $request->validate([
            'password' => 'required|current_password',
        ]);

        DB::transaction(function () {
            $userId = Auth::id();
            Transaction::where('user_id', $userId)->delete();
            Budget::where('user_id', $userId)->delete();
            Wallet::where('user_id', $userId)->delete();
            // Optional: Reset categories to default? Or keep custom ones? 
            // "Reset Data: Clear transactions/wallets". Usually implies fresh start.
            Category::where('user_id', $userId)->delete();
        });

        return back()->with('success', 'All data has been reset.');
    }

    public function destroyAccount(Request $request)
    {
        $request->validate([
            'password' => 'required|current_password',
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }

    public function exportData()
    {
        $userId = Auth::id();

        $response = new StreamedResponse(function () use ($userId) {
            $handle = fopen('php://output', 'w');

            // Add BOM for Excel compatibility
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // Headers
            fputcsv($handle, ['ID', 'Date', 'Type', 'Category', 'Description', 'Amount', 'Wallet', 'Created At']);

            Transaction::where('user_id', $userId)
                ->with(['wallet'])
                ->chunk(100, function ($transactions) use ($handle) {
                    foreach ($transactions as $tx) {
                        fputcsv($handle, [
                            $tx->id,
                            $tx->date,
                            $tx->type,
                            $tx->category,
                            $tx->description,
                            $tx->amount,
                            $tx->wallet ? $tx->wallet->name : 'Deleted Wallet',
                            $tx->created_at,
                        ]);
                    }
                });

            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="transactions_export_' . date('Y-m-d') . '.csv"');

        return $response;
    }
}
