@extends('components.layout.app')

@section('content')
    <div class="container-fluid px-3 px-lg-4 mt-3">

        <x-form-alerts />

        {{-- Page header --}}
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
            <div>
                <h2 class="page-title">Event</h2>
                <p class="page-subtitle mb-0">Kelola semua event, harga, dan ketersediaan tiket.</p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('event.create') }}" class="btn btn-primary">
                    <i class="bi bi-plus-lg me-1"></i>Tambah Event
                </a>
                <a href="{{ route('event.trashed') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-trash3 me-1"></i>Terhapus
                </a>
            </div>
        </div>

        {{-- Data card --}}
        <div class="card">
            <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                <span>Daftar Event</span>
                <form method="GET" action="{{ route('event.search') }}" role="search"
                    style="width: min(320px, 100%);">
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                        <input class="form-control" type="search" name="search" value="{{ request('search') }}"
                            placeholder="Cari event..." aria-label="Cari event">
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
                                <th>Event</th>
                                <th>Harga</th>
                                <th>Status</th>
                                <th>Waktu Mulai</th>
                                <th>Waktu Berakhir</th>
                                <th>Kota</th>
                                <th>Jumlah Tiket</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($data as $item)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td class="fw-semibold">{{ $item->name }}</td>
                                    <td>@rupiah($item->harga)</td>
                                    <td>
                                        <form action="{{ route('event.update.status', $item->slug) }}" method="POST"
                                            class="m-0">
                                            @csrf
                                            @method('PUT')
                                            @if ($item->status == 1)
                                                <button type="submit" class="btn btn-sm badge bg-success border-0">Y</button>
                                            @else
                                                <button type="submit" class="btn btn-sm badge bg-danger border-0">N</button>
                                            @endif
                                        </form>
                                    </td>
                                    <td>{{ $item->waktu_mulai }}</td>
                                    <td>{{ $item->waktu_berakhir }}</td>
                                    <td>{{ $item->kota }}</td>
                                    <td>{{ $item->jumlah_tiket }}</td>
                                    <td class="text-end">
                                        <div class="d-inline-flex gap-1">
                                            <a href="{{ route('event.show', $item->slug) }}"
                                                class="btn btn-sm btn-outline-primary" title="Lihat">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <a href="{{ route('event.edit', $item->slug) }}"
                                                class="btn btn-sm btn-outline-secondary" title="Edit">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <form action="{{ route('event.destroy', $item->slug) }}" method="POST"
                                                class="m-0" onsubmit="return confirmDelete();">
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
                                    <td colspan="9" class="text-center text-muted py-4">Belum ada event.</td>
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
