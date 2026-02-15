<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $plans = [
            [
                'id' => 1,
                'name' => 'Starter',
                'price' => 0,
                'duration_type' => 'lifetime',
                'duration_value' => null,
            ],
            [
                'id' => 2,
                'name' => 'Professional',
                'price' => 25000,
                'duration_type' => 'month',
                'duration_value' => 1,
            ],
            [
                'id' => 3,
                'name' => 'Master',
                'price' => 249000,
                'duration_type' => 'year',
                'duration_value' => 1,
            ],
            [
                'id' => 4,
                'name' => 'Lifetime',
                'price' => 599000,
                'duration_type' => 'lifetime',
                'duration_value' => null,
            ],
        ];

        foreach ($plans as $plan) {
            Plan::updateOrCreate(['id' => $plan['id']], $plan);
        }
    }
}
