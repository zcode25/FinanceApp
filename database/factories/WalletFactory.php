<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Wallet>
 */
class WalletFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $types = ['cash', 'bank', 'ewallet'];
        $banks = ['BCA', 'Mandiri', 'BNI', 'BRI', 'CIMB Niaga'];
        $ewallets = ['GoPay', 'OVO', 'Dana', 'ShopeePay'];

        $type = $this->faker->randomElement($types);
        $bankName = null;

        if ($type === 'bank') {
            $bankName = $this->faker->randomElement($banks);
        } elseif ($type === 'ewallet') {
            $bankName = $this->faker->randomElement($ewallets);
        }

        return [
            'name' => $bankName ? $bankName . ' ' . $this->faker->word : $this->faker->word,
            'type' => $type,
            'balance' => $this->faker->numberBetween(100000, 10000000),
            'currency' => 'IDR',
            'account_number' => $type !== 'cash' ? $this->faker->bankAccountNumber : null,
            'bank_name' => $bankName,
            'color' => $this->faker->randomElement(['bg-blue-500', 'bg-emerald-500', 'bg-rose-500', 'bg-amber-500', 'bg-indigo-500', 'bg-purple-500', 'bg-cyan-500']),
            'is_active' => true,
        ];
    }
}
