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
        $query = Transaction::with(['wallet', 'category', 'targetWallet'])
            ->where('user_id', $request->user()->id)
            ->where('is_active', true);

        // Apply Filters
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhereHas('category', function ($cq) use ($search) {
                      $cq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('wallet_id')) {
            $walletId = $request->input('wallet_id');
            $query->where(function ($q) use ($walletId) {
                $q->where('wallet_id', $walletId)
                  ->orWhere('target_wallet_id', $walletId);
            });
        }

        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }

        if ($request->filled('start_date')) {
            $query->where('date', '>=', $request->input('start_date'));
        }

        if ($request->filled('end_date')) {
            $query->where('date', '<=', $request->input('end_date'));
        }

        $query->orderBy('date', 'desc')
            ->orderBy('created_at', 'desc');

        $exchangeRateService = app(ExchangeRateService::class);
        $currentRate = null;

        // Fetch rate only if user has USD wallets or if explicitly needed by frontend
        $hasUsdWallet = Wallet::where('user_id', $request->user()->id)
            ->where('currency', 'USD')
            ->where('is_active', true)
            ->exists();

        if ($hasUsdWallet) {
            $currentRate = $exchangeRateService->getCurrentRate('USD', 'IDR');
        }

        $perPage = $request->input('per_page', 10);
        $paginator = $query->paginate($perPage)->withQueryString();

        $walletIdFilter = $request->input('wallet_id');
        $items = $paginator->getCollection()->flatMap(function ($tx) use ($walletIdFilter) {
            $results = [];

            if ($tx->type === 'transfer') {
                $isSource = !$walletIdFilter || $tx->wallet_id == $walletIdFilter;
                $isTarget = !$walletIdFilter || $tx->target_wallet_id == $walletIdFilter;

                if ($isSource) {
                    $sourceTx = clone $tx;
                    $sourceTx->setAttribute('computed_type', 'transfer_out');
                    $results[] = $sourceTx;
                }
                
                if ($isTarget) {
                    $targetTx = clone $tx;
                    $targetTx->setAttribute('computed_type', 'transfer_in');
                    $results[] = $targetTx;
                }
            } else {
                $tx->setAttribute('computed_type', $tx->type);
                $results[] = $tx;
            }

            return $results;
        });
        
        $paginator->setCollection($items->values());

        return Inertia::render('Transactions/Index', [
            'transactions' => $paginator,
            'filters' => $request->only(['search', 'wallet_id', 'type', 'start_date', 'end_date', 'per_page']),
            'categories' => Category::forUser($request->user()->id)->get(),
            'wallets' => Wallet::where('user_id', $request->user()->id)->where('is_active', true)->get(),
            'currentExchangeRate' => $currentRate,
            'summary' => Inertia::defer(function () use ($request) {
                // Calculate summary for the USER, ignoring search/filters
                $summary = Transaction::where('user_id', $request->user()->id)
                    ->where('is_active', true)
                    ->select(
                        DB::raw('SUM(CASE WHEN type = "income" THEN amount_in_base_currency ELSE 0 END) as total_income'),
                        DB::raw('SUM(CASE WHEN type = "expense" THEN amount_in_base_currency WHEN type = "transfer" THEN fee ELSE 0 END) as total_expense'),
                        DB::raw('COUNT(CASE WHEN type = "income" THEN 1 END) as total_income_count'),
                        DB::raw('COUNT(CASE WHEN type = "expense" OR (type = "transfer" AND fee > 0) THEN 1 END) as total_expense_count')
                    )
                    ->first();

                $totalIncome = $summary->total_income ?? 0;
                $totalExpense = $summary->total_expense ?? 0;
                
                return [
                    'total_income' => (float) $totalIncome,
                    'total_expense' => (float) $totalExpense,
                    'total_income_count' => $summary->total_income_count ?? 0,
                    'total_expense_count' => $summary->total_expense_count ?? 0,
                    'net_balance' => (float) ($totalIncome - $totalExpense),
                ];
            }),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric',
            'type' => 'required|in:income,expense,transfer',
            'category' => 'nullable|string|required_unless:type,transfer', // Frontend sends name
            'date' => 'required|date',
            'description' => 'nullable|string',
            'wallet_id' => 'required|exists:wallets,id',
            'exchange_rate' => 'nullable|numeric',
            'target_wallet_id' => 'nullable|exists:wallets,id|required_if:type,transfer|different:wallet_id',
            'fee' => 'nullable|numeric|min:0',
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
            if ($validated['type'] !== 'transfer') {
                $categoryName = $validated['category'];
                $category = Category::forUser($request->user()->id)
                    ->where('name', $categoryName)
                    ->where('type', $validated['type'])
                    ->first();

                if (!$category) {
                    // Check limit for non-premium users before creating dynamic category
                    /** @var \App\Models\User $user */
                    $user = $request->user();
                    $customCategoryCount = Category::where('user_id', $user?->id)->count();

                    if (!$user?->is_premium && $customCategoryCount >= 3) {
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
            } else {
                $validated['category_id'] = null;
                unset($validated['category']); // Remove string field
            }

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
            } elseif ($validated['type'] === 'expense') {
                $wallet->decrement('balance', $validated['amount']);
            } elseif ($validated['type'] === 'transfer') {
                $feeAmount = $validated['fee'] ?? 0;
                $wallet->decrement('balance', $validated['amount'] + $feeAmount);
                $targetWallet = Wallet::find($validated['target_wallet_id']);
                if ($targetWallet) {
                    $targetWallet->increment('balance', $validated['amount']);
                }
            }
        });

        return redirect()->back();
    }

    public function update(Request $request, Transaction $transaction)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric',
            'type' => 'required|in:income,expense,transfer',
            'category' => 'nullable|string|required_unless:type,transfer',
            'date' => 'required|date',
            'description' => 'nullable|string',
            'wallet_id' => 'required|exists:wallets,id',
            'exchange_rate' => 'nullable|numeric',
            'target_wallet_id' => 'nullable|exists:wallets,id|required_if:type,transfer|different:wallet_id',
            'fee' => 'nullable|numeric|min:0',
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
            } elseif ($transaction->type === 'expense') {
                $oldWallet->increment('balance', $transaction->amount);
            } elseif ($transaction->type === 'transfer') {
                $oldFee = $transaction->fee ?? 0;
                $oldWallet->increment('balance', $transaction->amount + $oldFee);
                $oldTarget = Wallet::find($transaction->target_wallet_id);
                if ($oldTarget) {
                    $oldTarget->decrement('balance', $transaction->amount);
                }
            }

            // 2. Resolve Category ID
            if ($validated['type'] !== 'transfer') {
                $categoryName = $validated['category'];
                $category = Category::forUser($transaction->user_id)
                    ->where('name', $categoryName)
                    ->where('type', $validated['type'])
                    ->first();

                if (!$category) {
                    // Check limit for non-premium users
                    /** @var \App\Models\User $user */
                    $user = $request->user();
                    $customCategoryCount = Category::where('user_id', $user?->id)->count();

                    if (!$user?->is_premium && $customCategoryCount >= 3) {
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
            } else {
                $validated['category_id'] = null;
            }
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
            } elseif ($validated['type'] === 'expense') {
                $newWallet->decrement('balance', $validated['amount']);
            } elseif ($validated['type'] === 'transfer') {
                $feeAmount = $validated['fee'] ?? 0;
                $newWallet->decrement('balance', $validated['amount'] + $feeAmount);
                $newTarget = Wallet::find($validated['target_wallet_id']);
                if ($newTarget) {
                    $newTarget->increment('balance', $validated['amount']);
                }
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
            } elseif ($transaction->type === 'expense') {
                $wallet->increment('balance', $transaction->amount);
            } elseif ($transaction->type === 'transfer') {
                $feeAmount = $transaction->fee ?? 0;
                $wallet->increment('balance', $transaction->amount + $feeAmount);
                $targetWallet = Wallet::find($transaction->target_wallet_id);
                if ($targetWallet) {
                    $targetWallet->decrement('balance', $transaction->amount);
                }
            }

            // Soft delete/Deactivate
            $transaction->update(['is_active' => false]);
        });

        return redirect()->back();
    }
}
