@extends('components.layout.portal')

@section('content')
    <header id="header" class="header d-flex align-items-center fixed-top">
        <div class="container-fluid container-xl position-relative d-flex align-items-center">
            <a href="{{ url('/') }}" class="logo d-flex align-items-center me-auto">
                <img src="{{ asset('assets/img/za/Logo ZA_.png') }}" alt="">
            </a>
            <nav id="navmenu" class="navmenu">
                <ul></ul>
                <i class="mobile-nav-toggle d-xl-none bi bi-list"></i>
            </nav>
        </div>
    </header>

    <main class="main">

        <!-- Page Title -->
        <div class="page-title">
            <div class="heading">
                <div class="container">
                    <div class="row d-flex justify-content-center text-center">
                        <div class="col-lg-8">
                            <h1>Pembayaran Berhasil</h1>
                        </div>
                    </div>
                </div>
            </div>
            <nav class="breadcrumbs">
                <div class="container">
                    <ol>
                        <li><a href="{{ url('/') }}">Home</a></li>
                        <li class="current">Pembayaran Berhasil</li>
                    </ol>
                </div>
            </nav>
        </div><!-- End Page Title -->

        <section class="section">
            <div class="container" data-aos="fade-up" data-aos-delay="100">
                <div class="row justify-content-center">
                    <div class="col-lg-7 col-md-9">

                        <!-- Success Card -->
                        <div class="card shadow-sm border-0 text-center mb-4">
                            <div class="card-body p-5">
                                <!-- Ikon Sukses -->
                                <div class="mb-4">
                                    <div class="d-inline-flex align-items-center justify-content-center rounded-circle"
                                        style="width:90px;height:90px;background-color:#f0f9f0;">
                                        <i class="bi bi-check-circle-fill" style="font-size:3rem;color:#28a745;"></i>
                                    </div>
                                </div>

                                <h2 class="fw-bold mb-1" style="color: #5a2d67;">Pembayaran Berhasil!</h2>
                                <p class="text-muted mb-4">
                                    Terima kasih, pembayaran kamu telah kami terima.<br>
                                    Tiket akan segera dikirimkan ke email yang kamu daftarkan.
                                </p>

                                <!-- Info Transaksi -->
                                <div class="card bg-light border-0 text-start mb-4">
                                    <div class="card-body p-4">
                                        <h6 class="fw-bold mb-3" style="color:#5a2d67;">
                                            <i class="bi bi-receipt me-2"></i>Detail Pesanan
                                        </h6>

                                        <div class="d-flex justify-content-between mb-2">
                                            <span class="text-muted">Kode Pesanan</span>
                                            <span class="fw-semibold">{{ $data->invoice }}</span>
                                        </div>
                                        <div class="d-flex justify-content-between mb-2">
                                            <span class="text-muted">Event</span>
                                            <span class="fw-semibold">{{ $data->event->name }}</span>
                                        </div>
                                        <div class="d-flex justify-content-between mb-2">
                                            <span class="text-muted">Jumlah Tiket</span>
                                            <span class="fw-semibold">{{ $data->jumlah_tiket }} Tiket</span>
                                        </div>
                                        <div class="d-flex justify-content-between mb-2">
                                            <span class="text-muted">Nama</span>
                                            <span class="fw-semibold">{{ $data->name }}</span>
                                        </div>
                                        <div class="d-flex justify-content-between mb-2">
                                            <span class="text-muted">Email</span>
                                            <span class="fw-semibold">{{ $data->email }}</span>
                                        </div>
                                        <hr>
                                        <div class="d-flex justify-content-between">
                                            <span class="fw-bold">Total Pembayaran</span>
                                            <span class="fw-bold" style="color:#5a2d67;">@rupiah($data->total_pembayaran)</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Status Badge -->
                                <div class="mb-4">
                                    @if ($data->status_pembayaran === 'Success')
                                        <span class="badge fs-6 px-4 py-2" style="background-color:#28a745;">
                                            <i class="bi bi-check-circle me-1"></i> Lunas
                                        </span>
                                    @elseif ($data->status_pembayaran === 'Pending')
                                        <span class="badge fs-6 px-4 py-2 bg-warning text-dark">
                                            <i class="bi bi-hourglass-split me-1"></i> Menunggu Konfirmasi
                                        </span>
                                        <p class="text-muted small mt-2">
                                            Pembayaran sedang diverifikasi. Tiket akan dikirim setelah pembayaran terkonfirmasi.
                                        </p>
                                    @else
                                        <span class="badge fs-6 px-4 py-2 bg-secondary">
                                            {{ $data->status_pembayaran }}
                                        </span>
                                    @endif
                                </div>

                                <!-- Info Email -->
                                <div class="alert border-0 mb-4" style="background-color:#f3eaff;color:#5a2d67;">
                                    <i class="bi bi-envelope-check me-2"></i>
                                    Tiket dikirim ke <strong>{{ $data->email }}</strong>.<br>
                                    Cek folder <strong>Spam/Junk</strong> jika tidak ada di Inbox.
                                </div>

                                <!-- Tombol Aksi -->
                                <div class="d-flex flex-column flex-sm-row gap-3 justify-content-center">
                                    <a href="{{ url('/') }}" class="btn btn-outline-secondary btn-lg">
                                        <i class="bi bi-house me-1"></i> Kembali ke Beranda
                                    </a>
                                    <a href="{{ route('invoice', $data->invoice) }}"
                                        class="btn btn-lg text-white"
                                        style="background-color:#5a2d67;">
                                        <i class="bi bi-receipt me-1"></i> Lihat Invoice
                                    </a>
                                    <a href="https://wa.me/6282121392363?text=Halo%20Kak,%20saya%20sudah%20melakukan%20pembayaran%20untuk%20{{ urlencode($data->event->name) }}%20dengan%20kode%20pesanan%20{{ $data->invoice }}"
                                        class="btn btn-lg text-white" style="background-color:#25D366;"
                                        target="_blank">
                                        <i class="bi bi-whatsapp me-1"></i> Hubungi Admin
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Info Event -->
                        @php
                            $waktuMulai = new DateTime($data->event->waktu_mulai);
                        @endphp
                        <div class="card shadow-sm border-0 mb-4">
                            <div class="card-body p-4">
                                <h6 class="fw-bold mb-3" style="color:#5a2d67;">
                                    <i class="bi bi-calendar-event me-2"></i>Info Event
                                </h6>
                                <div class="row align-items-center">
                                    <div class="col-md-3 mb-3 mb-md-0">
                                        <img src="{{ asset($data->event->image) }}" alt="{{ $data->event->name }}"
                                            class="img-fluid rounded" style="max-height:80px;object-fit:cover;">
                                    </div>
                                    <div class="col-md-9">
                                        <h6 class="fw-bold mb-1">{{ $data->event->name }}</h6>
                                        <p class="text-muted mb-1">
                                            <i class="bi bi-calendar3 me-1"></i>
                                            {{ $waktuMulai->format('d M Y, H:i') }} WIB
                                        </p>
                                        <p class="text-muted mb-0">
                                            <i class="bi bi-geo-alt me-1"></i>
                                            {{ $data->event->nama_tempat }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </section>

    </main>

    @include('components.layout.footer')
@endsection
