<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Transaction;
use Carbon\Carbon;

class TransactionSeeder extends Seeder
{
    public function run(): void
    {
        $cash = \App\Models\Wallet::where('name', 'Cash')->first();
        $bca = \App\Models\Wallet::where('name', 'BCA Main')->first();
        $gopay = \App\Models\Wallet::where('name', 'GoPay')->first();

        // Helper to get category ID
        $getCatId = fn($name) => \App\Models\Category::where('name', $name)->first()?->id;

        // Income
        Transaction::create([
            'type' => 'income',
            'amount' => 15000000,
            'category_id' => $getCatId('Salary'),
            'description' => 'Monthly Salary',
            'date' => Carbon::now()->startOfMonth(),
            'wallet_id' => $bca->id,
            'user_id' => $bca->user_id, // Ensure user_id is set
            'is_active' => true,
            'currency' => 'IDR',
            'amount_in_base_currency' => 15000000
        ]);

        Transaction::create([
            'type' => 'income',
            'amount' => 2000000,
            'category_id' => $getCatId('Freelance'),
            'description' => 'Freelance Project',
            'date' => Carbon::now()->subDays(5),
            'wallet_id' => $bca->id,
            'user_id' => $bca->user_id,
            'is_active' => true,
            'currency' => 'IDR',
            'amount_in_base_currency' => 2000000
        ]);

        // Expenses
        Transaction::create([
            'type' => 'expense',
            'amount' => 5000000,
            'category_id' => $getCatId('Rent'),
            'description' => 'Monthly Rent',
            'date' => Carbon::now()->startOfMonth()->addDay(),
            'wallet_id' => $bca->id,
            'user_id' => $bca->user_id,
            'is_active' => true,
            'currency' => 'IDR',
            'amount_in_base_currency' => 5000000
        ]);

        Transaction::create([
            'type' => 'expense',
            'amount' => 1200000,
            'category_id' => $getCatId('Food & Drink'), // Changed from Groceries
            'description' => 'Weekly Groceries',
            'date' => Carbon::now()->subDays(2),
            'wallet_id' => $cash->id,
            'user_id' => $cash->user_id,
            'is_active' => true,
            'currency' => 'IDR',
            'amount_in_base_currency' => 1200000
        ]);

        Transaction::create([
            'type' => 'expense',
            'amount' => 350000,
            'category_id' => $getCatId('Utilities'),
            'description' => 'Internet Bill', // Mapping Internet to Utilities or similar
            'date' => Carbon::now()->subDays(10),
            'wallet_id' => $gopay->id,
            'user_id' => $gopay->user_id,
            'is_active' => true,
            'currency' => 'IDR',
            'amount_in_base_currency' => 350000
        ]);
    }
}
