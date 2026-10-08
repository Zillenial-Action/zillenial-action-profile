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

    @if ($data->status_pembayaran === 'Success')
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

            // eventID = invoice agar reload halaman tidak dihitung sebagai Purchase baru
            fbq('track', 'Purchase', {
                content_name: @json($data->event->name),
                content_type: 'product',
                currency: 'IDR',
                value: {{ (int) $data->total_pembayaran }}
            }, {
                eventID: @json('purchase-'.$data->invoice)
            });
        </script>
        <!-- End Facebook Pixel Code -->

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

                ttq.load('C4I4LTHCF95KKVVI6N7G');
                ttq.page();

                ttq.track('CompletePayment', {
                    content_id: @json((string) $data->event->id),
                    content_name: @json($data->event->name),
                    value: {{ (int) $data->total_pembayaran }},
                    currency: 'IDR'
                }, {
                    event_id: @json('purchase-'.$data->invoice)
                });
            }(window, document, 'ttq');
        </script>
        <!-- TikTok Pixel Code End -->
    @elseif ($data->status_pembayaran === 'Pending')
        <script>
            // Snap redirect ke sini sebelum webhook Midtrans tiba. Cek status berkala; begitu Success,
            // muat ulang supaya server merender pixel Purchase (eventID sama, jadi tidak dobel).
            (function() {
                var url = @json(url('/api/transaksi/'.rawurlencode($data->invoice))) +
                    '?token=' + encodeURIComponent(new URLSearchParams(window.location.search).get('token') || '');
                var attempt = 0;

                function poll() {
                    if (attempt++ >= 40) return; // ±2 menit, setelah itu user bisa reload manual
                    fetch(url, { headers: { Accept: 'application/json' } })
                        .then(function(res) { return res.ok ? res.json() : null; })
                        .then(function(res) {
                            var status = res && res.data ? res.data.status_pembayaran : null;
                            if (status === 'Success') { window.location.reload(); return; }
                            if (status === 'Failed') return;
                            setTimeout(poll, 3000);
                        })
                        .catch(function() { setTimeout(poll, 3000); });
                }

                setTimeout(poll, 3000);
            })();
        </script>
    @endif

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
