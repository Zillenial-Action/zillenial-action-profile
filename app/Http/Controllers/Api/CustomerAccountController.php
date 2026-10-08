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
        $orders = $request->user('customer')
            ->transaksiQuery()
            ->with('event:id,name,slug,waktu_mulai,nama_tempat')
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
        $token = (string) $transaksi->public_token;

        if ($transaksi->status_pembayaran === 'Success') {
            $action = [
                'type' => 'view_ticket',
                'url' => route('portal.tiket', $token !== '' ? ['invoice' => $transaksi->invoice, 'token' => $token] : $transaksi->invoice),
            ];
        } elseif ($transaksi->status_pembayaran === 'Pending' && $transaksi->canAccessInvoice()) {
            $action = [
                'type' => 'continue_payment',
                'url' => rtrim((string) config('services.customer_auth.frontend_url'), '/')
                    .'/payment?order_id='.rawurlencode($transaksi->invoice)
                    .($token !== '' ? '#token='.rawurlencode($token) : ''),
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
