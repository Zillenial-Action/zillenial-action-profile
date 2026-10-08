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
        fbq('track', 'ViewContent', {
            content_name: "{{ $data->name }}",
            content_type: 'product'
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


            ttq.load('C4I4LTHCF95KKVVI6N7G');
            ttq.page();

            // Menambahkan parameter detail
            ttq.track('ViewContent', {
                content_id: "{{ $data->id }}",
                content_name: "{{ $data->name }}",
                content_type: "product"
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
                            <h1>Event View</h1>
                        </div>
                    </div>
                </div>
            </div>
            <nav class="breadcrumbs">
                <div class="container">
                    <ol>
                        <li><a href="{{ url('/') }}">Home</a></li>
                        <li class="current">Event View</li>
                    </ol>
                </div>
            </nav>
        </div><!-- End Page Title -->

        <!-- Portfolio Details Section -->
        <section id="portfolio-details" class="portfolio-details section">

            <div class="container" data-aos="fade-up" data-aos-delay="100">

                <div class="row gy-4">

                    <div class="col-lg-8">
                        <div class="portfolio-details-slider swiper init-swiper">

                            <div class="swiper-wrapper align-items-center">

                                <div class="swiper-slide">
                                    <img src="{{ $data->image }}" alt="">
                                </div>

                            </div>
                            <!-- <div class="swiper-pagination"> -->
                            <div class="container mt-5">
                                <p class="mb-4 h3">{{ $data->name }}</p>
                                <p class="mb-4 h4">Syarat & Ketentuan</p>
                                {!! $data->deskripsi !!}
                            </div>
                            <!-- </div> -->
                        </div>
                    </div>

                    <div class="col-lg-4">

                        <div class="portfolio-info mt-4" data-aos="fade-up" data-aos-delay="200">
                            <h3>{{ $data->name }}</h3>

                            @php
                                $waktuMulai = new DateTime($data->waktu_mulai);
                            @endphp

                            <!-- Waktu -->
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <p class="mb-0">Waktu</p>
                                <h5 class="mb-0">{{ $waktuMulai->format('H:i') }}</h5>
                            </div>

                            <!-- Tanggal -->
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <p class="mb-0">Tanggal</p>
                                <h5 class="mb-0">{{ $waktuMulai->format('d-m-Y') }}</h5>
                            </div>


                            <!-- Tempat -->
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <p class="mb-0">Lokasi</p>
                                <h5 class="mb-0">{{ $data->nama_tempat }}</h5>
                            </div>

                            <!-- Tempat -->
                            <div class="d-flex justify-content-between align-items-center border-top pt-5 mt-2 mb-3">
                                <p class="mb-0">Oleh: </p>
                                <h5 class="mb-0">{{ $data->mitra }}</h5>
                            </div>

                        </div>

                        <div class="portfolio-info mt-4" data-aos="fade-up" data-aos-delay="200">
                            <!-- Harga Mulai -->
                            <div class="d-flex justify-content-between align-items-center pb-5 mb-2 mb-3 border-bottom">
                                <p class="mb-0">Harga Mulai</p>
                                <h5 class="mb-0">@rupiah($data->harga)</h5>
                            </div>
                            @if ($data->jumlah_tiket > 0 && $data->status == 1)
                                <a href="{{ route('checkout', ['slug' => $data->slug]) }}" class="btn btn-block text-white"
                                    style="background-color:#5a2d67">Beli Tiket</a>
                            @else
                                <div
                                    style="background-color: #f2f2f2; padding: 15px; border-radius: 8px; text-align: center; margin-top: 20px;">
                                    <p style="font-size: 1.1rem; font-weight: bold; color: #dc3545; margin-bottom: 15px;">
                                        Tiket Habis</p>
                                    <a href="{{ url('/event-sostrip') }}"
                                        style="background-color: #5a2d67; border-color: #5a2d67; color: white; font-weight: bold; padding: 10px 25px; border-radius: 5px; text-decoration: none; display: inline-block;">Lihat
                                        Event Lain</a>
                                </div>
                            @endif
                        </div>

                    </div>

                </div>

        </section><!-- /Portfolio Details Section -->

    </main>
    @include('components.layout.footer')
@endsection
