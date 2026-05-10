<?php
session_start();
require 'koneksi.php'; 

if (!isset($_SESSION['log']) || $_SESSION['role'] != 'DRIVER') {
    // Jika bukan driver, tendang balik ke index/login
    header('location:index.php');
    exit();
}

date_default_timezone_set('Asia/Jakarta');

// Tangkap Email (Unik) dan Nama
$email_driver = mysqli_real_escape_string($conn, $_SESSION['email']);
$nama_driver  = !empty($_SESSION['nama_lengkap']) ? $_SESSION['nama_lengkap'] : explode('@', $email_driver)[0];

// --- CEK IDENTITAS DRIVER BERDASARKAN EMAIL (PASTI AKURAT) ---
$q_driver_info = mysqli_query($conn, "
    SELECT d.id, d.status_kerja 
    FROM driver d 
    JOIN login l ON d.id_login = l.id 
    WHERE l.email = '$email_driver'
");
$driver_info = mysqli_fetch_assoc($q_driver_info);

if ($driver_info) {
    // Jika data operasional sudah diisi admin, tangkap ID Aslinya
    $id_supir_asli = $driver_info['id'];
    $status_supir = $driver_info['status_kerja'];
} else {
    // Jika belum diisi admin, beri status UNREGISTERED
    $id_supir_asli = 0;
    $status_supir = 'UNREGISTERED';
}

// =======================================================================
// --- LOGIKA 1: SELESAIKAN PENGIRIMAN (UBAH STATUS JADI PERJALANAN PULANG)
// =======================================================================
if (isset($_POST['selesaikan_trip'])) {
    $id_sj = mysqli_real_escape_string($conn, $_POST['id_sj']);
    $id_armada = mysqli_real_escape_string($conn, $_POST['id_armada']);
    $waktu = date('Y-m-d H:i:s');

    // Update Surat Jalan -> DELIVERED
    mysqli_query($conn, "UPDATE surat_jalan SET status='DELIVERED' WHERE id='$id_sj'");
    
    // Update Driver & Armada -> RETURNING
    mysqli_query($conn, "UPDATE driver SET status_kerja='RETURNING' WHERE id='$id_supir_asli'");
    mysqli_query($conn, "UPDATE armada SET status='RETURNING' WHERE id='$id_armada'");

    // Catat History Otomatis
    mysqli_query($conn, "INSERT INTO sj_checkpoints (id_sj, lokasi, waktu, catatan) VALUES ('$id_sj', 'Lokasi Klien', '$waktu', 'Barang berhasil diserahterimakan. Armada memulai perjalanan kembali ke gudang.')");

    echo "<script>alert('Kerja Bagus! Pengiriman Selesai. Silakan berhati-hati dalam perjalanan kembali ke Gudang.'); window.location.href='dashboard_driver.php';</script>";
    exit();
}

// =======================================================================
// --- LOGIKA 2: KONFIRMASI TIBA DI GUDANG (UBAH STATUS JADI AVAILABLE)
// =======================================================================
if (isset($_POST['tiba_di_gudang'])) {
    $id_armada = mysqli_real_escape_string($conn, $_POST['id_armada_kembali']);
    $id_sj_terakhir = mysqli_real_escape_string($conn, $_POST['id_sj_terakhir']);
    $waktu = date('Y-m-d H:i:s');
    
    // Update Driver -> AVAILABLE & Armada -> READY
    mysqli_query($conn, "UPDATE driver SET status_kerja='AVAILABLE' WHERE id='$id_supir_asli'");
    mysqli_query($conn, "UPDATE armada SET status='READY' WHERE id='$id_armada'");

    // Catat History Tiba di Gudang
    mysqli_query($conn, "INSERT INTO sj_checkpoints (id_sj, lokasi, waktu, catatan) VALUES ('$id_sj_terakhir', 'Gudang Pusat Jatinegara', '$waktu', 'Armada dan Supir telah tiba di markas. Tugas selesai sepenuhnya.')");

    echo "<script>alert('Selamat istirahat! Anda dan Armada telah berstatus Siap Sedia (Ready).'); window.location.href='dashboard_driver.php';</script>";
    exit();
}

// --- LOGIKA 3: TAMBAH CHECKPOINT LOKASI ---
if (isset($_POST['tambah_checkpoint'])) {
    $id_sj   = mysqli_real_escape_string($conn, $_POST['id_sj']);
    $lokasi  = mysqli_real_escape_string($conn, $_POST['lokasi']);
    $catatan = mysqli_real_escape_string($conn, $_POST['catatan']);
    $waktu   = date('Y-m-d H:i:s');

    mysqli_query($conn, "INSERT INTO sj_checkpoints (id_sj, lokasi, waktu, catatan) VALUES ('$id_sj', '$lokasi', '$waktu', '$catatan')");
    echo "<script>alert('Checkpoint lokasi berhasil diupdate!'); window.location.href='dashboard_driver.php';</script>";
    exit();
}

// --- AMBIL DATA TUGAS AKTIF (ON TRIP) ---
$q_aktif = mysqli_query($conn, "SELECT sj.*, a.id as id_armada, a.nopol, a.tipe 
                                FROM surat_jalan sj 
                                JOIN armada a ON sj.id_armada = a.id
                                WHERE sj.id_driver = '$id_supir_asli' AND sj.status = 'ON TRIP' 
                                ORDER BY sj.id DESC LIMIT 1");
$tugas_aktif = mysqli_fetch_assoc($q_aktif);

// --- AMBIL DATA TUGAS TERAKHIR (UNTUK REFERENSI SAAT PULANG) ---
$q_last_trip = mysqli_query($conn, "SELECT sj.*, a.id as id_armada, a.nopol, a.tipe 
                                    FROM surat_jalan sj 
                                    JOIN armada a ON sj.id_armada = a.id
                                    WHERE sj.id_driver = '$id_supir_asli' AND sj.status = 'DELIVERED' 
                                    ORDER BY sj.id DESC LIMIT 1");
$last_trip = mysqli_fetch_assoc($q_last_trip);

// Data Peta & Checkpoint untuk ON TRIP
$checkpoints = [];
if ($tugas_aktif) {
    $q_cp = mysqli_query($conn, "SELECT * FROM sj_checkpoints WHERE id_sj='{$tugas_aktif['id']}' ORDER BY waktu DESC");
    while($cp = mysqli_fetch_assoc($q_cp)) { $checkpoints[] = $cp; }
    
    $lokasi_gudang = "Jl. IPN Kb. Nanas No.2, Jatinegara, Jakarta Timur"; 
    $origin_map    = urlencode($lokasi_gudang);
    $dest_map      = urlencode($tugas_aktif['alamat_tujuan'] . ", " . $tugas_aktif['kota_tujuan']);
    $embed_map     = "https://maps.google.com/maps?saddr={$origin_map}&daddr={$dest_map}&output=embed";
    $link_navigasi = "https://www.google.com/maps/dir/?api=1&origin={$origin_map}&destination={$dest_map}";
}

// Data Peta & Checkpoint untuk RETURNING (Pulang)
$checkpoints_rt = [];
if ($status_supir == 'RETURNING' && $last_trip) {
    $q_cp_rt = mysqli_query($conn, "SELECT * FROM sj_checkpoints WHERE id_sj='{$last_trip['id']}' ORDER BY waktu DESC");
    while($cp = mysqli_fetch_assoc($q_cp_rt)) { $checkpoints_rt[] = $cp; }

    $lokasi_gudang = "Jl. IPN Kb. Nanas No.2, Jatinegara, Jakarta Timur"; 
    $origin_pulang = urlencode($last_trip['alamat_tujuan'] . ", " . $last_trip['kota_tujuan']);
    $dest_pulang   = urlencode($lokasi_gudang);
    $embed_pulang  = "https://maps.google.com/maps?saddr={$origin_pulang}&daddr={$dest_pulang}&output=embed";
    $nav_pulang    = "https://www.google.com/maps/dir/?api=1&origin={$origin_pulang}&destination={$dest_pulang}";
}

// Ambil Riwayat 5 Pengiriman Selesai Terakhir
$q_riwayat = mysqli_query($conn, "SELECT sj.*, a.nopol FROM surat_jalan sj JOIN armada a ON sj.id_armada = a.id WHERE sj.id_driver = '$id_supir_asli' AND sj.status = 'DELIVERED' ORDER BY sj.id DESC LIMIT 5");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Driver Dashboard - PT. Global Logistics</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        /* --- CORE LAYOUT CSS --- */
        :root {
            --sidebar-width: 260px;
            --primary-blue: #1a367e;
            --accent-yellow: #ffcc00;
            --bg-light: #f4f7f6;
        }

        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: var(--bg-light); overflow-x: hidden; }

        /* SIDEBAR & NAVBAR ADAPTATION */
        #sidebar {
            width: var(--sidebar-width); position: fixed; top: 0; left: 0; height: 100vh;
            background: linear-gradient(180deg, var(--primary-blue) 0%, #0d1b3e 100%);
            z-index: 1000; display: flex; flex-direction: column; transition: all 0.3s;
        }
        #main-content {
            margin-left: var(--sidebar-width); width: calc(100% - var(--sidebar-width)); 
            min-height: 100vh; display: flex; flex-direction: column; transition: all 0.3s;
        }
        .top-navbar { background: white; padding: 15px 30px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); position: sticky; top: 0; z-index: 998; }
        .sidebar-header { padding: 25px 20px; background: rgba(0,0,0,0.15); border-bottom: 1px solid rgba(255,255,255,0.05); }
        .sidebar-menu { padding: 20px 0; flex-grow: 1; overflow-y: auto; }
        .menu-label { color: rgba(255,255,255,0.4); font-size: 0.7rem; font-weight: 700; letter-spacing: 1px; padding: 0 25px; margin-bottom: 10px; display: block; }
        #sidebar ul li a { padding: 12px 25px; color: rgba(255,255,255,0.7); text-decoration: none; display: block; font-size: 0.85rem; transition: 0.3s; }
        #sidebar ul li a:hover, #sidebar ul li.active > a { color: #fff; background: rgba(255,255,255,0.1); border-left: 4px solid var(--accent-yellow); }
        #sidebar ul li a i { width: 25px; text-align: center; }
        .sidebar-footer { padding: 20px 25px; background: rgba(0,0,0,0.2); }

        /* Responsive Mobile Behavior for Driver */
        @media (max-width: 768px) {
            #sidebar { margin-left: calc(var(--sidebar-width) * -1); }
            #sidebar.show { margin-left: 0; }
            #main-content { width: 100%; margin-left: 0; }
            .top-navbar { padding: 15px; }
        }

        /* --- DRIVER UI CSS --- */
        .task-card { background: white; border-radius: 20px; padding: 25px; box-shadow: 0 10px 40px rgba(0,0,0,0.04); border: 1px solid #edf2f7; }
        
        .returning-card { background: #fffbeb; border: 2px solid #fde68a; }
        .returning-badge { background: #f59e0b; color: white; padding: 8px 16px; border-radius: 12px; font-weight: 800; font-size: 0.75rem; letter-spacing: 1px; }

        .status-badge { background: #eff6ff; color: #2563eb; padding: 8px 16px; border-radius: 12px; font-weight: 800; font-size: 0.75rem; letter-spacing: 1px; }
        .dest-icon { width: 40px; height: 40px; background: #fee2e2; color: #ef4444; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; }
        .map-container { border-radius: 16px; overflow: hidden; border: 2px solid #f1f5f9; margin-bottom: 20px; box-shadow: 0 4px 10px rgba(0,0,0,0.03); }

        .checkpoint-container { background: #f8fafc; border-radius: 20px; padding: 20px; border: 1px solid #e2e8f0; margin-bottom: 20px; }
        .checkpoint-container.return { background: #fff; border-color: #fde68a; }
        
        .timeline { position: relative; padding-left: 20px; margin-top: 15px; border-left: 3px solid #cbd5e1; margin-left: 10px; }
        .timeline-item { position: relative; margin-bottom: 20px; }
        .timeline-marker { position: absolute; left: -28.5px; top: 0; width: 16px; height: 16px; border-radius: 50%; background: white; border: 4px solid #3b82f6; box-shadow: 0 0 0 3px #eff6ff; }
        .timeline-marker.first { border-color: #10b981; box-shadow: 0 0 0 3px #d1fae5; }
        .timeline-marker.return { border-color: #f59e0b; box-shadow: 0 0 0 3px #fef3c7; }
        .timeline-time { font-size: 0.75rem; color: #64748b; font-weight: 700; background: white; padding: 4px 10px; border-radius: 8px; display: inline-block; border: 1px solid #e2e8f0; margin-bottom: 8px;}
        .timeline-title { font-size: 1rem; font-weight: 800; color: #1e293b; margin-bottom: 4px; }
        .timeline-notes { font-size: 0.85rem; color: #475569; }
        
        .btn-navigasi { background: #eff6ff; color: #2563eb; font-weight: 800; border-radius: 12px; border: 2px solid #bfdbfe; padding: 12px; width: 100%; display: block; text-align: center; text-decoration: none; }
        .btn-navigasi:hover { background: #dbeafe; color: #1d4ed8; }
        .btn-checkpoint { background: #f1f5f9; color: #2563eb; border-radius: 16px; padding: 14px; font-weight: 800; font-size: 0.95rem; width: 100%; border: 2px dashed #bfdbfe; margin-bottom: 12px; transition: 0.3s;}
        
        .btn-selesai { background: #10b981; color: white; border-radius: 16px; padding: 16px; font-weight: 800; font-size: 1.1rem; width: 100%; border: none; transition: 0.3s;}
        .btn-pulang { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: white; border-radius: 16px; padding: 18px; font-weight: 800; font-size: 1.1rem; width: 100%; border: none; box-shadow: 0 10px 20px rgba(245, 158, 11, 0.4); transition: 0.3s;}
        .btn-pulang:hover { transform: translateY(-3px); box-shadow: 0 12px 25px rgba(245, 158, 11, 0.5); }
        
        .form-control-sultan { border-radius: 12px; padding: 12px 15px; border: 2px solid #e2e8f0; background: #f8fafc; font-weight: 500; }
    </style>
</head>
<body>

    <?php include 'sidebar.php'; ?>

    <div id="main-content">
        <?php include 'navbar.php'; ?>

        <div class="container-fluid p-4 flex-grow-1 mb-5">
            
            <div class="mb-4">
                <h3 class="fw-bold mb-1" style="color: #1a367e;">Driver Dashboard</h3>
                <p class="text-muted small mb-0">Panel operasional pengiriman untuk <b><?= explode(' ', $nama_driver)[0]; ?></b>.</p>
            </div>
            
            <?php 
            // -------------------------------------------------------------
            // KONDISI 0: UNREGISTERED (BELUM DILENGKAPI ADMIN)
            // -------------------------------------------------------------
            if ($status_supir == 'UNREGISTERED') : 
            ?>
                <div class="task-card mb-4 text-center py-5 border-danger" style="border: 2px solid #fca5a5;">
                    <div class="mb-3"><i class="fas fa-user-lock text-danger" style="font-size: 3rem; opacity: 0.8;"></i></div>
                    <h5 class="fw-bold text-dark">Akun Belum Aktif</h5>
                    <p class="text-muted small mb-0">Data operasional Anda (SIM, No HP, dll) belum dilengkapi oleh Admin. Silakan lapor ke Admin Logistik agar Anda bisa mulai menerima tugas.</p>
                </div>

            <?php
            // -------------------------------------------------------------
            // KONDISI 1: WUJUD ON TRIP (MENGANTAR BARANG)
            // -------------------------------------------------------------
            elseif ($tugas_aktif) : 
            ?>
                <div class="task-card mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="status-badge"><i class="fas fa-route me-1"></i> MENUJU LOKASI</span>
                        <small class="text-muted fw-bold"><?= $tugas_aktif['no_sj']; ?></small>
                    </div>
                    
                    <div class="d-flex mb-4 align-items-center">
                        <div class="dest-icon me-3"><i class="fas fa-map-marker-alt"></i></div>
                        <div>
                            <small class="text-muted fw-bold d-block mb-1">TUJUAN PENGIRIMAN</small>
                            <h5 class="fw-bold mb-1 text-dark"><?= $tugas_aktif['kota_tujuan']; ?></h5>
                            <p class="text-muted small mb-0 lh-sm"><?= $tugas_aktif['alamat_tujuan']; ?></p>
                        </div>
                    </div>

                    <div class="map-container">
                        <div class="bg-dark text-white p-2 px-3 small fw-bold"><i class="fas fa-route text-warning me-2"></i> Rute Perjalanan</div>
                        <iframe width="100%" height="220" frameborder="0" scrolling="no" marginheight="0" marginwidth="0" src="<?= $embed_map; ?>" style="filter: contrast(1.1);"></iframe>
                    </div>

                    <a href="<?= $link_navigasi; ?>" target="_blank" class="btn-navigasi mb-4 shadow-sm">
                        <i class="fas fa-location-arrow me-2"></i> BUKA NAVIGASI MAPS
                    </a>

                    <div class="checkpoint-container">
                        <h6 class="fw-bold mb-3 text-dark"><i class="fas fa-history text-primary me-2"></i> Log Checkpoint</h6>
                        <div class="timeline">
                            <?php if(count($checkpoints) > 0): foreach($checkpoints as $index => $cp): ?>
                                <div class="timeline-item">
                                    <div class="timeline-marker <?= $index == 0 ? 'first' : '' ?>"></div>
                                    <div class="timeline-content">
                                        <div class="timeline-time"><?= date('H:i | d M', strtotime($cp['waktu'])); ?></div>
                                        <div class="timeline-title"><i class="fas fa-map-pin text-primary me-1"></i> <?= $cp['lokasi']; ?></div>
                                        <?php if(!empty($cp['catatan'])): ?><div class="timeline-notes"><?= $cp['catatan']; ?></div><?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; else: ?>
                                <p class="small text-muted mb-0 mt-3">Belum ada checkpoint.</p>
                            <?php endif; ?>
                            <div class="timeline-item">
                                <div class="timeline-marker" style="border-color: #fbbf24; box-shadow: 0 0 0 3px #fef3c7;"></div>
                                <div class="timeline-content">
                                    <div class="timeline-title text-muted"><i class="fas fa-warehouse text-warning me-1"></i> Keluar Gudang Pusat</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <button type="button" class="btn-checkpoint" data-bs-toggle="modal" data-bs-target="#modalCheckpoint">
                        <i class="fas fa-map-marker-alt me-2"></i> Update Checkpoint
                    </button>

                    <form method="POST">
                        <input type="hidden" name="id_sj" value="<?= $tugas_aktif['id']; ?>">
                        <input type="hidden" name="id_armada" value="<?= $tugas_aktif['id_armada']; ?>">
                        <button type="submit" name="selesaikan_trip" class="btn-selesai" onclick="return confirm('Yakin barang sudah diterima klien? Status Anda akan otomatis berubah menjadi PERJALANAN PULANG.')">
                            <i class="fas fa-check-circle me-2"></i> SELESAIKAN PENGIRIMAN
                        </button>
                    </form>
                </div>

                <div class="modal fade" id="modalCheckpoint" tabindex="-1">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content" style="border-radius: 24px; border: none;">
                            <form method="POST">
                                <div class="modal-header border-0 pt-4 px-4">
                                    <h5 class="fw-bold mb-0 text-dark">Update Lokasi</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body p-4">
                                    <input type="hidden" name="id_sj" value="<?= $tugas_aktif['id']; ?>">
                                    <div class="mb-3"><input type="text" name="lokasi" class="form-control form-control-sultan" placeholder="Posisi saat ini..." required></div>
                                    <div class="mb-2"><textarea name="catatan" class="form-control form-control-sultan" rows="2" placeholder="Catatan..."></textarea></div>
                                </div>
                                <div class="modal-footer border-0 pb-4 px-4 pt-0">
                                    <button type="submit" name="tambah_checkpoint" class="btn btn-primary w-100 fw-bold" style="border-radius: 14px; padding: 14px;">Kirim Laporan Posisi</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

            <?php 
            // -------------------------------------------------------------
            // WUJUD 2: JIKA STATUS SUPIR = 'RETURNING' (PERJALANAN PULANG)
            // -------------------------------------------------------------
            elseif ($status_supir == 'RETURNING' && $last_trip) : 
            ?>
                <div class="task-card returning-card mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="returning-badge"><i class="fas fa-undo-alt me-1"></i> PERJALANAN PULANG</span>
                        <small class="text-muted fw-bold"><?= $last_trip['no_sj']; ?></small>
                    </div>
                    
                    <h4 class="fw-bold text-dark mt-2 mb-1">Kembali ke Gudang</h4>
                    <p class="text-muted small mb-4">Pengiriman ke <b><?= $last_trip['kota_tujuan']; ?></b> telah selesai. Silakan arahkan armada <b><?= $last_trip['nopol']; ?></b> kembali ke Markas Jatinegara.</p>
                    
                    <div class="map-container border-warning">
                        <div class="bg-warning text-dark p-2 px-3 small fw-bold"><i class="fas fa-route me-2"></i> Rute Perjalanan Pulang</div>
                        <iframe width="100%" height="220" frameborder="0" scrolling="no" marginheight="0" marginwidth="0" src="<?= $embed_pulang; ?>" style="filter: contrast(1.1);"></iframe>
                    </div>

                    <a href="<?= $nav_pulang; ?>" target="_blank" class="btn-navigasi mb-4 shadow-sm" style="color: #d97706; border-color: #fde68a; background: #fffbeb;">
                        <i class="fas fa-location-arrow me-2"></i> BUKA NAVIGASI MAPS
                    </a>

                    <div class="checkpoint-container return">
                        <h6 class="fw-bold mb-3 text-dark"><i class="fas fa-history text-warning me-2"></i> Log Checkpoint Kepulangan</h6>
                        <div class="timeline">
                            <?php if(count($checkpoints_rt) > 0): foreach($checkpoints_rt as $index => $cp): ?>
                                <div class="timeline-item">
                                    <div class="timeline-marker <?= $index == 0 ? 'first return' : 'return' ?>"></div>
                                    <div class="timeline-content">
                                        <div class="timeline-time"><?= date('H:i | d M', strtotime($cp['waktu'])); ?></div>
                                        <div class="timeline-title"><i class="fas fa-map-pin text-warning me-1"></i> <?= $cp['lokasi']; ?></div>
                                        <?php if(!empty($cp['catatan'])): ?><div class="timeline-notes"><?= $cp['catatan']; ?></div><?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; else: ?>
                                <p class="small text-muted mb-0 mt-3">Belum ada checkpoint kembali.</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <button type="button" class="btn-checkpoint" data-bs-toggle="modal" data-bs-target="#modalCheckpointReturn" style="color: #d97706; border-color: #fde68a; background: #fffbeb;">
                        <i class="fas fa-map-marker-alt me-2"></i> Update Checkpoint Lokasi
                    </button>

                    <form method="POST">
                        <input type="hidden" name="id_armada_kembali" value="<?= $last_trip['id_armada']; ?>">
                        <input type="hidden" name="id_sj_terakhir" value="<?= $last_trip['id']; ?>">
                        <button type="submit" name="tiba_di_gudang" class="btn-pulang" onclick="return confirm('Apakah Anda yakin sudah tiba di Gudang Pusat dengan selamat? (Status Armada akan jadi READY)')">
                            <i class="fas fa-warehouse me-2"></i> SAYA SUDAH TIBA DI GUDANG
                        </button>
                    </form>
                </div>

                <div class="modal fade" id="modalCheckpointReturn" tabindex="-1">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content" style="border-radius: 24px; border: 2px solid #fde68a;">
                            <form method="POST">
                                <div class="modal-header border-0 pt-4 px-4">
                                    <h5 class="fw-bold mb-0 text-dark"><i class="fas fa-undo-alt text-warning me-2"></i>Update Lokasi Kepulangan</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body p-4">
                                    <input type="hidden" name="id_sj" value="<?= $last_trip['id']; ?>">
                                    <div class="mb-3"><input type="text" name="lokasi" class="form-control form-control-sultan" placeholder="Posisi saat ini..." required></div>
                                    <div class="mb-2"><textarea name="catatan" class="form-control form-control-sultan" rows="2" placeholder="Catatan cuaca/jalan..."></textarea></div>
                                </div>
                                <div class="modal-footer border-0 pb-4 px-4 pt-0">
                                    <button type="submit" name="tambah_checkpoint" class="btn btn-warning w-100 fw-bold text-dark" style="border-radius: 14px; padding: 14px;">Kirim Laporan Posisi</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

            <?php 
            // -------------------------------------------------------------
            // WUJUD 3: NGANGGUR / STANDBY DI GUDANG (AVAILABLE)
            // -------------------------------------------------------------
            else : 
            ?>
                <div class="task-card mb-4 text-center py-5">
                    <div class="mb-3"><i class="fas fa-mug-hot text-muted" style="font-size: 3rem; opacity: 0.3;"></i></div>
                    <h5 class="fw-bold text-dark">Tidak Ada Tugas</h5>
                    <p class="text-muted small mb-0">Kamu belum ditugaskan untuk pengiriman apapun hari ini. Silakan istirahat sambil ngopi bosku ☕</p>
                </div>
            <?php endif; ?>

            <h6 class="fw-bold text-dark mb-3 px-1 mt-4">Riwayat Selesai (Terbaru)</h6>
            <div class="mb-4">
                <?php if(mysqli_num_rows($q_riwayat) > 0) { while($rw = mysqli_fetch_assoc($q_riwayat)) : ?>
                    <div class="history-item shadow-sm" style="background: white; border-radius: 16px; padding: 15px; margin-bottom: 12px; border: 1px solid #f1f5f9; display: flex; align-items: center;">
                        <div class="history-icon me-3" style="width: 40px; height: 40px; background: #d1fae5; color: #059669; border-radius: 12px; display: flex; align-items: center; justify-content: center;"><i class="fas fa-check"></i></div>
                        <div class="flex-grow-1">
                            <h6 class="fw-bold mb-0" style="font-size: 0.9rem;"><?= $rw['kota_tujuan']; ?></h6>
                            <small class="text-muted" style="font-size: 0.75rem;"><?= $rw['no_sj']; ?> • <?= date('d M Y', strtotime($rw['tgl_kirim'])); ?></small>
                        </div>
                    </div>
                <?php endwhile; } else { echo "<p class='text-center text-muted small mt-4'>Belum ada riwayat pengiriman.</p>"; } ?>
            </div>

        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Memastikan tombol toggle sidebar dari navbar.php berfungsi dengan baik di mobile
        document.getElementById('sidebarToggle').addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('show');
        });
    </script>
</body>
</html>