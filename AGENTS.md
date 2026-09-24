# AGENTS.md

Panduan ini berlaku untuk semua pekerjaan di folder backend `zillenial-action-prof`.

## Project Scope

Backend ini adalah aplikasi Laravel 10 untuk Zillenial Action. Fokus utamanya adalah portal event dan ticketing, termasuk:

- Portal publik untuk daftar event, detail event, checkout, invoice, dan tiket.
- Admin panel untuk dashboard, event, transaksi, payment method, pixel tracking, voucher, soft delete, restore, dan export Excel.
- Alur pembelian tiket berbasis invoice, data volunteer/pengunjung, validasi voucher, stok tiket, dan email tiket.
- Integrasi view Blade backend dengan sibling frontend `zillenial-action-sostrip` hanya jika diminta eksplisit.

Jangan mengubah folder sibling `zillenial-action-sostrip` dari instruksi backend ini kecuali user meminta integrasi/frontend secara jelas.

## Stack

- PHP `^8.1`
- Laravel `^10`
- Laravel Sanctum
- Laravel Telescope
- MySQL
- Blade views
- Vite untuk asset frontend backend
- Intervention Image untuk upload/kompresi gambar
- Maatwebsite Excel untuk export
- PHPUnit untuk test
- Laravel Pint untuk formatting

## Key Domain Rules

- `Event` memakai slug sebagai route key dan mendukung soft delete.
- `Transaksi` memakai invoice sebagai route key dan status pembayaran utama: `Pending`, `Success`, `Failed`.
- Checkout publik harus menjaga stok tiket secara atomik. Jangan mengurangi stok tanpa transaksi database.
- Voucher harus valid terhadap event, status aktif, tanggal kadaluarsa, dan sisa kuota.
- Saat transaksi menjadi `Success`, tiket email dikirim ke semua volunteer terkait. Jika ada email gagal, jangan diam-diam mengubah status menjadi sukses.
- Saat transaksi `Failed` atau dihapus, pastikan implikasi stok tiket tetap benar.
- `Payment`, `Pixel`, `Voucher`, `Event`, dan `Transaksi` memakai pola admin CRUD dengan soft delete/restore/force delete.
- File gambar event/payment/pixel disimpan melalui service/pola yang sudah ada. Jangan bypass `ImageService` untuk upload event.
- Cache yang sudah ada harus dihormati:
  - `homepage_events`
  - `active_payment_methods`

## Coding Guidelines

- Ikuti pola Laravel yang sudah ada di repo: Controller tipis secukupnya, validasi di Form Request jika fitur CRUD/admin, Eloquent relationship untuk query relasional.
- Pertahankan nama field database yang sudah berjalan seperti `id_event`, `id_payment`, `id_voucher`, `transaksis`, dan `transaksi_volunteers`.
- Gunakan migration untuk perubahan schema. Jangan mengubah database secara manual tanpa migration kecuali user secara eksplisit meminta tindakan recovery.
- Gunakan eager loading untuk halaman list/detail yang menampilkan relasi transaksi, event, payment, voucher, atau volunteer.
- Pakai `DB::beginTransaction()`/`DB::transaction()` untuk alur yang mengubah beberapa tabel sekaligus.
- Jangan menghapus atau menonaktifkan soft delete management tanpa alasan bisnis yang jelas.
- Pertahankan pesan UI/admin dalam Bahasa Indonesia.
- Jangan menyimpan secret, credential production, token API, atau data sensitif di repo.
- Logging boleh dipakai untuk audit, tetapi jangan menambah log yang membocorkan password, token, atau data pembayaran sensitif.
- Jika menambah endpoint publik/API, tambahkan validasi input, response error yang jelas, dan pembatasan scope data yang dikembalikan.
- Untuk perubahan yang menyentuh sibling frontend, pastikan kontrak URL, payload, dan format error tetap eksplisit.

## Commands

Jalankan dari folder `zillenial-action-prof`.

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
npm run dev
```

Verifikasi yang disarankan:

```bash
php artisan test
vendor/bin/pint --test
npm run build
php artisan route:list
```

Jika test butuh database, pastikan `.env` atau `.env.testing` mengarah ke database lokal yang aman. Jangan menjalankan migrate/fresh/seed pada database production.

## Change Checklist

Sebelum menyatakan pekerjaan selesai:

- Cek `git status --short` dan pastikan hanya file relevan yang berubah.
- Jalankan test/format/build yang sesuai dengan area perubahan.
- Untuk perubahan alur checkout/transaksi/voucher, uji minimal:
  - validasi input gagal,
  - transaksi berhasil,
  - stok tiket berubah benar,
  - voucher quota/kadaluarsa benar,
  - status pembayaran dan email tiket tidak membuat data inkonsisten.
- Untuk perubahan CRUD admin, cek create, update, delete, restore, search/filter jika area tersebut terdampak.
- Untuk perubahan route publik, cek route name dan link Blade terkait.

## Agent Behavior

- Mulai dengan membaca file terkait, bukan menebak dari nama fitur.
- Buat perubahan sekecil mungkin yang menyelesaikan request user.
- Jangan merapikan/refactor area besar tanpa diminta.
- Jika menemukan bug yang tidak terkait, catat sebagai risiko atau follow-up, jangan diam-diam memperluas scope.
- Jika ada instruksi user yang lebih spesifik dari file ini, ikuti instruksi user.
