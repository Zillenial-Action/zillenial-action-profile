@extends('components.layout.app')

@section('content')
    <div class="container-fluid px-3 px-lg-4 mt-3">

        <x-form-alerts />

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <div>
                <h2 class="page-title">Metode Pembayaran Terhapus</h2>
                <p class="page-subtitle mb-0">Pulihkan atau hapus permanen metode yang sudah dihapus.</p>
            </div>
            <a href="{{ route('payment.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Kembali
            </a>
        </div>

        <div class="card">
            <div class="card-header">
                <i class="bi bi-trash3 me-2"></i>Daftar Metode Terhapus
            </div>
            <div class="card-body p-0">
                <div class="table-responsive"
                    style="border:0;box-shadow:none;border-radius:0;background:transparent;">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Logo</th>
                                <th>Nama</th>
                                <th>Rekening</th>
                                <th>Dihapus Pada</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($data as $item)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>
                                        @if ($item->image)
                                            <img src="{{ asset($item->image) }}" alt="{{ $item->name }}" height="36"
                                                style="max-width:110px;object-fit:contain;">
                                        @else
                                            <span class="text-muted">No Image</span>
                                        @endif
                                    </td>
                                    <td class="fw-semibold">{{ $item->name }}</td>
                                    <td>{{ $item->no_rek }}</td>
                                    <td>{{ $item->deleted_at->format('d-m-Y H:i') }}</td>
                                    <td class="text-end">
                                        <div class="d-inline-flex gap-1">
                                            <form action="{{ route('payment.restore', $item->id) }}" method="POST"
                                                class="m-0" onsubmit="return confirm('Pulihkan metode ini?');">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-success">
                                                    <i class="bi bi-arrow-counterclockwise me-1"></i>Pulihkan
                                                </button>
                                            </form>
                                            <form action="{{ route('payment.forceDelete', $item->id) }}" method="POST"
                                                class="m-0"
                                                onsubmit="return confirm('Hapus permanen metode ini? Tindakan ini tidak dapat dibatalkan!');">
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
                                    <td colspan="6" class="text-center text-muted py-4">Tidak ada metode terhapus.</td>
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
