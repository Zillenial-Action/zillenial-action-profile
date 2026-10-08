@extends('components.layout.app')

@section('content')
    <div class="container-fluid px-3 px-lg-4 mt-3">

        <x-form-alerts />

        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
            <div>
                <h2 class="page-title">Fundraiser</h2>
                <p class="page-subtitle mb-0">Atur diskon pembeli dan komisi fundraiser per event. Fundraiser membuat kodenya sendiri dari akun Sostrip.</p>
            </div>
            <a href="{{ route('fundraiser.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg me-1"></i>Tambah Program
            </a>
        </div>

        <div class="card">
            <div class="card-header">Daftar Program Fundraiser</div>
            <div class="card-body p-0">
                <div class="table-responsive nowrap-table"
                    style="border:0;box-shadow:none;border-radius:0;background:transparent;">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Event</th>
                                <th>Diskon / tiket</th>
                                <th>Komisi / tiket</th>
                                <th>Kuota / kode</th>
                                <th>Berakhir</th>
                                <th>Fundraiser</th>
                                <th>Tiket Terjual</th>
                                <th>Total Komisi</th>
                                <th>Status</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($data as $item)
                                @php
                                    $total = $totals->get($item->id);
                                @endphp
                                <tr>
                                    <td>{{ $loop->iteration + ($data->currentPage() - 1) * $data->perPage() }}</td>
                                    <td class="fw-semibold">{{ $item->event->name ?? '-' }}</td>
                                    <td>Rp {{ number_format($item->nilai_diskon, 0, ',', '.') }}</td>
                                    <td>Rp {{ number_format($item->nilai_komisi, 0, ',', '.') }}</td>
                                    <td>{{ $item->kuota_per_kode }}</td>
                                    <td class="{{ $item->tanggal_berakhir->lt(today()) ? 'text-danger fw-semibold' : '' }}">
                                        {{ $item->tanggal_berakhir->format('d-m-Y') }}
                                    </td>
                                    <td>{{ $item->kode_vouchers_count }}</td>
                                    <td>{{ (int) ($total->tiket ?? 0) }}</td>
                                    <td>Rp {{ number_format((int) ($total->komisi ?? 0), 0, ',', '.') }}</td>
                                    <td>
                                        @if ($item->status)
                                            <span class="badge bg-success">Aktif</span>
                                        @else
                                            <span class="badge bg-secondary">Nonaktif</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <div class="d-inline-flex gap-1">
                                            <a href="{{ route('fundraiser.show', $item) }}"
                                                class="btn btn-sm btn-outline-primary" title="Detail">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <a href="{{ route('fundraiser.edit', $item) }}"
                                                class="btn btn-sm btn-outline-secondary" title="Edit">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            @if ($item->kode_vouchers_count === 0)
                                                <form action="{{ route('fundraiser.destroy', $item) }}" method="POST"
                                                    class="m-0" onsubmit="return confirm('Hapus program fundraiser ini?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus">
                                                        <i class="bi bi-trash3"></i>
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="11" class="text-center text-muted py-4">Belum ada program fundraiser.</td>
                                </tr>
                            @endforelse
                        </tbody>
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
