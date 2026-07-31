<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Transaksi;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Class DashboardController
 *
 * Handles the dashboard display.
 */
class DashboardController extends Controller
{
    /**
     * Display the dashboard.
     */
    public function index(): View
    {
        $title = 'Dashboard';

        $stats = Cache::remember('dashboard_stats', 60, function () {
            $transaksiStats = DB::table('transaksis')
                ->whereNull('deleted_at')
                ->selectRaw("
                    COUNT(*) as total,
                    SUM(status_pembayaran = 'Success') as sukses,
                    SUM(status_pembayaran = 'Pending') as pending,
                    SUM(CASE WHEN status_pembayaran = 'Success' THEN total_pembayaran ELSE 0 END) as pendapatan
                ")
                ->first();

            return [
                'events'            => Event::count(),
                'transaksi_total'   => (int) $transaksiStats->total,
                'transaksi_sukses'  => (int) $transaksiStats->sukses,
                'transaksi_pending' => (int) $transaksiStats->pending,
                'pendapatan'        => (int) $transaksiStats->pendapatan,
            ];
        });

        $recent = Transaksi::latest('id')
            ->select(['id', 'invoice', 'name', 'email', 'status_pembayaran', 'total_pembayaran', 'tanggal_register', 'created_at'])
            ->take(6)
            ->get();

        return view('admin.dashboard.index', compact('title', 'stats', 'recent'));
    }
}
