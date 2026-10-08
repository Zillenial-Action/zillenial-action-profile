@extends('components.layout.app')

@section('content')
    <div class="container-fluid px-3 px-lg-4 mt-3">

        <x-form-alerts />

        <div class="mb-3">
            <h2 class="page-title">Laporan UTM</h2>
            <p class="page-subtitle mb-0">
                Transaksi dikelompokkan berdasarkan UTM terakhir yang dibawa pembeli sebelum checkout (berlaku 30 hari).
                Detail semua parameter UTM ada di halaman detail transaksi.
            </p>
        </div>

        <form method="GET" action="{{ route('utm.index') }}" class="card mb-3">
            <div class="card-body d-flex flex-wrap align-items-end gap-2">
                <div>
                    <label for="dari" class="form-label small fw-semibold mb-1">Dari tanggal</label>
                    <input type="date" id="dari" name="dari" value="{{ $filters['dari'] ?? '' }}" class="form-control">
                </div>
                <div>
                    <label for="sampai" class="form-label small fw-semibold mb-1">Sampai tanggal</label>
                    <input type="date" id="sampai" name="sampai" value="{{ $filters['sampai'] ?? '' }}" class="form-control">
                </div>
                <div style="min-width: 220px;">
                    <label for="id_event" class="form-label small fw-semibold mb-1">Event</label>
                    <select id="id_event" name="id_event" class="form-select">
                        <option value="">Semua event</option>
                        @foreach ($events as $event)
                            <option value="{{ $event->id }}" @selected(($filters['id_event'] ?? null) == $event->id)>{{ $event->name }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="btn btn-primary"><i class="bi bi-funnel me-1"></i>Terapkan</button>
                @if (array_filter($filters))
                    <a href="{{ route('utm.index') }}" class="btn btn-outline-secondary">Reset</a>
                @endif
            </div>
        </form>

        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive nowrap-table"
                    style="border:0;box-shadow:none;border-radius:0;background:transparent;">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Source</th>
                                <th>Medium</th>
                                <th>Campaign</th>
                                <th class="text-end">Transaksi</th>
                                <th class="text-end">Berhasil</th>
                                <th class="text-end">Konversi</th>
                                <th class="text-end">Tiket Terjual</th>
                                <th class="text-end">Pendapatan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($data as $row)
                                <tr>
                                    @if ($row->utm_source === null && $row->utm_medium === null && $row->utm_campaign === null)
                                        <td colspan="3" class="text-muted fst-italic">Tanpa UTM (langsung / organik)</td>
                                    @else
                                        <td class="fw-semibold">{{ $row->utm_source ?? '-' }}</td>
                                        <td>{{ $row->utm_medium ?? '-' }}</td>
                                        <td>{{ $row->utm_campaign ?? '-' }}</td>
                                    @endif
                                    <td class="text-end">{{ (int) $row->transaksi }}</td>
                                    <td class="text-end">{{ (int) $row->sukses }}</td>
                                    <td class="text-end">{{ $row->transaksi > 0 ? number_format($row->sukses / $row->transaksi * 100, 1, ',', '.') : '0' }}%</td>
                                    <td class="text-end">{{ (int) $row->tiket }}</td>
                                    <td class="text-end fw-semibold">Rp {{ number_format((int) $row->pendapatan, 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">Belum ada transaksi pada filter ini.</td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if ($data->total() > 0)
                            <tfoot>
                                <tr class="table-light fw-semibold">
                                    <td colspan="3">Total</td>
                                    <td class="text-end">{{ (int) $total->transaksi }}</td>
                                    <td class="text-end">{{ (int) $total->sukses }}</td>
                                    <td class="text-end">{{ $total->transaksi > 0 ? number_format($total->sukses / $total->transaksi * 100, 1, ',', '.') : '0' }}%</td>
                                    <td class="text-end">{{ (int) $total->tiket }}</td>
                                    <td class="text-end">Rp {{ number_format((int) $total->pendapatan, 0, ',', '.') }}</td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-end mt-3">
            {!! $data->links('pagination::bootstrap-4') !!}
        </div>

    </div>

    <style>
        .nowrap-table th,
        .nowrap-table td {
            white-space: nowrap;
        }
    </style>
@endsection
