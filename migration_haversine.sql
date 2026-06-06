-- ================================================================
-- MIGRATION: Haversine Auto-Calculation Support
-- Database: doclogistics
-- Tanggal: 2026-06-06
-- Deskripsi: Tidak ada perubahan struktur tabel yang breaking.
--            Kolom jarak_dari_sebelumnya tetap ada, tapi nilainya
--            kini akan diisi OTOMATIS oleh fungsi Haversine di backend.
--            Migration ini hanya memastikan kolom-kolom sudah sesuai.
-- ================================================================

-- Pastikan kolom jarak_dari_sebelumnya bertipe DECIMAL yang cukup presisi
ALTER TABLE route_details MODIFY COLUMN jarak_dari_sebelumnya DECIMAL(10,2) DEFAULT 0;

-- Pastikan kolom total_jarak_km di routes cukup presisi
ALTER TABLE routes MODIFY COLUMN total_jarak_km DECIMAL(10,2) DEFAULT 0;

-- Reset semua jarak yang sudah ada agar dihitung ulang oleh Haversine
-- (Opsional: jalankan ini jika ingin recalculate semua rute existing)
-- UPDATE route_details SET jarak_dari_sebelumnya = 0;
-- UPDATE routes SET total_jarak_km = 0, total_biaya_rute = 0;
