-- =====================================================================
--  Seed Metode Pembayaran (Midtrans) - Zillenial Action
-- =====================================================================
--  Tabel  : payments
--  Field  : name, image, no_rek, type('manual'|'midtrans'),
--           midtrans_payment_type, midtrans_bank, status, timestamps
--
--  Mapping channel Midtrans (lihat app/Services/MidtransService.php):
--    - bank_transfer -> pakai midtrans_bank (bca|bni|bri|cimb|permata)
--    - echannel      -> Mandiri Bill (midtrans_bank TIDAK dipakai)
--    - gopay         -> GoPay          (midtrans_bank TIDAK dipakai)
--    - qris          -> QRIS           (midtrans_bank TIDAK dipakai)
--
--  Catatan:
--    - no_rek NULL   : hanya dipakai metode 'manual' (nomor rekening tujuan).
--    - status = 1    : metode AKTIF.
--    - image NULL    : upload logo via panel admin, atau isi path relatif
--                      mis. 'image/payment/2026-07/cimb.png'.
--
--  Cara pakai (MySQL Workbench): buka file ini -> jalankan (Execute).
--  Cara pakai (CLI):  mysql -h <host> -P <port> -u root -p railway < payment_methods.sql
-- =====================================================================

-- Hindari duplikat bila skrip dijalankan ulang: hapus dulu yang namanya sama.
-- (Hapus baris DELETE ini jika ingin murni menambah tanpa membersihkan.)
DELETE FROM payments
WHERE name IN (
    'CIMB Niaga',
    'GoPay Dynamic QRIS',
    'BNI',
    'BRI',
    'GoPay',
    'Bank Mandiri',
    'PermataBank'
);

INSERT INTO payments
    (name, image, no_rek, type, midtrans_payment_type, midtrans_bank, status, created_at, updated_at)
VALUES
    ('CIMB Niaga',          NULL, NULL, 'midtrans', 'bank_transfer', 'cimb',    1, NOW(), NOW()),
    ('GoPay Dynamic QRIS',  NULL, NULL, 'midtrans', 'qris',          NULL,      1, NOW(), NOW()),
    ('BNI',                 NULL, NULL, 'midtrans', 'bank_transfer', 'bni',     1, NOW(), NOW()),
    ('BRI',                 NULL, NULL, 'midtrans', 'bank_transfer', 'bri',     1, NOW(), NOW()),
    ('GoPay',               NULL, NULL, 'midtrans', 'gopay',         NULL,      1, NOW(), NOW()),
    ('Bank Mandiri',        NULL, NULL, 'midtrans', 'echannel',      NULL,      1, NOW(), NOW()),
    ('PermataBank',         NULL, NULL, 'midtrans', 'bank_transfer', 'permata', 1, NOW(), NOW());

-- Verifikasi hasil:
-- SELECT id, name, type, midtrans_payment_type, midtrans_bank, status FROM payments ORDER BY id;
