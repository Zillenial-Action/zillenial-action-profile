@extends('components.layout.app')

@section('content')
    <div class="container-fluid px-3 px-lg-4 mt-3">

        <div class="mb-4">
            <h2 class="page-title">Selamat datang, {{ auth()->user()->name ?? 'Admin' }} 👋</h2>
            <p class="page-subtitle">Ringkasan aktivitas Zillenial Action hari ini.</p>
        </div>

        {{-- Stat cards --}}
        <div class="row g-3 mb-4">
            @php
                $cards = [
                    ['label' => 'Total Event', 'value' => number_format($stats['events'] ?? 0, 0, ',', '.'), 'icon' => 'bi-calendar-event', 'tint' => '#7d297f'],
                    ['label' => 'Total Transaksi', 'value' => number_format($stats['transaksi_total'] ?? 0, 0, ',', '.'), 'icon' => 'bi-receipt', 'tint' => '#2563eb'],
                    ['label' => 'Transaksi Sukses', 'value' => number_format($stats['transaksi_sukses'] ?? 0, 0, ',', '.'), 'icon' => 'bi-check2-circle', 'tint' => '#15803d'],
                    ['label' => 'Pendapatan', 'value' => 'Rp ' . number_format($stats['pendapatan'] ?? 0, 0, ',', '.'), 'icon' => 'bi-cash-stack', 'tint' => '#b45309'],
                ];
            @endphp
            @foreach ($cards as $c)
                <div class="col-6 col-xl-3">
                    <div class="card h-100">
                        <div class="card-body d-flex align-items-center gap-3">
                            <div style="width:48px;height:48px;border-radius:12px;flex:none;display:flex;align-items:center;justify-content:center;background:{{ $c['tint'] }}1a;color:{{ $c['tint'] }};">
                                <i class="bi {{ $c['icon'] }}" style="font-size:22px;"></i>
                            </div>
                            <div class="min-w-0">
                                <div class="page-subtitle" style="margin:0;">{{ $c['label'] }}</div>
                                <div style="font-size:20px;font-weight:800;letter-spacing:-.02em;line-height:1.2;">{{ $c['value'] }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="row g-3">
            {{-- Quick actions --}}
            <div class="col-lg-4">
                <div class="card h-100">
                    <div class="card-header">Akses Cepat</div>
                    <div class="card-body d-flex flex-column gap-2">
                        @php
                            $links = [
                                ['route' => 'event.index', 'label' => 'Kelola Event', 'icon' => 'bi-calendar-event'],
                                ['route' => 'transaksi.index', 'label' => 'Kelola Transaksi', 'icon' => 'bi-receipt'],
                                ['route' => 'payment.index', 'label' => 'Metode Pembayaran', 'icon' => 'bi-credit-card'],
                                ['route' => 'voucher.index', 'label' => 'Kode Voucher', 'icon' => 'bi-ticket-perforated'],
                            ];
                        @endphp
                        @foreach ($links as $l)
                            <a href="{{ route($l['route']) }}"
                                class="d-flex align-items-center gap-3 text-decoration-none p-2 rounded"
                                style="color:var(--text);border:1px solid var(--border);transition:.15s;"
                                onmouseover="this.style.background='var(--brand-tint)'" onmouseout="this.style.background='transparent'">
                                <i class="bi {{ $l['icon'] }}" style="font-size:18px;color:var(--brand);width:22px;text-align:center;"></i>
                                <span style="font-weight:600;font-size:14px;">{{ $l['label'] }}</span>
                                <i class="bi bi-chevron-right ms-auto" style="font-size:13px;color:var(--muted);"></i>
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Recent transactions --}}
            <div class="col-lg-8">
                <div class="card h-100">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <span>Transaksi Terbaru</span>
                        <a href="{{ route('transaksi.index') }}" class="btn btn-sm btn-outline-primary">Lihat semua</a>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive" style="border:0;box-shadow:none;border-radius:0;">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Invoice</th>
                                        <th>Nama</th>
                                        <th>Total</th>
                                        <th>Status</th>
                                        <th>Tanggal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($recent as $trx)
                                        <tr>
                                            <td style="font-weight:600;">{{ $trx->invoice }}</td>
                                            <td>{{ $trx->name }}</td>
                                            <td>Rp {{ number_format($trx->total_pembayaran, 0, ',', '.') }}</td>
                                            <td>
                                                @php
                                                    $badge = match ($trx->status_pembayaran) {
                                                        'Success' => 'bg-success',
                                                        'Pending' => 'bg-warning text-dark',
                                                        default => 'bg-danger',
                                                    };
                                                @endphp
                                                <span class="badge {{ $badge }}">{{ $trx->status_pembayaran }}</span>
                                            </td>
                                            <td>{{ optional($trx->created_at)->format('d M Y') }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center text-muted py-4">Belum ada transaksi.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
@endsection
