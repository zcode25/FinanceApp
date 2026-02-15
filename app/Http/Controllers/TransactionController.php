<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Transaction;
use App\Models\Category;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;
use App\Services\ExchangeRateService;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        // Base query for the transaction list (will be filtered)
        $query = Transaction::with(['wallet', 'category'])
            ->where('user_id', $request->user()->id)
            ->where('is_active', true)
            ->orderBy('date', 'desc')
            ->orderBy('created_at', 'desc');

        // Summary calculations (unaffected by search/filters)
        $summaryQuery = Transaction::where('user_id', $request->user()->id)
            ->where('is_active', true);

        $totalIncome = (clone $summaryQuery)->where('type', 'income')->sum('amount_in_base_currency');
        $totalExpense = (clone $summaryQuery)->where('type', 'expense')->sum('amount_in_base_currency');
        $totalIncomeCount = (clone $summaryQuery)->where('type', 'income')->count();
        $totalExpenseCount = (clone $summaryQuery)->where('type', 'expense')->count();

        // Apply filters only to the list query
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                    ->orWhereHas('category', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('wallet_id')) {
            $query->where('wallet_id', $request->input('wallet_id'));
        }

        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }

        if ($request->filled('start_date')) {
            $query->whereDate('date', '>=', $request->input('start_date'));
        }

        if ($request->filled('end_date')) {
            $query->whereDate('date', '<=', $request->input('end_date'));
        }

        $exchangeRateService = app(ExchangeRateService::class);
        $currentRate = $exchangeRateService->getCurrentRate('USD', 'IDR');

        $perPage = $request->input('per_page', 10);

        return Inertia::render('Transactions/Index', [
            'transactions' => $query->paginate($perPage)->withQueryString(),
            'filters' => $request->only(['search', 'wallet_id', 'type', 'start_date', 'end_date', 'per_page']),
            'categories' => Category::forUser($request->user()->id)->get(),
            'wallets' => Wallet::where('user_id', $request->user()->id)->where('is_active', true)->get(),
            'currentExchangeRate' => $currentRate,
            'summary' => [
                'total_income' => (float) $totalIncome,
                'total_expense' => (float) $totalExpense,
                'total_income_count' => $totalIncomeCount,
                'total_expense_count' => $totalExpenseCount,
                'net_balance' => (float) ($totalIncome - $totalExpense),
            ]
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric',
            'type' => 'required|in:income,expense',
            'category' => 'required|string', // Frontend sends name
            'date' => 'required|date',
            'description' => 'nullable|string',
            'wallet_id' => 'required|exists:wallets,id',
            'exchange_rate' => 'nullable|numeric',
        ], [
            'amount.required' => __('amount_required'),
            'amount.numeric' => __('amount_numeric'),
            'type.required' => __('type_required'),
            'type.in' => __('type_in'),
            'category.required' => __('category_required'),
            'date.required' => __('date_required'),
            'date.date' => __('date_date'),
            'wallet_id.required' => __('wallet_id_required'),
            'wallet_id.exists' => __('wallet_id_exists'),
            'exchange_rate.numeric' => __('exchange_rate_numeric'),
        ]);

        DB::transaction(function () use ($validated, $request) {
            $wallet = Wallet::where('user_id', $request->user()->id)->findOrFail($validated['wallet_id']);
            $exchangeRateService = app(ExchangeRateService::class);

            // Resolve Category ID
            $categoryName = $validated['category'];
            $category = Category::forUser($request->user()->id)
                ->where('name', $categoryName)
                ->where('type', $validated['type'])
                ->first();

            if (!$category) {
                // Check limit for non-premium users before creating dynamic category
                $user = $request->user();
                $customCategoryCount = Category::where('user_id', $user->id)->count();

                if (!$user->is_premium && $customCategoryCount >= 3) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'premium' => 'You have reached the limit of 3 custom categories. Upgrade to Professional to add unlimited categories.'
                    ]);
                }

                // Create new User Category
                $category = Category::create([
                    'name' => $categoryName,
                    'type' => $validated['type'],
                    'color' => 'bg-gray-500',
                    'user_id' => $user->id,
                ]);
            }

            $validated['category_id'] = $category->id;
            unset($validated['category']); // Remove string field

            // Handle exchange rate and conversion
            $validated['currency'] = $wallet->currency;
            if ($wallet->currency !== 'IDR') {
                $validated['exchange_rate_date'] = now();
                $validated['amount_in_base_currency'] = $exchangeRateService->toBaseCurrency(
                    $validated['amount'],
                    $wallet->currency,
                    $validated['exchange_rate'] ?? null
                );
            } else {
                $validated['amount_in_base_currency'] = $validated['amount'];
            }

            // Create Transaction
            $validated['user_id'] = $request->user()->id;
            $transaction = Transaction::create($validated);

            // Update Wallet Balance
            if ($validated['type'] === 'income') {
                $wallet->increment('balance', $validated['amount']);
            } else {
                $wallet->decrement('balance', $validated['amount']);
            }
        });

        return redirect()->back();
    }

    public function update(Request $request, Transaction $transaction)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric',
            'type' => 'required|in:income,expense',
            'category' => 'required|string',
            'date' => 'required|date',
            'description' => 'nullable|string',
            'wallet_id' => 'required|exists:wallets,id',
            'exchange_rate' => 'nullable|numeric',
        ], [
            'amount.required' => __('amount_required'),
            'amount.numeric' => __('amount_numeric'),
            'type.required' => __('type_required'),
            'type.in' => __('type_in'),
            'category.required' => __('category_required'),
            'date.required' => __('date_required'),
            'date.date' => __('date_date'),
            'wallet_id.required' => __('wallet_id_required'),
            'wallet_id.exists' => __('wallet_id_exists'),
            'exchange_rate.numeric' => __('exchange_rate_numeric'),
        ]);

        DB::transaction(function () use ($validated, $transaction, $request) {
            $exchangeRateService = app(ExchangeRateService::class);

            // 1. Revert old transaction impact
            $oldWallet = Wallet::find($transaction->wallet_id);
            if ($transaction->type === 'income') {
                $oldWallet->decrement('balance', $transaction->amount);
            } else {
                $oldWallet->increment('balance', $transaction->amount);
            }

            // 2. Resolve Category ID
            $categoryName = $validated['category'];
            $category = Category::forUser($transaction->user_id)
                ->where('name', $categoryName)
                ->where('type', $validated['type'])
                ->first();

            if (!$category) {
                // Check limit for non-premium users
                $user = $request->user();
                $customCategoryCount = Category::where('user_id', $user->id)->count();

                if (!$user->is_premium && $customCategoryCount >= 3) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'premium' => 'You have reached the limit of 3 custom categories. Upgrade to Professional to add unlimited categories.'
                    ]);
                }

                $category = Category::create([
                    'name' => $categoryName,
                    'type' => $validated['type'],
                    'color' => 'bg-gray-500',
                    'user_id' => $user->id // Use authenticated user ID
                ]);
            }

            $validated['category_id'] = $category->id;
            unset($validated['category']); // Remove string field

            // 3. Handle exchange rate and conversion
            $newWallet = Wallet::find($validated['wallet_id']);
            $validated['currency'] = $newWallet->currency;
            if ($newWallet->currency !== 'IDR') {
                $validated['exchange_rate_date'] = now();
                $validated['amount_in_base_currency'] = $exchangeRateService->toBaseCurrency(
                    $validated['amount'],
                    $newWallet->currency,
                    $validated['exchange_rate'] ?? null
                );
            } else {
                $validated['amount_in_base_currency'] = $validated['amount'];
            }

            // 4. Update Transaction
            $transaction->update($validated);

            // 4. Apply new transaction impact
            $newWallet = Wallet::find($validated['wallet_id']);
            if ($validated['type'] === 'income') {
                $newWallet->increment('balance', $validated['amount']);
            } else {
                $newWallet->decrement('balance', $validated['amount']);
            }
        });

        return redirect()->back();
    }

    public function destroy(Transaction $transaction)
    {
        DB::transaction(function () use ($transaction) {
            // Revert wallet impact
            $wallet = Wallet::find($transaction->wallet_id);
            if ($transaction->type === 'income') {
                $wallet->decrement('balance', $transaction->amount);
            } else {
                $wallet->increment('balance', $transaction->amount);
            }

            // Soft delete/Deactivate
            $transaction->update(['is_active' => false]);
        });

        return redirect()->back();
    }
}
