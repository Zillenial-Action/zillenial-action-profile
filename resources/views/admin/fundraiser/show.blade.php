@extends('components.layout.app')

@section('content')
    <div class="container-fluid px-3 px-lg-4 mt-3">

        <x-form-alerts />

        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
            <div>
                <h2 class="page-title">{{ $program->event->name ?? 'Event terhapus' }}</h2>
                <p class="page-subtitle mb-0">
                    Diskon Rp {{ number_format($program->nilai_diskon, 0, ',', '.') }} &middot;
                    Komisi Rp {{ number_format($program->nilai_komisi, 0, ',', '.') }} per tiket &middot;
                    Kuota {{ $program->kuota_per_kode }} tiket per kode &middot;
                    Berakhir {{ $program->tanggal_berakhir->format('d-m-Y') }}
                    @if ($program->status)
                        <span class="badge bg-success ms-1">Aktif</span>
                    @else
                        <span class="badge bg-secondary ms-1">Nonaktif</span>
                    @endif
                </p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('fundraiser.edit', $program) }}" class="btn btn-primary">
                    <i class="bi bi-pencil me-1"></i>Edit Program
                </a>
                <a href="{{ route('fundraiser.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i>Kembali
                </a>
            </div>
        </div>

        <div class="card">
            <div class="card-header">Kode Fundraiser</div>
            <div class="card-body p-0">
                <div class="table-responsive nowrap-table"
                    style="border:0;box-shadow:none;border-radius:0;background:transparent;">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Fundraiser</th>
                                <th>Kode</th>
                                <th>Kuota Terpakai</th>
                                <th>Tiket Terjual</th>
                                <th>Komisi Berhasil</th>
                                <th>Komisi Menunggu Bayar</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($kodes as $kode)
                                <tr>
                                    <td>{{ $loop->iteration + ($kodes->currentPage() - 1) * $kodes->perPage() }}</td>
                                    <td>
                                        <div class="fw-semibold">{{ $kode->customer->name ?? 'Akun terhapus' }}</div>
                                        <small class="text-muted">{{ $kode->customer->email ?? '-' }}</small>
                                    </td>
                                    <td><code class="bg-light px-2 py-1 rounded">{{ $kode->kode }}</code></td>
                                    <td>{{ $kode->digunakan }} / {{ $kode->kuota }}</td>
                                    <td>{{ (int) $kode->tiket_terjual }}</td>
                                    <td class="fw-semibold">Rp {{ number_format((int) $kode->komisi_didapat, 0, ',', '.') }}</td>
                                    <td class="text-muted">Rp {{ number_format((int) $kode->komisi_pending, 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">
                                        Belum ada fundraiser. Customer bisa membuat kode dari halaman Fundraiser di Sostrip.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if ($kodes->total() > 0)
                            <tfoot>
                                <tr class="table-light fw-semibold">
                                    <td colspan="4">Total Komisi ({{ $kodes->total() }} fundraiser)</td>
                                    <td>{{ (int) $total->tiket }}</td>
                                    <td>Rp {{ number_format((int) $total->komisi, 0, ',', '.') }}</td>
                                    <td class="text-muted">Rp {{ number_format((int) $total->pending, 0, ',', '.') }}</td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-end mt-3">
            {!! $kodes->links('pagination::bootstrap-4') !!}
        </div>

    </div>

    <style>
        .nowrap-table th,
        .nowrap-table td {
            white-space: nowrap;
        }
    </style>
@endsection
