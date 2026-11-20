{{-- Biaya layanan metode pembayaran. Dipakai di form tambah & edit. --}}
@php
    $feeConfig = $payment?->serviceFeeConfig() ?? ['tipe' => 'none', 'nilai' => 0];
    $feeTipe = old('biaya_layanan_tipe', $feeConfig['tipe']);
    $feePersen = old('biaya_layanan_persen', $feeConfig['tipe'] === 'percent' ? rtrim(rtrim(number_format($feeConfig['nilai'], 2, '.', ''), '0'), '.') : '');
    $feeNominal = old('biaya_layanan_nominal', $feeConfig['tipe'] === 'fixed' ? (int) $feeConfig['nilai'] : '');
@endphp

<fieldset class="mb-3 border rounded p-3" data-service-fee>
    <legend class="float-none w-auto px-2 mb-0 fs-6 fw-semibold">Biaya Layanan</legend>
    <p class="text-muted small mb-2">
        Ditambahkan ke total pembeli yang memilih metode ini. Transaksi yang sudah dibuat tidak ikut berubah.
    </p>

    @foreach ([
        'none' => ['Tanpa biaya layanan', 'Pembeli hanya membayar harga tiket.'],
        'percent' => ['Persen', 'Persentase dari subtotal tiket setelah potongan voucher, dibulatkan ke atas.'],
        'fixed' => ['Nominal per tiket', 'Jumlah rupiah tetap untuk setiap tiket yang dibeli.'],
    ] as $value => [$label, $help])
        <div class="form-check mb-2">
            <input class="form-check-input" type="radio" name="biaya_layanan_tipe"
                id="biaya_layanan_tipe_{{ $value }}" value="{{ $value }}" @checked($feeTipe === $value)>
            <label class="form-check-label" for="biaya_layanan_tipe_{{ $value }}">
                <span class="fw-semibold">{{ $label }}</span>
                <small class="text-muted d-block">{{ $help }}</small>
            </label>
        </div>
    @endforeach
    @error('biaya_layanan_tipe')
        <div class="text-danger small">{{ $message }}</div>
    @enderror

    <div class="mt-2" data-fee-field="percent" @if ($feeTipe !== 'percent') hidden @endif>
        <label for="biaya_layanan_persen" class="form-label fw-semibold">Persentase</label>
        <div class="input-group" style="max-width: 220px;">
            <input type="number" step="0.01" min="0.01" max="100" inputmode="decimal"
                class="form-control @error('biaya_layanan_nilai') is-invalid @enderror"
                id="biaya_layanan_persen" name="biaya_layanan_persen" value="{{ $feePersen }}" placeholder="0.7">
            <span class="input-group-text">%</span>
        </div>
    </div>

    <div class="mt-2" data-fee-field="fixed" @if ($feeTipe !== 'fixed') hidden @endif>
        <label for="biaya_layanan_nominal" class="form-label fw-semibold">Nominal per tiket</label>
        <div class="input-group" style="max-width: 260px;">
            <span class="input-group-text">Rp</span>
            <input type="number" step="1" min="1" inputmode="numeric"
                class="form-control @error('biaya_layanan_nilai') is-invalid @enderror"
                id="biaya_layanan_nominal" name="biaya_layanan_nominal" value="{{ $feeNominal }}" placeholder="4000">
        </div>
    </div>

    @error('biaya_layanan_nilai')
        <div class="text-danger small mt-1">{{ $message }}</div>
    @enderror

    <p class="small text-muted mb-0 mt-3" data-fee-example></p>
</fieldset>

@push('scripts')
    <script>
        (() => {
            const box = document.querySelector('[data-service-fee]');
            if (!box) return;

            const rupiah = (n) => 'Rp' + n.toLocaleString('id-ID');
            const example = box.querySelector('[data-fee-example]');

            const render = () => {
                const tipe = box.querySelector('input[name="biaya_layanan_tipe"]:checked')?.value ?? 'none';
                box.querySelectorAll('[data-fee-field]').forEach((el) => {
                    el.hidden = el.dataset.feeField !== tipe;
                });

                // Contoh: 2 tiket @ Rp100.000 tanpa voucher.
                const subtotal = 200000;
                let fee = 0;
                if (tipe === 'percent') {
                    const pct = parseFloat(box.querySelector('#biaya_layanan_persen').value) || 0;
                    fee = Math.ceil(Math.round(subtotal * pct / 100 * 1e6) / 1e6);
                } else if (tipe === 'fixed') {
                    fee = Math.round(parseFloat(box.querySelector('#biaya_layanan_nominal').value) || 0) * 2;
                }

                example.textContent = 'Contoh 2 tiket @ Rp100.000: biaya layanan ' + rupiah(Math.max(0, fee))
                    + ', total ' + rupiah(subtotal + Math.max(0, fee)) + '.';
            };

            box.addEventListener('input', render);
            box.addEventListener('change', render);
            render();
        })();
    </script>
@endpush
