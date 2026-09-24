@extends('components.layout.app')

@section('content')
    <div class="container-fluid px-3 px-lg-4 mt-3">

        <x-form-alerts />

        {{-- Page header --}}
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
            <div>
                <h2 class="page-title">Kode Voucher</h2>
                <p class="page-subtitle mb-0">Kelola voucher diskon, kuota, dan masa berlaku.</p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('voucher.create') }}" class="btn btn-primary">
                    <i class="bi bi-plus-lg me-1"></i>Tambah Voucher
                </a>
                <a href="{{ route('voucher.trashed') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-trash3 me-1"></i>Terhapus
                </a>
            </div>
        </div>

        {{-- Data card --}}
        <div class="card">
            <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                <span>Daftar Voucher</span>
                <form method="GET" action="{{ route('voucher.search') }}" role="search"
                    style="width: min(320px, 100%);">
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                        <input class="form-control" type="search" name="search" value="{{ request('search') }}"
                            placeholder="Cari voucher..." aria-label="Cari voucher">
                        <button class="btn btn-primary" type="submit">Cari</button>
                    </div>
                </form>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive nowrap-table"
                    style="border:0;box-shadow:none;border-radius:0;background:transparent;">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Kode</th>
                                <th>Nama Voucher</th>
                                <th>Event</th>
                                <th>Diskon</th>
                                <th>Kuota</th>
                                <th>Digunakan</th>
                                <th>Kadaluarsa</th>
                                <th>Status</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($data as $item)
                                <tr>
                                    <td>{{ $loop->iteration + ($data->currentPage() - 1) * $data->perPage() }}</td>
                                    <td>
                                        <code class="bg-light px-2 py-1 rounded">{{ $item->kode }}</code>
                                        @if ($item->is_external)
                                            <span class="badge bg-primary ms-1" title="Voucher dari API Eksternal">
                                                <i class="bi bi-link-45deg"></i> External
                                            </span>
                                        @endif
                                    </td>
                                    <td class="fw-semibold">{{ $item->name_voucher }}</td>
                                    <td>{{ $item->event->name ?? '-' }}</td>
                                    <td>Rp {{ number_format($item->nilai_diskon, 0, ',', '.') }}</td>
                                    <td>{{ $item->kuota }}</td>
                                    <td>
                                        <span
                                            class="badge {{ $item->digunakan >= $item->kuota ? 'bg-danger' : 'bg-success' }}">
                                            {{ $item->digunakan }} / {{ $item->kuota }}
                                        </span>
                                    </td>
                                    <td>
                                        @if ($item->tanggal_kadaluarsa < now())
                                            <span class="text-danger fw-semibold">
                                                {{ $item->tanggal_kadaluarsa->format('d-m-Y') }}
                                            </span>
                                        @else
                                            {{ $item->tanggal_kadaluarsa->format('d-m-Y') }}
                                        @endif
                                    </td>
                                    <td>
                                        @if ($item->status)
                                            <span class="badge bg-success">Aktif</span>
                                        @else
                                            <span class="badge bg-secondary">Nonaktif</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <div class="d-inline-flex gap-1">
                                            <a href="{{ route('voucher.edit', $item->id) }}"
                                                class="btn btn-sm btn-outline-secondary" title="Edit">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <form action="{{ route('voucher.destroy', $item->id) }}" method="POST"
                                                class="m-0" onsubmit="return confirm('Hapus voucher ini?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus">
                                                    <i class="bi bi-trash3"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="text-center text-muted py-4">Belum ada voucher.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-end mt-3">
            {!! $data->appends(request()->query())->links('pagination::bootstrap-4') !!}
        </div>

    </div>

    <style>
        .nowrap-table th,
        .nowrap-table td {
            white-space: nowrap;
        }
    </style>
@endsection
