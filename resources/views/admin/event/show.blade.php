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
                        <a href="{{ route('event.index') }}" class="btn btn-secondary">Kembali</a>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">Show Event</div>

                    <div class="card-body">
                        <center>
                            <img src="{{ asset($event->image) }}" class="card-img-top" alt="{{ $event->id }}"
                                style="width: 200px;">
                        </center>
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item">
                                <h5 class="card-title">{{ $event->name }}</h5>
                            </li>
                            <li class="list-group-item">
                                Mitra : {{ $event->mitra }}
                            </li>
                            {{-- <li class="list-group-item">
                                Link : {{ $event->website }}
                            </li> --}}
                            <li class="list-group-item">
                                Jumlah Tiket : {{ $event->jumlah_tiket }}
                            </li>
                            <li class="list-group-item">
                                Harga Tiket : @rupiah($event->harga)
                            </li>
                            <li class="list-group-item">
                                @if ($event->status == 0)
                                    Status : <span class="badge bg-success"> Aktif</span>
                                @else
                                    Status : <span class="badge bg-danger"> Tidak Aktif</span>
                                @endif
                            </li>
                            <li class="list-group-item">
                                Tanggal Mulai <strong>{{ $event->waktu_mulai }}</strong> - Tanggal Berakhir
                                <strong>{{ $event->waktu_berakhir }}</strong>
                            </li>
                            <li class="list-group-item">
                                Lokasi : {{ $event->nama_tempat }} | {{ $event->kota }} || {{ $event->alamat }}
                                @if ($event->direction)
                                    <br>
                                    Direction :
                                    <a href="{{ $event->direction }}" target="_blank" rel="noopener noreferrer">
                                        Buka Maps
                                    </a>
                                @endif
                            </li>
                            <li class="list-group-item">
                                <strong>Yang Kamu Dapat</strong>
                                @if (!empty($event->benefits))
                                    <ul class="mb-0 mt-2">
                                        @foreach ($event->benefits as $benefit)
                                            <li>{{ $benefit }}</li>
                                        @endforeach
                                    </ul>
                                @else
                                    <div class="text-muted mt-2">Belum ada benefit.</div>
                                @endif
                            </li>
                            <li class="list-group-item">
                                <strong>Agenda Event</strong>
                                @if (!empty($event->agenda))
                                    <div class="mt-2">
                                        @foreach ($event->agenda as $agenda)
                                            <div class="border rounded p-2 mb-2">
                                                <div class="small text-muted">{{ $agenda['time_label'] ?? '-' }}</div>
                                                <div class="fw-semibold">{{ $agenda['title'] ?? '-' }}</div>
                                                <div>{{ $agenda['description'] ?? '-' }}</div>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="text-muted mt-2">Belum ada agenda.</div>
                                @endif
                            </li>
                            <li class="list-group-item">
                                {!! $event->deskripsi !!}
                            </li>
                        </ul>
                        {{-- <div class="card-body">
                            <a href="{{ $data->website }}" class="card-link">Lihat Campaign</a>
                        </div> --}}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
