<?php

namespace Database\Seeders;

use Database\Seeders\PaymentSeeder;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Membuat 1 user admin
        // \App\Models\User::factory()->admin()->create();
        
        // Membuat 2 user biasa
        // \App\Models\User::factory(2)->create();
        
        // Membuat 1 campaign
        // \App\Models\Campaign::factory(1)->create();
        
        // Membuat 6 event
        // \App\Models\Event::factory(6)->create();

        // Membuat 5 payment
        // \App\Models\Payment::factory(5)->create();

        // Membuat 10 transaksi
        // \App\Models\Transaksi::factory(10)->create();

        // Membuat 10 pixel
        // \App\Models\Pixel::factory(4)->create();

        $this->call(PaymentSeeder::class);

    }
}

