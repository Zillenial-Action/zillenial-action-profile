<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Transaksi;
use App\Models\Event;
use App\Models\Payment;
use App\Models\Volunteer;
use App\Models\KodeVoucher;
use App\Exports\ExportTransaksi;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Maatwebsite\Excel\Facades\Excel;
use App\Http\Requests\StoreTransaksiRequest;
use App\Http\Requests\UpdateTransaksiRequest;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use App\Mail\SendTicket;

/**
 * Class TransaksiController
 *
 * Handles CRUD operations for transactions including soft delete management.
 */
class TransaksiController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        $title = 'Transaksi';
        $payment = Payment::select(['id', 'name'])->get();
        $events  = Event::orderBy('name')->get(['id', 'name']);

        $data = Transaksi::with(['event:id,name', 'payment:id,name', 'volunteers:id,name,telepon', 'voucher'])
            ->orderByDesc('created_at')
            ->paginate(15);

        return view('admin.transaksi.index', compact('title', 'data', 'payment', 'events'));
    }

    /**
     * Display a listing of trashed resources.
     */
    public function trashed(): View
    {
        $title = 'Transaksi Terhapus';

        $data = Transaksi::onlyTrashed()
            ->with(['event:id,name', 'payment:id,name'])
            ->orderByDesc('deleted_at')
            ->paginate(5);

        return view('admin.transaksi.trashed', compact('title', 'data'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $title = 'Create Transaksi';
        $payment = Payment::select(['id', 'name'])->where('status', true)->get();
        $events = Event::select(['id', 'name', 'harga'])->where('status', true)->get();

        return view('admin.transaksi.create', compact('title', 'payment', 'events'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTransaksiRequest $request): RedirectResponse
    {
        try {
            $validated = $request->validated();

            $invoice = date('YmdHis') . uniqid();

            DB::transaction(function () use ($validated, $invoice) {
                $affected = Event::where('id', $validated['id_event'])
                    ->where('status', true)
                    ->where('jumlah_tiket', '>=', $validated['jumlah_tiket'])
                    ->decrement('jumlah_tiket', $validated['jumlah_tiket']);

                if ($affected === 0) {
                    throw new \Exception('Stok tiket tidak mencukupi atau event tidak aktif.');
                }

                $event = Event::findOrFail($validated['id_event']);

                Transaksi::create([
                    'id_event'           => $validated['id_event'],
                    'invoice'            => $invoice,
                    'jumlah_tiket'       => $validated['jumlah_tiket'],
                    'total_pembayaran'   => $validated['jumlah_tiket'] * $event->harga,
                    'name'               => $validated['name'],
                    'email'              => $validated['email'],
                    'telepon'            => $validated['telepon'],
                    'status_pembayaran'  => 'Pending',
                    'tanggal_register'   => now(),
                    'tanggal_pembayaran' => null,
                    'id_payment'         => $validated['id_payment'],
                ]);
            });

            Log::info('Transaction created via admin', [
                'invoice'  => $invoice,
                'event_id' => $validated['id_event'],
                'user_id'  => auth()->id(),
            ]);

            return redirect()->route('transaksi.index')->with('success', 'Transaksi berhasil ditambahkan');
        } catch (\Exception $e) {
            Log::error('Error creating transaction', [
                'error'   => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);
            return redirect()->route('transaksi.create')->with('error', 'Gagal menambahkan transaksi: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Transaksi $transaksi): View
    {
        $title = 'Show Transaksi';
        $transaksi->load(['event', 'payment', 'volunteers']);

        return view('admin.transaksi.show', compact('title', 'transaksi'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Transaksi $transaksi): View
    {
        $title = 'Edit Transaksi';
        $payment = Payment::select(['id', 'name'])->get();
        $events = Event::select(['id', 'name', 'harga'])->get();

        return view('admin.transaksi.edit', compact('title', 'transaksi', 'payment', 'events'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateTransaksiRequest $request, Transaksi $transaksi): RedirectResponse
    {
        try {
            $validated = $request->validated();

            $event = Event::findOrFail($validated['id_event']);

            $transaksi->update([
                'id_event' => $validated['id_event'],
                'jumlah_tiket' => $validated['jumlah_tiket'],
                'total_pembayaran' => $validated['jumlah_tiket'] * $event->harga,
                'name' => $validated['name'],
                'email' => $validated['email'],
                'telepon' => $validated['telepon'],
                'id_payment' => $validated['id_payment'],
            ]);

            Log::info('Transaction updated', [
                'transaksi_id' => $transaksi->id,
                'invoice' => $transaksi->invoice,
                'user_id' => auth()->id(),
            ]);

            return redirect()->route('transaksi.index')->with('success', 'Transaksi berhasil diperbarui');
        } catch (\Exception $e) {
            Log::error('Error updating transaction', [
                'transaksi_id' => $transaksi->id,
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);
            return redirect()->route('transaksi.index')->with('error', 'Gagal memperbarui transaksi');
        }
    }

    /**
     * Remove the specified resource from storage (soft delete).
     */
    public function destroy(Transaksi $transaksi): RedirectResponse
    {
        try {
            $transaksiId = $transaksi->id;
            $invoice     = $transaksi->invoice;

            DB::transaction(function () use ($transaksi) {
                if ($transaksi->id_event) {
                    Event::where('id', $transaksi->id_event)
                        ->increment('jumlah_tiket', $transaksi->jumlah_tiket);
                }
                $transaksi->delete();
            });

            Log::info('Transaction soft deleted', [
                'transaksi_id' => $transaksiId,
                'invoice'      => $invoice,
                'user_id'      => auth()->id(),
            ]);

            return redirect()->route('transaksi.index')->with('success', 'Transaksi berhasil dihapus');
        } catch (\Exception $e) {
            Log::error("Error deleting transaksi ID {$transaksi->id}: " . $e->getMessage());

            return redirect()->route('transaksi.index')->with('error', 'Gagal menghapus transaksi');
        }
    }

    /**
     * Restore the specified trashed resource.
     */
    public function restore(int $id): RedirectResponse
    {
        try {
            $transaksi = Transaksi::onlyTrashed()->findOrFail($id);

            DB::transaction(function () use ($transaksi) {
                if ($transaksi->id_event) {
                    Event::where('id', $transaksi->id_event)
                        ->decrement('jumlah_tiket', $transaksi->jumlah_tiket);
                }
                $transaksi->restore();
            });

            Log::info('Transaction restored', [
                'transaksi_id' => $transaksi->id,
                'invoice'      => $transaksi->invoice,
                'user_id'      => auth()->id(),
            ]);

            return redirect()->route('transaksi.trashed')->with('success', 'Transaksi berhasil dipulihkan');
        } catch (\Exception $e) {
            Log::error("Error restoring transaksi ID {$id}: " . $e->getMessage());

            return redirect()->route('transaksi.trashed')->with('error', 'Gagal memulihkan transaksi');
        }
    }

    /**
     * Permanently delete the specified trashed resource.
     */
    public function forceDelete(int $id): RedirectResponse
    {
        try {
            $transaksi = Transaksi::onlyTrashed()->findOrFail($id);
            $invoice = $transaksi->invoice;

            // Detach volunteers before permanent deletion
            $transaksi->volunteers()->detach();

            $transaksi->forceDelete();

            Log::warning('Transaction permanently deleted', [
                'transaksi_id' => $id,
                'invoice' => $invoice,
                'user_id' => auth()->id(),
            ]);

            return redirect()->route('transaksi.trashed')->with('success', 'Transaksi berhasil dihapus permanen');
        } catch (\Exception $e) {
            Log::error("Error force deleting transaksi ID {$id}: " . $e->getMessage());

            return redirect()->route('transaksi.trashed')->with('error', 'Gagal menghapus transaksi permanen');
        }
    }

    /**
     * Export transactions to Excel.
     */
    public function export(Request $request)
    {
        $filters = $request->only([
            'tanggal_awal', 'tanggal_akhir', 'id_event', 'status_pembayaran', 'id_payment',
        ]);

        Log::info('Transaction export requested', [
            'user_id' => auth()->id(),
            'filters' => $filters,
        ]);

        return Excel::download(new ExportTransaksi($filters), 'Transaksi.xlsx');
    }

    /**
     * Filter transactions.
     */
    public function filter(Request $request): View
    {
        $title   = 'Filter Transaksi';
        $payment = Payment::select(['id', 'name'])->get();
        $events  = Event::orderBy('name')->get(['id', 'name']);

        $data = Transaksi::with(['event:id,name', 'payment:id,name', 'volunteers:id,name,telepon', 'voucher'])
            ->orderByDesc('created_at')
            ->when($request->tanggal_awal && $request->tanggal_akhir, fn($q) =>
                $q->whereDate('tanggal_register', '>=', $request->tanggal_awal)
                  ->whereDate('tanggal_register', '<=', $request->tanggal_akhir)
            )
            ->when($request->tanggal_awal && !$request->tanggal_akhir, fn($q) =>
                $q->whereDate('tanggal_register', $request->tanggal_awal)
            )
            ->when($request->id_event, fn($q) =>
                $q->where('id_event', $request->id_event)
            )
            ->when($request->status_pembayaran, fn($q) =>
                $q->where('status_pembayaran', $request->status_pembayaran)
            )
            ->when($request->id_payment, fn($q) =>
                $q->where('id_payment', $request->id_payment)
            )
            ->paginate(15)
            ->appends($request->only(['tanggal_awal', 'tanggal_akhir', 'id_event', 'status_pembayaran', 'id_payment']));

        return view('admin.transaksi.index', compact('title', 'data', 'payment', 'events'));
    }

    /**
     * Search transactions.
     */
    public function search(Request $request): View
    {
        $title   = 'Search Transaksi';
        $payment = Payment::select(['id', 'name'])->get();
        $events  = Event::orderBy('name')->get(['id', 'name']);

        $data = Transaksi::with(['event:id,name', 'payment:id,name', 'volunteers:id,name,telepon', 'voucher'])
            ->orderByDesc('created_at')
            ->when($request->search, fn($q) =>
                $q->where(fn($query) =>
                    $query->where('id', 'like', '%' . $request->search . '%')
                          ->orWhere('invoice', 'like', '%' . $request->search . '%')
                          ->orWhere('name', 'like', '%' . $request->search . '%')
                          ->orWhere('telepon', 'like', '%' . $request->search . '%')
                          ->orWhere('email', 'like', '%' . $request->search . '%')
                          ->orWhereHas('event', fn($eq) =>
                              $eq->where('name', 'like', '%' . $request->search . '%')
                          )
                )
            )
            ->paginate(15)
            ->appends($request->only(['search']));

        return view('admin.transaksi.index', compact('title', 'data', 'payment', 'events'));
    }

    /**
     * Update payment status.
     */
    public function updateStatus(Request $request): JsonResponse
    {
        $transaksi = Transaksi::with('volunteers')->find($request->id);

        if (!$transaksi) {
            return response()->json(['message' => 'Item tidak ditemukan!'], 404);
        }

        $newStatus = $request->input('status', 'Success');
        $oldStatus = $transaksi->status_pembayaran;

        if ($newStatus === 'Success') {
            if ($transaksi->volunteers->isEmpty()) {
                return response()->json(['message' => 'Tidak ada volunteer yang terdaftar untuk transaksi ini!'], 400);
            }

            try {
                DB::transaction(function () use ($transaksi, $oldStatus) {
                    $fresh = Transaksi::where('id', $transaksi->id)
                        ->lockForUpdate()
                        ->first();

                    if (! $fresh || $fresh->status_pembayaran === 'Success') {
                        return;
                    }

                    $fresh->update([
                        'status_pembayaran'  => 'Success',
                        'tanggal_pembayaran' => now(),
                    ]);

                    if ($oldStatus === 'Failed') {
                        Event::where('id', $fresh->id_event)
                            ->lockForUpdate()
                            ->decrement('jumlah_tiket', $fresh->jumlah_tiket);
                    }
                });
            } catch (\Exception $e) {
                Log::error('Error updating transaction status', [
                    'transaksi_id' => $transaksi->id,
                    'error'        => $e->getMessage(),
                    'user_id'      => auth()->id(),
                ]);

                return response()->json(['message' => 'Terjadi kesalahan saat memproses transaksi.'], 500);
            }

            $sentEmails  = [];
            $failedEmails = [];
            $ticketUrl   = $this->ticketUrl($transaksi);

            foreach ($transaksi->volunteers as $volunteer) {
                try {
                    Mail::to($volunteer->email)->queue(
                        new SendTicket($transaksi->invoice, $ticketUrl)
                    );
                    $sentEmails[] = $volunteer->email;
                } catch (\Throwable $emailException) {
                    $failedEmails[] = $volunteer->email;
                    Log::error('Gagal mengantri email tiket (updateStatus admin)', [
                        'invoice'         => $transaksi->invoice,
                        'volunteer_email' => $volunteer->email,
                        'error'           => $emailException->getMessage(),
                        'user_id'         => auth()->id(),
                    ]);
                }
            }

            if ($transaksi->id_voucher) {
                $voucher = KodeVoucher::find($transaksi->id_voucher);
                if ($voucher && $voucher->is_external) {
                    try {
                        $cfg = config('services.chatkebaikan');
                        $url = $cfg['redeem_url'] ?? '';
                        if (! empty($url)) {
                            Http::timeout($cfg['timeout'] ?? 10)
                                ->acceptJson()
                                ->withOptions(['allow_redirects' => ['strict' => true]])
                                ->send(strtoupper($cfg['redeem_method'] ?? 'POST'), $url, [
                                    'json' => ['kode' => $voucher->kode],
                                ]);
                        }
                    } catch (\Exception $redeemException) {
                        Log::error('Failed to redeem external voucher', [
                            'kode'    => $voucher->kode,
                            'invoice' => $transaksi->invoice,
                            'error'   => $redeemException->getMessage(),
                        ]);
                    }
                }
            }

            Log::info('Transaction status updated to Success', [
                'transaksi_id' => $transaksi->id,
                'invoice'      => $transaksi->invoice,
                'sent_emails'  => $sentEmails,
                'user_id'      => auth()->id(),
            ]);

            $msg = 'Status berhasil diperbarui! Email diantri ke ' . count($sentEmails) . ' volunteer.';
            if (! empty($failedEmails)) {
                $msg .= ' Gagal antre: ' . implode(', ', $failedEmails) . '.';
            }

            return response()->json([
                'message'           => $msg,
                'sent_emails'       => $sentEmails,
                'tanggal_pembayaran' => now()->format('Y-m-d H:i:s'),
            ]);
        } else {
            // Update status to Pending or Failed
            try {
                $transaksi->update([
                    'status_pembayaran' => $newStatus,
                    'tanggal_pembayaran' => null,
                ]);

                // Return tickets if status changed to Failed
                if ($newStatus === 'Failed' && $oldStatus !== 'Failed') {
                    if ($transaksi->event) {
                        $transaksi->event->increment('jumlah_tiket', $transaksi->jumlah_tiket);
                    }
                }

                Log::info("Transaction status updated to {$newStatus}", [
                    'transaksi_id' => $transaksi->id,
                    'invoice' => $transaksi->invoice,
                    'user_id' => auth()->id(),
                ]);

                return response()->json([
                    'message' => "Status berhasil diperbarui menjadi {$newStatus}!",
                    'tanggal_pembayaran' => null,
                ]);
            } catch (\Exception $e) {
                Log::error('Error updating transaction status', [
                    'transaksi_id' => $transaksi->id,
                    'error' => $e->getMessage(),
                    'user_id' => auth()->id(),
                ]);

                return response()->json([
                    'message' => 'Terjadi kesalahan saat memperbarui status: ' . $e->getMessage(),
                ], 500);
            }
        }
    }

    /**
     * Kirim ulang email tiket secara manual dari menu transaksi.
     *
     * Dikirim SINKRON (bukan antrean) agar admin langsung tahu berhasil/gagal — berguna saat
     * job antrean gagal/timeout (lihat menu "Email Tidak Terkirim"). Tidak mengubah status
     * pembayaran; murni mengirim ulang tiket. Penerima: seluruh volunteer; bila tidak ada
     * volunteer, jatuh ke email pembeli.
     */
    public function sendTicketEmail(Transaksi $transaksi): JsonResponse
    {
        try {
            $transaksi->loadMissing('volunteers');

            // Penerima: volunteer bila ada, jika kosong pakai email pembeli sebagai cadangan.
            $recipients = $transaksi->volunteers->pluck('email')->filter()->unique()->values();

            if ($recipients->isEmpty() && ! empty($transaksi->email)) {
                $recipients = collect([$transaksi->email]);
            }

            if ($recipients->isEmpty()) {
                return response()->json(['message' => 'Tidak ada penerima email untuk transaksi ini.'], 400);
            }

            $ticketUrl = $this->ticketUrl($transaksi);
            $sent = [];
            $failed = [];

            foreach ($recipients as $email) {
                try {
                    // Kirim SINKRON meski SendTicket implements ShouldQueue: panggil send()
                    // langsung pada Mailable (bukan Mail::to()->send(), yang akan mengantre),
                    // supaya admin langsung tahu berhasil/gagal saat itu juga.
                    (new SendTicket($transaksi->invoice, $ticketUrl))
                        ->to($email)
                        ->send(app('mailer'));
                    $sent[] = $email;

                    Log::info('Ticket email manually resent', [
                        'invoice' => $transaksi->invoice,
                        'recipient' => $email,
                        'user_id' => auth()->id(),
                    ]);
                } catch (\Throwable $e) {
                    // Simpan pesan error nyata per penerima agar admin melihat penyebabnya.
                    $failed[$email] = $e->getMessage();

                    Log::error('Failed to manually resend ticket email', [
                        'invoice' => $transaksi->invoice,
                        'recipient' => $email,
                        'error' => $e->getMessage(),
                        'user_id' => auth()->id(),
                    ]);
                }
            }

            if (! empty($failed)) {
                $detail = [];
                foreach ($failed as $email => $err) {
                    $detail[] = $email.' ('.$err.')';
                }

                return response()->json([
                    'message' => 'Gagal mengirim ke: '.implode('; ', $detail).'.'
                        .(empty($sent) ? '' : ' Berhasil ke: '.implode(', ', $sent).'.'),
                    'sent' => $sent,
                    'failed' => array_keys($failed),
                ], 500);
            }

            return response()->json([
                'message' => 'Email tiket berhasil dikirim ke '.count($sent).' penerima ('.implode(', ', $sent).').',
                'sent' => $sent,
            ]);
        } catch (\Throwable $e) {
            // Tangkap error di luar loop (mis. saat memuat data) agar respons tetap JSON
            // dengan pesan asli, bukan halaman HTML 500 generik.
            Log::error('sendTicketEmail gagal', [
                'invoice' => $transaksi->invoice ?? null,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Gagal mengirim email: '.$e->getMessage(),
            ], 500);
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
