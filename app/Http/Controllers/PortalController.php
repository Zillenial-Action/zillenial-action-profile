<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Payment;
use App\Models\Transaksi;
use App\Models\KodeVoucher;
use App\Services\CheckoutService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Class PortalController
 *
 * Handles public-facing portal pages for events, checkout, and tickets.
 */
class PortalController extends Controller
{
    /**
     * Search events for portal.
     */
    public function eventSearch(Request $request): View
    {
        $event = Event::query()
            ->orderByDesc('created_at')
            ->when($request->search, fn($q) => 
                $q->where(fn($query) => 
                    $query->where('name', 'like', '%' . $request->search . '%')
                          ->orWhere('mitra', 'like', '%' . $request->search . '%')
                )
            )
            ->paginate(12);

        return view('portal.program', compact('event'));
    }

    /**
     * Display portal homepage.
     */
    public function index(): View
    {
        // Cache homepage events for 10 minutes
        $event = Cache::remember('homepage_events', 600, function () {
            return Event::query()
                ->select(['id', 'name', 'slug', 'image', 'harga', 'waktu_mulai', 'kota', 'mitra'])
                ->where('status', true)
                ->orderByDesc('id')
                ->limit(6)
                ->get();
        });

        return view('portal.index', compact('event'));
    }

    /**
     * View event content.
     */
    public function viewContent(string $slug): View
    {
        $data = Event::where('slug', $slug)->firstOrFail();

        return view('portal.view_content', compact('data'));
    }

    /**
     * Checkout page.
     */
    public function checkout(string $slug): View
    {
        $data = Event::where('slug', $slug)->firstOrFail();

        // Portal memakai Midtrans Snap: satu metode Snap (midtrans_payment_type = NULL).
        // Channel (VA/Transfer Bank, GoPay, QRIS, kartu kredit, dll) dipilih di dalam popup Snap,
        // jadi checkout cukup menampilkan satu opsi "Bayar via Midtrans".
        $payment = Cache::remember('portal_snap_payment_method', 1800, function () {
            return Payment::select(['id', 'name', 'image', 'type', 'midtrans_payment_type'])
                ->where('status', true)
                ->where('type', 'midtrans')
                ->whereNull('midtrans_payment_type')
                ->orderBy('id')
                ->first();
        });

        return view('portal.checkout', compact('data', 'payment'));
    }

    /**
     * Process transaction (web portal — delegates to CheckoutService).
     */
    public function transaksiPost(Request $request, string $slug): RedirectResponse
    {
        $request->validate([
            'jumlah_tiket'         => 'required|integer|min:1|max:3',
            'pengunjung'           => 'required|array|min:1',
            'pengunjung.*.name'    => ['required', 'string', 'min:3', 'max:100', 'regex:/^[a-zA-Z\s]+$/'],
            'pengunjung.*.telepon' => ['required', 'string', 'min:9', 'max:13', 'regex:/^[0-9]+$/'],
            'pengunjung.*.email'   => ['required', 'email:rfc,dns', 'max:255'],
            'voucher_code'         => 'nullable|string',
        ], [
            'pengunjung.*.name.required'    => 'Nama wajib diisi',
            'pengunjung.*.name.min'         => 'Nama minimal 3 karakter',
            'pengunjung.*.name.regex'       => 'Nama hanya boleh berisi huruf dan spasi',
            'pengunjung.*.telepon.required' => 'Nomor ponsel wajib diisi',
            'pengunjung.*.telepon.min'      => 'Nomor ponsel minimal 9 digit',
            'pengunjung.*.telepon.max'      => 'Nomor ponsel maksimal 13 digit',
            'pengunjung.*.telepon.regex'    => 'Nomor ponsel hanya boleh berisi angka',
            'pengunjung.*.email.required'   => 'Email wajib diisi',
            'pengunjung.*.email.email'      => 'Format email tidak valid',
        ]);

        if (count($request->input('pengunjung', [])) !== (int) $request->jumlah_tiket) {
            throw ValidationException::withMessages([
                'pengunjung' => 'Jumlah data volunteer harus sama dengan jumlah tiket.',
            ]);
        }

        try {
            $event = Event::where('slug', $slug)->firstOrFail();

            // Portal selalu memakai Midtrans Snap: ambil metode Snap (midtrans_payment_type = NULL).
            // Channel spesifik dipilih pengguna di dalam popup Snap pada halaman invoice.
            $payment = Payment::where('status', true)
                ->where('type', 'midtrans')
                ->whereNull('midtrans_payment_type')
                ->orderBy('id')
                ->first();

            if (! $payment) {
                return redirect()->back()->with('error', 'Metode pembayaran Midtrans belum tersedia. Silakan hubungi admin.');
            }

            $transaksi = app(CheckoutService::class)->process(
                $event,
                (int) $request->jumlah_tiket,
                $payment,
                $request->pengunjung,
                $request->voucher_code
            );

            return redirect()
                ->route('invoice', ['invoice' => $transaksi->invoice, 'token' => $transaksi->public_token])
                ->with('success', 'Transaksi berhasil');
        } catch (\Exception $e) {
            Log::error('Transaction failed via portal', [
                'event_slug' => $slug,
                'error'      => $e->getMessage(),
                'ip'         => $request->ip(),
            ]);

            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Display invoice.
     */
    public function invoice(Request $request, string $invoice): View|RedirectResponse
    {
        $data = Transaksi::with(['event', 'payment', 'volunteers'])
            ->where('invoice', $invoice)
            ->first();

        if (!$data) {
            return view('portal.error_tiket');
        }

        if (! $this->canAccessPublicTransaction($request, $data)) {
            return view('portal.error_tiket');
        }

        if ($data->status_pembayaran === 'Success') {
            return redirect()->route('portal.tiket', [
                'invoice' => $data->invoice,
                'token' => $data->public_token,
            ]);
        }

        if (!$data->canAccessInvoice()) {
            return view('portal.error_tiket');
        }

        return view('portal.invoice', compact('data'));
    }

    /**
     * Display program list.
     */
    public function program(): View
    {
        $event = Event::query()
            ->orderByDesc('created_at')
            ->paginate(9);

        return view('portal.program', compact('event'));
    }

    /**
     * Display ticket.
     */
    public function tiket(Request $request, string $invoice): View
    {
        $transaksi = Transaksi::with(['event', 'volunteers'])
            ->where('invoice', $invoice)
            ->where('status_pembayaran', 'Success')
            ->first();

        if (!$transaksi || ! $this->canAccessPublicTransaction($request, $transaksi)) {
            return view('portal.error_tiket');
        }

        return view('portal.tiket', compact('transaksi'));
    }

    /**
     * Validate voucher code via API (checkout).
     * Cek DB lokal dulu; jika tidak ada, validasi ke API eksternal lalu buat voucher.
     */
    public function validateVoucher(Request $request): JsonResponse
    {
        $request->validate([
            'code' => 'required|string',
            'event_id' => 'required|integer|exists:events,id',
            'jumlah_tiket' => 'nullable|integer|min:1',
        ]);

        $code = strtoupper(trim($request->code));
        $jumlahTiket = (int) ($request->jumlah_tiket ?? 1);

        $voucher = KodeVoucher::where('kode', $code)
            ->where('id_event', $request->event_id)
            ->where('status', true)
            ->first();

        if ($voucher) {
            return $this->respondWithLocalVoucher($voucher);
        }

        return $this->validateExternalVoucher($code, (int) $request->event_id, $jumlahTiket);
    }

    /**
     * Bangun response untuk voucher yang sudah ada di DB lokal (cek expiry & kuota).
     */
    private function respondWithLocalVoucher(KodeVoucher $voucher): JsonResponse
    {
        if ($voucher->tanggal_kadaluarsa < now()->startOfDay()) {
            return response()->json([
                'success' => false,
                'message' => 'Kode voucher sudah kadaluarsa.',
            ], 400);
        }

        if ($voucher->digunakan >= $voucher->kuota) {
            return response()->json([
                'success' => false,
                'message' => 'Kuota voucher sudah habis.',
            ], 400);
        }

        Log::info('Voucher validated successfully', [
            'voucher_id' => $voucher->id,
            'voucher_code' => $voucher->kode,
            'event_id' => $voucher->id_event,
            'discount_per_ticket' => $voucher->nilai_diskon,
            'is_external' => (bool) $voucher->is_external,
        ]);

        return response()->json([
            'success' => true,
            'message' => "Voucher '{$voucher->name_voucher}' berhasil diterapkan! Diskon Rp "
                . number_format($voucher->nilai_diskon, 0, ',', '.') . " per tiket",
            'discount_per_ticket' => $voucher->nilai_diskon,
            'voucher_id' => $voucher->id,
            'voucher_name' => $voucher->name_voucher,
            'is_external' => (bool) $voucher->is_external,
        ]);
    }

    /**
     * Validasi kode ke API eksternal; jika valid buat voucher lokal (kuota=1).
     */
    private function validateExternalVoucher(string $code, int $eventId, int $jumlahTiket): JsonResponse
    {
        $event = Event::findOrFail($eventId);
        $cfg = config('services.chatkebaikan');
        $url = rtrim($cfg['base_url'], '/') . str_replace('{kode}', $code, $cfg['validate_path']);

        try {
            $response = Http::timeout($cfg['timeout'])->get($url);
        } catch (\Throwable $e) {
            Log::error('External voucher validate request failed', [
                'kode' => $code,
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghubungi sistem voucher eksternal. Coba lagi.',
            ], 400);
        }

        if (!$response->successful()) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghubungi sistem voucher eksternal. Coba lagi.',
            ], 400);
        }

        $api = $response->json();

        if (!($api['valid'] ?? false)) {
            return response()->json([
                'success' => false,
                'message' => $api['reason'] ?? 'Kode voucher tidak valid di sistem eksternal.',
            ], 400);
        }

        if ($jumlahTiket > 1) {
            return response()->json([
                'success' => false,
                'message' => 'Voucher ini hanya berlaku untuk 1 volunteer (1 tiket).',
            ], 400);
        }

        $discountPercent = (int) ($api['discount_percent'] ?? 0);
        $nilaiDiskon = (int) round($event->harga * $discountPercent / 100);

        try {
            $voucher = KodeVoucher::create([
                'id_event' => $event->id,
                'name_voucher' => $api['reward_name'] ?? $code,
                'kode' => $code,
                'nilai_diskon' => $nilaiDiskon,
                'kuota' => 1,
                'digunakan' => 0,
                'tanggal_kadaluarsa' => Carbon::parse($api['berlaku_sampai'])->toDateString(),
                'status' => true,
                'is_external' => true,
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            // Race / dobel: kode sudah dibuat proses lain. Pakai yang sudah ada.
            $existing = KodeVoucher::where('kode', $code)->where('id_event', $event->id)->first();
            if (!$existing) {
                throw $e;
            }
            return $this->respondWithLocalVoucher($existing);
        }

        Log::info('External voucher created at checkout', [
            'voucher_id' => $voucher->id,
            'kode' => $code,
            'event_id' => $event->id,
            'discount_percent' => $discountPercent,
            'nilai_diskon' => $nilaiDiskon,
        ]);

        return $this->respondWithLocalVoucher($voucher);
    }

    private function canAccessPublicTransaction(Request $request, Transaksi $transaksi): bool
    {
        if (empty($transaksi->public_token)) {
            return true;
        }

        return $transaksi->hasPublicToken((string) $request->query('token', ''));
    }

}
