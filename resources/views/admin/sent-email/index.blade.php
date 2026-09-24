@extends('components.layout.app')

@section('content')
    <div class="container-fluid px-3 px-lg-4 mt-3">

        <x-form-alerts />

        {{-- Page header --}}
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
            <div>
                <h2 class="page-title">Email Tiket Terkirim</h2>
                <p class="page-subtitle mb-0">Riwayat email tiket yang berhasil terkirim ke penerima.</p>
            </div>
            @if ($emails->total() > 0)
                <form method="POST" action="{{ route('sent-email.clearAll') }}" class="m-0"
                    onsubmit="return confirm('Bersihkan seluruh riwayat email terkirim?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger">
                        <i class="bi bi-trash3 me-1"></i>Bersihkan Riwayat
                    </button>
                </form>
            @endif
        </div>

        {{-- Info --}}
        <div class="alert d-flex align-items-start gap-2"
            style="background:var(--brand-tint);border:1px solid var(--border);color:var(--text);">
            <i class="bi bi-info-circle-fill" style="color:var(--brand);font-size:18px;"></i>
            <div style="font-size:13.5px;">
                Setiap email tiket yang berhasil terkirim — baik otomatis setelah pembayaran maupun
                dikirim ulang manual — tercatat di sini beserta waktu kirimnya.
            </div>
        </div>

        {{-- Data card --}}
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <span>Daftar Email Terkirim</span>
                <span class="badge bg-success">{{ $emails->total() }}</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive nowrap-table"
                    style="border:0;box-shadow:none;border-radius:0;background:transparent;">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Invoice</th>
                                <th>Penerima</th>
                                <th>Terkirim Pada</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($emails as $email)
                                <tr>
                                    <td>{{ $loop->iteration + ($emails->currentPage() - 1) * $emails->perPage() }}</td>
                                    <td class="fw-semibold">{{ $email->invoice }}</td>
                                    <td>{{ $email->recipient }}</td>
                                    <td>{{ \Illuminate\Support\Carbon::parse($email->sent_at)->format('d-m-Y H:i') }}</td>
                                    <td class="text-end">
                                        <form action="{{ route('sent-email.destroy', $email->id) }}" method="POST"
                                            class="m-0" onsubmit="return confirm('Hapus catatan ini?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus">
                                                <i class="bi bi-trash3"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-5">
                                        <i class="bi bi-envelope" style="font-size:28px;color:#94a3b8;"></i>
                                        <div class="mt-2">Belum ada email tiket yang tercatat terkirim.</div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-end mt-3">
            {!! $emails->links('pagination::bootstrap-4') !!}
        </div>

    </div>

    <style>
        .nowrap-table th,
        .nowrap-table td {
            white-space: nowrap;
        }
    </style>
@endsection
