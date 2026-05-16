-- ================================================================
-- MIGRATION: Fixed Route System
-- Database: doclogistics
-- Tanggal: 2026-05-16
-- Deskripsi: Tabel master koordinat, rute, dan detail checkpoint
-- ================================================================

-- 1. Master Points (Master Data Koordinat Kota)
CREATE TABLE IF NOT EXISTS master_points (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_kota VARCHAR(100) NOT NULL,
    latitude DECIMAL(10,7) NOT NULL,
    longitude DECIMAL(10,7) NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Routes (Rute Perjalanan)
CREATE TABLE IF NOT EXISTS routes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_rute VARCHAR(150) NOT NULL,
    tipe_rute ENUM('PERGI','PULANG') DEFAULT 'PERGI',
    id_rute_induk INT DEFAULT NULL,
    total_jarak_km DECIMAL(10,2) DEFAULT 0,
    biaya_per_km DECIMAL(10,0) DEFAULT 5000,
    total_biaya_rute DECIMAL(15,0) DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_rute_induk) REFERENCES routes(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Route Details (Pivot: Checkpoint per Rute dengan urutan)
CREATE TABLE IF NOT EXISTS route_details (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_route INT NOT NULL,
    id_master_point INT NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    jarak_dari_sebelumnya DECIMAL(10,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_route) REFERENCES routes(id) ON DELETE CASCADE,
    FOREIGN KEY (id_master_point) REFERENCES master_points(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. ALTER surat_jalan: tambah kolom rute
ALTER TABLE surat_jalan 
    ADD COLUMN id_route INT DEFAULT NULL,
    ADD COLUMN id_return_route INT DEFAULT NULL,
    ADD COLUMN last_checkpoint_id INT DEFAULT NULL,
    ADD COLUMN last_checkpoint_return_id INT DEFAULT NULL;

-- 5. ALTER sj_checkpoints: sambungkan ke master_points & route_details
ALTER TABLE sj_checkpoints
    ADD COLUMN id_master_point INT DEFAULT NULL,
    ADD COLUMN id_route_detail INT DEFAULT NULL,
    ADD COLUMN tipe_checkpoint ENUM('PERGI','PULANG') DEFAULT 'PERGI';

-- ================================================================
-- SEED DATA: Kota-kota utama Pulau Jawa
-- ================================================================
INSERT INTO master_points (nama_kota, latitude, longitude) VALUES
('Jakarta (Gudang Pusat)', -6.2146200, 106.8451000),
('Bekasi', -6.2382700, 106.9756200),
('Karawang', -6.3023600, 107.3021500),
('Cirebon', -6.7320400, 108.5523400),
('Tegal', -6.8797000, 109.1425600),
('Pekalongan', -6.8885500, 109.6753100),
('Semarang', -6.9666100, 110.4196300),
('Solo', -7.5755600, 110.8243900),
('Madiun', -7.6298100, 111.5239600),
('Ponorogo', -7.8652800, 111.4609400),
('Surabaya', -7.2574700, 112.7520900),
('Bandung', -6.9174600, 107.6191200),
('Yogyakarta', -7.7955800, 110.3694900),
('Malang', -7.9786500, 112.6304200),
('Kediri', -7.8161700, 112.0182600);
