<?php

namespace App\Services;

use App\Models\Event;
use App\Models\KodeVoucher;
use App\Models\Payment;
use App\Models\Transaksi;
use App\Models\Volunteer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CheckoutService
{
    public function __construct(protected MidtransService $midtransService) {}

    /**
     * Proses checkout: potong stok, buat transaksi, attach volunteer, redeem voucher, buat snap token.
     * Total pembayaran dihitung server-side; jangan percaya nilai dari client.
     *
     * @param  array<int, array{name: string, email: string, telepon: string, jenis_kelamin?: string}>  $pengunjung
     * @throws \Exception
     */
    public function process(
        Event $event,
        int $jumlahTiket,
        Payment $payment,
        array $pengunjung,
        ?string $voucherCode = null
    ): Transaksi {
        if (! $payment->status || $payment->type !== 'midtrans') {
            throw new \Exception('Metode pembayaran tidak tersedia.');
        }

        if (! $event->isActive()) {
            throw new \Exception('Event sudah sold out.');
        }

        // ── Fase 1: Semua operasi DB dalam satu transaction pendek ────────────
        $transaksi     = null;
        $appliedVoucher = null;

        try {
            DB::transaction(function () use (
                $event, $jumlahTiket, $payment, $pengunjung, $voucherCode,
                &$transaksi, &$appliedVoucher
            ) {
                // 1. Potong stok secara atomik
                $affected = Event::where('id', $event->id)
                    ->where('status', true)
                    ->where('jumlah_tiket', '>=', $jumlahTiket)
                    ->decrement('jumlah_tiket', $jumlahTiket);

                if ($affected === 0) {
                    throw new \Exception('Tiket sudah sold out atau jumlah tiket tidak mencukupi.');
                }

                // 2. Proses voucher
                $voucherId         = null;
                $discountPerTicket = 0;

                if ($voucherCode) {
                    $appliedVoucher = KodeVoucher::where('kode', strtoupper(trim($voucherCode)))
                        ->where('id_event', $event->id)
                        ->where('status', true)
                        ->where('tanggal_kadaluarsa', '>=', now()->startOfDay())
                        ->lockForUpdate()
                        ->first();

                    if ($appliedVoucher) {
                        $remaining = $appliedVoucher->kuota - $appliedVoucher->digunakan;
                        if ($remaining < $jumlahTiket) {
                            throw new \Exception("Kuota voucher tidak mencukupi. Tersisa {$remaining} kuota, dibutuhkan {$jumlahTiket}.");
                        }

                        $emailUtama   = $pengunjung[0]['email'];
                        $sudahDipakai = Transaksi::where('id_voucher', $appliedVoucher->id)
                            ->where('email', $emailUtama)
                            ->whereIn('status_pembayaran', ['Pending', 'Success'])
                            ->exists();
                        if ($sudahDipakai) {
                            throw new \Exception('Voucher ini sudah pernah digunakan oleh email ' . $emailUtama . '.');
                        }

                        $voucherId         = $appliedVoucher->id;
                        $discountPerTicket = $appliedVoucher->nilai_diskon;
                        $appliedVoucher->increment('digunakan', $jumlahTiket);
                    }
                }

                // 3. Hitung total server-side
                $totalPembayaran = max(0, ($event->harga - $discountPerTicket) * $jumlahTiket);

                // 4. Buat transaksi
                $invoice   = date('YmdHis') . uniqid();
                $utamaData = $pengunjung[0];

                $transaksi = Transaksi::create([
                    'id_event'           => $event->id,
                    'invoice'            => $invoice,
                    'jumlah_tiket'       => $jumlahTiket,
                    'total_pembayaran'   => $totalPembayaran,
                    'name'               => $utamaData['name'],
                    'telepon'            => $this->formatPhone($utamaData['telepon']),
                    'email'              => $utamaData['email'],
                    'status_pembayaran'  => 'Pending',
                    'tanggal_register'   => now(),
                    'tanggal_pembayaran' => null,
                    'id_payment'         => $payment->id,
                    'id_voucher'         => $voucherId,
                ]);

                // 5. Attach volunteer
                foreach ($pengunjung as $p) {
                    $volunteer = Volunteer::firstOrCreate(
                        ['email' => $p['email']],
                        [
                            'name'          => $p['name'],
                            'telepon'       => $p['telepon'],
                            'jenis_kelamin' => $p['jenis_kelamin'] ?? null,
                        ]
                    );
                    if (! $volunteer->wasRecentlyCreated && empty($volunteer->jenis_kelamin) && ! empty($p['jenis_kelamin'])) {
                        $volunteer->update(['jenis_kelamin' => $p['jenis_kelamin']]);
                    }
                    $transaksi->volunteers()->syncWithoutDetaching([$volunteer->id]);
                }
            });
        } catch (\Exception $e) {
            Log::error('Checkout process gagal (fase DB): ' . $e->getMessage(), [
                'event_id'     => $event->id ?? null,
                'jumlah_tiket' => $jumlahTiket,
                'voucher_code' => $voucherCode,
            ]);
            throw $e;
        }

        // ── Fase 2: HTTP call DI LUAR transaction ─────────────────────────────
        // Jika gagal di sini, compensating transaction: release reservation.

        try {
            // 6. Redeem voucher eksternal
            if ($appliedVoucher && $appliedVoucher->is_external) {
                $this->redeemExternalVoucher($appliedVoucher, $transaksi);
            }

            // 7. Generate instruksi pembayaran Midtrans
            $transaksi->load('event');
            $this->createPaymentInstrument($transaksi, $payment);

        } catch (\Exception $e) {
            Log::error('Checkout process gagal (fase HTTP), melepas reservasi: ' . $e->getMessage(), [
                'invoice'      => $transaksi->invoice,
                'event_id'     => $event->id,
                'voucher_code' => $voucherCode,
            ]);

            // Compensating transaction: kembalikan stok + kuota voucher + hapus transaksi
            DB::transaction(function () use ($transaksi) {
                $this->releaseReservation($transaksi);
                $transaksi->volunteers()->detach();
                $transaksi->forceDelete();
            });

            throw $e;
        }

        Log::info('Transaction created', [
            'invoice'         => $transaksi->invoice,
            'event_id'        => $event->id,
            'total'           => $transaksi->total_pembayaran,
            'ticket_count'    => $jumlahTiket,
            'voucher_applied' => $transaksi->id_voucher !== null,
            'payment_type'    => $payment->type,
        ]);

        return $transaksi;
    }

    /**
     * Kembalikan sumber daya yang direservasi saat checkout ketika transaksi batal/gagal/expired:
     * stok tiket dan kuota voucher internal. Voucher eksternal tidak punya endpoint pembatalan,
     * jadi hanya dicatat untuk rekonsiliasi manual.
     *
     * Pemanggil bertanggung jawab memastikan transaksi memang masih 'Pending' (belum pernah dirilis)
     * dan menjalankan ini di dalam DB transaction agar konsisten dengan perubahan status.
     */
    public function releaseReservation(Transaksi $transaksi): void
    {
        // 1. Kembalikan stok tiket
        if ($transaksi->id_event) {
            Event::where('id', $transaksi->id_event)
                ->increment('jumlah_tiket', $transaksi->jumlah_tiket);
        }

        // 2. Kembalikan kuota voucher internal
        if ($transaksi->id_voucher) {
            $voucher = KodeVoucher::where('id', $transaksi->id_voucher)
                ->lockForUpdate()
                ->first();

            if ($voucher) {
                // Jangan sampai negatif walau ada anomali data.
                $restore = min($transaksi->jumlah_tiket, $voucher->digunakan);
                if ($restore > 0) {
                    $voucher->decrement('digunakan', $restore);
                }

                if ($voucher->is_external) {
                    Log::warning('Voucher eksternal terpakai pada transaksi gagal; perlu rekonsiliasi manual (tidak ada endpoint pembatalan).', [
                        'invoice' => $transaksi->invoice,
                        'kode'    => $voucher->kode,
                    ]);
                }
            }
        }

        Log::info('Reservasi transaksi dikembalikan', [
            'invoice'      => $transaksi->invoice,
            'event_id'     => $transaksi->id_event,
            'ticket_count' => $transaksi->jumlah_tiket,
            'voucher_id'   => $transaksi->id_voucher,
        ]);
    }

    private function formatPhone(string $phone): string
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        if (str_starts_with($phone, '0')) {
            $phone = substr($phone, 1);
        }
        if (!str_starts_with($phone, '62')) {
            $phone = '62' . $phone;
        }
        return '+' . $phone;
    }

    private function createPaymentInstrument(Transaksi $transaksi, Payment $payment): void
    {
        try {
            $transaksi->load('event');

            if ($payment->midtrans_payment_type) {
                $instructions = $this->midtransService->charge($transaksi, $payment);
                $this->assertPaymentInstructions($payment, $instructions);

                $transaksi->update(['payment_instructions' => $instructions]);
                $transaksi->payment_instructions = $instructions;

                return;
            }

            $snapToken = $this->midtransService->createSnapToken($transaksi);
            if (! is_string($snapToken) || trim($snapToken) === '') {
                throw new \RuntimeException('Midtrans Snap token kosong.');
            }

            $transaksi->update(['snap_token' => $snapToken]);
            $transaksi->snap_token = $snapToken;
        } catch (\Throwable $e) {
            Log::error('Gagal membuat instruksi pembayaran Midtrans', [
                'invoice' => $transaksi->invoice,
                'channel' => $payment->midtrans_payment_type,
                'error'   => $e->getMessage(),
            ]);

            throw new \Exception('Gagal membuat instruksi pembayaran. Silakan coba lagi.');
        }
    }

    /**
     * @param array<string, mixed> $instructions
     */
    private function assertPaymentInstructions(Payment $payment, array $instructions): void
    {
        $channel = $payment->midtrans_payment_type;

        $missing = match ($channel) {
            'bank_transfer' => empty($instructions['bank']) || empty($instructions['va_number']),
            'echannel'      => empty($instructions['bill_key']) || empty($instructions['biller_code']),
            'qris'          => empty($instructions['qr_url']),
            'gopay', 'shopeepay' => empty($instructions['deeplink_url']) && empty($instructions['qr_url']),
            default         => true,
        };

        if ($missing) {
            throw new \RuntimeException("Instruksi pembayaran Midtrans tidak lengkap untuk channel {$channel}.");
        }
    }

    private function redeemExternalVoucher(KodeVoucher $voucher, Transaksi $transaksi): void
    {
        $cfg = config('services.chatkebaikan');
        $url = $cfg['redeem_url'] ?? '';

        if (empty($url)) {
            throw new \Exception('Endpoint redeem voucher eksternal belum dikonfigurasi.');
        }

        $method  = strtoupper($cfg['redeem_method'] ?? 'POST');
        $payload = ['kode' => $voucher->kode];

        $response = Http::timeout($cfg['timeout'])
            ->acceptJson()
            ->withOptions(['allow_redirects' => ['strict' => true]])
            ->send($method, $url, ['json' => $payload]);

        if (!$response->successful()) {
            Log::error('External voucher redeem failed', [
                'kode'    => $voucher->kode,
                'invoice' => $transaksi->invoice,
                'status'  => $response->status(),
            ]);
            throw new \Exception('Gagal redeem voucher eksternal (HTTP ' . $response->status() . ').');
        }

        Log::info('External voucher redeemed', [
            'kode'    => $voucher->kode,
            'invoice' => $transaksi->invoice,
        ]);
    }
}