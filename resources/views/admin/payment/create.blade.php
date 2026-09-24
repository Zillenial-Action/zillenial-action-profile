@extends('components.layout.app')

@section('content')
    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">

                {{-- Alerts --}}
                <x-form-alerts />

                {{-- Header --}}
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                    <div>
                        <h2 class="page-title">Tambah Metode Pembayaran</h2>
                        <p class="page-subtitle mb-0">Tambahkan bank, e-wallet, atau channel Midtrans.</p>
                    </div>
                    <a href="{{ route('payment.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i>Kembali
                    </a>
                </div>

                <div class="card">
                    <div class="card-header">
                        <i class="bi bi-credit-card me-2"></i>Form Metode Pembayaran
                    </div>
                    <div class="card-body p-4">
                        <form method="POST" action="{{ route('payment.store') }}" enctype="multipart/form-data">
                            @csrf

                            {{-- Image Upload --}}
                            <x-form-file name="image" label="Logo Payment"
                                hint="Upload logo bank/e-wallet (PNG/JPG, max 1MB)" :required="true" />

                            {{-- Payment Type --}}
                            <div class="mb-3">
                                <label for="type" class="form-label fw-semibold">Tipe Payment <span class="text-danger">*</span></label>
                                <select name="type" id="type" class="form-select @error('type') is-invalid @enderror" required>
                                    <option value="manual" {{ old('type') === 'midtrans' ? '' : 'selected' }}>Manual (Transfer Bank / QRIS)</option>
                                    <option value="midtrans" {{ old('type') === 'midtrans' ? 'selected' : '' }}>Midtrans (Otomatis)</option>
                                </select>
                                @error('type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Pilih <strong>Midtrans</strong> untuk pembayaran otomatis via Snap. Pilih <strong>Manual</strong> untuk transfer biasa.</small>
                            </div>

                            {{-- Midtrans Channel --}}
                            <div class="mb-3">
                                <label for="midtrans_payment_type" class="form-label fw-semibold">Channel Midtrans</label>
                                <select name="midtrans_payment_type" id="midtrans_payment_type" class="form-select @error('midtrans_payment_type') is-invalid @enderror">
                                    <option value="">— Kosongkan untuk kartu kredit / Snap popup —</option>
                                    <option value="bank_transfer" {{ old('midtrans_payment_type') === 'bank_transfer' ? 'selected' : '' }}>Virtual Account (Bank Transfer)</option>
                                    <option value="echannel" {{ old('midtrans_payment_type') === 'echannel' ? 'selected' : '' }}>Mandiri Bill Payment</option>
                                    <option value="gopay" {{ old('midtrans_payment_type') === 'gopay' ? 'selected' : '' }}>GoPay</option>
                                    <option value="shopeepay" {{ old('midtrans_payment_type') === 'shopeepay' ? 'selected' : '' }}>ShopeePay</option>
                                    <option value="qris" {{ old('midtrans_payment_type') === 'qris' ? 'selected' : '' }}>QRIS</option>
                                </select>
                                @error('midtrans_payment_type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Tampilan instruksi bayar custom (bukan modal Midtrans) hanya berlaku jika channel dipilih di sini.</small>
                            </div>

                            {{-- Midtrans Bank (hanya untuk Virtual Account) --}}
                            <div class="mb-3">
                                <label for="midtrans_bank" class="form-label fw-semibold">Bank VA</label>
                                <select name="midtrans_bank" id="midtrans_bank" class="form-select @error('midtrans_bank') is-invalid @enderror">
                                    <option value="">—</option>
                                    <option value="bca" {{ old('midtrans_bank') === 'bca' ? 'selected' : '' }}>BCA</option>
                                    <option value="bni" {{ old('midtrans_bank') === 'bni' ? 'selected' : '' }}>BNI</option>
                                    <option value="bri" {{ old('midtrans_bank') === 'bri' ? 'selected' : '' }}>BRI</option>
                                    <option value="permata" {{ old('midtrans_bank') === 'permata' ? 'selected' : '' }}>Permata</option>
                                    <option value="cimb" {{ old('midtrans_bank') === 'cimb' ? 'selected' : '' }}>CIMB Niaga</option>
                                </select>
                                @error('midtrans_bank')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Wajib diisi jika Channel Midtrans = Virtual Account.</small>
                            </div>

                            {{-- Payment Name --}}
                            <x-form-input name="name" label="Nama Payment" placeholder="Contoh: BCA, Mandiri, GoPay"
                                :required="true" />

                            {{-- Account Number --}}
                            <x-form-input name="no_rek" label="Nomor Rekening" placeholder="Nomor rekening / kosongkan jika Midtrans"
                                :required="false" />

                            {{-- Submit --}}
                            <div class="d-flex gap-2 pt-3 border-top">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-check-lg me-1"></i>Simpan
                                </button>
                                <a href="{{ route('payment.index') }}" class="btn btn-outline-secondary">
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
