@extends('components.layout.app')

@section('title', 'Error Log')

@section('content')
<div class="container-fluid py-3">
    <x-form-alerts />

    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <h2 class="page-title">Error Log</h2>
            <p class="page-subtitle mb-0">Monitor log aplikasi Laravel &bull; Ukuran file: <strong>{{ $logSize }}</strong></p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('error-log.download') }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-download me-1"></i> Download Log
            </a>
            <form method="POST" action="{{ route('error-log.clear') }}"
                onsubmit="return confirm('Yakin ingin membersihkan seluruh isi log? Tindakan ini tidak bisa dibatalkan.')">
                @csrf
                <button type="submit" class="btn btn-sm btn-outline-danger">
                    <i class="bi bi-trash me-1"></i> Bersihkan Log
                </button>
            </form>
        </div>
    </div>

    {{-- Statistik --}}
    <div class="row g-3 mb-3">
        <div class="col-6 col-md-2">
            <div class="card text-center py-3">
                <div style="font-size:22px;font-weight:800;color:var(--text);">{{ number_format($stats['total']) }}</div>
                <div style="font-size:12px;color:var(--muted);font-weight:600;margin-top:2px;">Total Entry</div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="card text-center py-3" style="border-color:#fecdca;">
                <div style="font-size:22px;font-weight:800;color:#b42318;">{{ number_format($stats['error']) }}</div>
                <div style="font-size:12px;color:var(--muted);font-weight:600;margin-top:2px;">Error</div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="card text-center py-3" style="border-color:#fde68a;">
                <div style="font-size:22px;font-weight:800;color:#b45309;">{{ number_format($stats['warning']) }}</div>
                <div style="font-size:12px;color:var(--muted);font-weight:600;margin-top:2px;">Warning</div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="card text-center py-3" style="border-color:#f5b7b1;">
                <div style="font-size:22px;font-weight:800;color:#922b21;">{{ number_format($stats['critical']) }}</div>
                <div style="font-size:12px;color:var(--muted);font-weight:600;margin-top:2px;">Critical</div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="card text-center py-3" style="border-color:#a9cce3;">
                <div style="font-size:22px;font-weight:800;color:#1a5276;">{{ number_format($stats['info']) }}</div>
                <div style="font-size:12px;color:var(--muted);font-weight:600;margin-top:2px;">Info</div>
            </div>
        </div>
    </div>

    {{-- Filter --}}
    <div class="card mb-3">
        <div class="card-header d-flex align-items-center gap-2"
            style="cursor:pointer;" data-bs-toggle="collapse" data-bs-target="#filterPanel"
            aria-expanded="true" aria-controls="filterPanel">
            <i class="bi bi-funnel"></i>
            <span>Filter Log</span>
            <i class="bi bi-chevron-down ms-auto" style="transition:transform .2s;"></i>
        </div>
        <div class="collapse show" id="filterPanel">
            <div class="card-body">
                <form method="GET" action="{{ route('error-log.index') }}">
                    <div class="row g-2">
                        <div class="col-md-4">
                            <label class="form-label">Cari Pesan</label>
                            <input type="text" name="search" class="form-control form-control-sm"
                                value="{{ $search }}" placeholder="Kata kunci pesan error...">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Level</label>
                            <select name="level" class="form-select form-select-sm">
                                <option value="">Semua Level</option>
                                @foreach(['emergency','alert','critical','error','warning','notice','info','debug'] as $lvl)
                                    <option value="{{ $lvl }}" {{ $level === $lvl ? 'selected' : '' }}>
                                        {{ ucfirst($lvl) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2 d-flex align-items-end gap-2">
                            <button type="submit" class="btn btn-sm btn-primary">
                                <i class="bi bi-search me-1"></i> Cari
                            </button>
                            <a href="{{ route('error-log.index') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Tabel Log --}}
    <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between">
            <span><i class="bi bi-journal-text me-2"></i>Log Entries</span>
            <span class="badge bg-secondary">{{ number_format($total) }} hasil</span>
        </div>

        @if(count($items) === 0)
            <div class="card-body text-center py-5 text-muted">
                <i class="bi bi-check-circle" style="font-size:40px;color:#abefc6;"></i>
                <p class="mt-3 mb-0">Tidak ada log ditemukan.</p>
            </div>
        @else
        <div class="table-responsive" style="border:none;border-radius:0;">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th style="width:160px;">Waktu</th>
                        <th style="width:100px;">Level</th>
                        <th style="width:80px;">Env</th>
                        <th>Pesan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($items as $log)
                    @php
                        $lvl = $log['level'];
                        [$badgeClass, $rowStyle] = match($lvl) {
                            'emergency', 'alert', 'critical' => ['bg-danger',  'background:#fff5f5;'],
                            'error'                          => ['bg-danger',  ''],
                            'warning'                        => ['bg-warning text-dark', 'background:#fffbeb;'],
                            'notice'                         => ['bg-info text-dark',    ''],
                            'info'                           => ['bg-info text-dark',    ''],
                            'debug'                          => ['bg-secondary',         ''],
                            default                          => ['bg-secondary',         ''],
                        };
                        // Potong pesan panjang untuk tampilan ringkas
                        $shortMsg  = mb_strimwidth(preg_replace('/\s+/', ' ', $log['message']), 0, 300, '…');
                        $fullMsg   = htmlspecialchars($log['message']);
                        $entryId   = 'log-' . $loop->index;
                    @endphp
                    <tr style="{{ $rowStyle }}">
                        <td style="font-size:12px;white-space:nowrap;color:var(--muted);">
                            {{ \Carbon\Carbon::parse($log['datetime'])->format('d/m/Y H:i:s') }}
                        </td>
                        <td>
                            <span class="badge {{ $badgeClass }}">{{ strtoupper($lvl) }}</span>
                        </td>
                        <td style="font-size:12px;color:var(--muted);">{{ $log['environment'] ?? '-' }}</td>
                        <td>
                            <div style="font-size:13px;font-family:monospace;white-space:pre-wrap;word-break:break-word;max-height:80px;overflow:hidden;" id="{{ $entryId }}-short">{{ $shortMsg }}</div>
                            @if(mb_strlen($log['message']) > 300)
                            <div style="display:none;font-size:12px;font-family:monospace;white-space:pre-wrap;word-break:break-word;background:#f8f8f8;padding:10px;border-radius:8px;margin-top:6px;max-height:400px;overflow:auto;" id="{{ $entryId }}-full">{{ $log['message'] }}</div>
                            <button type="button" class="btn btn-sm btn-link p-0 mt-1" style="font-size:12px;"
                                onclick="toggleLog('{{ $entryId }}', this)">
                                <i class="bi bi-chevron-down"></i> Lihat selengkapnya
                            </button>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Pagination manual --}}
        @if($lastPage > 1)
        <div class="card-body border-top d-flex align-items-center justify-content-between">
            <small class="text-muted">
                Menampilkan {{ number_format(($currentPage - 1) * $perPage + 1) }}–{{ number_format(min($currentPage * $perPage, $total)) }}
                dari {{ number_format($total) }} entry
            </small>
            <nav>
                <ul class="pagination pagination-sm mb-0">
                    {{-- Prev --}}
                    <li class="page-item {{ $currentPage <= 1 ? 'disabled' : '' }}">
                        <a class="page-link" href="{{ request()->fullUrlWithQuery(['page' => $currentPage - 1]) }}">
                            <i class="bi bi-chevron-left"></i>
                        </a>
                    </li>
                    {{-- Pages --}}
                    @php
                        $start = max(1, $currentPage - 2);
                        $end   = min($lastPage, $currentPage + 2);
                    @endphp
                    @if($start > 1)
                        <li class="page-item"><a class="page-link" href="{{ request()->fullUrlWithQuery(['page' => 1]) }}">1</a></li>
                        @if($start > 2)<li class="page-item disabled"><span class="page-link">…</span></li>@endif
                    @endif
                    @for($p = $start; $p <= $end; $p++)
                        <li class="page-item {{ $p === $currentPage ? 'active' : '' }}">
                            <a class="page-link" href="{{ request()->fullUrlWithQuery(['page' => $p]) }}">{{ $p }}</a>
                        </li>
                    @endfor
                    @if($end < $lastPage)
                        @if($end < $lastPage - 1)<li class="page-item disabled"><span class="page-link">…</span></li>@endif
                        <li class="page-item"><a class="page-link" href="{{ request()->fullUrlWithQuery(['page' => $lastPage]) }}">{{ $lastPage }}</a></li>
                    @endif
                    {{-- Next --}}
                    <li class="page-item {{ $currentPage >= $lastPage ? 'disabled' : '' }}">
                        <a class="page-link" href="{{ request()->fullUrlWithQuery(['page' => $currentPage + 1]) }}">
                            <i class="bi bi-chevron-right"></i>
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
        @endif
        @endif
    </div>
</div>

@push('scripts')
<script>
function toggleLog(id, btn) {
    const shortEl = document.getElementById(id + '-short');
    const fullEl  = document.getElementById(id + '-full');
    const isOpen  = fullEl.style.display === 'block';
    fullEl.style.display  = isOpen ? 'none' : 'block';
    shortEl.style.display = isOpen ? '' : 'none';
    btn.innerHTML = isOpen
        ? '<i class="bi bi-chevron-down"></i> Lihat selengkapnya'
        : '<i class="bi bi-chevron-up"></i> Sembunyikan';
}
</script>
@endpush
@endsection