<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Wallet;
use Illuminate\Validation\Rule;
use App\Services\ExchangeRateService;
use Illuminate\Support\Facades\Log;

class WalletController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $exchangeRateService = app(ExchangeRateService::class);
        $currentRate = null;

        $hasUsdWallet = Wallet::where('user_id', $user?->id)
            ->where('currency', 'USD')
            ->where('is_active', true)
            ->exists();

        if ($hasUsdWallet) {
            $currentRate = $exchangeRateService->getCurrentRate('USD', 'IDR');
        }

        return Inertia::render('Wallets/Index', [
            'wallets' => Wallet::where('user_id', $user?->id)
                ->orderBy('sort_order', 'asc')
                ->latest()
                ->get(),
            'currentExchangeRate' => $currentRate,
        ]);
    }

    public function reorder(Request $request)
    {
        Log::info('Reorder Request Payload:', $request->all());

        $validated = $request->validate([
            'wallets' => 'required|array',
            'wallets.*.id' => 'required|exists:wallets,id',
            'wallets.*.sort_order' => 'required|integer',
        ]);

        $user = $request->user();

        foreach ($request->wallets as $walletData) {
            Log::info("Updating Wallet ID: {$walletData['id']} to Order: {$walletData['sort_order']}");

            Wallet::where('id', $walletData['id'])
                ->where('user_id', $user?->id)
                ->update(['sort_order' => $walletData['sort_order']]);
        }

        return redirect()->back();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:cash,bank,ewallet',
            'currency' => 'required|in:IDR,USD',
            'balance' => 'required|numeric',
            'account_number' => 'nullable|string|max:255',
            'bank_name' => 'nullable|string|max:255',
        ]);

        $user = $request->user();
        $walletCount = Wallet::where('user_id', $user?->id)->count();

        if (!$user->is_premium && $walletCount >= 3) {
            return redirect()->back()->withErrors([
                'premium' => 'You have reached the limit of 3 wallets. Upgrade to Professional to add unlimited wallets.'
            ]);
        }

        // Add default color if not present or let it handle in frontend/random
        $validated['color'] = $this->getRandomColor();
        $validated['user_id'] = $user?->id;

        // Set new wallet to be last
        $maxOrder = Wallet::where('user_id', $user?->id)->max('sort_order');
        $validated['sort_order'] = $maxOrder ? $maxOrder + 1 : 0;

        Wallet::create($validated);

        return redirect()->back();
    }

    public function update(Request $request, Wallet $wallet)
    {
        if ($wallet->user_id !== $request->user()?->id) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:cash,bank,ewallet',
            // Currency and Balance are immutable during update
            'account_number' => 'nullable|string|max:255',
            'bank_name' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ]);

        $wallet->update($validated);

        return redirect()->back();
    }

    public function toggle(Request $request, Wallet $wallet)
    {
        if ($wallet->user_id !== $request->user()?->id) {
            abort(403);
        }

        $wallet->update([
            'is_active' => !$wallet->is_active
        ]);

        return redirect()->back();
    }

    private function getRandomColor()
    {
        $colors = ['bg-emerald-500', 'bg-blue-500', 'bg-indigo-500', 'bg-purple-500', 'bg-rose-500', 'bg-orange-500', 'bg-cyan-500'];
        return $colors[array_rand($colors)];
    }
}
