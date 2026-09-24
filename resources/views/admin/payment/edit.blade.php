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
                        <h2 class="page-title">Edit Metode Pembayaran</h2>
                        <p class="page-subtitle mb-0">Perbarui data bank, e-wallet, atau channel Midtrans.</p>
                    </div>
                    <a href="{{ route('payment.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i>Kembali
                    </a>
                </div>

                <div class="card">
                    <div class="card-header">
                        <i class="bi bi-pencil-square me-2"></i>Form Edit Metode Pembayaran
                    </div>
                    <div class="card-body p-4">
                        <form method="POST" action="{{ route('payment.update', $payment->id) }}"
                            enctype="multipart/form-data">
                            @csrf
                            @method('PUT')

                            {{-- Image Upload --}}
                            <x-form-file name="image" label="Logo Payment"
                                hint="Upload logo bank/e-wallet (PNG/JPG, max 1MB)" :current-image="$payment->image" />

                            {{-- Payment Type --}}
                            <div class="mb-3">
                                <label for="type" class="form-label fw-semibold">Tipe Payment <span class="text-danger">*</span></label>
                                <select name="type" id="type" class="form-select @error('type') is-invalid @enderror" required>
                                    <option value="manual" {{ (old('type', $payment->type ?? 'manual') === 'manual') ? 'selected' : '' }}>Manual (Transfer Bank / QRIS)</option>
                                    <option value="midtrans" {{ (old('type', $payment->type ?? '') === 'midtrans') ? 'selected' : '' }}>Midtrans (Otomatis)</option>
                                </select>
                                @error('type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Pilih <strong>Midtrans</strong> untuk pembayaran otomatis via Snap.</small>
                            </div>

                            {{-- Midtrans Channel --}}
                            <div class="mb-3">
                                <label for="midtrans_payment_type" class="form-label fw-semibold">Channel Midtrans</label>
                                <select name="midtrans_payment_type" id="midtrans_payment_type" class="form-select @error('midtrans_payment_type') is-invalid @enderror">
                                    @php($currentChannel = old('midtrans_payment_type', $payment->midtrans_payment_type ?? ''))
                                    <option value="">— Kosongkan untuk kartu kredit / Snap popup —</option>
                                    <option value="bank_transfer" {{ $currentChannel === 'bank_transfer' ? 'selected' : '' }}>Virtual Account (Bank Transfer)</option>
                                    <option value="echannel" {{ $currentChannel === 'echannel' ? 'selected' : '' }}>Mandiri Bill Payment</option>
                                    <option value="gopay" {{ $currentChannel === 'gopay' ? 'selected' : '' }}>GoPay</option>
                                    <option value="shopeepay" {{ $currentChannel === 'shopeepay' ? 'selected' : '' }}>ShopeePay</option>
                                    <option value="qris" {{ $currentChannel === 'qris' ? 'selected' : '' }}>QRIS</option>
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
                                    @php($currentBank = old('midtrans_bank', $payment->midtrans_bank ?? ''))
                                    <option value="">—</option>
                                    <option value="bca" {{ $currentBank === 'bca' ? 'selected' : '' }}>BCA</option>
                                    <option value="bni" {{ $currentBank === 'bni' ? 'selected' : '' }}>BNI</option>
                                    <option value="bri" {{ $currentBank === 'bri' ? 'selected' : '' }}>BRI</option>
                                    <option value="permata" {{ $currentBank === 'permata' ? 'selected' : '' }}>Permata</option>
                                    <option value="cimb" {{ $currentBank === 'cimb' ? 'selected' : '' }}>CIMB Niaga</option>
                                </select>
                                @error('midtrans_bank')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Wajib diisi jika Channel Midtrans = Virtual Account.</small>
                            </div>

                            {{-- Payment Name --}}
                            <x-form-input name="name" label="Nama Payment" placeholder="Contoh: BCA, Mandiri, GoPay"
                                :value="$payment->name" :required="true" />

                            {{-- Account Number --}}
                            <x-form-input name="no_rek" label="Nomor Rekening"
                                placeholder="Nomor rekening / kosongkan jika Midtrans" :value="$payment->no_rek"
                                :required="false" />

                            {{-- Submit --}}
                            <div class="d-flex gap-2 pt-3 border-top">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-check-lg me-1"></i>Update
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