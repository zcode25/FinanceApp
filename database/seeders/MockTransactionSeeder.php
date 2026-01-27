<?php

namespace Database\Seeders;

use App\Models\Transaction;
use App\Models\Wallet;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class MockTransactionSeeder extends Seeder
{
    public function run(): void
    {
        $wallets = Wallet::all();
        if ($wallets->isEmpty()) {
            return;
        }

        // Fetch real categories from DB
        $categories = \App\Models\Category::all();
        $incomeCategories = $categories->where('type', 'income')->values();
        $expenseCategories = $categories->where('type', 'expense')->values();

        if ($incomeCategories->isEmpty() || $expenseCategories->isEmpty()) {
            return; // Cannot mock without categories
        }

        $descriptions = [
            'Salary' => ['Monthly Salary', 'Company Payroll'],
            'Bonus' => ['Quarterly Bonus', 'Performance Reward'],
            'Food & Drink' => ['Lunch at Padang', 'Starbucks Coffee', 'Dinner with Family', 'Grocery Shopping (Super Indo)'],
            'Transportation' => ['Grab Ride', 'Fuel (Pertamax)', 'Parking Fee', 'Train Ticket'],
            'Shopping' => ['Uniqlo T-Shirt', 'Shopee Order', 'Tokopedia Electronics'],
            'Utilities' => ['Electricity Bill (PLN)', 'Water Bill', 'Internet (Indihome)'],
            'Rent' => ['Monthly Rent Payment'],
            'Entertainment' => ['Netflix Subscription', 'Cinema Ticket', 'Taman Safari Entry'],
        ];

        $startDate = Carbon::create(2025, 11, 1);
        $endDate = Carbon::create(2026, 1, 21);

        for ($i = 0; $i < 100; $i++) {
            $type = rand(0, 100) < 15 ? 'income' : 'expense';

            // Pick random category model
            $categoryModel = $type === 'income'
                ? $incomeCategories->random()
                : $expenseCategories->random();

            $categoryName = $categoryModel->name;

            $wallet = $wallets->random();
            $date = Carbon::createFromTimestamp(rand($startDate->timestamp, $endDate->timestamp));

            // Realistic amounts based on category
            $amount = $this->getRandomAmount($categoryName, $type);

            $exchangeRate = 1;
            if ($wallet->currency === 'USD') {
                $exchangeRate = 16250.00;
            }

            Transaction::create([
                'wallet_id' => $wallet->id,
                'user_id' => $wallet->user_id, // Ensure ownership matches wallet
                'type' => $type,
                'amount' => $amount,
                'category_id' => $categoryModel->id,
                'description' => isset($descriptions[$categoryName]) ? $descriptions[$categoryName][array_rand($descriptions[$categoryName])] : "Regular $categoryName",
                'date' => $date,
                'currency' => $wallet->currency,
                'exchange_rate' => $exchangeRate,
                'exchange_rate_date' => $date,
                'amount_in_base_currency' => $amount * $exchangeRate,
                'is_active' => true,
            ]);
        }
    }

    private function getRandomAmount($category, $type)
    {
        if ($type === 'income') {
            return match ($category) {
                'Salary' => rand(15000000, 25000000),
                'Bonus' => rand(5000000, 10000000),
                default => rand(500000, 2000000),
            };
        }

        return match ($category) {
            'Rent' => rand(3000000, 5000000),
            'Utilities' => rand(500000, 1500000),
            'Food & Drink' => rand(50000, 350000),
            'Transportation' => rand(20000, 200000),
            'Shopping' => rand(100000, 2000000),
            default => rand(50000, 500000),
        };
    }
}
