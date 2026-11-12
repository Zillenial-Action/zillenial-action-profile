@extends('components.layout.app')

@section('content')
    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-10 col-lg-8">

                {{-- Alerts --}}
                <x-form-alerts />

                {{-- Header --}}
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                    <div>
                        <h2 class="page-title">Buat Event Baru</h2>
                        <p class="page-subtitle mb-0">Lengkapi detail event, lokasi, dan tiket.</p>
                    </div>
                    <a href="{{ route('event.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i>Kembali
                    </a>
                </div>

                <div class="card">
                    <div class="card-header">
                        <i class="bi bi-calendar-event me-2"></i>Form Event
                    </div>
                    <div class="card-body p-4">
                        <form method="POST" action="{{ route('event.store') }}" enctype="multipart/form-data">
                            @csrf

                            {{-- Image Upload --}}
                            <x-form-file name="image" label="Gambar/Poster Event"
                                hint="Direkomendasikan 724 x 340px, maksimal 2MB (JPG/PNG)" />

                            {{-- Event Name --}}
                            <x-form-input name="name" label="Nama Event" placeholder="Masukkan nama event"
                                :required="true" autofocus />

                            {{-- Slug --}}
                            <x-form-input name="slug" label="Slug (URL)" placeholder="contoh: social-trip-bandung"
                                hint="Otomatis dibuat dari nama event jika dikosongkan" />

                            {{-- Website & Mitra --}}
                            <div class="row">
                                <div class="col-md-6">
                                    <x-form-input name="website" label="Website" type="url"
                                        placeholder="https://example.com" />
                                </div>
                                <div class="col-md-6">
                                    <x-form-input name="mitra" label="Mitra" placeholder="Nama mitra/sponsor" />
                                </div>
                            </div>

                            {{-- Date & Time --}}
                            <div class="row">
                                <div class="col-md-6">
                                    <x-form-input name="waktu_mulai" label="Waktu Mulai" type="datetime-local"
                                        :required="true" />
                                </div>
                                <div class="col-md-6">
                                    <x-form-input name="waktu_berakhir" label="Waktu Berakhir" type="datetime-local"
                                        :required="true" />
                                </div>
                            </div>

                            {{-- Location --}}
                            <div class="row">
                                <div class="col-md-8">
                                    <x-form-input name="nama_tempat" label="Nama Tempat" placeholder="Contoh: Gedung Sate"
                                        :required="true" />
                                </div>
                                <div class="col-md-4">
                                    <x-form-input name="kota" label="Kota" placeholder="Contoh: Bandung"
                                        :required="true" />
                                </div>
                            </div>

                            <x-form-input name="alamat" label="Alamat Lengkap"
                                placeholder="Masukkan alamat lengkap lokasi event" :required="true" />

                            <x-form-input name="direction" label="Link Direction" type="url"
                                placeholder="https://maps.google.com/?q=nama+lokasi"
                                hint="Opsional. Isi dengan link Google Maps atau direction lokasi." />

                            {{-- Price & Tickets --}}
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="mb-3">
                                        <label for="harga" class="form-label fw-semibold">
                                            Harga Tiket <span class="text-danger">*</span>
                                        </label>
                                        <div class="input-group">
                                            <span class="input-group-text">Rp</span>
                                            <input type="text" class="form-control @error('harga') is-invalid @enderror"
                                                id="harga" name="harga" placeholder="0" oninput="formatNumber(this)"
                                                value="{{ old('harga') }}" required>
                                            @error('harga')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <small class="text-muted">Masukkan 0 untuk event gratis</small>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <x-form-input name="jumlah_tiket" label="Jumlah Tiket" type="number" placeholder="100"
                                        :required="true" />
                                </div>
                            </div>

                            {{-- Baju --}}
                            <div class="mb-3">
                                <input type="hidden" name="ada_baju" value="0">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch" id="ada_baju"
                                        name="ada_baju" value="1" @checked(old('ada_baju'))>
                                    <label class="form-check-label fw-semibold" for="ada_baju">Event ini menyediakan baju</label>
                                </div>
                                <small class="text-muted">Jika aktif, volunteer wajib memilih ukuran baju (XXS&ndash;XL) saat checkout.</small>
                            </div>

                            {{-- Description --}}
                            <div class="mb-3">
                                <label for="deskripsi" class="form-label fw-semibold">
                                    Deskripsi & Syarat Ketentuan <span class="text-danger">*</span>
                                </label>
                                <div data-rich-editor data-target="deskripsi" data-placeholder="Tulis deskripsi event..." data-upload-url="{{ route('event.description-image') }}">{!! app(\App\Services\HtmlSanitizer::class)->sanitize(old('deskripsi')) !!}</div>
                                <input type="hidden" name="deskripsi" id="deskripsi">
                            </div>

                            {{-- Syarat & Ketentuan Tiket --}}
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Syarat &amp; Ketentuan di Tiket</label>
                                <div data-rich-editor data-target="syarat_tiket"
                                    data-placeholder="Kosongkan untuk memakai S&amp;K default dari menu Pengaturan Tiket">{!! app(\App\Services\HtmlSanitizer::class)->sanitizeText(old('syarat_tiket')) !!}</div>
                                <input type="hidden" name="syarat_tiket" id="syarat_tiket">
                                <small class="text-muted">Tampil di tiket peserta yang sudah membayar. Jika kosong, tiket memakai S&amp;K default dari
                                    <a href="{{ route('ticket-setting.edit') }}" target="_blank">Pengaturan Tiket</a>.</small>
                            </div>

                            @include('admin.event.partials.benefit-fields')

                            @include('admin.event.partials.agenda-fields')

                            {{-- Submit --}}
                            <div class="d-flex gap-2 pt-3 border-top">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-check-lg me-1"></i>Simpan Event
                                </button>
                                <a href="{{ route('event.index') }}" class="btn btn-outline-secondary">
                                    Batal
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
