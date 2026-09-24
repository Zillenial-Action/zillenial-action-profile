<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Transaksi>
 */
class TransaksiFactory extends Factory
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
            'invoice' => fake()->unique()->numerify('##############') . fake()->unique()->lexify('????'),
            'jumlah_tiket' => rand(1, 10),
            'total_pembayaran' => rand(100000, 500000),
            'name' => fake()->name(),
            'email' => fake()->safeEmail(),
            'telepon' => fake()->phoneNumber(),
            'status_pembayaran' => fake()->randomElement(['Success', 'Failed', 'Pending']),
            'tanggal_register' => fake()->dateTime(),
            'tanggal_pembayaran' => fake()->dateTime(),
            'id_payment' => Payment::factory(),
        ];
    }
}
