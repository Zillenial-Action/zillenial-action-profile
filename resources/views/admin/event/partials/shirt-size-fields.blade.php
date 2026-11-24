@php
    $event = $event ?? null;
    $adaBaju = (bool) old('ada_baju', $event?->ada_baju);
    $ukuranTerpilih = (array) old('ukuran_baju', $event?->ukuran_baju ?: \App\Models\Event::UKURAN_BAJU_DEFAULT);
    $ukuranTerpilihUpper = array_map(fn ($u) => strtoupper((string) $u), $ukuranTerpilih);
    $ukuranLain = old('ukuran_baju_lain', implode(', ', array_filter(
        $ukuranTerpilih,
        fn ($u) => ! in_array(strtoupper((string) $u), array_map('strtoupper', \App\Models\Event::UKURAN_BAJU_PRESET), true)
    )));
    $errorUkuranItem = collect($errors->getMessages())
        ->filter(fn ($messages, $key) => str_starts_with($key, 'ukuran_baju.'))
        ->flatten()
        ->first();
@endphp

<div class="mb-3" data-shirt-builder>
    <input type="hidden" name="ada_baju" value="0">
    <div class="form-check form-switch">
        <input class="form-check-input" type="checkbox" role="switch" id="ada_baju"
            name="ada_baju" value="1" data-shirt-toggle @checked($adaBaju)>
        <label class="form-check-label fw-semibold" for="ada_baju">Event ini menyediakan baju</label>
    </div>

    <div class="border rounded p-3 bg-light mt-2 {{ $adaBaju ? '' : 'd-none' }}" data-shirt-sizes>
        <label class="form-label small fw-semibold d-block">Ukuran yang tersedia <span class="text-danger">*</span></label>
        <div class="d-flex flex-wrap gap-3 mb-2">
            @foreach (\App\Models\Event::UKURAN_BAJU_PRESET as $preset)
                <div class="form-check mb-0">
                    <input class="form-check-input" type="checkbox" name="ukuran_baju[]" value="{{ $preset }}"
                        id="ukuran_baju_{{ $preset }}" @checked(in_array(strtoupper($preset), $ukuranTerpilihUpper, true))>
                    <label class="form-check-label" for="ukuran_baju_{{ $preset }}">{{ $preset }}</label>
                </div>
            @endforeach
        </div>
        @error('ukuran_baju')
            <div class="text-danger small mb-2">{{ $message }}</div>
        @enderror

        <label for="ukuran_baju_lain" class="form-label small fw-semibold">Ukuran lain</label>
        <input type="text" class="form-control @if ($errorUkuranItem) is-invalid @endif" id="ukuran_baju_lain"
            name="ukuran_baju_lain" value="{{ $ukuranLain }}" placeholder="Contoh: 6XL, Anak-M" maxlength="200">
        @if ($errorUkuranItem)
            <div class="invalid-feedback">{{ $errorUkuranItem }}</div>
        @endif
        <small class="text-muted d-block mt-1">Pisahkan dengan koma, maksimal 10 karakter per ukuran. Volunteer wajib memilih salah satu ukuran ini saat checkout.</small>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-shirt-builder]').forEach(function (builder) {
            const toggle = builder.querySelector('[data-shirt-toggle]');
            const sizes = builder.querySelector('[data-shirt-sizes]');

            if (!toggle || !sizes) {
                return;
            }

            toggle.addEventListener('change', function () {
                sizes.classList.toggle('d-none', !toggle.checked);
            });
        });
    });
</script>
