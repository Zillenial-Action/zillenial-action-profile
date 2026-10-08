<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Transaksi;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Admin: rekap transaksi per sumber UTM (last touch) untuk tracking kampanye.
 */
class UtmReportController extends Controller
{
    public function index(Request $request): View
    {
        $title = 'Laporan UTM';

        $filters = $request->validate([
            'dari' => ['nullable', 'date'],
            'sampai' => ['nullable', 'date', 'after_or_equal:dari'],
            'id_event' => ['nullable', 'integer', 'exists:events,id'],
        ]);

        $base = Transaksi::query()
            ->when($filters['dari'] ?? null, fn ($q, $dari) => $q->where('tanggal_register', '>=', $dari))
            ->when($filters['sampai'] ?? null, fn ($q, $sampai) => $q->where('tanggal_register', '<', now()->parse($sampai)->addDay()->startOfDay()))
            ->when($filters['id_event'] ?? null, fn ($q, $eventId) => $q->where('id_event', $eventId));

        $aggregates = "COUNT(*) AS transaksi,
            SUM(CASE WHEN status_pembayaran = 'Success' THEN 1 ELSE 0 END) AS sukses,
            SUM(CASE WHEN status_pembayaran = 'Success' THEN jumlah_tiket ELSE 0 END) AS tiket,
            SUM(CASE WHEN status_pembayaran = 'Success' THEN total_pembayaran ELSE 0 END) AS pendapatan";

        $data = (clone $base)
            ->selectRaw('utm_source, utm_medium, utm_campaign, '.$aggregates)
            ->groupBy('utm_source', 'utm_medium', 'utm_campaign')
            ->orderByDesc('pendapatan')
            ->orderByDesc('transaksi')
            ->paginate(25)
            ->withQueryString();

        $total = (clone $base)->selectRaw($aggregates)->toBase()->first();

        $events = Event::select(['id', 'name'])->orderByDesc('id')->get();

        return view('admin.utm.index', compact('title', 'data', 'total', 'events', 'filters'));
    }
}
