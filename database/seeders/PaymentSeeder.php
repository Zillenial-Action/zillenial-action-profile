<?php

namespace Database\Seeders;

use App\Models\Payment;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;

class PaymentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Channel pembayaran Midtrans yang aktif di dashboard (lihat screenshot user):
     * CIMB Niaga, GoPay Dynamic QRIS, BNI, BRI, GoPay, Bank Mandiri, PermataBank.
     */
    public function run(): void
    {
        $channels = [
            ['name' => 'BNI Virtual Account', 'midtrans_payment_type' => 'bank_transfer', 'midtrans_bank' => 'bni'],
            ['name' => 'BRI Virtual Account', 'midtrans_payment_type' => 'bank_transfer', 'midtrans_bank' => 'bri'],
            ['name' => 'PermataBank Virtual Account', 'midtrans_payment_type' => 'bank_transfer', 'midtrans_bank' => 'permata'],
            ['name' => 'CIMB Niaga Virtual Account', 'midtrans_payment_type' => 'bank_transfer', 'midtrans_bank' => 'cimb'],
            ['name' => 'Bank Mandiri Bill Payment', 'midtrans_payment_type' => 'echannel', 'midtrans_bank' => null],
            ['name' => 'GoPay', 'midtrans_payment_type' => 'gopay', 'midtrans_bank' => null],
            ['name' => 'GoPay Dynamic QRIS', 'midtrans_payment_type' => 'qris', 'midtrans_bank' => null],
        ];

        foreach ($channels as $channel) {
            Payment::updateOrCreate(
                [
                    'midtrans_payment_type' => $channel['midtrans_payment_type'],
                    'midtrans_bank' => $channel['midtrans_bank'],
                ],
                [
                    'name' => $channel['name'],
                    'type' => 'midtrans',
                    'no_rek' => null,
                    'status' => true,
                ]
            );
        }

        Cache::forget('active_payment_methods');
    }
}
