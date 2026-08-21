@extends('components.layout.app')

@section('content')
    <div class="container-fluid px-3 px-lg-4 mt-3">

        <x-form-alerts />

        {{-- Page header --}}
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
            <div>
                <h2 class="page-title">Transaksi</h2>
                <p class="page-subtitle mb-0">Pantau pembelian tiket & status pembayaran.</p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('transaksi.trashed') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-trash3 me-1"></i>Terhapus
                </a>
            </div>
        </div>

        {{-- Filter panel --}}
        <div class="card mb-3">
            <div class="card-header d-flex align-items-center gap-2">
                <i class="bi bi-funnel me-1"></i>
                <span class="fw-semibold">Filter Transaksi</span>
                @if(request('tanggal_awal') || request('tanggal_akhir') || request('id_event') || request('status_pembayaran'))
                    <span class="badge bg-primary ms-1">Aktif</span>
                @endif
            </div>
            <div id="filterPanel">
                <div class="card-body">
                    <form method="GET" action="{{ route('transaksi.filter') }}">
                        <div class="row g-3">
                            <div class="col-12 col-md-3">
                                <label class="form-label fw-semibold">Tanggal Awal</label>
                                <input type="date" name="tanggal_awal" class="form-control"
                                    value="{{ request('tanggal_awal') }}">
                            </div>
                            <div class="col-12 col-md-3">
                                <label class="form-label fw-semibold">Tanggal Akhir</label>
                                <input type="date" name="tanggal_akhir" class="form-control"
                                    value="{{ request('tanggal_akhir') }}">
                            </div>
                            <div class="col-12 col-md-3">
                                <label class="form-label fw-semibold">Event</label>
                                <select name="id_event" class="form-select">
                                    <option value="">Semua Event</option>
                                    @foreach($events as $event)
                                        <option value="{{ $event->id }}"
                                            {{ request('id_event') == $event->id ? 'selected' : '' }}>
                                            {{ $event->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12 col-md-3">
                                <label class="form-label fw-semibold">Status Pembayaran</label>
                                <select name="status_pembayaran" class="form-select">
                                    <option value="">Semua Status</option>
                                    <option value="Pending" {{ request('status_pembayaran') === 'Pending' ? 'selected' : '' }}>Pending</option>
                                    <option value="Success" {{ request('status_pembayaran') === 'Success' ? 'selected' : '' }}>Success</option>
                                    <option value="Failed" {{ request('status_pembayaran') === 'Failed' ? 'selected' : '' }}>Failed</option>
                                </select>
                            </div>
                        </div>
                        <div class="d-flex gap-2 mt-3">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-funnel me-1"></i>Terapkan Filter
                            </button>
                            @if(request('tanggal_awal') || request('tanggal_akhir') || request('id_event') || request('status_pembayaran'))
                                <a href="{{ route('transaksi.index') }}" class="btn btn-outline-secondary">
                                    <i class="bi bi-x-circle me-1"></i>Reset Filter
                                </a>
                            @endif
                        </div>
                    </form>

                    {{-- Active filter badges --}}
                    @if(request('tanggal_awal') || request('tanggal_akhir') || request('id_event') || request('status_pembayaran'))
                        <div class="d-flex flex-wrap gap-2 mt-3 pt-3 border-top">
                            <span class="text-muted small fw-semibold me-1 align-self-center">Filter aktif:</span>
                            @if(request('tanggal_awal'))
                                <span class="badge bg-secondary">Dari: {{ request('tanggal_awal') }}</span>
                            @endif
                            @if(request('tanggal_akhir'))
                                <span class="badge bg-secondary">Sampai: {{ request('tanggal_akhir') }}</span>
                            @endif
                            @if(request('id_event'))
                                <span class="badge bg-secondary">Event: {{ $events->firstWhere('id', request('id_event'))?->name }}</span>
                            @endif
                            @if(request('status_pembayaran'))
                                <span class="badge {{ request('status_pembayaran') === 'Success' ? 'bg-success' : (request('status_pembayaran') === 'Failed' ? 'bg-danger' : 'bg-warning text-dark') }}">
                                    {{ request('status_pembayaran') }}
                                </span>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Data card --}}
        <div class="card">
            <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                <span>Daftar Transaksi</span>
                <form method="GET" action="{{ route('transaksi.search') }}" role="search"
                    style="width: min(320px, 100%);">
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                        <input class="form-control" type="search" name="search" value="{{ request('search') }}"
                            placeholder="Cari transaksi..." aria-label="Cari transaksi">
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
                                <th>Invoice</th>
                                <th>Event</th>
                                <th>Nama Pembeli</th>
                                <th>Email Pembeli</th>
                                <th>Telepon Pembeli</th>
                                <th>Volunteer</th>
                                <th>Jumlah Tiket</th>
                                <th>Kode Voucher</th>
                                <th>Potongan Voucher (Pertiket)</th>
                                <th>Total Pembayaran</th>
                                <th>Tanggal Register</th>
                                <th>Status Pembayaran</th>
                                <th>Tanggal Pembayaran</th>
                                <th>Metode Pembayaran</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($data as $item)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td class="fw-semibold">{{ $item->invoice }}</td>
                                    <td>{{ $item->event->name }}</td>
                                    <td>{{ $item->name }}</td>
                                    <td>{{ $item->email }}</td>
                                    <td>{{ $item->telepon }}</td>
                                    <td>
                                        <ul class="mb-0 ps-3">
                                            @foreach ($item->volunteers as $volunteer)
                                                <li>{{ $volunteer->name }} - {{ $volunteer->telepon }}</li>
                                            @endforeach
                                        </ul>
                                    </td>
                                    <td>{{ $item->jumlah_tiket }}</td>
                                    <td>{{ $item->voucher->kode ?? '-' }}</td>
                                    <td>
                                        @isset($item->voucher)
                                            @rupiah($item->voucher->nilai_diskon)
                                        @else
                                            -
                                        @endisset
                                    </td>
                                    <td>@rupiah($item->total_pembayaran)</td>
                                    <td>{{ $item->tanggal_register }}</td>
                                    <td id="status-{{ $item->id }}">
                                        <select
                                            class="form-select form-select-sm fw-semibold {{ $item->status_pembayaran === 'Success' ? 'bg-success text-white border-success' : ($item->status_pembayaran === 'Failed' ? 'bg-danger text-white border-danger' : 'bg-warning text-dark border-warning') }}"
                                            style="width: auto; display: inline-block; cursor: pointer;"
                                            onfocus="this.setAttribute('data-prev', this.value)"
                                            onchange="updateStatus('{{ $item->id }}', this)">
                                            <option value="Pending" class="bg-white text-dark"
                                                {{ $item->status_pembayaran === 'Pending' ? 'selected' : '' }}>Pending</option>
                                            <option value="Success" class="bg-white text-dark"
                                                {{ $item->status_pembayaran === 'Success' ? 'selected' : '' }}>Success</option>
                                            <option value="Failed" class="bg-white text-dark"
                                                {{ $item->status_pembayaran === 'Failed' ? 'selected' : '' }}>Failed</option>
                                        </select>
                                    </td>
                                    <td id="tanggal-bayar-{{ $item->id }}">
                                        @if ($item->tanggal_pembayaran === null)
                                            <span class="text-muted">Belum Bayar</span>
                                        @else
                                            {{ $item->tanggal_pembayaran }}
                                        @endif
                                    </td>
                                    <td>{{ $item->payment->name ?? '-' }}</td>
                                    <td class="text-end">
                                        <div class="d-inline-flex gap-1">
                                            <a href="{{ route('transaksi.show', $item->invoice) }}"
                                                class="btn btn-sm btn-outline-primary" title="Lihat">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <button type="button" class="btn btn-sm btn-outline-success"
                                                title="Kirim Email Tiket"
                                                onclick="sendTicketEmail('{{ $item->invoice }}', this)">
                                                <i class="bi bi-envelope"></i>
                                            </button>
                                            <form action="{{ route('transaksi.destroy', $item->invoice) }}" method="POST"
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
                                    <td colspan="16" class="text-center text-muted py-4">Belum ada transaksi.</td>
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

    <script>
        async function sendTicketEmail(invoice, btn) {
            if (!confirm('Kirim ulang email tiket untuk transaksi ini?')) return;

            const original = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

            try {
                const response = await fetch(`/transaksi/${encodeURIComponent(invoice)}/kirim-email`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });

                const result = await response.json();
                alert(result.message || (response.ok ? 'Email terkirim.' : 'Gagal mengirim email.'));
            } catch (error) {
                console.error('Error:', error);
                alert('Terjadi kesalahan. Silakan coba lagi.');
            } finally {
                btn.disabled = false;
                btn.innerHTML = original;
            }
        }

        async function updateStatus(id, selectElement) {
            const confirmation = confirm("Apakah Anda yakin ingin memperbarui status transaksi ini?");
            const previousValue = selectElement.getAttribute('data-prev') || selectElement.value;
            const newValue = selectElement.value;

            if (!confirmation) {
                selectElement.value = previousValue; // Revert
                return;
            }

            selectElement.disabled = true;

            const baseClass = 'form-select form-select-sm fw-semibold';
            const classFor = (status) => {
                if (status === 'Success') return `${baseClass} bg-success text-white border-success`;
                if (status === 'Failed') return `${baseClass} bg-danger text-white border-danger`;
                return `${baseClass} bg-warning text-dark border-warning`;
            };

            try {
                const response = await fetch('/transaksi/update', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        id: id,
                        status: newValue
                    })
                });

                const result = await response.json();

                if (response.ok) {
                    selectElement.className = classFor(newValue);
                    selectElement.style.width = 'auto';
                    selectElement.style.display = 'inline-block';
                    selectElement.setAttribute('data-prev', newValue);
                    selectElement.disabled = false;

                    const tanggalBayarElement = document.querySelector(`#tanggal-bayar-${id}`);
                    if (tanggalBayarElement) {
                        tanggalBayarElement.textContent = result.tanggal_pembayaran || 'Belum Bayar';
                    }

                    alert(result.message);
                } else {
                    selectElement.value = previousValue;
                    selectElement.disabled = false;
                    alert(result.message || 'Gagal memperbarui status.');
                }
            } catch (error) {
                console.error('Error:', error);
                selectElement.value = previousValue;
                selectElement.disabled = false;
                alert('Terjadi kesalahan. Silakan coba lagi.');
            }
        }
    </script>
@endsection
