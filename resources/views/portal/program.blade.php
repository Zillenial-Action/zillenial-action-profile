@extends('components.layout.portal')

@section('content')
    @include('components.layout.header')

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

        }(window, document, 'ttq');
    </script>
    <!-- TikTok Pixel Code End -->

    <main class="main py-5">

        <!-- Services Section -->
        <section id="services" class="services section">

            <!-- Section Title -->
            <div class="container section-title " data-aos="fade-up">
                <p>Event Sosial Trip</p>

                <div class="col-md-4 mb-3 mt-4"> <!-- Mengubah ukuran kolom menjadi 4 -->
                    <form class="input-group" method="GET" action="{{ route('portal.search') }}">
                        <input id="search" name="search" type="text" class="form-control" placeholder="Search ..."
                            aria-label="Search ..." aria-describedby="button-addon2">
                        <div class="input-group-append">
                            <button class="btn btn-outline-secondary" type="submit" id="button-addon2">Search</button>
                        </div>
                    </form>
                </div>


            </div>
            <!-- End Section Title -->

            <div class="container">

                {{-- @foreach ($event as $item)
                    <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="100">
                        <div class="service-item item-cyan position-relative">
                            <img src="{{ $item->image }}" alt="{{ $item->name }}" height="200" width="200"
                                class="img-fluid mb-4" />
                            <h3>{{ $item->name }}</h3>
                            <p>{{ $item->deskripsi }}</p>
                            <a href="{{ route('view_content', ['slug' => $item->slug]) }}" class="read-more stretched-link">
                                <span>Lihat</span>
                                <i class="bi bi-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                @endforeach --}}
                <div class="row gy-4" id="program-list">
                    @foreach ($event as $item)
                        <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="100">
                            <a href="{{ route('view_content', ['slug' => $item->slug]) }}">
                                <div class="service-item item-indigo position-relative">
                                    <img src="{{ asset($item->image) }}" alt="Social trip service image" height="200"
                                        width="200" class="img-fluid mb-4 rounded" loading="lazy" />
                                    <h3 class="text-start">{{ $item->name }}</h3>
                                    <p class="text-start">{{ date('d F Y', strtotime($item->waktu_mulai)) }} Pukul
                                        {{ date('H:i', strtotime($item->waktu_mulai)) }} -
                                        {{ date('H:i', strtotime($item->waktu_berakhir)) }}</p>
                                    <p class="text-start">@rupiah($item->harga)</p>
                                </div>
                            </a>
                        </div>
                    @endforeach
                </div>

                <!-- Tombol Selengkapnya -->
                <div class="text-center mt-4">
                    {!! $event->appends(request()->query())->links('pagination::bootstrap-4') !!}
                </div>
        </section>
        <!-- /Services Section -->

    </main>

    @include('components.layout.footer')
@endsection
