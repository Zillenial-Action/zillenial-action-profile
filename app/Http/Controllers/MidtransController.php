<?php

namespace App\Http\Controllers;

use App\Mail\SendTicket;
use App\Models\Transaksi;
use App\Services\CheckoutService;
use App\Services\MidtransService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Class MidtransController
 *
 * Menangani webhook notifikasi dari Midtrans dan halaman finish setelah pembayaran.
 */
class MidtransController extends Controller
{
    protected MidtransService $midtransService;

    protected CheckoutService $checkoutService;

    public function __construct(MidtransService $midtransService, CheckoutService $checkoutService)
    {
        $this->midtransService = $midtransService;
        $this->checkoutService = $checkoutService;
    }

    /**
     * Halaman finish setelah user selesai di popup Midtrans Snap.
     * GET /midtrans/finish/{invoice}
     *
     * Tampilkan halaman "Pembayaran Berhasil" dengan detail transaksi.
     */
    public function finish(string $invoice)
    {
        $transaksi = Transaksi::with(['event', 'payment', 'volunteers'])
            ->where('invoice', $invoice)
            ->first();

        if (! $transaksi) {
            return redirect('/')->with('error', 'Transaksi tidak ditemukan.');
        }

        if (! empty($transaksi->public_token)) {
            $token = request()->query('token', '');
            if (! hash_equals($transaksi->public_token, (string) $token)) {
                abort(403);
            }
        }

        return view('portal.payment-success', ['data' => $transaksi]);
    }

    /**
     * Endpoint webhook notifikasi Midtrans.
     * POST /midtrans/notification — exempt dari CSRF.
     *
     * Midtrans mengirim HTTP POST ke sini setiap kali status pembayaran berubah.
     */
    public function notification(Request $request): JsonResponse
    {
        try {
            $notif = $this->midtransService->parseNotification($request);

            $invoice = $notif['order_id'];
            $transactionStatus = $notif['transaction_status'];
            $fraudStatus = $notif['fraud_status'];

            $newStatus = $this->midtransService->resolveStatus($transactionStatus, $fraudStatus);

            if ($newStatus === null) {
                Log::info('Midtrans: status tidak dikenali, diabaikan', [
                    'invoice' => $invoice,
                    'transaction_status' => $transactionStatus,
                ]);

                return response()->json(['message' => 'Status not handled'], 200);
            }

            $transaksi = Transaksi::with(['volunteers', 'event'])->where('invoice', $invoice)->first();

            if (! $transaksi) {
                Log::warning('Midtrans notification: transaksi tidak ditemukan', [
                    'invoice' => $invoice,
                ]);

                return response()->json(['message' => 'Transaction not found'], 404);
            }

            // Jangan proses ulang jika status sudah final
            if ($transaksi->status_pembayaran === 'Success' && $newStatus !== 'Success') {
                Log::info('Midtrans: transaksi sudah Success, notifikasi diabaikan', [
                    'invoice' => $invoice,
                ]);

                return response()->json(['message' => 'Already processed'], 200);
            }

            if ($newStatus === 'Success') {
                // Kunci baris & baca ulang status di dalam transaction agar webhook ganda
                // (Midtrans dapat mengirim/retry notifikasi bersamaan) tidak memproses dua kali.
                try {
                    $processed = DB::transaction(function () use ($invoice) {
                        $fresh = Transaksi::where('invoice', $invoice)
                            ->lockForUpdate()
                            ->first();

                        if (! $fresh || $fresh->status_pembayaran === 'Success') {
                            return null;
                        }

                        $fresh->update([
                            'status_pembayaran' => 'Success',
                            'tanggal_pembayaran' => now(),
                        ]);

                        return $fresh;
                    });
                } catch (\Exception $e) {
                    Log::error('Midtrans: gagal menyimpan status Success', [
                        'invoice' => $invoice,
                        'error' => $e->getMessage(),
                    ]);

                    return response()->json(['message' => 'Internal server error'], 500);
                }

                if ($processed === null) {
                    Log::info('Midtrans: transaksi sudah Success, notifikasi diabaikan', [
                        'invoice' => $invoice,
                    ]);

                    return response()->json(['message' => 'Already processed'], 200);
                }

                // Kirim email tiket TERPISAH dari status pembayaran. Pembayaran sudah sukses
                // dan tidak boleh dibatalkan hanya karena email gagal terkirim.
                $processed->load(['volunteers', 'event']);
                $this->dispatchTickets($processed);

                Log::info('Midtrans: transaksi berhasil', ['invoice' => $invoice]);

                return response()->json(['message' => 'Notification processed', 'status' => $newStatus], 200);
            }

            if ($newStatus === 'Failed') {
                try {
                    $released = DB::transaction(function () use ($invoice) {
                        // Kunci baris & pastikan masih Pending untuk menghindari balapan dengan
                        // webhook ganda maupun command expire (dobel-restore stok tiket/voucher).
                        $fresh = Transaksi::where('invoice', $invoice)
                            ->where('status_pembayaran', 'Pending')
                            ->lockForUpdate()
                            ->first();

                        if (! $fresh) {
                            return false;
                        }

                        // Kembalikan stok tiket dan kuota voucher yang direservasi saat checkout.
                        $this->checkoutService->releaseReservation($fresh);
                        $fresh->update(['status_pembayaran' => 'Failed']);

                        return true;
                    });
                } catch (\Exception $e) {
                    Log::error('Midtrans: gagal memproses transaksi gagal', [
                        'invoice' => $invoice,
                        'error' => $e->getMessage(),
                    ]);

                    return response()->json(['message' => 'Internal server error'], 500);
                }

                if ($released) {
                    Log::info('Midtrans: transaksi gagal, reservasi dikembalikan', ['invoice' => $invoice]);
                }

                return response()->json(['message' => 'Notification processed', 'status' => $newStatus], 200);
            }

            // Pending atau tidak ada perubahan status yang perlu diproses.
            return response()->json(['message' => 'No state change', 'status' => $newStatus], 200);

        } catch (\Exception $e) {
            Log::error('Midtrans: gagal parse notifikasi', [
                'error' => $e->getMessage(),
            ]);

            return response()->json(['message' => 'Failed to parse notification'], 400);
        }
    }

    /**
     * Antrikan email tiket ke seluruh volunteer. Kegagalan tiap email dicatat, tidak dilempar,
     * agar tidak memengaruhi status pembayaran yang sudah final.
     */
    private function dispatchTickets(Transaksi $transaksi): void
    {
        foreach ($transaksi->volunteers as $volunteer) {
            try {
                Mail::to($volunteer->email)->queue(
                    new SendTicket(
                        $transaksi->invoice,
                        $this->ticketUrl($transaksi)
                    )
                );
            } catch (\Throwable $e) {
                Log::error('Midtrans: gagal mengantrikan email tiket', [
                    'invoice' => $transaksi->invoice,
                    'volunteer_email' => $volunteer->email,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    private function ticketUrl(Transaksi $transaksi): string
    {
        $url = url('/tiket/'.$transaksi->invoice);

        if (! empty($transaksi->public_token)) {
            $url .= '?token='.urlencode($transaksi->public_token);
        }

        return $url;
    }
}
