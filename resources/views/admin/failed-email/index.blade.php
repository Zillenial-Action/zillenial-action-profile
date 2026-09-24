@extends('components.layout.app')

@section('content')
    <div class="container-fluid px-3 px-lg-4 mt-3">

        <x-form-alerts />

        {{-- Page header --}}
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
            <div>
                <h2 class="page-title">Email Tidak Terkirim</h2>
                <p class="page-subtitle mb-0">Lacak email tiket yang gagal terkirim dan kirim ulang dari sini.</p>
            </div>
            @if ($jobs->total() > 0)
                <form method="POST" action="{{ route('failed-email.retryAll') }}" class="m-0"
                    onsubmit="return confirm('Kirim ulang semua email gagal?');">
                    @csrf
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-arrow-repeat me-1"></i>Kirim Ulang Semua
                    </button>
                </form>
            @endif
        </div>

        {{-- Info --}}
        <div class="alert d-flex align-items-start gap-2"
            style="background:var(--brand-tint);border:1px solid var(--border);color:var(--text);">
            <i class="bi bi-info-circle-fill" style="color:var(--brand);font-size:18px;"></i>
            <div style="font-size:13.5px;">
                Email tiket yang gagal terkirim setelah beberapa percobaan otomatis akan muncul di sini.
                Klik <strong>Kirim Ulang</strong> untuk memasukkannya kembali ke antrian — worker akan mencoba mengirim lagi.
            </div>
        </div>

        {{-- Data card --}}
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <span>Daftar Email Gagal</span>
                <span class="badge bg-danger">{{ $jobs->total() }}</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive nowrap-table"
                    style="border:0;box-shadow:none;border-radius:0;background:transparent;">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Penerima</th>
                                <th>Jenis</th>
                                <th style="white-space:normal;min-width:280px;">Pesan Error</th>
                                <th>Gagal Pada</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($jobs as $job)
                                <tr>
                                    <td>{{ $loop->iteration + ($jobs->currentPage() - 1) * $jobs->perPage() }}</td>
                                    <td class="fw-semibold">{{ $job->recipient }}</td>
                                    <td><span class="badge bg-secondary">{{ $job->type }}</span></td>
                                    <td style="white-space:normal;">
                                        <span class="text-danger small">{{ \Illuminate\Support\Str::limit($job->error, 160) }}</span>
                                    </td>
                                    <td>{{ \Illuminate\Support\Carbon::parse($job->failed_at)->format('d-m-Y H:i') }}</td>
                                    <td class="text-end">
                                        <div class="d-inline-flex gap-1">
                                            <form action="{{ route('failed-email.retry', $job->uuid) }}" method="POST"
                                                class="m-0">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-primary"
                                                    title="Kirim ulang">
                                                    <i class="bi bi-arrow-repeat me-1"></i>Kirim Ulang
                                                </button>
                                            </form>
                                            <form action="{{ route('failed-email.destroy', $job->uuid) }}" method="POST"
                                                class="m-0" onsubmit="return confirm('Hapus catatan ini?');">
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
                                    <td colspan="6" class="text-center text-muted py-5">
                                        <i class="bi bi-check-circle" style="font-size:28px;color:#15803d;"></i>
                                        <div class="mt-2">Tidak ada email yang gagal terkirim. 🎉</div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-end mt-3">
            {!! $jobs->links('pagination::bootstrap-4') !!}
        </div>

    </div>

    <style>
        .nowrap-table th,
        .nowrap-table td {
            white-space: nowrap;
        }
    </style>
@endsection
