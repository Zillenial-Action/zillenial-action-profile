<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PaymentMethodResource;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class PaymentMethodController extends Controller
{
    public function index(): JsonResponse
    {
        $methods = Cache::remember('active_payment_methods', 1800, function () {
            return Payment::select(['id', 'name', 'image', 'type', 'midtrans_payment_type'])
                ->where('status', true)
                ->orderBy('id')
                ->get();
        })->filter(fn ($p) => $p->type === 'midtrans')->values();

        return response()->json(['data' => PaymentMethodResource::collection($methods)]);
    }
}
