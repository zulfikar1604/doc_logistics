<?php
session_start();
require 'koneksi.php';

// Cek keamanan session
if (!isset($_SESSION['log'])) {
    header('location:login.php');
    exit();
}

// Cek apakah Admin/Manager sedang mengklik detail "LIHAT RUTE"
$id_tracking = isset($_GET['order']) ? $_GET['order'] : '';

// 1. QUERY UNTUK DAFTAR TRACKING (SISI KIRI)
$query_tracking = mysqli_query($conn, "
    SELECT sj.id, sj.no_sj, sj.kota_tujuan, sj.alamat_tujuan, sj.tgl_kirim, sj.status, l.nama_lengkap as nama_driver, a.nopol 
    FROM surat_jalan sj 
    JOIN driver d ON sj.id_driver = d.id 
    JOIN login l ON d.id_login = l.id 
    JOIN armada a ON sj.id_armada = a.id
    WHERE sj.status = 'ON TRIP'
    ORDER BY sj.id DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Live Tracking - Global Logistics</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --sidebar-width: 260px;
            --primary-blue: #1a367e;
            --accent-yellow: #ffcc00;
            --bg-light: #f4f7f6;
        }

        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: var(--bg-light); overflow-x: hidden; }

        /* --- CSS WAJIB LAYOUT --- */
        #sidebar {
            width: var(--sidebar-width); position: fixed; top: 0; left: 0; height: 100vh;
            background: linear-gradient(180deg, var(--primary-blue) 0%, #0d1b3e 100%);
            z-index: 1000; display: flex; flex-direction: column;
        }
        #main-content { margin-left: var(--sidebar-width); width: calc(100% - var(--sidebar-width)); min-height: 100vh; display: flex; flex-direction: column;}
        .top-navbar { background: white; padding: 15px 30px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); position: sticky; top: 0; z-index: 998; }
        .sidebar-header { padding: 25px 20px; background: rgba(0,0,0,0.15); border-bottom: 1px solid rgba(255,255,255,0.05); }
        .sidebar-menu { padding: 20px 0; flex-grow: 1; overflow-y: auto; }
        .menu-label { color: rgba(255,255,255,0.4); font-size: 0.7rem; font-weight: 700; letter-spacing: 1px; padding: 0 25px; margin-bottom: 10px; display: block; }
        #sidebar ul li a { padding: 12px 25px; color: rgba(255,255,255,0.7); text-decoration: none; display: block; font-size: 0.85rem; transition: 0.3s; }
        #sidebar ul li a:hover, #sidebar ul li.active > a { color: #fff; background: rgba(255,255,255,0.1); border-left: 4px solid var(--accent-yellow); }
        #sidebar ul li a i { width: 25px; text-align: center; }
        .sidebar-footer { padding: 20px 25px; background: rgba(0,0,0,0.2); }

        /* --- SULTAN CARD STYLING --- */
        .card-sultan { background: white; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.04); border: 1px solid rgba(0,0,0,0.05); overflow: hidden;}
        .header-sultan { background: #f8fafc; border-bottom: 1px solid #e2e8f0; padding: 20px 25px; }

        /* --- TIMELINE CHECKPOINT SULTAN --- */
        .timeline-container { background: #f8fafc; border-radius: 16px; border: 1px solid #e2e8f0; padding: 25px; height: 100%;}
        .timeline-sultan { border-left: 3px solid #e5e7eb; padding-left: 25px; list-style: none; margin-left: 15px; margin-bottom: 0; padding-top: 5px; }
        .timeline-sultan li { margin-bottom: 30px; position: relative; }
        .timeline-sultan li:last-child { margin-bottom: 0; }
        .timeline-sultan li::before { 
            content: ''; position: absolute; left: -34px; top: 0; 
            width: 16px; height: 16px; border-radius: 50%; 
            background: #3b82f6; border: 3px solid #fff; box-shadow: 0 0 0 3px #bfdbfe; 
        }
        .timeline-sultan li.delivered::before { background: #10b981; box-shadow: 0 0 0 3px #a7f3d0; }
        .timeline-sultan li.start::before { background: #f59e0b; box-shadow: 0 0 0 3px #fde68a; }
        .time-badge { font-size: 11px; font-weight: 800; background: white; padding: 4px 10px; border-radius: 8px; color: #64748b; margin-bottom: 5px; display: inline-block; border: 1px solid #e2e8f0;}
        
        .status-pill { border-radius: 999px; padding: 6px 12px; font-weight: 800; font-size: 10px; }
        .anim-blink { animation: blinker 1.5s linear infinite; }
        @keyframes blinker { 50% { opacity: 0.3; } }
        
        /* List Armada Active */
        .vehicle-item { padding: 15px; border-radius: 12px; border: 1px solid #edf2f7; margin-bottom: 12px; transition: all 0.2s; background: white; text-decoration: none; color: inherit; display: block;}
        .vehicle-item:hover { border-color: var(--primary-blue); box-shadow: 0 5px 15px rgba(26,54,126,0.05); }
        .vehicle-item.active { background: var(--primary-blue); color: white; border-color: var(--primary-blue); box-shadow: 0 8px 20px rgba(26,54,126,0.15); }
        .vehicle-item.active .text-muted, .vehicle-item.active .text-primary { color: rgba(255,255,255,0.8) !important; }
        .vehicle-item.active .fw-bold { color: white !important; }
    </style>
</head>
<body>

    <?php include 'sidebar.php'; ?>

    <div id="main-content">
        
        <?php include 'navbar.php'; ?>

        <div class="container-fluid p-4 flex-grow-1 mb-5">
            
            <?php 
            // =========================================================================
            // TAMPILAN 1: JIKA TIDAK ADA SURAT JALAN YANG DIPILIH, TAMPILKAN LIST SEMUA
            // =========================================================================
            if (empty($id_tracking)) { 
            ?>
            <div class="d-flex align-items-center justify-content-between mt-2 mb-4 flex-wrap">
                <div>
                    <h3 class="fw-bold mb-1" style="color: #1a367e;">Live Tracking Armada</h3>
                    <p class="text-muted small mb-0">Pilih armada yang sedang <strong class="text-dark">ON TRIP</strong> untuk melihat rute spesifiknya.</p>
                </div>
            </div>

            <div class="row">
                <!-- Kolom Kiri: Daftar Armada -->
                <div class="col-xl-5 col-lg-6 mb-4">
                    <div class="card-sultan p-4 h-100">
                        <div class="alert bg-primary bg-opacity-10 border-primary text-primary mb-4 py-2" style="border-radius: 10px;">
                            <i class="fas fa-satellite-dish me-2"></i> <span class="small fw-bold">Sinkronisasi Database Aktif</span>
                        </div>
                        <h6 class="fw-bold mb-3 text-dark"><i class="fas fa-list-ul me-2 text-primary"></i> Daftar Pengiriman Aktif</h6>
                        
                        <?php if (mysqli_num_rows($query_tracking) > 0): ?>
                            <?php while($trip = mysqli_fetch_assoc($query_tracking)): ?>
                                <a href="live_tracking.php?order=<?= $trip['id']; ?>" class="vehicle-item">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <h6 class="mb-0 fw-bold text-dark"><i class="fas fa-truck text-muted me-2"></i><?= $trip['nopol']; ?></h6>
                                        <span class="badge bg-primary bg-opacity-10 text-primary status-pill anim-blink"><i class="fas fa-broadcast-tower me-1"></i> ON WAY</span>
                                    </div>
                                    <div class="small mt-2" style="font-size: 0.8rem;">
                                        <div class="mb-1"><span class="text-muted">No. SJ:</span> <span class="fw-bold text-primary"><?= $trip['no_sj']; ?></span></div>
                                        <div class="mb-1"><span class="text-muted">Supir:</span> <?= $trip['nama_driver']; ?></div>
                                        <div class="text-truncate"><i class="fas fa-map-marker-alt text-danger me-1"></i> Tujuan: <?= $trip['kota_tujuan']; ?></div>
                                    </div>
                                </a>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="text-center mt-5 pt-5 opacity-50">
                                <i class="fas fa-truck-loading fa-3x mb-3"></i>
                                <p class="small fw-bold">Belum Ada Pengiriman Aktif</p>
                                <p class="small">Semua armada saat ini berada di garasi (Head Office).</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Kolom Kanan: Peta Kosong (Default Pusat Jakarta) -->
                <div class="col-xl-7 col-lg-6 mb-4">
                    <div class="card-sultan overflow-hidden h-100">
                        <div class="header-sultan bg-dark text-white p-3 border-0">
                            <h6 class="font-weight-bold mb-0"><i class="fas fa-route text-warning me-2"></i> Peta Nasional (Standby)</h6>
                        </div>
                        <div class="card-body p-0">
                            <!-- Menampilkan peta Google statis (Zoom Out Indonesia) jika belum ada order dipilih -->
                            <iframe width="100%" height="600" frameborder="0" scrolling="no" marginheight="0" marginwidth="0" 
                                src="https://maps.google.com/maps?q=Jawa,Indonesia&t=&z=6&ie=UTF8&iwloc=&output=embed" style="filter: contrast(1.1) opacity(0.9);">
                            </iframe>
                        </div>
                    </div>
                </div>
            </div>

            <?php 
            } else { 
            // =========================================================================
            // TAMPILAN 2: JIKA ADMIN MENGKLIK SALAH SATU ARMADA -> MUNCUL RUTE & TIMELINE
            // =========================================================================
                
                // Ambil Data Spesifik Orderan Ini
                $q_detail = mysqli_query($conn, "SELECT sj.*, l.nama_lengkap as nama_driver, a.nopol, a.tipe 
                                                 FROM surat_jalan sj 
                                                 JOIN driver d ON sj.id_driver = d.id 
                                                 JOIN login l ON d.id_login = l.id 
                                                 JOIN armada a ON sj.id_armada = a.id 
                                                 WHERE sj.id='$id_tracking'");
                $dt = mysqli_fetch_assoc($q_detail);
                
                // --- GOOGLE MAPS EMBED LOGIC ---
                // Origin selalu kantor pusat kita di Jakarta Timur
                $lokasi_gudang = "Jl. IPN Kb. Nanas No.2, Jatinegara, Jakarta Timur"; 
                
                $origin_map = urlencode($lokasi_gudang);
                // Destinasi diambil dari alamat_tujuan di tabel surat_jalan
                $dest_map = urlencode($dt['alamat_tujuan'] . ", " . $dt['kota_tujuan']);
                
                // Link Iframe ke Google Maps Directions (Menggambar Garis Rute)
                $embed_map = "https://maps.google.com/maps?saddr={$origin_map}&daddr={$dest_map}&output=embed";
            ?>
            
            <div class="mt-2 d-flex justify-content-between align-items-center mb-4 flex-wrap">
                <div>
                    <a href="live_tracking.php" class="btn btn-sm btn-light border shadow-sm mb-2" style="border-radius:8px;"><i class="fas fa-arrow-left me-2"></i> Kembali ke Daftar Track</a>
                    <h3 class="fw-bold mb-0" style="color: #1a367e;">Detail Perjalanan</h3>
                    <p class="text-muted small">Tracking History <?= $dt['no_sj']; ?></p>
                </div>
                <?php if($dt['status'] == 'DELIVERED') { ?>
                    <div class="badge bg-success bg-opacity-10 text-success p-3 shadow-sm mt-2 mt-md-0" style="border-radius:12px; font-size:0.9rem;"><i class="fas fa-check-circle me-1"></i> PENGIRIMAN SELESAI</div>
                <?php } else { ?>
                    <div class="badge bg-primary bg-opacity-10 text-primary p-3 shadow-sm mt-2 mt-md-0 anim-blink" style="border-radius:12px; font-size:0.9rem;"><i class="fas fa-truck-moving me-1"></i> SEDANG DI JALAN</div>
                <?php } ?>
            </div>

            <!-- PETA GOOGLE SULTAN ROW (BENTUK PANJANG DI ATAS SEPERTI REFERENSI GAMBAR) -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card-sultan overflow-hidden">
                        <div class="header-sultan bg-dark text-white p-3 border-0 d-flex justify-content-between align-items-center">
                            <h6 class="font-weight-bold mb-0"><i class="fas fa-route text-warning me-2"></i> Peta Rute Perjalanan (Gudang ➔ Tujuan)</h6>
                            <span class="small fw-bold text-warning">Target: <?= $dt['kota_tujuan']; ?></span>
                        </div>
                        <div class="card-body p-0">
                            <!-- MENAMPILKAN RUTE JALAN RAYA ASLI MENGGUNAKAN GOOGLE MAPS -->
                            <iframe width="100%" height="350" frameborder="0" scrolling="no" marginheight="0" marginwidth="0" 
                                src="<?= $embed_map; ?>" style="filter: contrast(1.1) opacity(0.9);">
                            </iframe>
                        </div>
                        <div class="card-footer bg-white p-4">
                            <div class="row align-items-center text-center text-md-start">
                                <div class="col-md-4 mb-3 mb-md-0 border-end">
                                    <div class="small text-muted fw-bold text-uppercase mb-1">Kurir / Armada</div>
                                    <h6 class="fw-bold mb-0 text-dark"><?= $dt['tipe']; ?></h6>
                                    <div class="badge bg-dark mt-1 px-3"><?= $dt['nopol']; ?></div>
                                </div>
                                <div class="col-md-4 mb-3 mb-md-0 border-end">
                                    <div class="small text-muted fw-bold text-uppercase mb-1">Supir Bertugas</div>
                                    <h6 class="fw-bold mb-0 text-primary"><i class="fas fa-user-circle me-1"></i> <?= $dt['nama_driver']; ?></h6>
                                </div>
                                <div class="col-md-4">
                                    <div class="small text-muted fw-bold text-uppercase mb-1">Penerima Tujuan</div>
                                    <h6 class="fw-bold mb-0 text-dark"><?= $dt['penerima']; ?></h6>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- LOG CHECKPOINT TIMELINE -->
            <div class="row">
                <div class="col-12">
                    <div class="timeline-container shadow-sm">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h5 class="fw-bold mb-0"><i class="fas fa-history text-primary me-2"></i> Log Checkpoint Supir</h5>
                            <button onclick="location.reload()" class="btn btn-sm btn-light border"><i class="fas fa-sync-alt me-1 text-muted"></i> Refresh</button>
                        </div>
                        
                        <ul class="timeline-sultan">
                            <?php
                            // Tarik data riwayat posisi dari tabel sj_checkpoints
                            $q_track = mysqli_query($conn, "SELECT * FROM sj_checkpoints WHERE id_sj='$id_tracking' ORDER BY waktu DESC");
                            
                            if(mysqli_num_rows($q_track) > 0){
                                while($trk = mysqli_fetch_assoc($q_track)){
                            ?>
                                <li>
                                    <div class="time-badge"><i class="far fa-clock me-1"></i> <?= date('H:i | d M Y', strtotime($trk['waktu'])); ?></div><br>
                                    <i class="fas fa-map-marker-alt text-primary mb-2 mt-1"></i> <span class="fw-bold text-dark fs-6"><?= $trk['lokasi']; ?></span>
                                    <?php if(!empty($trk['catatan'])): ?>
                                        <div class="small text-muted mt-1"><i class="fas fa-quote-left opacity-50 me-1"></i> <?= $trk['catatan']; ?></div>
                                    <?php endif; ?>
                                </li>
                            <?php 
                                }
                            } 
                            ?>
                            
                            <!-- Titik Awal: Default dari sistem Surat Jalan -->
                            <li class="start">
                                <div class="time-badge"><i class="far fa-clock me-1"></i> <?= date('H:i | d M Y', strtotime($dt['tgl_kirim'])); ?></div><br>
                                <i class="fas fa-warehouse text-warning mb-2 mt-1"></i> <span class="fw-bold text-dark fs-6">Keluar dari Gudang Pusat</span><br>
                                <small class="text-muted">Barang diserahterimakan ke <?= explode(' ', $dt['nama_driver'])[0]; ?></small>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            <?php } ?>

        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>