@extends('components.layout.app')

@section('content')
    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-8">

                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                    <div>
                        <h2 class="page-title">Edit Voucher</h2>
                        <p class="page-subtitle mb-0">Perbarui kode, diskon, kuota, dan status voucher.</p>
                    </div>
                    <a href="{{ route('voucher.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i>Kembali
                    </a>
                </div>

                <div class="card">
                    <div class="card-header">
                        <i class="bi bi-pencil-square me-2"></i>Form Edit Voucher
                    </div>
                    <div class="card-body">
                        <x-form-alerts />

                        <form action="{{ route('voucher.update', $voucher->id) }}" method="POST">
                            @csrf
                            @method('PUT')

                            <x-form-select name="id_event" label="Event" :options="$events" optionValue="id"
                                optionLabel="name" :selected="$voucher->id_event" required="true" />

                            <x-form-input name="name_voucher" label="Nama Voucher" placeholder="Contoh: Diskon Tahun Baru"
                                :value="$voucher->name_voucher" required="true" />

                            <x-form-input name="kode" label="Kode Voucher" placeholder="Contoh: TAHUNBARU2024"
                                :value="$voucher->kode" required="true" hint="Kode akan otomatis diubah ke huruf kapital" />

                            <x-form-input name="nilai_diskon" label="Nilai Diskon (Rp)" type="number"
                                placeholder="Contoh: 50000" :value="$voucher->nilai_diskon" required="true" />

                            <x-form-input name="kuota" label="Kuota Penggunaan" type="number" placeholder="Contoh: 100"
                                :value="$voucher->kuota" required="true" />

                            <div class="mb-3">
                                <label class="form-label">Sudah Digunakan</label>
                                <input type="text" class="form-control" value="{{ $voucher->digunakan }} kali" readonly
                                    disabled>
                            </div>

                            <x-form-input name="tanggal_kadaluarsa" label="Tanggal Kadaluarsa" type="date"
                                :value="$voucher->tanggal_kadaluarsa->format('Y-m-d')" required="true" />

                            <div class="mb-3">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select" required>
                                    <option value="1" {{ $voucher->status ? 'selected' : '' }}>Aktif</option>
                                    <option value="0" {{ !$voucher->status ? 'selected' : '' }}>Nonaktif</option>
                                </select>
                            </div>

                            <div class="d-flex gap-2 pt-3 mt-2 border-top">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-check-lg me-1"></i>Update
                                </button>
                                <a href="{{ route('voucher.index') }}" class="btn btn-outline-secondary">
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
