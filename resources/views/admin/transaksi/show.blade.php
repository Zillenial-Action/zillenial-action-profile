@extends('components.layout.app')

@section('content')
    <div class="container mt-4">

        <div class="row justify-content-center">
            <div class="col-md-8">
                <!-- Menampilkan Notifikasi Sukses -->
                @if (session('success'))
                    <div class="alert alert-success">
                        {{ session('success') }}
                    </div>
                @endif

                <!-- Menampilkan Notifikasi Error -->
                @if (session('error'))
                    <div class="alert alert-danger">
                        {{ session('error') }}
                    </div>
                @endif

                <!-- Menampilkan Pesan Validasi Error -->
                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="py-4 d-flex justify-content-between align-items-center">
                    <div>
                        <a href="{{ route('transaksi.index') }}" class="btn btn-secondary">Kembali</a>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">Show Transaksi</div>

                    <div class="card-body">
                        <p><strong class="h5">Detail pemesanan.</strong></p>
                        <div class="border border-secondary rounded pt-1">
                            <div class="d-flex justify-content-between align-items-stretch">
                                <div class="h-100 p-3 d-flex flex-column justify-content-center w-100">
                                    <p><strong>Nama Lengkap</strong></p>
                                    <p>{{ $transaksi->name }}</p>
                                </div>
                                <div class="h-100 p-3 d-flex flex-column justify-content-center w-100">
                                    <p><strong>Email</strong></p>
                                    <p>{{ $transaksi->email }}</p>
                                </div>
                                <div class="h-100 p-3 d-flex flex-column justify-content-center w-100">
                                    <p><strong>Telepon</strong></p>
                                    <p>{{ $transaksi->telepon }}</p>
                                </div>
                            </div>
                        </div>

                        <p class="mt-3"><strong class="h5 mt-4 pt-2">Detail pembayaran.</strong></p>
                        <div class="border border-secondary rounded pt-1 mb-4">
                            <div class="d-flex justify-content-between align-items-stretch">
                                <div class="h-100 p-3 d-flex flex-column justify-content-center w-100">
                                    <p><strong>Waktu Registrasi</strong></p>
                                    <p>{{ $transaksi->tanggal_register }}</p>
                                </div>
                                <div class="h-100 p-3 d-flex flex-column justify-content-center w-100">
                                    <p><strong>Waktu Pembayaran</strong></p>
                                    <p>
                                        @if ($transaksi->tanggal_pembayaran === null)
                                            Belum Bayar
                                        @else
                                            {{ $transaksi->tanggal_pembayaran }}
                                        @endif
                                    </p>
                                </div>
                                <div class="h-100 p-3 d-flex flex-column justify-content-center w-100">
                                    <p><strong>Metode Pembayaran</strong></p>
                                    <p>{{ $transaksi->payment->name ?? '-' }}</p>
                                </div>
                                <div class="h-100 p-3 d-flex flex-column justify-content-center w-100">
                                    <p><strong>Status Pembayaran</strong></p>
                                    <p>
                                        @if ($transaksi->status_pembayaran === 'Success')
                                            <span class="badge bg-success">Success</span>
                                        @elseif($transaksi->status_pembayaran === 'Failed')
                                            <span class="badge bg-danger">Failed</span>
                                        @else
                                            <span class="badge bg-warning">Pending</span>
                                        @endif
                                    </p>
                                </div>
                            </div>
                        </div>

                        <p class="mt-3">
                            <strong class="h5">Detail Volunteer</strong>
                        </p>

                        @if ($transaksi->volunteers->count())
                            @foreach ($transaksi->volunteers as $volunteer)
                                <div class="border border-secondary rounded mb-3">
                                    <div class="d-flex justify-content-between align-items-stretch">
                                        <div class="p-3 w-100">
                                            <p><strong>Nama</strong></p>
                                            <p>{{ $volunteer->name }}</p>
                                        </div>
                                        <div class="p-3 w-100">
                                            <p><strong>Email</strong></p>
                                            <p>{{ $volunteer->email }}</p>
                                        </div>
                                        <div class="p-3 w-100">
                                            <p><strong>Telepon</strong></p>
                                            <p>{{ $volunteer->telepon }}</p>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <p class="text-muted">Tidak ada volunteer</p>
                        @endif

                        <p class="mt-3"><strong class="h5 mt-4 pt-2">Detail event.</strong></p>
                        <div class="border border-secondary rounded pt-1 mb-4">
                            <div class="d-flex justify-content-between align-items-stretch">
                                <div class="h-100 p-3 d-flex flex-column justify-content-center w-100">
                                    <p><strong>Event</strong></p>
                                    <p>{{ $transaksi->event->name }}</p>
                                </div>
                                <div class="h-100 p-3 d-flex flex-column justify-content-center w-100">
                                    <p><strong>Jumlah</strong></p>
                                    <p>{{ $transaksi->jumlah_tiket }}</p>
                                </div>
                                <div class="h-100 p-3 d-flex flex-column justify-content-center w-100">
                                    <p><strong>Kode Voucher</strong></p>
                                    <p>{{ $transaksi->voucher->kode ?? '-' }}</p>
                                </div>
                                <div class="h-100 p-3 d-flex flex-column justify-content-center w-100">
                                    <p><strong>Potongan Harga</strong></p>
                                    <p>
                                        @isset($transaksi->voucher)
                                            @rupiah($transaksi->voucher->nilai_diskon)
                                        @else
                                            -
                                        @endisset
                                    </p>
                                </div>
                                <div class="h-100 p-3 d-flex flex-column justify-content-center w-100">
                                    <p><strong>Total</strong></p>
                                    <p>@rupiah($transaksi->total_pembayaran)</p>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex flex-row-reverse bd-highlight justify-items-center">
                            <div class="p-2 bd-highlight h4">@rupiah($transaksi->total_pembayaran)</div>
                            <div class="p-2 bd-highlight"><strong>Total Pembayaran</strong></div>
                        </div>
                    </div>

                </div>

            </div>
        </div>
    </div>
@endsection
