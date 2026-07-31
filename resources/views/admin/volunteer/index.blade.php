@extends('components.layout.app')

@section('content')
    <div class="container-fluid px-3 px-lg-4 mt-3">

        <x-form-alerts />

        {{-- Page header --}}
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
            <div>
                <h2 class="page-title">Volunteer</h2>
                <p class="page-subtitle mb-0">Daftar volunteer yang terdaftar melalui checkout tiket.</p>
            </div>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-outline-success" data-bs-toggle="modal"
                    data-bs-target="#exportModal">
                    <i class="bi bi-file-earmark-excel me-1"></i>Export Excel
                </button>
                <a href="{{ route('volunteer.trashed') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-trash3 me-1"></i>Terhapus
                </a>
            </div>
        </div>

        {{-- Data card --}}
        <div class="card">
            <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                <span>Daftar Volunteer</span>

                {{-- Filter form --}}
                <form method="GET" action="{{ route('volunteer.search') }}" role="search"
                    class="d-flex flex-wrap gap-2 align-items-center">

                    {{-- Filter by event --}}
                    <select name="event_id" class="form-select form-select-sm" style="width:auto;min-width:180px;"
                        onchange="this.form.submit()">
                        <option value="">— Semua Event —</option>
                        @foreach ($events as $event)
                            <option value="{{ $event->id }}"
                                {{ request('event_id') == $event->id ? 'selected' : '' }}>
                                {{ $event->name }}
                            </option>
                        @endforeach
                    </select>

                    {{-- Search by name/email/phone --}}
                    <div class="input-group input-group-sm" style="width: min(260px, 100%);">
                        <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                        <input class="form-control" type="search" name="search" value="{{ request('search') }}"
                            placeholder="Cari nama, email, telepon..." aria-label="Cari volunteer">
                        <button class="btn btn-primary btn-sm" type="submit">Cari</button>
                    </div>

                    @if (request('search') || request('event_id'))
                        <a href="{{ route('volunteer.index') }}" class="btn btn-sm btn-outline-secondary"
                            title="Reset filter">
                            <i class="bi bi-x-lg"></i>
                        </a>
                    @endif
                </form>
            </div>

            {{-- Active filter badge --}}
            @if (request('event_id'))
                @php $activeEvent = $events->firstWhere('id', request('event_id')); @endphp
                @if ($activeEvent)
                    <div class="px-3 py-2 border-bottom bg-light d-flex align-items-center gap-2" style="font-size:13px;">
                        <i class="bi bi-funnel-fill text-primary"></i>
                        <span>Filter event: <strong>{{ $activeEvent->name }}</strong></span>
                    </div>
                @endif
            @endif

            <div class="card-body p-0">
                <div class="table-responsive nowrap-table"
                    style="border:0;box-shadow:none;border-radius:0;background:transparent;">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama</th>
                                <th>Email</th>
                                <th>Telepon</th>
                                <th>Jenis Kelamin</th>
                                <th>Transaksi</th>
                                <th>Tanggal Daftar</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($data as $item)
                                <tr>
                                    <td>{{ $loop->iteration + ($data->currentPage() - 1) * $data->perPage() }}</td>
                                    <td class="fw-semibold">{{ $item->name }}</td>
                                    <td>{{ $item->email }}</td>
                                    <td>{{ $item->telepon }}</td>
                                    <td>
                                        @if($item->jenis_kelamin)
                                            <span class="badge {{ $item->jenis_kelamin === 'Laki-laki' ? 'bg-primary' : 'bg-danger' }}">
                                                {{ $item->jenis_kelamin }}
                                            </span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span
                                            class="badge {{ $item->transaksis_count > 0 ? 'bg-success' : 'bg-secondary' }}">
                                            {{ $item->transaksis_count }}
                                        </span>
                                    </td>
                                    <td>{{ $item->created_at->format('d-m-Y H:i') }}</td>
                                    <td class="text-end">
                                        <form action="{{ route('volunteer.destroy', $item->id) }}" method="POST"
                                            class="m-0 d-inline"
                                            onsubmit="return confirm('Hapus volunteer {{ addslashes($item->name) }}?');">
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
                                    <td colspan="8" class="text-center text-muted py-4">
                                        @if (request('event_id') || request('search'))
                                            Tidak ada volunteer ditemukan dengan filter ini.
                                        @else
                                            Belum ada volunteer.
                                        @endif
                                    </td>
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

@push('modals')
    {{-- Export Modal --}}
    <div class="modal fade" id="exportModal" tabindex="-1" aria-labelledby="exportModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exportModalLabel">
                        <i class="bi bi-file-earmark-excel me-2 text-success"></i>Export Volunteer ke Excel
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <form method="POST" action="{{ route('volunteer.export') }}">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-0">
                            <label for="exportEventId" class="form-label">Filter Event</label>
                            <select class="form-select" id="exportEventId" name="event_id">
                                <option value="">— Semua Event (export semua volunteer) —</option>
                                @foreach ($events as $event)
                                    <option value="{{ $event->id }}"
                                        {{ request('event_id') == $event->id ? 'selected' : '' }}>
                                        {{ $event->name }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-text">Pilih event untuk mengexport volunteer event tersebut saja.</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-download me-1"></i>Download Excel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endpush