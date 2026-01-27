<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Wallet;
use Illuminate\Validation\Rule;
use App\Services\ExchangeRateService;

class WalletController extends Controller
{
    public function index()
    {
        $exchangeRateService = app(ExchangeRateService::class);
        $currentRate = $exchangeRateService->getCurrentRate('USD', 'IDR');

        return Inertia::render('Wallets/Index', [
            'wallets' => Wallet::where('user_id', auth()->id())->latest()->get(),
            'currentExchangeRate' => $currentRate,
        ]);
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

        // Add default color if not present or let it handle in frontend/random
        $validated['color'] = $this->getRandomColor();
        $validated['user_id'] = auth()->id();

        Wallet::create($validated);

        return redirect()->back();
    }

    public function update(Request $request, Wallet $wallet)
    {
        if ($wallet->user_id !== auth()->id()) {
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

    public function toggle(Wallet $wallet)
    {
        if ($wallet->user_id !== auth()->id()) {
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
