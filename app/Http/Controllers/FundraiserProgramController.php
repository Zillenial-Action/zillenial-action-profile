<?php

namespace App\Http\Controllers;

use App\Http\Requests\FundraiserProgramRequest;
use App\Models\Event;
use App\Models\FundraiserProgram;
use App\Models\KodeVoucher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * Admin: setting program fundraiser per event (diskon untuk pembeli, komisi
 * untuk fundraiser) dan rekap kode yang dibuat para fundraiser.
 */
class FundraiserProgramController extends Controller
{
    public function index(): View
    {
        $title = 'Fundraiser';

        $data = FundraiserProgram::with('event:id,name,harga')
            ->withCount('kodeVouchers')
            ->orderByDesc('id')
            ->paginate(10);

        $totals = DB::table('transaksis')
            ->join('kode_vouchers', 'kode_vouchers.id', '=', 'transaksis.id_voucher')
            ->whereIn('kode_vouchers.id_fundraiser_program', $data->pluck('id'))
            ->where('transaksis.status_pembayaran', 'Success')
            ->whereNull('transaksis.deleted_at')
            ->groupBy('kode_vouchers.id_fundraiser_program')
            ->selectRaw('kode_vouchers.id_fundraiser_program, SUM(transaksis.jumlah_tiket) AS tiket, SUM(transaksis.komisi_fundraiser) AS komisi')
            ->get()
            ->keyBy('id_fundraiser_program');

        return view('admin.fundraiser.index', compact('title', 'data', 'totals'));
    }

    public function create(): View
    {
        $title = 'Tambah Program Fundraiser';
        $events = $this->eventOptions();

        return view('admin.fundraiser.create', compact('title', 'events'));
    }

    public function store(FundraiserProgramRequest $request): RedirectResponse
    {
        $program = FundraiserProgram::create([
            ...$request->safe()->only(['id_event', 'nilai_diskon', 'nilai_komisi', 'kuota_per_kode', 'tanggal_berakhir']),
            'status' => $request->boolean('status', true),
        ]);

        Log::info('Program fundraiser dibuat', ['program_id' => $program->id, 'event_id' => $program->id_event, 'user_id' => auth()->id()]);

        return redirect()->route('fundraiser.show', $program)->with('success', 'Program fundraiser berhasil dibuat.');
    }

    public function show(FundraiserProgram $fundraiser): View
    {
        $title = 'Detail Program Fundraiser';
        $program = $fundraiser->load('event:id,name,harga');

        $kodes = KodeVoucher::with('customer:id,name,email')
            ->where('id_fundraiser_program', $program->id)
            ->withSum(['transaksis as tiket_terjual' => fn ($q) => $q->where('status_pembayaran', 'Success')], 'jumlah_tiket')
            ->withSum(['transaksis as komisi_didapat' => fn ($q) => $q->where('status_pembayaran', 'Success')], 'komisi_fundraiser')
            ->withSum(['transaksis as komisi_pending' => fn ($q) => $q->where('status_pembayaran', 'Pending')], 'komisi_fundraiser')
            ->orderByDesc('komisi_didapat')
            ->paginate(20);

        // Total seluruh kode di program ini, bukan hanya halaman yang tampil.
        $total = DB::table('transaksis')
            ->join('kode_vouchers', 'kode_vouchers.id', '=', 'transaksis.id_voucher')
            ->where('kode_vouchers.id_fundraiser_program', $program->id)
            ->whereNull('transaksis.deleted_at')
            ->selectRaw("
                COALESCE(SUM(CASE WHEN transaksis.status_pembayaran = 'Success' THEN transaksis.jumlah_tiket ELSE 0 END), 0) AS tiket,
                COALESCE(SUM(CASE WHEN transaksis.status_pembayaran = 'Success' THEN transaksis.komisi_fundraiser ELSE 0 END), 0) AS komisi,
                COALESCE(SUM(CASE WHEN transaksis.status_pembayaran = 'Pending' THEN transaksis.komisi_fundraiser ELSE 0 END), 0) AS pending")
            ->first();

        return view('admin.fundraiser.show', compact('title', 'program', 'kodes', 'total'));
    }

    public function edit(FundraiserProgram $fundraiser): View
    {
        $title = 'Edit Program Fundraiser';
        $program = $fundraiser;
        $events = $this->eventOptions();

        return view('admin.fundraiser.edit', compact('title', 'program', 'events'));
    }

    public function update(FundraiserProgramRequest $request, FundraiserProgram $fundraiser): RedirectResponse
    {
        if ((int) $request->input('id_event') !== $fundraiser->id_event && $fundraiser->kodeVouchers()->exists()) {
            return back()->withInput()->with('error', 'Event tidak bisa diganti karena sudah ada kode fundraiser di program ini.');
        }

        DB::transaction(function () use ($request, $fundraiser) {
            $fundraiser->update($request->safe()->only([
                'id_event', 'nilai_diskon', 'nilai_komisi', 'kuota_per_kode', 'tanggal_berakhir', 'status',
            ]));
            $fundraiser->syncKodeVouchers();
        });

        Log::info('Program fundraiser diperbarui', ['program_id' => $fundraiser->id, 'user_id' => auth()->id()]);

        return redirect()->route('fundraiser.show', $fundraiser)->with('success', 'Program fundraiser berhasil diperbarui.');
    }

    public function destroy(FundraiserProgram $fundraiser): RedirectResponse
    {
        // Program yang sudah punya kode menyimpan riwayat komisi; cukup dinonaktifkan.
        if ($fundraiser->kodeVouchers()->exists()) {
            return back()->with('error', 'Program sudah punya kode fundraiser. Nonaktifkan program lewat Edit.');
        }

        $fundraiser->delete();

        Log::info('Program fundraiser dihapus', ['program_id' => $fundraiser->id, 'user_id' => auth()->id()]);

        return redirect()->route('fundraiser.index')->with('success', 'Program fundraiser berhasil dihapus.');
    }

    private function eventOptions()
    {
        return Event::select(['id', 'name', 'harga'])->orderByDesc('id')->get();
    }
}
