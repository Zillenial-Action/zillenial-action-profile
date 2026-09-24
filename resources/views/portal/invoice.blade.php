@extends('components.layout.portal')


@section('content')
    <header id="header" class="header d-flex align-items-center fixed-top">
        <div class="container-fluid container-xl position-relative d-flex align-items-center">

            <a href="{{ url('/') }}" class="logo d-flex align-items-center me-auto">
                <img src="{{ asset('assets/img/za/Logo ZA_.png') }}" alt="">
            </a>

            <nav id="navmenu" class="navmenu">
                <ul>
                </ul>
                <i class="mobile-nav-toggle d-xl-none bi bi-list"></i>
            </nav>
        </div>
    </header>


    <!-- Facebook Pixel Code -->
    <script>
        ! function(f, b, e, v, n, t, s) {
            if (f.fbq) return;
            n = f.fbq = function() {
                n.callMethod ?
                    n.callMethod.apply(n, arguments) : n.queue.push(arguments)
            };
            if (!f._fbq) f._fbq = n;
            n.push = n;
            n.loaded = !0;
            n.version = '2.0';
            n.queue = [];
            t = b.createElement(e);
            t.async = !0;
            t.src = v;
            s = b.getElementsByTagName(e)[0];
            s.parentNode.insertBefore(t, s)
        }(window, document, 'script',
            'https://connect.facebook.net/en_US/fbevents.js');
        fbq('init', '824012039961095');
        fbq('track', 'PageView');

        // Menambahkan custom parameter
        fbq('track', 'Purchase', {
            content_name: "{{ $data->event->name }}",
            content_type: 'product',
            currency: 'IDR',
            value: {{ (int) $data->total_pembayaran }}
        });
    </script>
    <noscript>
        <img height="1" width="1" style="display:none"
            src="https://www.facebook.com/tr?id=824012039961095&ev=PageView&noscript=1" />
    </noscript>
    <!-- End Facebook Pixel Code -->

    <!-- TikTok Pixel Code Start -->
    <script>
        ! function(w, d, t) {
            w.TiktokAnalyticsObject = t;
            var ttq = w[t] = w[t] || [];
            ttq.methods = ["page", "track", "identify", "instances", "debug", "on", "off", "once", "ready", "alias",
                "group", "enableCookie", "disableCookie", "holdConsent", "revokeConsent", "grantConsent"
            ], ttq.setAndDefer = function(t, e) {
                t[e] = function() {
                    t.push([e].concat(Array.prototype.slice.call(arguments, 0)))
                }
            };
            for (var i = 0; i < ttq.methods.length; i++) ttq.setAndDefer(ttq, ttq.methods[i]);
            ttq.instance = function(t) {
                for (
                    var e = ttq._i[t] || [], n = 0; n < ttq.methods.length; n++) ttq.setAndDefer(e, ttq.methods[n]);
                return e
            }, ttq.load = function(e, n) {
                var r = "https://analytics.tiktok.com/i18n/pixel/events.js",
                    o = n && n.partner;
                ttq._i = ttq._i || {}, ttq._i[e] = [], ttq._i[e]._u = r, ttq._t = ttq._t || {}, ttq._t[e] = +new Date,
                    ttq._o = ttq._o || {}, ttq._o[e] = n || {};
                n = document.createElement("script");
                n.type = "text/javascript", n.async = !0, n.src = r + "?sdkid=" + e + "&lib=" + t;
                e = document.getElementsByTagName("script")[0];
                e.parentNode.insertBefore(n, e)
            };


            ttq.load('{id pixel}');
            ttq.page();

            // Menambahkan parameter detail
            ttq.track('CompletePayment', {
                content_id: "{{ $data->event->id }}",
                content_name: "{{ $data->event->name }}",
                value: "{{ $data->total_pembayaran }}",
                currency: 'IDR'
            });

        }(window, document, 'ttq');
    </script>
    <!-- TikTok Pixel Code End -->

    <main class="main">

        <!-- Page Title -->
        <div class="page-title">
            <div class="heading">
                <div class="container">
                    <div class="row d-flex justify-content-center text-center">
                        <div class="col-lg-8">
                            <h1>Pembayaran</h1>
                        </div>
                    </div>
                </div>
            </div>
            <nav class="breadcrumbs">
                <div class="container">
                    <ol>
                        <li><a href="{{ url('/') }}">Home</a></li>
                        <li class="current">Pembayaran</li>
                    </ol>
                </div>
            </nav>
        </div><!-- End Page Title -->

        <!-- Portfolio Details Section -->
        <section id="portfolio-details" class="portfolio-details section">

            <div class="container" data-aos="fade-up" data-aos-delay="100">

                <div class="row gy-4">


                    <div class="col-lg-4 mx-auto portfolio-info">
                        <!-- Countdown Timer -->
                        <div class="text-center mb-4">
                            <div class="card-body bg-light p-3">
                                <p class="fw-bold text-muted">Sisa Waktu Pembayaran</p>
                                <h2 class="display-5 fw-bold" style="color: #5a2d67" id="countdown-timer">
                                    00 : 00 : 00
                                </h2>
                                <small class="text-muted">jam &nbsp; menit &nbsp; detik</small>
                                <p class="mt-3 text-muted">
                                    Selesaikan pembayaranmu sebelum <br>
                                    <strong id="payment-deadline-display">Mon, 16 Dec 2024 10:01 AM</strong>
                                </p>
                            </div>
                        </div>

                        <div class="shadow-sm mb-4">
                            <div class="card-body p-3">
                                <div class="d-flex justify-content-between">
                                    <p class="mb-1 text-muted">Metode Pembayaran</p>
                                    <p class="mb-1 fw-bold" style="color: #5a2d67">
                                        {{ $data->payment?->type === 'midtrans' ? $data->payment->name : 'Midtrans' }}
                                    </p>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <p class="mb-1 text-muted">Total Pembayaran</p>
                                    <p class="mb-1 fw-bold" style="color: #5a2d67">@rupiah($data->total_pembayaran)</p>
                                </div>
                            </div>
                        </div>

                        <!-- Midtrans Snap Payment Button -->
                        @if ($data->snap_token && $data->status_pembayaran === 'Pending')
                        <div class="text-center mb-4">
                            <div class="card-body">
                                <p class="fw-bold text-muted">Selesaikan pembayaran via Midtrans</p>
                                <button id="pay-button" class="btn btn-lg w-100 text-white"
                                    style="background-color: #5a2d67"
                                    onclick="payWithMidtrans()">
                                    <i class="bi bi-credit-card-2-front"></i> Bayar Sekarang
                                </button>
                            </div>
                        </div>
                        @else
                        <div class="text-center mb-4">
                            <div class="card-body">
                                <p>Pembayaran sedang menunggu diproses. Jika tombol pembayaran tidak muncul, silakan
                                    hubungi admin untuk bantuan.</p>
                                <a href="https://wa.me/6282121392363?text=Halo%20Kak,%20apakah%20pembayaran%20untuk%20sosial%20trip%20saya%20sudah%20masuk%20"
                                    class="btn btn-lg w-100 text-white" style="background-color: #5a2d67">
                                    <i class="bi bi-shield-check"></i> Hubungi Admin
                                </a>
                            </div>
                        </div>
                        @endif

                        <!-- Petunjuk Pembayaran -->
                        {{-- <div class="shadow-sm">
                            <div class="card-body">
                                <p class="fw-bold text-muted mb-0">Petunjuk Pembayaran</p>
                            </div>
                        </div> --}}

                    </div>


                    <div class="col-lg-8 mx-auto">
                        <!-- Kode Pesanan -->
                        <div class="portfolio-info">
                            @php
                                $waktuMulai = new DateTime($data->event->waktu_mulai);
                                $waktuBerakhir = new DateTime($data->event->waktu_berakhir);
                            @endphp
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <p class="text-muted mb-0">Kode Pesanan <span
                                            class="fw-bold">{{ $data->invoice }}</span></p>
                                    <p class="text-muted mb-0">
                                        {{ \Carbon\Carbon::parse($data->tanggal_register)->format('D, d M Y h:i A') }}</p>
                                </div>

                                <!-- Detail Konten -->
                                <div class="row align-items-center">
                                    <!-- Gambar -->
                                    <div class="col-md-4">
                                        <img src="{{ asset($data->event->image) }}" alt="HOLIMOON 2024"
                                            class="img-fluid rounded">
                                    </div>

                                    <!-- Jadwal dan Lokasi -->
                                    <div class="col-md-8">
                                        <h5 class="fw-bold">{{ $data->event->name }}</h5>
                                        <div class="d-flex align-items-center mb-2">
                                            <i class="bi bi-calendar3 me-2"></i>
                                            <p class="mb-0">{{ $waktuMulai->format('d-m-Y') }}</p>
                                        </div>
                                        <div class="d-flex align-items-center mb-2">
                                            <i class="bi bi-clock me-2"></i>
                                            <p class="mb-0">{{ $waktuMulai->format('H:i') }} :
                                                {{ $waktuBerakhir->format('H:i') }}</p>
                                        </div>
                                        <div class="d-flex align-items-center mb-2">
                                            <i class="bi bi-geo-alt me-2"></i>
                                            <p class="mb-0">{{ $data->event->nama_tempat }}</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Info Tiket -->
                                <hr>
                                <div class="mt-3">
                                    <h6 class="fw-bold mb-2">Info Tiket</h6>
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-ticket-perforated me-2"></i>
                                        <p class="mb-0">&nbsp; {{ $data->jumlah_tiket }} Tiket</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>



                </div>

        </section><!-- /Portfolio Details Section -->

    </main>
    @include('components.layout.footer')
    {{-- Midtrans Snap.js hanya dimuat jika ada snap_token --}}
    @if ($data->snap_token && $data->status_pembayaran === 'Pending')
    <script src="{{ config('midtrans.snap_url') }}"
        data-client-key="{{ config('midtrans.client_key') }}"></script>
    <script>
        function payWithMidtrans() {
            const btn = document.getElementById('pay-button');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span> Memproses...';

            snap.pay('{{ $data->snap_token }}', {
                onSuccess: function(result) {
                    window.location.href = '/midtrans/finish/{{ $data->invoice }}';
                },
                onPending: function(result) {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="bi bi-credit-card-2-front"></i> Bayar Sekarang';
                    Swal.fire({
                        icon: 'info',
                        title: 'Menunggu Pembayaran',
                        text: 'Pembayaran sedang diproses. Cek email Anda untuk instruksi lebih lanjut.'
                    });
                },
                onError: function(result) {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="bi bi-credit-card-2-front"></i> Bayar Sekarang';
                    Swal.fire({
                        icon: 'error',
                        title: 'Pembayaran Gagal',
                        text: 'Terjadi kesalahan. Silakan coba lagi.'
                    });
                },
                onClose: function() {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="bi bi-credit-card-2-front"></i> Bayar Sekarang';
                }
            });
        }
    </script>
    @endif

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Ambil tanggal register dari PHP, lalu tambahkan 24 jam (atau deadline yang sudah dihitung)
            // Pastikan $data->tanggal_register adalah instance Carbon yang valid dan telah di-casts sebagai 'datetime' di model.
            // Asumsi $data->tanggal_register sudah ada dan merupakan tanggal mulai pembayaran.
            // Kita akan menambahkan 24 jam ke tanggal tersebut di JavaScript.
            const registerDateString = @json($data->tanggal_register->toIso8601String());
            console.log('registerDateString:', registerDateString); // Cek format ini
            const registerDate = new Date(registerDateString);
            console.log('registerDate (JS Object):', registerDate); // Cek apakah ini objek Date yang valid

            const paymentDeadline = new Date(registerDate.getTime() + (24 * 60 * 60 * 1000));
            console.log('paymentDeadline:', paymentDeadline); // Cek apakah deadline terhitung benar

            // Update tampilan tanggal deadline
            document.getElementById('payment-deadline-display').innerText = paymentDeadline.toLocaleString(
                'id-ID', {
                    weekday: 'short',
                    day: 'numeric',
                    month: 'short',
                    year: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit',
                    hour12: true
                });

            const countdownElement = document.getElementById('countdown-timer');

            function updateCountdown() {
                const now = new Date().getTime();
                const distance = paymentDeadline.getTime() - now;

                // Hitung waktu yang tersisa
                const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                const seconds = Math.floor((distance % (1000 * 60)) / 1000);

                // Format angka menjadi dua digit (misal: 05 daripada 5)
                const formattedHours = String(hours).padStart(2, '0');
                const formattedMinutes = String(minutes).padStart(2, '0');
                const formattedSeconds = String(seconds).padStart(2, '0');

                // Tampilkan sisa waktu
                countdownElement.innerHTML = `${formattedHours} : ${formattedMinutes} : ${formattedSeconds}`;

                // Jika hitungan mundur selesai
                if (distance < 0) {
                    clearInterval(countdownInterval); // Hentikan timer
                    countdownElement.innerHTML = "00 : 00 : 00";
                    alert("Waktu pembayaran telah berakhir!");
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal',
                        text: 'Waktu pembayaran telah berakhir!',
                        toast: true,
                        position: 'top-end',
                        timer: 3000,
                        showConfirmButton: false
                    });
                    // Anda bisa menambahkan logika lain di sini, seperti menonaktifkan tombol pembayaran
                    // atau mengubah status transaksi di backend via AJAX request.
                }
            }

            // Panggil fungsi sekali segera untuk menghindari jeda awal
            updateCountdown();

            // Perbarui hitungan mundur setiap 1 detik
            const countdownInterval = setInterval(updateCountdown, 1000);
        });
    </script>
@endsection
