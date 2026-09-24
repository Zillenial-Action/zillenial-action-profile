-- =====================================================================
--  Seed Metode Pembayaran Snap (Midtrans) - Portal Zillenial Action
-- =====================================================================
--  Portal web (PortalController::checkout / transaksiPost) memakai Midtrans
--  Snap. Snap menampilkan SEMUA channel (Transfer Bank/VA, GoPay, QRIS,
--  kartu kredit, dll) di dalam popup-nya, sehingga cukup SATU metode:
--
--    type = 'midtrans'  DAN  midtrans_payment_type = NULL  -> jalur Snap
--
--  (lihat CheckoutService::createPaymentInstrument: bila midtrans_payment_type
--   NULL maka dibuat Snap token, bukan Core API charge.)
--
--  Idempoten: hanya menambah bila metode Snap belum ada. Metode Core API lama
--  (BNI/GoPay/QRIS/dll) TIDAK dihapus — masih dipakai API /api/checkout.
--
--  Cara pakai (Railway Console / MySQL):
--    mysql -h <host> -P <port> -u root -p railway < payment_snap.sql
-- =====================================================================

INSERT INTO payments
    (name, image, no_rek, type, midtrans_payment_type, midtrans_bank, status, created_at, updated_at)
SELECT 'Midtrans', NULL, NULL, 'midtrans', NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM payments
    WHERE type = 'midtrans' AND midtrans_payment_type IS NULL
);

-- Verifikasi:
-- SELECT id, name, type, midtrans_payment_type, status FROM payments
-- WHERE type = 'midtrans' AND midtrans_payment_type IS NULL;
