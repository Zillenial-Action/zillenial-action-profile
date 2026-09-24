<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Payment;
use App\Services\CheckoutService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;

class CheckoutController extends Controller
{
    public function __construct(protected CheckoutService $checkoutService) {}

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'event_slug'           => 'required|string|exists:events,slug',
            'jumlah_tiket'         => 'required|integer|min:1|max:3',
            'payment_method_id'    => ['required', Rule::exists('payments', 'id')->where(fn ($q) =>
                $q->where('status', true)->where('type', 'midtrans')
            )],
            'voucher_code'         => 'nullable|string|max:100',
            'pengunjung'           => 'required|array|min:1|max:3',
            'pengunjung.*.name'    => ['required', 'string', 'min:3', 'max:100', 'regex:/^[a-zA-Z\s]+$/'],
            'pengunjung.*.telepon' => ['required', 'string', 'min:9', 'max:13', 'regex:/^[0-9]+$/'],
            'pengunjung.*.email'   => ['required', 'email:rfc,dns', 'max:255'],
            'pengunjung.*.jenis_kelamin' => ['nullable', 'string', 'in:Laki-laki,Perempuan'],
        ], [
            'pengunjung.*.name.required'    => 'Nama wajib diisi.',
            'pengunjung.*.name.min'         => 'Nama minimal 3 karakter.',
            'pengunjung.*.name.regex'       => 'Nama hanya boleh berisi huruf dan spasi.',
            'pengunjung.*.telepon.required' => 'Nomor ponsel wajib diisi.',
            'pengunjung.*.telepon.min'      => 'Nomor ponsel minimal 9 digit.',
            'pengunjung.*.telepon.max'      => 'Nomor ponsel maksimal 13 digit.',
            'pengunjung.*.telepon.regex'    => 'Nomor ponsel hanya boleh berisi angka.',
            'pengunjung.*.email.required'   => 'Email wajib diisi.',
            'pengunjung.*.email.email'      => 'Format email tidak valid.',
            'payment_method_id.exists'      => 'Metode pembayaran tidak tersedia.',
        ]);

        if (count($data['pengunjung']) !== (int) $data['jumlah_tiket']) {
            throw ValidationException::withMessages([
                'pengunjung' => 'Jumlah data volunteer harus sama dengan jumlah tiket.',
            ]);
        }

        try {
            $event   = Event::where('slug', $data['event_slug'])->firstOrFail();
            $payment = Payment::where('status', true)
                ->where('type', 'midtrans')
                ->findOrFail($data['payment_method_id']);

            $transaksi = $this->checkoutService->process(
                $event,
                $data['jumlah_tiket'],
                $payment,
                $data['pengunjung'],
                $data['voucher_code'] ?? null
            );

            return response()->json([
                'success'              => true,
                'order_id'             => $transaksi->invoice,
                'access_token'         => $transaksi->public_token,
                'snap_token'           => $transaksi->snap_token ?? '',
                'payment_channel'      => $payment->midtrans_payment_type,
                'payment_instructions' => $transaksi->payment_instructions,
            ]);
        } catch (\Exception $e) {
            Log::error('API checkout failed', [
                'event_slug' => $data['event_slug'],
                'error'      => $e->getMessage(),
                'ip'         => $request->ip(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 409);
        }
    }
}
