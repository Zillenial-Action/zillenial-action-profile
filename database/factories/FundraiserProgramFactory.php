<?php

namespace Database\Factories;

use App\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\FundraiserProgram>
 */
class FundraiserProgramFactory extends Factory
{
    public function definition(): array
    {
        return [
            'id_event' => Event::factory()->state(['status' => true, 'harga' => 200000]),
            'nilai_diskon' => 20000,
            'nilai_komisi' => 15000,
            'kuota_per_kode' => 10,
            'tanggal_berakhir' => now()->addDays(30)->toDateString(),
            'status' => true,
        ];
    }
}
