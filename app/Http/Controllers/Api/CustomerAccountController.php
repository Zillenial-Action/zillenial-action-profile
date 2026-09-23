<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaksi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerAccountController extends Controller
{
    public function orders(Request $request): JsonResponse
    {
        $orders = Transaksi::query()
            ->with('event')
            ->where('id_customer', $request->user('customer')->id)
            ->latest('tanggal_register')
            ->paginate(10);

        return response()->json([
            'data' => $orders->getCollection()->map(fn (Transaksi $transaksi) => $this->orderPayload($transaksi))->values(),
            'meta' => [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'per_page' => $orders->perPage(),
                'total' => $orders->total(),
            ],
        ]);
    }

    private function orderPayload(Transaksi $transaksi): array
    {
        $action = null;
        if ($transaksi->status_pembayaran === 'Success') {
            $ticketUrl = rtrim((string) config('app.public_backend_url'), '/').'/tiket/'.rawurlencode($transaksi->invoice);
            if ($transaksi->public_token) {
                $ticketUrl .= '?token='.rawurlencode((string) $transaksi->public_token);
            }
            $action = [
                'type' => 'view_ticket',
                'url' => $ticketUrl,
            ];
        } elseif ($transaksi->status_pembayaran === 'Pending' && $transaksi->canAccessInvoice()) {
            $action = [
                'type' => 'continue_payment',
                'url' => rtrim((string) config('app.frontend_url'), '/').'/payment?order_id='.
                    rawurlencode($transaksi->invoice).'#token='.rawurlencode((string) $transaksi->public_token),
            ];
        }

        return [
            'invoice' => $transaksi->invoice,
            'status' => $transaksi->status_pembayaran,
            'total_pembayaran' => $transaksi->total_pembayaran,
            'jumlah_tiket' => $transaksi->jumlah_tiket,
            'tanggal_register' => optional($transaksi->tanggal_register)->toIso8601String(),
            'event' => $transaksi->event ? [
                'name' => $transaksi->event->name,
                'slug' => $transaksi->event->slug,
                'waktu_mulai' => optional($transaksi->event->waktu_mulai)->toIso8601String(),
                'nama_tempat' => $transaksi->event->nama_tempat,
            ] : null,
            'action' => $action,
        ];
    }
}
