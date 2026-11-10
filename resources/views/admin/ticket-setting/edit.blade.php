@extends('components.layout.app')

@section('content')
    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-10 col-lg-8">

                <x-form-alerts />

                <div class="mb-3">
                    <h2 class="page-title">Pengaturan Tiket</h2>
                    <p class="page-subtitle mb-0">Isi tiket dan email tiket yang dikirim ke volunteer setelah pembayaran berhasil.</p>
                </div>

                <form method="POST" action="{{ route('ticket-setting.update') }}">
                    @csrf
                    @method('PUT')

                    <div class="card mb-3">
                        <div class="card-header">
                            <i class="bi bi-list-check me-2"></i>Syarat &amp; Ketentuan Default
                        </div>
                        <div class="card-body p-4">
                            <p class="text-muted small mb-3">
                                Tampil di tiket semua event, kecuali event yang punya S&amp;K tiket sendiri
                                (diatur di form event).
                            </p>
                            <div data-rich-editor data-target="ticket_terms"
                                data-placeholder="Tulis syarat & ketentuan tiket...">{!! app(\App\Services\HtmlSanitizer::class)->sanitizeText(old('ticket_terms', $settings['ticket_terms'])) !!}</div>
                            <input type="hidden" name="ticket_terms" id="ticket_terms">
                            @error('ticket_terms')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="card mb-3">
                        <div class="card-header">
                            <i class="bi bi-envelope-paper me-2"></i>Pesan Email Tiket
                        </div>
                        <div class="card-body p-4">
                            <p class="text-muted small mb-3">
                                Pembuka email "Pembayaran Berhasil". ID transaksi dan tombol
                                <strong>Lihat Tiket Anda</strong> otomatis ditambahkan di bawah pesan ini.
                            </p>
                            <div data-rich-editor data-target="ticket_email_message"
                                data-placeholder="Tulis pesan email...">{!! app(\App\Services\HtmlSanitizer::class)->sanitizeText(old('ticket_email_message', $settings['ticket_email_message'])) !!}</div>
                            <input type="hidden" name="ticket_email_message" id="ticket_email_message">
                            @error('ticket_email_message')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="card mb-3">
                        <div class="card-header">
                            <i class="bi bi-telephone me-2"></i>Kontak di Footer Tiket
                        </div>
                        <div class="card-body p-4">
                            <p class="text-muted small mb-3">Kosongkan kolom yang tidak ingin ditampilkan.</p>

                            <x-form-input name="ticket_contact_whatsapp" label="Nomor WhatsApp"
                                placeholder="6282121392363" :value="$settings['ticket_contact_whatsapp']"
                                hint="Boleh diawali 0, 62, atau +62." />

                            <x-form-input name="ticket_contact_email" label="Email" type="email"
                                placeholder="zillenialaction@gmail.com" :value="$settings['ticket_contact_email']" />

                            <x-form-input name="ticket_contact_website" label="Website" type="url"
                                placeholder="https://zillenialaction.id" :value="$settings['ticket_contact_website']" />
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg me-1"></i>Simpan Pengaturan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
