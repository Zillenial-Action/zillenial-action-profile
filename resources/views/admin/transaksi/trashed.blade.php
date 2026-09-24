@extends('components.layout.app')

@section('content')
    <div class="container-fluid px-3 px-lg-4 mt-3">

        <x-form-alerts />

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <div>
                <h2 class="page-title">Transaksi Terhapus</h2>
                <p class="page-subtitle mb-0">Pulihkan atau hapus permanen transaksi yang sudah dihapus.</p>
            </div>
            <a href="{{ route('transaksi.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Kembali
            </a>
        </div>

        <div class="card">
            <div class="card-header">
                <i class="bi bi-trash3 me-2"></i>Daftar Transaksi Terhapus
            </div>
            <div class="card-body p-0">
                <div class="table-responsive nowrap-table"
                    style="border:0;box-shadow:none;border-radius:0;background:transparent;">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Invoice</th>
                                <th>Event</th>
                                <th>Nama Pembeli</th>
                                <th>Email</th>
                                <th>Total Pembayaran</th>
                                <th>Status</th>
                                <th>Dihapus Pada</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($data as $item)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td class="fw-semibold">{{ $item->invoice }}</td>
                                    <td>{{ $item->event?->name ?? 'N/A' }}</td>
                                    <td>{{ $item->name }}</td>
                                    <td>{{ $item->email }}</td>
                                    <td>@rupiah($item->total_pembayaran)</td>
                                    <td>
                                        @if ($item->status_pembayaran === 'Success')
                                            <span class="badge bg-success">Success</span>
                                        @elseif($item->status_pembayaran === 'Failed')
                                            <span class="badge bg-danger">Failed</span>
                                        @else
                                            <span class="badge bg-warning text-dark">Pending</span>
                                        @endif
                                    </td>
                                    <td>{{ $item->deleted_at->format('d-m-Y H:i') }}</td>
                                    <td class="text-end">
                                        <div class="d-inline-flex gap-1">
                                            <form action="{{ route('transaksi.restore', $item->id) }}" method="POST"
                                                class="m-0"
                                                onsubmit="return confirm('Pulihkan transaksi ini? Tiket akan dikurangi dari event.');">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-success">
                                                    <i class="bi bi-arrow-counterclockwise me-1"></i>Pulihkan
                                                </button>
                                            </form>
                                            <form action="{{ route('transaksi.forceDelete', $item->id) }}" method="POST"
                                                class="m-0"
                                                onsubmit="return confirm('Hapus permanen transaksi ini? Tindakan ini tidak dapat dibatalkan!');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                                    <i class="bi bi-trash3 me-1"></i>Hapus Permanen
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center text-muted py-4">Tidak ada transaksi terhapus.</td>
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
