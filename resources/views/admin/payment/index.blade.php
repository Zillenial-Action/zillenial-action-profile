@extends('components.layout.app')

@section('content')
    <div class="container-fluid px-3 px-lg-4 mt-3">

        <x-form-alerts />

        {{-- Page header --}}
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
            <div>
                <h2 class="page-title">Metode Pembayaran</h2>
                <p class="page-subtitle mb-0">Kelola daftar metode pembayaran & rekening.</p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('payment.create') }}" class="btn btn-primary">
                    <i class="bi bi-plus-lg me-1"></i>Tambah Metode
                </a>
                <a href="{{ route('payment.trashed') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-trash3 me-1"></i>Terhapus
                </a>
            </div>
        </div>

        {{-- Data card --}}
        <div class="card">
            <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                <span>Daftar Metode Pembayaran</span>
                <form method="GET" action="{{ route('payment.search') }}" role="search"
                    style="width: min(320px, 100%);">
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                        <input class="form-control" type="search" name="search" value="{{ request('search') }}"
                            placeholder="Cari metode..." aria-label="Cari metode pembayaran">
                        <button class="btn btn-primary" type="submit">Cari</button>
                    </div>
                </form>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive"
                    style="border:0;box-shadow:none;border-radius:0;background:transparent;">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Logo</th>
                                <th>ID</th>
                                <th>Nama</th>
                                <th>Rekening</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($data as $item)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>
                                        <img src="{{ asset($item->image) }}" alt="{{ $item->name }}" height="36"
                                            style="max-width:110px;object-fit:contain;">
                                    </td>
                                    <td>{{ $item->id }}</td>
                                    <td class="fw-semibold">{{ $item->name }}</td>
                                    <td>{{ $item->no_rek }}</td>
                                    <td class="text-end">
                                        <div class="d-inline-flex gap-1">
                                            <a href="{{ route('payment.edit', $item->id) }}"
                                                class="btn btn-sm btn-outline-secondary" title="Edit">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <form action="{{ route('payment.destroy', $item->id) }}" method="POST"
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
                                    <td colspan="6" class="text-center text-muted py-4">Belum ada metode pembayaran.</td>
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
@endsection
