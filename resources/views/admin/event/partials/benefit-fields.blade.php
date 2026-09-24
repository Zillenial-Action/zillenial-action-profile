@php
    $benefitItems = old('benefits', $event->benefits ?? []);

    if (is_string($benefitItems)) {
        $benefitItems = preg_split('/\r\n|\r|\n/', $benefitItems) ?: [];
    }

    if (empty($benefitItems)) {
        $benefitItems = [''];
    }
@endphp

<div class="mb-3" data-benefit-builder>
    <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
        <label class="form-label fw-semibold mb-0">Yang Kamu Dapat</label>
        <button type="button" class="btn btn-sm btn-outline-primary" data-benefit-add>
            <i class="bi bi-plus-lg me-1"></i>Tambah Benefit
        </button>
    </div>

    @error('benefits')
        <div class="text-danger small mb-2">{{ $message }}</div>
    @enderror

    <div class="vstack gap-3" data-benefit-list>
        @foreach ($benefitItems as $index => $benefit)
            <div class="border rounded p-3 bg-light" data-benefit-item>
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <strong class="small text-muted">Benefit</strong>
                    <button type="button" class="btn btn-sm btn-outline-danger" data-benefit-remove>
                        Hapus
                    </button>
                </div>
                <label class="form-label small fw-semibold" for="benefits-{{ $index }}">Isi Benefit</label>
                <input
                    type="text"
                    class="form-control @error("benefits.$index") is-invalid @enderror"
                    id="benefits-{{ $index }}"
                    name="benefits[{{ $index }}]"
                    data-benefit-name
                    value="{{ $benefit }}"
                    placeholder="Contoh: Sertifikat digital"
                >
                @error("benefits.$index")
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        @endforeach
    </div>

    <small class="text-muted d-block mt-2">Tambah, ubah, atau hapus benefit di sini. Benefit kosong tidak akan disimpan.</small>

    <template data-benefit-template>
        <div class="border rounded p-3 bg-light" data-benefit-item>
            <div class="d-flex justify-content-between align-items-center mb-2">
                <strong class="small text-muted">Benefit</strong>
                <button type="button" class="btn btn-sm btn-outline-danger" data-benefit-remove>
                    Hapus
                </button>
            </div>
            <label class="form-label small fw-semibold">Isi Benefit</label>
            <input type="text" class="form-control" data-benefit-name placeholder="Contoh: Sertifikat digital">
        </div>
    </template>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-benefit-builder]').forEach(function (builder) {
            const list = builder.querySelector('[data-benefit-list]');
            const template = builder.querySelector('[data-benefit-template]');
            const addButton = builder.querySelector('[data-benefit-add]');

            if (!list || !(template instanceof HTMLTemplateElement) || !addButton) {
                return;
            }

            const reindex = function () {
                list.querySelectorAll('[data-benefit-item]').forEach(function (item, index) {
                    item.querySelectorAll('[data-benefit-name]').forEach(function (field) {
                        field.setAttribute('name', 'benefits[' + index + ']');
                    });
                });
            };

            addButton.addEventListener('click', function () {
                const node = template.content.firstElementChild.cloneNode(true);
                list.appendChild(node);
                reindex();
            });

            list.addEventListener('click', function (event) {
                const removeButton = event.target.closest('[data-benefit-remove]');
                if (!removeButton) {
                    return;
                }

                const item = removeButton.closest('[data-benefit-item]');
                if (list.querySelectorAll('[data-benefit-item]').length === 1) {
                    item.querySelectorAll('input').forEach(function (field) {
                        field.value = '';
                    });
                    return;
                }

                item.remove();
                reindex();
            });

            reindex();
        });
    });
</script>
