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
                    <div class="card-header">Edit Transaksi {{ $transaksi->invoice }}</div>

                    <div class="card-body">
                        {{-- Event, jumlah tiket, metode bayar, dan total tidak bisa diubah di sini karena terikat
                             ke stok tiket, kuota voucher, data peserta, dan nominal yang sudah dikirim ke Midtrans. --}}
                        <dl class="row mb-4">
                            <dt class="col-sm-4">Event</dt>
                            <dd class="col-sm-8">{{ $transaksi->event->name ?? '-' }}</dd>
                            <dt class="col-sm-4">Jumlah Tiket</dt>
                            <dd class="col-sm-8">{{ $transaksi->jumlah_tiket }}</dd>
                            <dt class="col-sm-4">Metode Pembayaran</dt>
                            <dd class="col-sm-8">{{ $transaksi->payment->name ?? '-' }}</dd>
                            <dt class="col-sm-4">Total Pembayaran</dt>
                            <dd class="col-sm-8">@rupiah($transaksi->total_pembayaran)</dd>
                            <dt class="col-sm-4">Status</dt>
                            <dd class="col-sm-8">{{ $transaksi->status_pembayaran }}</dd>
                        </dl>

                        <form method="POST" action="{{ route('transaksi.update', $transaksi->id) }}">
                            @csrf
                            @method('PUT')
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="name">Name</label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror"
                                        value="{{ old('name', $transaksi->name) }}" name="name" id="name">
                                    @error('name')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="email">Email</label>
                                    <input type="email" class="form-control @error('email') is-invalid @enderror"
                                        value="{{ old('email', $transaksi->email) }}" name="email" id="email">
                                    @error('email')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-4">
                                    <label for="telepon">Telepon</label>
                                    <input type="text" class="form-control @error('telepon') is-invalid @enderror"
                                        value="{{ old('telepon', $transaksi->telepon) }}" name="telepon" id="telepon">
                                    @error('telepon')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary">Simpan</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
