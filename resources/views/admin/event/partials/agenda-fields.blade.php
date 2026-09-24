@php
    $agendaItems = old('agenda', $event->agenda ?? []);

    if (empty($agendaItems)) {
        $agendaItems = [
            ['time_label' => '', 'title' => '', 'description' => ''],
        ];
    }
@endphp

<div class="mb-3" data-agenda-builder>
    <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
        <label class="form-label fw-semibold mb-0">Agenda Event</label>
        <button type="button" class="btn btn-sm btn-outline-primary" data-agenda-add>
            <i class="bi bi-plus-lg me-1"></i>Tambah Agenda
        </button>
    </div>

    <div class="vstack gap-3" data-agenda-list>
        @foreach ($agendaItems as $index => $agenda)
            <div class="border rounded p-3 bg-light" data-agenda-item>
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <strong class="small text-muted">Agenda</strong>
                    <button type="button" class="btn btn-sm btn-outline-danger" data-agenda-remove>
                        Hapus
                    </button>
                </div>
                <div class="row">
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold" for="agenda-{{ $index }}-time">Waktu/Label</label>
                        <input
                            type="text"
                            class="form-control @error("agenda.$index.time_label") is-invalid @enderror"
                            id="agenda-{{ $index }}-time"
                            name="agenda[{{ $index }}][time_label]"
                            data-agenda-name="time_label"
                            value="{{ $agenda['time_label'] ?? '' }}"
                            placeholder="Contoh: Pagi / 08.00"
                        >
                        @error("agenda.$index.time_label")
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-8">
                        <label class="form-label small fw-semibold" for="agenda-{{ $index }}-title">Judul</label>
                        <input
                            type="text"
                            class="form-control @error("agenda.$index.title") is-invalid @enderror"
                            id="agenda-{{ $index }}-title"
                            name="agenda[{{ $index }}][title]"
                            data-agenda-name="title"
                            value="{{ $agenda['title'] ?? '' }}"
                            placeholder="Contoh: Registrasi ulang"
                        >
                        @error("agenda.$index.title")
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="mt-2">
                    <label class="form-label small fw-semibold" for="agenda-{{ $index }}-description">Deskripsi</label>
                    <textarea
                        class="form-control @error("agenda.$index.description") is-invalid @enderror"
                        id="agenda-{{ $index }}-description"
                        name="agenda[{{ $index }}][description]"
                        data-agenda-name="description"
                        rows="2"
                        placeholder="Deskripsi singkat agenda"
                    >{{ $agenda['description'] ?? '' }}</textarea>
                    @error("agenda.$index.description")
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        @endforeach
    </div>

    <small class="text-muted d-block mt-2">Tambah, ubah, atau hapus baris agenda di sini. Agenda kosong tidak akan disimpan.</small>

    <template data-agenda-template>
        <div class="border rounded p-3 bg-light" data-agenda-item>
            <div class="d-flex justify-content-between align-items-center mb-2">
                <strong class="small text-muted">Agenda</strong>
                <button type="button" class="btn btn-sm btn-outline-danger" data-agenda-remove>
                    Hapus
                </button>
            </div>
            <div class="row">
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Waktu/Label</label>
                    <input type="text" class="form-control" data-agenda-name="time_label" placeholder="Contoh: Pagi / 08.00">
                </div>
                <div class="col-md-8">
                    <label class="form-label small fw-semibold">Judul</label>
                    <input type="text" class="form-control" data-agenda-name="title" placeholder="Contoh: Registrasi ulang">
                </div>
            </div>
            <div class="mt-2">
                <label class="form-label small fw-semibold">Deskripsi</label>
                <textarea class="form-control" data-agenda-name="description" rows="2" placeholder="Deskripsi singkat agenda"></textarea>
            </div>
        </div>
    </template>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-agenda-builder]').forEach(function (builder) {
            const list = builder.querySelector('[data-agenda-list]');
            const template = builder.querySelector('[data-agenda-template]');
            const addButton = builder.querySelector('[data-agenda-add]');

            if (!list || !(template instanceof HTMLTemplateElement) || !addButton) {
                return;
            }

            const reindex = function () {
                list.querySelectorAll('[data-agenda-item]').forEach(function (item, index) {
                    item.querySelectorAll('[data-agenda-name]').forEach(function (field) {
                        const key = field.getAttribute('data-agenda-name');
                        field.setAttribute('name', 'agenda[' + index + '][' + key + ']');
                    });
                });
            };

            addButton.addEventListener('click', function () {
                const node = template.content.firstElementChild.cloneNode(true);
                list.appendChild(node);
                reindex();
            });

            list.addEventListener('click', function (event) {
                const removeButton = event.target.closest('[data-agenda-remove]');
                if (!removeButton) {
                    return;
                }

                const item = removeButton.closest('[data-agenda-item]');
                if (list.querySelectorAll('[data-agenda-item]').length === 1) {
                    item.querySelectorAll('input, textarea').forEach(function (field) {
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
