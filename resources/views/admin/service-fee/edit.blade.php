@extends('components.layout.app')

@section('content')
    @php
        $tipe = old('service_fee_type', $config['tipe']);
        $percent = old('service_fee_percent', $config['tipe'] === 'percent' ? rtrim(rtrim(number_format($config['nilai'], 2, '.', ''), '0'), '.') : '');
        $fixed = old('service_fee_fixed', $config['tipe'] === 'fixed' ? (int) $config['nilai'] : '');
    @endphp

    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-10 col-lg-8">

                <x-form-alerts />

                <div class="mb-3">
                    <h2 class="page-title">Biaya Layanan</h2>
                    <p class="page-subtitle mb-0">
                        Ditambahkan ke total setiap pembelian tiket di halaman checkout. Transaksi yang sudah dibuat
                        tidak ikut berubah saat pengaturan ini diganti.
                    </p>
                </div>

                <form method="POST" action="{{ route('service-fee.update') }}" data-service-fee-form>
                    @csrf
                    @method('PUT')

                    <div class="card mb-3">
                        <div class="card-header">
                            <i class="bi bi-percent me-2"></i>Jenis Biaya
                        </div>
                        <div class="card-body p-4">
                            @foreach ([
                                'none' => ['Tanpa biaya layanan', 'Pembeli hanya membayar harga tiket.'],
                                'percent' => ['Persen', 'Persentase dari subtotal tiket setelah potongan voucher.'],
                                'fixed' => ['Nominal per tiket', 'Jumlah rupiah tetap untuk setiap tiket yang dibeli.'],
                            ] as $value => [$label, $help])
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="radio" name="service_fee_type"
                                        id="service_fee_type_{{ $value }}" value="{{ $value }}"
                                        @checked($tipe === $value)>
                                    <label class="form-check-label" for="service_fee_type_{{ $value }}">
                                        <span class="fw-semibold">{{ $label }}</span>
                                        <small class="text-muted d-block">{{ $help }}</small>
                                    </label>
                                </div>
                            @endforeach
                            @error('service_fee_type')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror

                            <div class="mt-3" data-fee-field="percent" @if ($tipe !== 'percent') hidden @endif>
                                <label for="service_fee_percent" class="form-label fw-semibold">Persentase</label>
                                <div class="input-group" style="max-width: 220px;">
                                    <input type="number" step="0.01" min="0.01" max="100" inputmode="decimal"
                                        class="form-control @error('service_fee_percent') is-invalid @enderror"
                                        id="service_fee_percent" name="service_fee_percent" value="{{ $percent }}"
                                        placeholder="5">
                                    <span class="input-group-text">%</span>
                                    @error('service_fee_percent')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <small class="text-muted">Dibulatkan ke atas ke rupiah terdekat.</small>
                            </div>

                            <div class="mt-3" data-fee-field="fixed" @if ($tipe !== 'fixed') hidden @endif>
                                <label for="service_fee_fixed" class="form-label fw-semibold">Nominal per tiket</label>
                                <div class="input-group" style="max-width: 260px;">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" step="1" min="1" inputmode="numeric"
                                        class="form-control @error('service_fee_fixed') is-invalid @enderror"
                                        id="service_fee_fixed" name="service_fee_fixed" value="{{ $fixed }}"
                                        placeholder="5000">
                                    @error('service_fee_fixed')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-3">
                        <div class="card-header">
                            <i class="bi bi-calculator me-2"></i>Contoh Perhitungan
                        </div>
                        <div class="card-body p-4">
                            <p class="text-muted small mb-3">2 tiket @ Rp100.000, tanpa voucher.</p>
                            <dl class="row mb-0 small" style="max-width: 360px;">
                                <dt class="col-7 fw-normal text-muted">Subtotal tiket</dt>
                                <dd class="col-5 text-end mb-1">Rp200.000</dd>
                                <dt class="col-7 fw-normal text-muted">Biaya Layanan</dt>
                                <dd class="col-5 text-end mb-1" data-example-fee>Rp0</dd>
                                <dt class="col-7">Total dibayar</dt>
                                <dd class="col-5 text-end fw-bold mb-0" data-example-total>Rp200.000</dd>
                            </dl>
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg me-1"></i>Simpan Biaya Layanan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (() => {
            const form = document.querySelector('[data-service-fee-form]');
            if (!form) return;

            const subtotal = 200000;
            const qty = 2;
            const rupiah = (n) => 'Rp' + n.toLocaleString('id-ID');
            const feeEl = form.querySelector('[data-example-fee]');
            const totalEl = form.querySelector('[data-example-total]');

            const render = () => {
                const tipe = form.querySelector('input[name="service_fee_type"]:checked')?.value ?? 'none';
                form.querySelectorAll('[data-fee-field]').forEach((el) => {
                    el.hidden = el.dataset.feeField !== tipe;
                });

                let fee = 0;
                if (tipe === 'percent') {
                    const pct = parseFloat(form.service_fee_percent.value) || 0;
                    fee = Math.ceil(Math.round(subtotal * pct / 100 * 1e6) / 1e6);
                } else if (tipe === 'fixed') {
                    fee = Math.round(parseFloat(form.service_fee_fixed.value) || 0) * qty;
                }
                fee = Math.max(0, fee);

                feeEl.textContent = rupiah(fee);
                totalEl.textContent = rupiah(subtotal + fee);
            };

            form.addEventListener('input', render);
            form.addEventListener('change', render);
            render();
        })();
    </script>
@endpush
