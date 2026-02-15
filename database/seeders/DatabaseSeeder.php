<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Disable foreign key checks for truncation
        \Illuminate\Support\Facades\Schema::disableForeignKeyConstraints();

        $this->call([
            PlanSeeder::class,
            CategorySeeder::class,
            WalletSeeder::class,
            TransactionSeeder::class,
            AdminSeeder::class,
        ]);

        \Illuminate\Support\Facades\Schema::enableForeignKeyConstraints();

        // Ensure a test user exists
        if (!\App\Models\User::where('email', 'test@example.com')->exists()) {
            \App\Models\User::factory()->create([
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);
        }
    }
}
