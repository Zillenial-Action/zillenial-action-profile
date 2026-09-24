<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Transaksi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Midtrans\Config;
use Midtrans\CoreApi;
use Midtrans\Snap;

/**
 * Class MidtransService
 *
 * Mengelola integrasi dengan Midtrans Payment Gateway.
 * Handles: Snap token creation, payment notification verification.
 */
class MidtransService
{
    public function __construct()
    {
        Config::$serverKey = config('midtrans.server_key');
        Config::$clientKey = config('midtrans.client_key');
        Config::$isProduction = config('midtrans.is_production');
        Config::$isSanitized = config('midtrans.is_sanitized');
        Config::$is3ds = config('midtrans.is_3ds');
    }

    /**
     * Buat Snap token untuk transaksi yang diberikan.
     * Kembalikan string token atau lempar exception jika gagal.
     *
     * @throws \Exception
     */
    public function createSnapToken(Transaksi $transaksi): string
    {
        $params = [
            'transaction_details' => [
                'order_id' => $transaksi->invoice,
                'gross_amount' => (int) $transaksi->total_pembayaran,
            ],
            'customer_details' => [
                'first_name' => $transaksi->name,
                'email' => $transaksi->email,
                'phone' => $transaksi->telepon,
            ],
            'item_details' => [
                [
                    'id'       => 'TIKET-'.$transaksi->id_event,
                    'price'    => (int) $transaksi->total_pembayaran,
                    'quantity' => 1,
                    'name'     => 'Tiket '.($transaksi->event?->name ?? 'Event').' ('.$transaksi->jumlah_tiket.' tiket)',
                ],
            ],
            'callbacks' => [
                'finish' => rtrim(config('midtrans.frontend_url', url('/')), '/') . '/payment/success?order_id=' . $transaksi->invoice,
            ],
        ];

        $snapToken = Snap::getSnapToken($params);

        Log::info('Midtrans Snap token created', [
            'invoice' => $transaksi->invoice,
            'order_id' => $transaksi->invoice,
            'gross_amount' => $transaksi->total_pembayaran,
        ]);

        return $snapToken;
    }

    /**
     * Buat charge Core API untuk channel spesifik (VA, QRIS, e-wallet).
     * Kembalikan instruksi pembayaran ternormalisasi untuk dirender di UI sendiri.
     *
     * @throws \Exception
     */
    public function charge(Transaksi $transaksi, Payment $payment): array
    {
        $params = [
            'transaction_details' => [
                'order_id' => $transaksi->invoice,
                'gross_amount' => (int) $transaksi->total_pembayaran,
            ],
            'customer_details' => [
                'first_name' => $transaksi->name,
                'email' => $transaksi->email,
                'phone' => $transaksi->telepon,
            ],
            // Batas waktu pembayaran seragam untuk semua channel (default 15 menit).
            // Midtrans akan mengembalikan expiry_time yang dipakai timer frontend & release stok.
            'custom_expiry' => [
                'expiry_duration' => (int) config('midtrans.payment_expiry_minutes', 15),
                'unit' => 'minute',
            ],
        ];

        switch ($payment->midtrans_payment_type) {
            case 'bank_transfer':
                $params['payment_type'] = 'bank_transfer';
                $params['bank_transfer'] = ['bank' => $payment->midtrans_bank];
                break;
            case 'echannel':
                $params['payment_type'] = 'echannel';
                $params['echannel'] = [
                    'bill_info1' => 'Pembayaran',
                    'bill_info2' => 'Tiket '.($transaksi->event?->name ?? 'Event'),
                ];
                break;
            case 'gopay':
            case 'shopeepay':
            case 'qris':
                $params['payment_type'] = $payment->midtrans_payment_type;
                break;
            default:
                throw new InvalidArgumentException(
                    "Channel Midtrans tidak didukung: {$payment->midtrans_payment_type}."
                );
        }

        $response = (array) CoreApi::charge($params);

        $instructions = $this->normalizeChargeResponse($response);

        Log::info('Midtrans Core API charge created', [
            'invoice' => $transaksi->invoice,
            'payment_type' => $payment->midtrans_payment_type,
        ]);

        return $instructions;
    }

    /**
     * Normalisasi response Core API charge ke struktur konsisten untuk frontend.
     */
    private function normalizeChargeResponse(array $response): array
    {
        $instructions = [
            'expiry_time' => $response['expiry_time'] ?? null,
        ];

        if (! empty($response['va_numbers'][0])) {
            $va = (array) $response['va_numbers'][0];
            $instructions['bank'] = $va['bank'] ?? null;
            $instructions['va_number'] = $va['va_number'] ?? null;
        }

        if (! empty($response['bill_key'])) {
            $instructions['bill_key'] = $response['bill_key'];
            $instructions['biller_code'] = $response['biller_code'] ?? null;
        }

        foreach ((array) ($response['actions'] ?? []) as $action) {
            $action = (array) $action;
            if (($action['name'] ?? null) === 'generate-qr-code') {
                $instructions['qr_url'] = $action['url'] ?? null;
            }
            if (($action['name'] ?? null) === 'deeplink-redirect') {
                $instructions['deeplink_url'] = $action['url'] ?? null;
            }
        }

        return $instructions;
    }

    /**
     * Verifikasi dan parsing notifikasi dari Midtrans.
     * Kembalikan array dengan order_id, transaction_status, fraud_status.
     *
     * @throws \Exception
     */
    public function parseNotification(Request $request): array
    {
        $payload = $request->json()->all();

        if (empty($payload)) {
            $payload = $request->all();
        }

        foreach (['order_id', 'transaction_status', 'gross_amount', 'status_code', 'signature_key'] as $field) {
            if (empty($payload[$field])) {
                throw new InvalidArgumentException("Missing Midtrans notification field: {$field}.");
            }
        }

        $serverKey = config('midtrans.server_key');
        if (empty($serverKey)) {
            throw new InvalidArgumentException('Midtrans server key is not configured.');
        }

        $expectedSignature = hash(
            'sha512',
            $payload['order_id'].$payload['status_code'].$payload['gross_amount'].$serverKey
        );

        if (! hash_equals($expectedSignature, (string) $payload['signature_key'])) {
            throw new InvalidArgumentException('Invalid Midtrans signature.');
        }

        $orderId = (string) $payload['order_id'];
        $transactionStatus = (string) $payload['transaction_status'];
        $fraudStatus = $payload['fraud_status'] ?? null;
        $paymentType = $payload['payment_type'] ?? null;
        $grossAmount = (string) $payload['gross_amount'];

        Log::info('Midtrans notification received', [
            'order_id' => $orderId,
            'transaction_status' => $transactionStatus,
            'fraud_status' => $fraudStatus,
            'payment_type' => $paymentType,
        ]);

        return [
            'order_id' => $orderId,
            'transaction_status' => $transactionStatus,
            'fraud_status' => $fraudStatus,
            'payment_type' => $paymentType,
            'gross_amount' => $grossAmount,
            'status_code' => (string) $payload['status_code'],
        ];
    }

    /**
     * Tentukan status_pembayaran berdasarkan transaction_status dan fraud_status Midtrans.
     */
    public function resolveStatus(string $transactionStatus, ?string $fraudStatus): ?string
    {
        if ($transactionStatus === 'capture') {
            return $fraudStatus === 'accept' ? 'Success' : 'Failed';
        }

        if ($transactionStatus === 'settlement') {
            return 'Success';
        }

        if (in_array($transactionStatus, ['cancel', 'deny', 'expire', 'failure'])) {
            return 'Failed';
        }

        if ($transactionStatus === 'pending') {
            return 'Pending';
        }

        return null; // Status tidak dikenali, abaikan
    }
}
