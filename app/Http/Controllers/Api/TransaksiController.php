<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TransaksiStatusResource;
use App\Models\Transaksi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TransaksiController extends Controller
{
    public function show(Request $request, string $invoice): JsonResponse
    {
        $transaksi = Transaksi::with(['event', 'payment'])
            ->where('invoice', $invoice)
            ->first();

        if (!$transaksi) {
            return response()->json(['success' => false, 'message' => 'Transaksi tidak ditemukan.'], 404);
        }

        if (! empty($transaksi->public_token)) {
            $token = (string) $request->query('token', '');

            if (! $transaksi->hasPublicToken($token)) {
                return response()->json(['success' => false, 'message' => 'Token transaksi tidak valid.'], 403);
            }
        } else {
            Log::info('Legacy invoice-only transaction status access', [
                'invoice' => $transaksi->invoice,
                'ip' => $request->ip(),
            ]);
        }

        return response()->json(['data' => new TransaksiStatusResource($transaksi)]);
    }
}
