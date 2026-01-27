<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Wallet;

class WalletSeeder extends Seeder
{
    public function run(): void
    {
        Wallet::create([
            'name' => 'Cash',
            'type' => 'cash',
            'balance' => 0,
            'color' => 'bg-green-500',
        ]);

        Wallet::create([
            'name' => 'BCA Main',
            'type' => 'bank',
            'balance' => 15000000,
            'account_number' => '1234567890',
            'bank_name' => 'BCA',
            'color' => 'bg-blue-500',
        ]);

        Wallet::create([
            'name' => 'GoPay',
            'type' => 'ewallet',
            'balance' => 250000,
            'bank_name' => 'GoPay',
            'color' => 'bg-cyan-500',
        ]);

        Wallet::create([
            'name' => 'USD Account',
            'type' => 'bank',
            'balance' => 1000,
            'currency' => 'USD',
            'account_number' => '9876543210',
            'bank_name' => 'International Bank',
            'color' => 'bg-purple-500',
        ]);
    }
}
