<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Check if admin already exists
        if (!User::where('email', 'admin@vibefinance.id')->exists()) {
            User::create([
                'name' => 'Super Admin',
                'email' => 'admin@vibefinance.id',
                'password' => Hash::make('password'),
                'is_admin' => true,
                'email_verified_at' => now(),
            ]);
        }
    }
}
