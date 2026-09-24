@extends('components.layout.app')

@section('content')
    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-8">

                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                    <div>
                        <h2 class="page-title">Tambah Voucher</h2>
                        <p class="page-subtitle mb-0">Buat kode voucher diskon untuk sebuah event.</p>
                    </div>
                    <a href="{{ route('voucher.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i>Kembali
                    </a>
                </div>

                <div class="card">
                    <div class="card-header">
                        <i class="bi bi-ticket-perforated me-2"></i>Form Voucher
                    </div>
                    <div class="card-body">
                        <x-form-alerts />

                        <form action="{{ route('voucher.store') }}" method="POST">
                            @csrf

                            <x-form-select name="id_event" label="Event" :options="$events" optionValue="id"
                                optionLabel="name" required="true" />

                            <x-form-input name="name_voucher" label="Nama Voucher" placeholder="Contoh: Diskon Tahun Baru"
                                required="true" />

                            <x-form-input name="kode" label="Kode Voucher" placeholder="Contoh: TAHUNBARU2024"
                                required="true" hint="Kode akan otomatis diubah ke huruf kapital" />

                            <x-form-input name="nilai_diskon" label="Nilai Diskon (Rp)" type="number"
                                placeholder="Contoh: 50000" required="true" />

                            <x-form-input name="kuota" label="Kuota Penggunaan" type="number" placeholder="Contoh: 100"
                                required="true" />

                            <x-form-input name="tanggal_kadaluarsa" label="Tanggal Kadaluarsa" type="date"
                                required="true" />

                            <div class="d-flex gap-2 pt-3 mt-2 border-top">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-check-lg me-1"></i>Simpan
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
