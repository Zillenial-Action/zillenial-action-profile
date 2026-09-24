<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Event>
 */
class EventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'mitra' => fake()->name(),
            'website' => fake()->freeEmailDomain(),
            'status' => fake()->boolean() ? 1 : 0,
            'waktu_mulai' => fake()->dateTime(),
            'waktu_berakhir' => fake()->dateTime(),
            'nama_tempat' => fake()->company(),
            'alamat' => fake()->address(),
            'direction' => fake()->url(),
            'kota' => fake()->city(),
            'jumlah_tiket' => fake()->numberBetween(1, 20),
            'harga' => fake()->numberBetween(10000, 1000000),
            'deskripsi' => fake()->text(100),
            'benefits' => [
                'Benefit peserta pertama',
                'Benefit peserta kedua',
            ],
            'agenda' => [
                [
                    'time_label' => 'Pagi',
                    'title' => 'Registrasi ulang',
                    'description' => 'Peserta melakukan check-in di lokasi.',
                ],
            ],
            'image' => fake()->imageUrl(360, 360, 'animals', true),
        ];
        
    }
}
