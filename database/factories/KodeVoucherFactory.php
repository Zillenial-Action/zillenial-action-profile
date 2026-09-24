<?php

namespace Database\Factories;

use App\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\KodeVoucher>
 */
class KodeVoucherFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id_event' => Event::factory(),
            'name_voucher' => fake()->unique()->words(2, true),
            'kode' => strtoupper(fake()->unique()->bothify('VC-####??')),
            'nilai_diskon' => fake()->numberBetween(1000, 50000),
            'kuota' => 5,
            'digunakan' => 0,
            'tanggal_kadaluarsa' => now()->addDays(30)->toDateString(),
            'status' => true,
            'is_external' => false,
        ];
    }

    /**
     * Voucher dari API eksternal: kuota 1.
     */
    public function external(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_external' => true,
            'kuota' => 1,
        ]);
    }
}
