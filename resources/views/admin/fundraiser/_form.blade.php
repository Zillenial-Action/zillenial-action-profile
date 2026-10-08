@php($program = $program ?? null)

<x-form-select name="id_event" label="Event" :options="$events" optionValue="id" optionLabel="name"
    :selected="$program?->id_event" required="true" />

<x-form-input name="nilai_diskon" label="Diskon untuk Pembeli (Rp per tiket)" type="number" min="0"
    placeholder="Contoh: 25000" :value="$program?->nilai_diskon" required="true"
    hint="Potongan harga yang didapat pembeli saat memakai kode fundraiser." />

<x-form-input name="nilai_komisi" label="Komisi Fundraiser (Rp per tiket)" type="number" min="0"
    placeholder="Contoh: 15000" :value="$program?->nilai_komisi" required="true"
    hint="Dihitung dari tiket yang terjual lewat kode fundraiser dan transaksinya berhasil." />

<x-form-input name="kuota_per_kode" label="Kuota per Kode (tiket)" type="number" min="1"
    placeholder="Contoh: 50" :value="$program?->kuota_per_kode" required="true"
    hint="Maksimal tiket yang bisa dibeli dengan satu kode fundraiser." />

<x-form-input name="tanggal_berakhir" label="Tanggal Berakhir" type="date"
    :value="$program?->tanggal_berakhir?->format('Y-m-d')" required="true"
    hint="Setelah tanggal ini kode fundraiser tidak bisa dipakai dan kode baru tidak bisa dibuat." />

<div class="mb-3">
    <label for="status" class="form-label fw-semibold">Status</label>
    <select name="status" id="status" class="form-select">
        <option value="1" @selected(old('status', $program?->status ?? true))>Aktif</option>
        <option value="0" @selected(! old('status', $program?->status ?? true))>Nonaktif</option>
    </select>
</div>

@if ($program)
    <div class="alert alert-info small mb-0">
        <i class="bi bi-info-circle me-1"></i>
        Perubahan diskon, kuota, tanggal, dan status langsung berlaku ke semua kode fundraiser di program ini.
        Komisi transaksi yang sudah terjadi tidak berubah.
    </div>
@endif
