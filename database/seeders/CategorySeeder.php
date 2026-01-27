<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            // Income
            ['name' => 'Salary', 'type' => 'income', 'color' => 'bg-emerald-500'],
            ['name' => 'Freelance', 'type' => 'income', 'color' => 'bg-teal-500'],
            ['name' => 'Investment', 'type' => 'income', 'color' => 'bg-cyan-500'],

            // Expenses (including Savings & Investment for budgeting)
            ['name' => 'Investment', 'type' => 'expense', 'color' => 'bg-cyan-500'],
            ['name' => 'Saving', 'type' => 'expense', 'color' => 'bg-emerald-600'],

            // Expenses
            ['name' => 'Food & Drink', 'type' => 'expense', 'color' => 'bg-orange-500'],
            ['name' => 'Rent', 'type' => 'expense', 'color' => 'bg-rose-500'],
            ['name' => 'Transport', 'type' => 'expense', 'color' => 'bg-blue-500'],
            ['name' => 'Entertainment', 'type' => 'expense', 'color' => 'bg-purple-500'],
            ['name' => 'Utilities', 'type' => 'expense', 'color' => 'bg-yellow-500'],
            ['name' => 'Shopping', 'type' => 'expense', 'color' => 'bg-pink-500'],
            ['name' => 'Groceries', 'type' => 'expense', 'color' => 'bg-indigo-500'],
        ];

        foreach ($categories as $cat) {
            Category::firstOrCreate(
                [
                    'name' => $cat['name'],
                    'type' => $cat['type'],
                    'user_id' => null
                ],
                [
                    'color' => $cat['color'],
                    'is_active' => true,
                    'is_system' => true
                ]
            );
        }
    }
}
