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
                        <h2 class="page-title">Edit Event</h2>
                        <p class="page-subtitle mb-0">Perbarui detail event, lokasi, dan tiket.</p>
                    </div>
                    <a href="{{ route('event.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i>Kembali
                    </a>
                </div>

                <div class="card">
                    <div class="card-header">
                        <i class="bi bi-pencil-square me-2"></i>Form Edit Event
                    </div>
                    <div class="card-body p-4">
                        <form method="POST" action="{{ route('event.update', $event->slug) }}"
                            enctype="multipart/form-data">
                            @csrf
                            @method('PUT')

                            {{-- Image Upload --}}
                            <x-form-file name="image" label="Gambar/Poster Event"
                                hint="Direkomendasikan 724 x 340px, maksimal 2MB (JPG/PNG)" :current-image="$event->image" />

                            {{-- Event Name --}}
                            <x-form-input name="name" label="Nama Event" placeholder="Masukkan nama event"
                                :value="$event->name" :required="true" />

                            {{-- Slug --}}
                            <x-form-input name="slug" label="Slug (URL)" placeholder="contoh: social-trip-bandung"
                                :value="$event->slug" hint="URL-friendly. Otomatis diupdate jika nama berubah." />

                            {{-- Website & Mitra --}}
                            <div class="row">
                                <div class="col-md-6">
                                    <x-form-input name="website" label="Website" type="url"
                                        placeholder="https://example.com" :value="$event->website" />
                                </div>
                                <div class="col-md-6">
                                    <x-form-input name="mitra" label="Mitra" placeholder="Nama mitra/sponsor"
                                        :value="$event->mitra" />
                                </div>
                            </div>

                            {{-- Date & Time --}}
                            <div class="row">
                                <div class="col-md-6">
                                    <x-form-input name="waktu_mulai" label="Waktu Mulai" type="datetime-local"
                                        :value="$event->waktu_mulai" :required="true" />
                                </div>
                                <div class="col-md-6">
                                    <x-form-input name="waktu_berakhir" label="Waktu Berakhir" type="datetime-local"
                                        :value="$event->waktu_berakhir" :required="true" />
                                </div>
                            </div>

                            {{-- Location --}}
                            <div class="row">
                                <div class="col-md-8">
                                    <x-form-input name="nama_tempat" label="Nama Tempat" placeholder="Contoh: Gedung Sate"
                                        :value="$event->nama_tempat" :required="true" />
                                </div>
                                <div class="col-md-4">
                                    <x-form-input name="kota" label="Kota" placeholder="Contoh: Bandung"
                                        :value="$event->kota" :required="true" />
                                </div>
                            </div>

                            <x-form-input name="alamat" label="Alamat Lengkap"
                                placeholder="Masukkan alamat lengkap lokasi event" :value="$event->alamat" :required="true" />

                            <x-form-input name="direction" label="Link Direction" type="url"
                                placeholder="https://maps.google.com/?q=nama+lokasi" :value="$event->direction"
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
                                                value="{{ old('harga', $event->harga) }}" required>
                                            @error('harga')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <small class="text-muted">Masukkan 0 untuk event gratis</small>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <x-form-input name="jumlah_tiket" label="Jumlah Tiket" type="number" placeholder="100"
                                        :value="$event->jumlah_tiket" :required="true" />
                                </div>
                            </div>

                            {{-- Description --}}
                            <div class="mb-3">
                                <label for="deskripsi" class="form-label fw-semibold">
                                    Deskripsi & Syarat Ketentuan <span class="text-danger">*</span>
                                </label>
                                <div id="editor">{!! $event->deskripsi !!}</div>
                                <input type="hidden" name="deskripsi" id="content">
                            </div>

                            @include('admin.event.partials.benefit-fields', ['event' => $event])

                            @include('admin.event.partials.agenda-fields', ['event' => $event])

                            {{-- Submit --}}
                            <div class="d-flex gap-2 pt-3 border-top">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-check-lg me-1"></i>Update Event
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
