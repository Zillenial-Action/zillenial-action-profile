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
        fbq('track', 'AddToCart', {
            content_name: "{{ $data->name }}",
            content_type: 'product'
        });
    </script>
    <noscript>
        <img height="1" width="1" style="display:none"
            src="https://www.facebook.com/tr?id=824012039961095&ev=PageView&noscript=1" />
    </noscript>
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


            ttq.load('{id pixel}');
            ttq.page();

            // Menambahkan parameter detail
            ttq.track('AddToCart', {
                content_id: "{{ $data->id }}",
                content_name: "{{ $data->name }}",
                content_type: "product"
            });

        }(window, document, 'ttq');
    </script>
    <!-- TikTok Pixel Code End -->

    <main class="main">

        <!-- Page Title -->
        <div class="page-title">
            <div class="heading">
                <div class="container">
                    <!-- Menampilkan Notifikasi Sukses -->
                    @if (session('success'))
                        <script>
                            Swal.fire({
                                icon: 'success',
                                title: 'Berhasil',
                                text: {{ session('success') }},
                                toast: true,
                                position: 'top-end',
                                timer: 3000,
                                showConfirmButton: false

                            });
                        </script>
                    @endif

                    <!-- Menampilkan Notifikasi Error -->
                    @if (session('error'))
                        <script>
                            Swal.fire({
                                icon: 'error',
                                title: 'Gagal',
                                text: {{ session('error') }},
                                toast: true,
                                position: 'top-end',
                                timer: 3000,
                                showConfirmButton: false

                            });
                        </script>
                    @endif

                    <!-- Menampilkan Pesan Validasi Error -->
                    @if ($errors->any())
                        {{-- <div class="alert alert-danger">
                            <ul> --}}
                        @foreach ($errors->all() as $error)
                            {{-- <li>{{ $error }}</li> --}}
                            <script>
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Cek Form',
                                    text: {{ $error }},
                                    toast: true,
                                    position: 'top-end',
                                    timer: 3000,
                                    showConfirmButton: false

                                });
                            </script>
                        @endforeach
                        {{-- </ul>
                        </div> --}}
                    @endif
                    <div class="row d-flex justify-content-center text-center">
                        <div class="col-lg-8">
                            <h1>Detail Pemesanan</h1>
                        </div>
                    </div>
                </div>
            </div>
            <nav class="breadcrumbs">
                <div class="container">
                    <ol>
                        <li><a href="{{ url('/') }}">Home</a></li>
                        <li class="current">Detail Pemesanan</li>
                    </ol>
                </div>
            </nav>
        </div><!-- End Page Title -->

        <!-- Portfolio Details Section -->
        <section id="portfolio-details" class="portfolio-details section">

            <div class="container" data-aos="fade-up" data-aos-delay="100">

                <form action="{{ route('transaksi.post', ['slug' => $data->slug]) }}" method="POST"
                    onsubmit="return validatePayment()">
                    @csrf
                    <div class="row gy-4">

                        <div class="col-lg-8">
                            <div class="portfolio-details-slider swiper init-swiper">

                                <h3>Detail Pemesanan</h3>

                                <div class="swiper-wrapper align-items-center">
                                    <div class="w-100 p-3">
                                        <!-- Container untuk form data diri yang akan di-generate dinamis -->
                                        <div id="form-data-diri-container">
                                            <!-- Form akan ditambahkan di sini secara dinamis -->
                                        </div>

                                        <div class="card-price py-2">
                                            <div class="d-flex justify-content-between align-items-center">
                                                @php
                                                    $waktuMulai = new DateTime($data->waktu_mulai);
                                                @endphp
                                                <div>
                                                    <h5 class="mb-0 font-weight-bold">NORMAL PRICE</h5>
                                                    <p class="text-muted mb-0">
                                                        <i class="fas fa-info-circle text-primary"></i> Berakhir
                                                        {{ $waktuMulai->format('d-m-Y / H:i') }}
                                                    </p>
                                                </div>
                                                <div>
                                                    <h5 class="font-weight-bold mb-0">@rupiah($data['harga'])</h5>
                                                </div>
                                            </div>
                                            <div class="divider"></div>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div class="d-flex align-items-center">
                                                    <button class="btn btn-circle text-white"
                                                        style="background-color: #5a2d67" type="button"
                                                        id="decrease-btn">-</button>
                                                    <span class="mx-3 font-weight-bold" id="counter">1</span>
                                                    <button class="btn btn-circle text-white"
                                                        style="background-color: #5a2d67" type="button"
                                                        id="increase-btn">+</button>
                                                    <input type="hidden" id="jumlah_tiket_input" name="jumlah_tiket"
                                                        value="1">
                                                </div>
                                                <div>
                                                    <!-- Total Harga -->
                                                    <h5 class="mb-0" id="total-price-display">@rupiah($data['harga'])</h5>
                                                    <input type="hidden" name="price"
                                                        class="form-control text-right mb-2" id="total-price-input"
                                                        value="{{ $data['harga'] }}" readonly>
                                                </div>
                                            </div>
                                        </div>

                                    </div>

                                </div>
                                <div class="container mt-4">
                                    <h3 class="mb-3">Kode Voucher</h3>
                                    <div class="input-group mb-3">
                                        <input type="text" class="form-control" id="voucher_code"
                                            placeholder="Masukkan kode voucher (opsional)">
                                        <button class="btn text-white" style="background-color: #5a2d67" type="button"
                                            id="apply-voucher-btn">
                                            Terapkan
                                        </button>
                                    </div>
                                    <input type="hidden" name="voucher_code" id="voucher_code_input" value="">
                                    <input type="hidden" name="discount_amount" id="discount_amount_input" value="0">
                                    <div id="voucher-message" class="mb-2" style="display: none;">
                                        <!-- Voucher success/error message will appear here -->
                                    </div>
                                    <div id="discount-info" class="alert alert-success" style="display: none;">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span>Diskon Voucher</span>
                                            <strong id="discount-display">-Rp 0</strong>
                                        </div>
                                    </div>
                                </div>
                                <div class="container mt-5">
                                    <h3 class="mb-4">Metode Pembayaran</h3>
                                    <div class="list-group">
                                        @if ($payment)
                                            <div class="list-group-item d-flex align-items-center">
                                                <i class="bi bi-credit-card-2-front fs-3 me-3"
                                                    style="color: #5a2d67"></i>
                                                <div>
                                                    <span class="fw-semibold d-block">Bayar via Midtrans</span>
                                                    <small class="text-muted">
                                                        Pilih metode pembayaran (Transfer Bank/VA, GoPay, QRIS, kartu
                                                        kredit, dan lainnya) di halaman pembayaran berikutnya.
                                                    </small>
                                                </div>
                                            </div>
                                        @else
                                            <div class="alert alert-warning mb-0">
                                                Metode pembayaran Midtrans belum tersedia. Silakan hubungi admin.
                                            </div>
                                        @endif
                                    </div>
                                </div>
                                <script>
                                    function validatePayment() {
                                        return true;
                                    }
                                </script>
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
                                <div class="form-group">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="gridCheck"
                                            onclick="toggleButton()" onchange="toggleCheckboxColor(this)">
                                        <!-- Tambahkan event onclick -->
                                        <label class="form-check-label" for="gridCheck">
                                            Saya setuju dengan Syarat & Ketentuan yang berlaku.
                                        </label>
                                    </div>
                                </div>
                                <!-- Total Harga -->
                                <div class="d-flex justify-content-between align-items-center pt-3">
                                    <button type="submit" id="submitButton" class="btn btn-lg w-100 text-white"
                                        style="background-color: #5a2d67" disabled>
                                        <!-- Tombol awal dalam status disabled -->
                                        Bayar Tiket
                                    </button>
                                </div>
                            </div>

                            <script>
                                function toggleButton() {
                                    const checkbox = document.getElementById('gridCheck');
                                    const button = document.getElementById('submitButton');

                                    if (checkbox.checked) {
                                        // Jika checkbox dicentang
                                        button.disabled = false;
                                        button.style.backgroundColor = '#5a2d67'; // Kembalikan ke warna default Bootstrap
                                        button.style.borderColor = '';
                                    } else {
                                        // Jika checkbox tidak dicentang
                                        button.disabled = true;
                                        button.style.backgroundColor = '#5a2d67'; // Warna biru keputihan
                                        button.style.borderColor = '';
                                    }
                                }
                            </script>
                        </div>
                </form>

            </div>

        </section><!-- /Portfolio Details Section -->

    </main>
    @include('components.layout.footer')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // JavaScript untuk menangani jumlah
        let counter = 1; // Ubah default dari 0 menjadi 1
        const jumlah_tiket = @json($data['jumlah_tiket']);
        const pricePerItem = @json($data['harga']);
        let jumlahTiketInput = document.getElementById('jumlah_tiket_input');
        const counterDisplay = document.getElementById('counter');
        const formContainer = document.getElementById('form-data-diri-container');

        // Fungsi untuk membuat form data diri
        function generateFormDataDiri(index) {
            const arrayIndex = index - 1; // Convert to 0-based index for array
            return `
            <div class="card mb-3 p-3" style="border: 1px solid #dee2e6; border-radius: 8px;" id="form-tiket-${index}">
                <h5 class="mb-3" style="color: #5a2d67; font-weight: bold;">Data Volunteer ${index}</h5>
                <div class="form-group py-2">
                    <label for="name_${index}">Nama Lengkap <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="name_${index}" name="pengunjung[${arrayIndex}][name]"
                        placeholder="Masukan Nama Anda" required minlength="3" maxlength="100"
                        pattern="[A-Za-z\\s]+" title="Nama hanya boleh berisi huruf dan spasi">
                    <small class="text-muted">Minimal 3 karakter, hanya huruf dan spasi</small>
                </div>
                <div class="form-group py-2">
                    <label for="telepon_${index}">Nomor Ponsel <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text" id="basic-addon-${index}">+62</span>
                        </div>
                        <input type="tel" class="form-control" id="telepon_${index}" name="pengunjung[${arrayIndex}][telepon]"
                            placeholder="8123456789" aria-label="Nomor Ponsel"
                            aria-describedby="basic-addon-${index}" required
                            pattern="[0-9]{9,13}" minlength="9" maxlength="13"
                            title="Nomor telepon harus 9-13 digit angka (tanpa +62)"
                            oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                    </div>
                    <small class="text-muted">Contoh: 8123456789 (tanpa 0 di depan)</small>
                </div>
                <div class="form-group py-2 mb-2">
                    <label for="email_${index}">Email <span class="text-danger">*</span></label>
                    <input type="email" class="form-control" id="email_${index}" name="pengunjung[${arrayIndex}][email]"
                        placeholder="contoh@email.com" required
                        pattern="[a-zA-Z0-9._%+\\-]+@[a-zA-Z0-9.\\-]+\\.[a-zA-Z]{2,}"
                        title="Masukkan alamat email yang valid">
                    <small class="text-muted">E-tiket akan dikirim ke email ini</small>
                </div>
            </div>
        `;
        }

        // Object untuk menyimpan data form sementara
        let formData = {};

        // Fungsi untuk menyimpan data form yang sudah diisi
        function saveFormData() {
            const forms = formContainer.querySelectorAll('.card');
            forms.forEach((form, idx) => {
                const index = idx + 1;
                const nameInput = document.getElementById(`name_${index}`);
                const teleponInput = document.getElementById(`telepon_${index}`);
                const emailInput = document.getElementById(`email_${index}`);

                if (nameInput && teleponInput && emailInput) {
                    formData[index] = {
                        name: nameInput.value,
                        telepon: teleponInput.value,
                        email: emailInput.value
                    };
                }
            });
        }

        // Fungsi untuk memperbarui form data diri
        function updateFormDataDiri() {
            // Simpan data yang sudah diisi sebelum update
            saveFormData();

            formContainer.innerHTML = '';
            for (let i = 1; i <= counter; i++) {
                formContainer.innerHTML += generateFormDataDiri(i);
            }

            // Kembalikan data yang sudah diisi sebelumnya
            for (let i = 1; i <= counter; i++) {
                if (formData[i]) {
                    const nameInput = document.getElementById(`name_${i}`);
                    const teleponInput = document.getElementById(`telepon_${i}`);
                    const emailInput = document.getElementById(`email_${i}`);

                    if (nameInput) nameInput.value = formData[i].name || '';
                    if (teleponInput) teleponInput.value = formData[i].telepon || '';
                    if (emailInput) emailInput.value = formData[i].email || '';
                }
            }
        }

        // Fungsi untuk memperbarui total harga dan tampilan counter
        function updateTotalPriceAndCounter() {
            const totalPrice = counter * pricePerItem;

            // Perbarui nilai di input field (tanpa titik)
            document.getElementById('total-price-input').value = totalPrice;

            // Perbarui nilai di elemen <h5> (dengan format Rp XXX.XXX)
            document.getElementById('total-price-display').innerText = `Rp ${totalPrice.toLocaleString('id-ID')}`;

            // Perbarui nilai di elemen <span> counter
            counterDisplay.innerText = counter;

            // Update form data diri
            updateFormDataDiri();
        }

        // Inisialisasi form pertama kali saat halaman load
        updateFormDataDiri();

        // Tombol tambah
        document.getElementById('increase-btn').addEventListener('click', () => {
            if (counter < jumlah_tiket) {
                if (counter >= 3) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal',
                        text: 'Maksimal tiket hanya 3!',
                        toast: true,
                        position: 'top-end',
                        timer: 3000,
                        showConfirmButton: false
                    });
                } else {
                    counter++;
                    jumlahTiketInput.value = counter;
                    updateTotalPriceAndCounter();
                }
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal',
                    text: 'Sisa Tiket Hanya Ada ' + jumlah_tiket,
                    toast: true,
                    position: 'top-end',
                    timer: 3000,
                    showConfirmButton: false
                });
            }
        });

        // Tombol kurang
        document.getElementById('decrease-btn').addEventListener('click', () => {
            if (counter > 1) { // Ubah dari > 0 menjadi > 1 agar minimal 1 tiket
                counter--;
                jumlahTiketInput.value = counter;
                updateTotalPriceAndCounter();
            }
        });

        function toggleCheckboxColor(checkbox) {
            if (checkbox.checked) {
                checkbox.style.backgroundColor = '#5a2d67';
                checkbox.style.borderColor = '#5a2d67';
            } else {
                checkbox.style.backgroundColor = '';
                checkbox.style.borderColor = '';
            }
        }

        // Voucher functionality
        let discountPerTicket = 0; // Diskon per tiket
        const eventId = @json($data->id);

        document.getElementById('apply-voucher-btn').addEventListener('click', async function() {
            const voucherCode = document.getElementById('voucher_code').value.trim();
            const messageDiv = document.getElementById('voucher-message');
            const discountInfo = document.getElementById('discount-info');
            const discountDisplay = document.getElementById('discount-display');

            if (!voucherCode) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Kode Voucher Kosong',
                    text: 'Masukkan kode voucher terlebih dahulu'
                });
                return;
            }

            this.disabled = true;
            this.innerHTML =
            '<span class="spinner-border spinner-border-sm" role="status"></span> Memproses...';

            try {
                const response = await fetch('/api/voucher/validate', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                            'content')
                    },
                    body: JSON.stringify({
                        code: voucherCode,
                        event_id: eventId,
                        jumlah_tiket: counter
                    })
                });

                const result = await response.json();

                if (result.success) {
                    // Apply discount per ticket
                    discountPerTicket = result.discount_per_ticket;
                    document.getElementById('voucher_code_input').value = voucherCode;
                    document.getElementById('discount_amount_input').value = discountPerTicket;

                    // Calculate total discount based on current ticket count
                    const totalDiscount = discountPerTicket * counter;

                    // Show success - hide message div since we use SweetAlert
                    messageDiv.style.display = 'none';

                    // Show discount info
                    discountDisplay.textContent = `-Rp ${totalDiscount.toLocaleString('id-ID')}`;
                    discountInfo.style.display = 'block';

                    // Disable input after success
                    document.getElementById('voucher_code').disabled = true;
                    this.textContent = 'Diterapkan ✓';
                    this.disabled = true;
                    this.style.backgroundColor = '#28a745';

                    // Voucher external hanya untuk 1 volunteer: kunci jumlah tiket ke 1.
                    if (result.is_external) {
                        counter = 1;
                        jumlahTiketInput.value = 1;
                        counterDisplay.innerText = 1;
                        document.getElementById('increase-btn').disabled = true;
                        document.getElementById('decrease-btn').disabled = true;
                    }

                    // Update total price with discount
                    updateTotalPriceWithDiscount();

                    Swal.fire({
                        icon: 'success',
                        title: 'Voucher Berhasil!',
                        text: result.message
                    });
                } else {
                    discountInfo.style.display = 'none';
                    discountPerTicket = 0;
                    document.getElementById('discount_amount_input').value = 0;

                    Swal.fire({
                        icon: 'error',
                        title: 'Voucher Gagal',
                        text: result.message
                    });

                    this.disabled = false;
                    this.textContent = 'Terapkan';
                }
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Terjadi Kesalahan',
                    text: 'Gagal memvalidasi voucher. Coba lagi.'
                });
                this.disabled = false;
                this.textContent = 'Terapkan';
            }
        });

        function updateTotalPriceWithDiscount() {
            let totalPrice = counter * pricePerItem;
            let totalDiscount = discountPerTicket * counter; // Diskon = diskon per tiket * jumlah tiket
            let finalPrice = Math.max(0, totalPrice - totalDiscount);

            document.getElementById('total-price-input').value = finalPrice;
            document.getElementById('total-price-display').innerText = `Rp ${finalPrice.toLocaleString('id-ID')}`;

            // Update discount display
            if (discountPerTicket > 0) {
                document.getElementById('discount-display').textContent = `-Rp ${totalDiscount.toLocaleString('id-ID')}`;
                document.getElementById('total-price-display').innerHTML =
                    `<span class="text-decoration-line-through text-muted me-2">Rp ${totalPrice.toLocaleString('id-ID')}</span>` +
                    `<span class="text-success fw-bold">Rp ${finalPrice.toLocaleString('id-ID')}</span>`;
            }
        }

        // Override updateTotalPriceAndCounter to include discount
        const originalUpdateFunction = updateTotalPriceAndCounter;
        updateTotalPriceAndCounter = function() {
            const totalPrice = counter * pricePerItem;
            const totalDiscount = discountPerTicket * counter; // Diskon = diskon per tiket * jumlah tiket
            const finalPrice = Math.max(0, totalPrice - totalDiscount);

            document.getElementById('total-price-input').value = finalPrice;

            if (discountPerTicket > 0) {
                document.getElementById('discount-display').textContent =
                `-Rp ${totalDiscount.toLocaleString('id-ID')}`;
                document.getElementById('total-price-display').innerHTML =
                    `<span class="text-decoration-line-through text-muted me-2">Rp ${totalPrice.toLocaleString('id-ID')}</span>` +
                    `<span class="text-success fw-bold">Rp ${finalPrice.toLocaleString('id-ID')}</span>`;
            } else {
                document.getElementById('total-price-display').innerText = `Rp ${totalPrice.toLocaleString('id-ID')}`;
            }

            counterDisplay.innerText = counter;
            updateFormDataDiri();
        };
    </script>
@endsection
