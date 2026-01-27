<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Transaction>
 */
class TransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = $this->faker->randomElement(['income', 'expense']);

        $categories = [
            'income' => [
                'Salary' => ['Gaji Bulanan', 'Overtime Bonus'],
                'Freelance' => ['Proyek Website', 'Desain Logo', 'Menulis Artikel'],
                'Investment' => ['Dividen Saham', 'Bunga Deposito'],
                'Gift' => ['Hadiah Ulang Tahun', 'THR'],
            ],
            'expense' => [
                'Food' => ['Makan Siang', 'Kopi Pagi', 'Gojek Food', 'Dinner Keluarga'],
                'Transport' => ['Isi Bensin', 'Tarif Tol', 'Grab/Gojek Ride', 'Parkir'],
                'Rent' => ['Bayar Kost', 'Cicilan Rumah'],
                'Social' => ['Nonton Bioskop', 'Kado Pernikahan', 'Donasi'],
                'Healthcare' => ['Beli Obat', 'Checkup Dokter'],
                'Shopping' => ['Beli Baju', 'Belanja Bulanan', 'Tokopedia Order'],
                'Utility' => ['Token Listrik', 'Tagihan Air', 'Pulsa HP', 'Internet Bulanan']
            ]
        ];

        $category = $this->faker->randomKey($categories[$type]);
        $description = $this->faker->randomElement($categories[$type][$category]);

        return [
            'type' => $type,
            'amount' => $type === 'income'
                ? $this->faker->numberBetween(1000000, 10000000)
                : $this->faker->numberBetween(10000, 1000000),
            'category' => $category,
            'description' => $description,
            'date' => $this->faker->dateTimeBetween('-1 month', 'now')->format('Y-m-d'),
            'currency' => 'IDR',
            'wallet_id' => null, // Will be set by Seeder
        ];
    }
}
