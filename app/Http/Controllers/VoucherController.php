<?php

namespace App\Http\Controllers;

use App\Models\KodeVoucher;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Log;

/**
 * Class VoucherController
 *
 * Handles CRUD operations for voucher codes including soft delete management.
 */
class VoucherController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        $title = 'Kode Voucher';

        $data = KodeVoucher::with('event')
            ->orderByDesc('created_at')
            ->paginate(10);

        return view('admin.voucher.index', compact('title', 'data'));
    }

    /**
     * Display a listing of trashed resources.
     */
    public function trashed(): View
    {
        $title = 'Voucher Terhapus';

        $data = KodeVoucher::onlyTrashed()
            ->with('event')
            ->orderByDesc('deleted_at')
            ->paginate(10);

        return view('admin.voucher.trashed', compact('title', 'data'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $title = 'Tambah Voucher';
        $events = Event::select(['id', 'name'])->orderByDesc('id')->get();

        return view('admin.voucher.create', compact('title', 'events'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'id_event' => 'required|exists:events,id',
            'name_voucher' => 'required|string|max:100',
            'kode' => 'required|string|max:50|unique:kode_vouchers,kode',
            'nilai_diskon' => 'required|integer|min:1',
            'kuota' => 'required|integer|min:1',
            'tanggal_kadaluarsa' => 'required|date|after:today',
        ]);

        try {
            $voucher = KodeVoucher::create([
                'id_event' => $request->id_event,
                'name_voucher' => $request->name_voucher,
                'kode' => strtoupper($request->kode),
                'nilai_diskon' => $request->nilai_diskon,
                'kuota' => $request->kuota,
                'digunakan' => 0,
                'tanggal_kadaluarsa' => $request->tanggal_kadaluarsa,
                'status' => true,
            ]);

            Log::info('Voucher created', [
                'voucher_id' => $voucher->id,
                'voucher_code' => $voucher->kode,
                'event_id' => $voucher->id_event,
                'user_id' => auth()->id(),
            ]);

            return redirect()->route('voucher.index')->with('success', 'Voucher berhasil ditambahkan');
        } catch (\Exception $e) {
            Log::error('Error creating voucher: ' . $e->getMessage());
            return redirect()->route('voucher.create')->with('error', 'Gagal menambahkan voucher');
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(KodeVoucher $voucher): View
    {
        $title = 'Detail Voucher';
        $voucher->load('event');

        return view('admin.voucher.show', compact('title', 'voucher'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(KodeVoucher $voucher): View|RedirectResponse
    {
        if ($voucher->id_fundraiser_program) {
            return $this->redirectToFundraiser($voucher);
        }

        $title = 'Edit Voucher';
        $events = Event::select(['id', 'name'])->orderByDesc('id')->get();

        return view('admin.voucher.edit', compact('title', 'voucher', 'events'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, KodeVoucher $voucher): RedirectResponse
    {
        if ($voucher->id_fundraiser_program) {
            return $this->redirectToFundraiser($voucher);
        }

        $request->validate([
            'id_event' => 'required|exists:events,id',
            'name_voucher' => 'required|string|max:100',
            'kode' => 'required|string|max:50|unique:kode_vouchers,kode,' . $voucher->id,
            'nilai_diskon' => 'required|integer|min:1',
            'kuota' => 'required|integer|min:1',
            'tanggal_kadaluarsa' => 'required|date',
            'status' => 'required|boolean',
        ]);

        try {
            $voucher->update([
                'id_event' => $request->id_event,
                'name_voucher' => $request->name_voucher,
                'kode' => strtoupper($request->kode),
                'nilai_diskon' => $request->nilai_diskon,
                'kuota' => $request->kuota,
                'tanggal_kadaluarsa' => $request->tanggal_kadaluarsa,
                'status' => $request->status,
            ]);

            Log::info('Voucher updated', [
                'voucher_id' => $voucher->id,
                'voucher_code' => $voucher->kode,
                'user_id' => auth()->id(),
            ]);

            return redirect()->route('voucher.index')->with('success', 'Voucher berhasil diperbarui');
        } catch (\Exception $e) {
            Log::error('Error updating voucher: ' . $e->getMessage());
            return redirect()->route('voucher.index')->with('error', 'Gagal memperbarui voucher');
        }
    }

    /**
     * Remove the specified resource from storage (soft delete).
     */
    public function destroy(KodeVoucher $voucher): RedirectResponse
    {
        if ($voucher->id_fundraiser_program) {
            return $this->redirectToFundraiser($voucher);
        }

        try {
            $voucherId = $voucher->id;
            $voucherCode = $voucher->kode;
            
            $voucher->delete();
            
            Log::info('Voucher soft deleted', [
                'voucher_id' => $voucherId,
                'voucher_code' => $voucherCode,
                'user_id' => auth()->id(),
            ]);
            
            return redirect()->route('voucher.index')->with('success', 'Voucher berhasil dihapus');
        } catch (\Exception $e) {
            Log::error("Error deleting voucher ID {$voucher->id}: " . $e->getMessage());
            return redirect()->route('voucher.index')->with('error', 'Gagal menghapus voucher');
        }
    }

    /**
     * Restore the specified trashed resource.
     */
    public function restore(int $id): RedirectResponse
    {
        try {
            $voucher = KodeVoucher::onlyTrashed()->findOrFail($id);
            $voucher->restore();

            Log::info('Voucher restored', [
                'voucher_id' => $voucher->id,
                'voucher_code' => $voucher->kode,
                'user_id' => auth()->id(),
            ]);

            return redirect()->route('voucher.trashed')->with('success', 'Voucher berhasil dipulihkan');
        } catch (\Exception $e) {
            Log::error("Error restoring voucher ID {$id}: " . $e->getMessage());
            return redirect()->route('voucher.trashed')->with('error', 'Gagal memulihkan voucher');
        }
    }

    /**
     * Permanently delete the specified trashed resource.
     */
    public function forceDelete(int $id): RedirectResponse
    {
        try {
            $voucher = KodeVoucher::onlyTrashed()->findOrFail($id);
            $voucherCode = $voucher->kode;
            
            $voucher->forceDelete();

            Log::warning('Voucher permanently deleted', [
                'voucher_id' => $id,
                'voucher_code' => $voucherCode,
                'user_id' => auth()->id(),
            ]);

            return redirect()->route('voucher.trashed')->with('success', 'Voucher berhasil dihapus permanen');
        } catch (\Exception $e) {
            Log::error("Error force deleting voucher ID {$id}: " . $e->getMessage());
            return redirect()->route('voucher.trashed')->with('error', 'Gagal menghapus voucher permanen');
        }
    }

    /**
     * Search vouchers.
     */
    public function search(Request $request): View
    {
        $title = 'Cari Voucher';

        $data = KodeVoucher::with('event')
            ->orderByDesc('created_at')
            ->when($request->search, fn($q) => 
                $q->where(fn($query) => 
                    $query->where('name_voucher', 'like', '%' . $request->search . '%')
                          ->orWhere('kode', 'like', '%' . $request->search . '%')
                          ->orWhereHas('event', fn($eq) => 
                              $eq->where('name', 'like', '%' . $request->search . '%')
                          )
                )
            )
            ->paginate(10);

        return view('admin.voucher.index', compact('title', 'data'));
    }

    /**
     * Kode fundraiser mengikuti setting programnya, jadi diubah lewat menu Fundraiser.
     * Menghapusnya di sini juga membuat fundraiser tidak bisa membuat kode lagi
     * karena unique index (program, customer) tetap berlaku untuk baris terhapus.
     */
    private function redirectToFundraiser(KodeVoucher $voucher): RedirectResponse
    {
        return redirect()->route('fundraiser.show', $voucher->id_fundraiser_program)
            ->with('error', 'Kode fundraiser diatur lewat program fundraiser. Ubah diskon, kuota, tanggal, atau status di sini.');
    }
}
