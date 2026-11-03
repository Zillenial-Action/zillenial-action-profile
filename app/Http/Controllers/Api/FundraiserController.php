<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FundraiserProgram;
use App\Models\KodeVoucher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Dashboard fundraiser di portal Sostrip: daftar program yang bisa diikuti,
 * kode milik customer, dan rekap komisinya.
 */
class FundraiserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $customer = $request->user('customer');

        $kodeByProgram = KodeVoucher::where('id_customer', $customer->id)
            ->whereNotNull('id_fundraiser_program')
            ->get()
            ->keyBy('id_fundraiser_program');

        // Program yang masih dibuka, ditambah program lama yang sudah punya kode
        // supaya riwayat komisi tetap terlihat setelah program ditutup.
        $programs = FundraiserProgram::with('event:id,name,slug,waktu_mulai,harga,image,status')
            ->where(fn ($query) => $query->open()->orWhereIn('id', $kodeByProgram->keys()))
            ->orderByDesc('id')
            ->get();

        $stats = $this->statsPerVoucher($kodeByProgram->pluck('id'));

        $items = $programs->map(fn (FundraiserProgram $program) => $this->programPayload(
            $program,
            $kodeByProgram->get($program->id),
            $stats
        ))->values();

        return response()->json([
            'data' => $items,
            'summary' => [
                'tiket_terjual' => (int) $stats->sum('tiket_terjual'),
                'komisi_didapat' => (int) $stats->sum('komisi_didapat'),
                'komisi_pending' => (int) $stats->sum('komisi_pending'),
            ],
        ]);
    }

    public function generate(Request $request, FundraiserProgram $program): JsonResponse
    {
        $program->load('event:id,name,slug,waktu_mulai,harga,image,status');

        if (! $program->isOpen()) {
            return response()->json([
                'message' => 'Program fundraiser untuk event ini sudah ditutup.',
            ], 422);
        }

        $customer = $request->user('customer');
        $kode = DB::transaction(fn () => $program->kodeForCustomer($customer));

        if ($kode->wasRecentlyCreated) {
            Log::info('Kode fundraiser dibuat', [
                'program_id' => $program->id,
                'customer_id' => $customer->id,
                'kode' => $kode->kode,
            ]);
        }

        return response()->json([
            'message' => $kode->wasRecentlyCreated ? 'Kode fundraiser berhasil dibuat.' : 'Kamu sudah punya kode untuk event ini.',
            'data' => $this->programPayload($program, $kode, $this->statsPerVoucher(collect([$kode->id]))),
        ], $kode->wasRecentlyCreated ? 201 : 200);
    }

    /**
     * @param  Collection<int, int>  $voucherIds
     * @return Collection<int, object{id_voucher: int, tiket_terjual: int, komisi_didapat: int, komisi_pending: int}>
     */
    private function statsPerVoucher(Collection $voucherIds): Collection
    {
        if ($voucherIds->isEmpty()) {
            return collect();
        }

        return DB::table('transaksis')
            ->whereIn('id_voucher', $voucherIds)
            ->whereNull('deleted_at')
            ->groupBy('id_voucher')
            ->selectRaw("id_voucher,
                SUM(CASE WHEN status_pembayaran = 'Success' THEN jumlah_tiket ELSE 0 END) AS tiket_terjual,
                SUM(CASE WHEN status_pembayaran = 'Success' THEN komisi_fundraiser ELSE 0 END) AS komisi_didapat,
                SUM(CASE WHEN status_pembayaran = 'Pending' THEN komisi_fundraiser ELSE 0 END) AS komisi_pending")
            ->get()
            ->keyBy('id_voucher');
    }

    private function programPayload(FundraiserProgram $program, ?KodeVoucher $kode, Collection $stats): array
    {
        $event = $program->event;
        $stat = $kode ? $stats->get($kode->id) : null;

        return [
            'id' => $program->id,
            'nilai_diskon' => $program->nilai_diskon,
            'nilai_komisi' => $program->nilai_komisi,
            'kuota_per_kode' => $program->kuota_per_kode,
            'tanggal_berakhir' => $program->tanggal_berakhir->toDateString(),
            'is_open' => $program->isOpen(),
            'event' => $event ? [
                'name' => $event->name,
                'slug' => $event->slug,
                'waktu_mulai' => optional($event->waktu_mulai)->toIso8601String(),
                'harga' => $event->harga,
                'image' => $event->image ? url('/'.$event->image) : null,
            ] : null,
            'kode' => $kode ? [
                'kode' => $kode->kode,
                'kuota' => (int) $kode->kuota,
                // Kode yang baru dibuat belum memuat default DB, jadi digunakan masih null.
                'digunakan' => (int) $kode->digunakan,
                'tiket_terjual' => (int) ($stat->tiket_terjual ?? 0),
                'komisi_didapat' => (int) ($stat->komisi_didapat ?? 0),
                'komisi_pending' => (int) ($stat->komisi_pending ?? 0),
                // UTM ikut di tautan supaya penjualan dari fundraiser terbaca di laporan UTM.
                'share_url' => $event ? rtrim((string) config('services.customer_auth.frontend_url'), '/')
                    .'/checkout/detail?'.http_build_query([
                        'slug' => $event->slug,
                        'voucher' => $kode->kode,
                        'utm_source' => 'fundraiser',
                        'utm_medium' => 'referral',
                        'utm_campaign' => $event->slug,
                        'utm_content' => $kode->kode,
                    ], '', '&', PHP_QUERY_RFC3986) : null,
            ] : null,
        ];
    }
}
