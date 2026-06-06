<?php
session_start();
require 'koneksi.php';

if (!isset($_SESSION['log'])) {
    header('location:index.php');
    exit();
}
$role = isset($_SESSION['role']) ? strtoupper($_SESSION['role']) : 'DRIVER';
if ($role == 'DRIVER') {
    header('location:dashboard_driver.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Status Pengiriman - Global Logistics</title>
    <link rel="icon" type="image/png" href="assets/img/icon-192-nobg.png?v=4">
    
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --primary-blue: #1a367e;
            --accent-yellow: #ffcc00;
            --bg-light: #f4f7f6;
        }

        body { font-family: 'Poppins', sans-serif; background-color: var(--bg-light); overflow-x: hidden; }

        /* --- CSS WAJIB LAYOUT ANTI TABRAKAN --- */
        #sidebar ul li a:hover, #sidebar ul li.active > a { color: #fff; background: rgba(255,255,255,0.1); border-left: 4px solid var(--accent-yellow); }
        #sidebar ul li a i { width: 25px; text-align: center; }
        .submenu a { padding-left: 50px !important; font-size: 0.8rem !important; }

        /* --- TRACKING CARD SULTAN --- */
        .tracking-card {
            background: white;
            border-radius: 20px;
            border: 1px solid rgba(0,0,0,0.05);
            box-shadow: 0 10px 30px rgba(0,0,0,0.03);
            margin-bottom: 30px;
            transition: transform 0.3s;
        }
        .tracking-card:hover { transform: translateY(-5px); }

        .card-header-sultan {
            background: #f8fafc;
            border-bottom: 1px solid #edf2f7;
            padding: 20px 25px;
            border-radius: 20px 20px 0 0;
        }

        /* --- TIMELINE DESIGN (Responsive Fixes) --- */
        .timeline-steps { 
            display: flex; justify-content: center; flex-wrap: nowrap; 
            padding: 20px 0; overflow-x: auto; 
        }
        .timeline-step { 
            align-items: center; display: flex; flex-direction: column; 
            position: relative; margin: 10px 0; flex: 1; text-align: center;
        }
        
        .inner-circle { 
            border-radius: 50%; height: 1.5rem; width: 1.5rem; 
            border: 3px solid #e2e8f0; background-color: #fff; 
            margin: 0 auto 10px; position: relative; z-index: 2; 
        }
        .timeline-step.active .inner-circle { background-color: var(--primary-blue); border-color: var(--primary-blue); box-shadow: 0 0 0 5px rgba(26, 54, 126, 0.1); }
        .timeline-step.completed .inner-circle { background-color: #10b981; border-color: #10b981; }

        /* Garis penghubung timeline */
        .timeline-step:not(:last-child):after { 
            content: ""; display: block; position: absolute; 
            border-top: 3px solid #e2e8f0; width: 100%; top: 10px; left: 50%; z-index: 1; 
        }
        .timeline-step.completed:not(:last-child):after { border-color: #10b981; }

        .status-badge { padding: 6px 15px; border-radius: 50px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; }
        .bg-on-trip { background: #e0f2fe; color: #0369a1; }
        .bg-delivered { background: #dcfce7; color: #166534; }
        
        .info-label { font-size: 0.75rem; color: #64748b; font-weight: 600; text-transform: uppercase; margin-bottom: 2px; display: block;}
        .info-value { font-size: 0.95rem; color: #1e293b; font-weight: 700; }
    </style>
</head>
<body>

    <?php include 'sidebar.php'; ?>

    <div id="main-content">
        
        <?php include 'navbar.php'; ?>

        <div class="container-fluid p-4 flex-grow-1 mb-5">
            <div class="d-flex align-items-center justify-content-between flex-wrap mb-4">
                <div>
                    <h3 class="fw-bold mb-1" style="color: #1a367e;">Status Pengiriman</h3>
                    <p class="text-muted small mb-0">Pantau perjalanan armada secara real-time.</p>
                </div>
                <button class="btn btn-primary shadow-sm mt-2 mt-md-0" style="border-radius: 12px; padding: 10px 20px; font-weight: 500;">
                    <i class="fas fa-sync-alt me-2"></i> Refresh Data
                </button>
            </div>

            <?php
            $q_sj = mysqli_query($conn, "SELECT sj.*, l.nama_lengkap as nama_driver, d.no_telepon as telp_driver, a.nopol, a.tipe 
                                         FROM surat_jalan sj 
                                         JOIN driver d ON sj.id_driver = d.id 
                                         JOIN login l ON d.id_login = l.id 
                                         JOIN armada a ON sj.id_armada = a.id
                                         WHERE sj.status IN ('ON TRIP', 'DRAFT', 'DELIVERED', 'RETURNING', 'FINISHED')
                                         ORDER BY FIELD(sj.status, 'ON TRIP', 'RETURNING', 'DRAFT', 'DELIVERED', 'FINISHED'), sj.tgl_kirim DESC");
            
            if(mysqli_num_rows($q_sj) > 0) {
                while($row = mysqli_fetch_assoc($q_sj)) {
                    $st = $row['status'];
                    $id_sj = $row['id'];
                    
                    $prioritas = isset($row['prioritas']) ? $row['prioritas'] : 'Normal';
                    $bg_prioritas = '';
                    if ($prioritas == 'Urgent') $bg_prioritas = 'bg-warning text-dark';
                    else if ($prioritas == 'High') $bg_prioritas = 'bg-danger text-white';
                    else $bg_prioritas = 'bg-info text-white';

                    if($st == 'ON TRIP' || $st == 'DRAFT') {
                        // Ambil checkpoint terakhir jika ada
                        $q_cp = mysqli_query($conn, "SELECT * FROM sj_checkpoints WHERE id_sj='$id_sj' ORDER BY waktu DESC LIMIT 1");
                        $latest_cp = mysqli_fetch_assoc($q_cp);
                        $cp_text = $latest_cp ? "In Transit (" . $latest_cp['lokasi'] . ")" : "Menunggu Keberangkatan";
            ?>
            <div class="tracking-card">
                <div class="card-header-sultan d-flex justify-content-between align-items-center flex-wrap">
                    <div class="mb-2 mb-md-0">
                        <span class="info-label">No. Surat Jalan</span>
                        <span class="info-value text-primary"><?= $row['no_sj']; ?></span>
                        <span class="badge rounded-pill <?= $bg_prioritas ?> ms-2" style="font-size: 0.65rem; padding: 4px 8px; vertical-align: text-bottom;"><?= strtoupper($prioritas); ?></span>
                    </div>
                    <?php if($st == 'ON TRIP'): ?>
                        <span class="status-badge bg-on-trip"><i class="fas fa-truck me-1"></i> Sedang Dikirim</span>
                    <?php else: ?>
                        <span class="status-badge bg-secondary text-white"><i class="fas fa-file-alt me-1"></i> Draft / Persiapan</span>
                    <?php endif; ?>
                </div>
                
                <div class="card-body p-4">
                    <div class="timeline-steps">
                        <div class="timeline-step completed">
                            <div class="inner-circle"></div>
                            <p class="small fw-bold mb-0">Manifest</p>
                            <p class="text-muted" style="font-size: 0.7rem;"><?= date('d M, H:i', strtotime($row['tgl_kirim'])); ?></p>
                        </div>
                        <div class="timeline-step <?= ($st == 'ON TRIP') ? 'active' : ''; ?>">
                            <div class="inner-circle"></div>
                            <p class="small fw-bold mb-0">On Trip</p>
                            <p class="text-muted" style="font-size: 0.7rem;"><?= ($st == 'ON TRIP') ? $cp_text : 'Pending'; ?></p>
                        </div>
                        <div class="timeline-step">
                            <div class="inner-circle"></div>
                            <p class="small fw-bold mb-0">Destination</p>
                            <p class="text-muted" style="font-size: 0.7rem;">Pending</p>
                        </div>
                        <div class="timeline-step">
                            <div class="inner-circle"></div>
                            <p class="small fw-bold mb-0">Delivered</p>
                            <p class="text-muted" style="font-size: 0.7rem;">Pending</p>
                        </div>
                    </div>

                    <hr class="opacity-25 my-4">

                    <div class="row text-center">
                        <div class="col-6 col-md-3 border-end mb-3 mb-md-0">
                            <span class="info-label">Driver</span>
                            <span class="info-value"><?= $row['nama_driver']; ?></span>
                        </div>
                        <div class="col-6 col-md-3 border-end mb-3 mb-md-0">
                            <span class="info-label">Kendaraan</span>
                            <span class="info-value"><?= $row['nopol']; ?> (<?= $row['tipe']; ?>)</span>
                        </div>
                        <div class="col-6 col-md-3 border-end">
                            <span class="info-label">Tujuan</span>
                            <span class="info-value"><?= $row['kota_tujuan']; ?></span>
                        </div>
                        <div class="col-6 col-md-3">
                            <span class="info-label">Estimasi Tiba</span>
                            <span class="info-value text-success">-</span>
                        </div>
                    </div>
                </div>
                
                <div class="card-footer bg-white border-top-0 pb-4 px-4 d-flex justify-content-end">
                    <a href="live_tracking.php?order=<?= $row['id']; ?>" class="btn btn-sm btn-outline-primary me-2 fw-medium" style="border-radius: 8px;">
                        <i class="fas fa-map-marker-alt me-1"></i> Lihat Map
                    </a>
                    <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $row['telp_driver']); ?>" target="_blank" class="btn btn-sm btn-light border fw-medium" style="border-radius: 8px;">
                        <i class="fas fa-phone-alt me-1"></i> Hubungi Driver
                    </a>
                </div>
            </div>
            <?php 
                    } elseif ($st == 'DELIVERED') { 
            ?>
            <div class="tracking-card" style="opacity: 0.85;">
                <div class="card-header-sultan d-flex justify-content-between align-items-center flex-wrap">
                    <div class="mb-2 mb-md-0">
                        <span class="info-label">No. Surat Jalan</span>
                        <span class="info-value"><?= $row['no_sj']; ?></span>
                        <span class="badge rounded-pill <?= $bg_prioritas ?> ms-2" style="font-size: 0.65rem; padding: 4px 8px; vertical-align: text-bottom;"><?= strtoupper($prioritas); ?></span>
                    </div>
                    <span class="status-badge bg-delivered"><i class="fas fa-check-circle me-1"></i> Berhasil Diterima</span>
                </div>
                <div class="card-body p-4 text-center">
                    <p class="mb-0 text-muted">Pengiriman ini telah selesai dan diterima oleh <strong class="text-dark"><?= $row['penerima']; ?></strong>. Armada sedang menunggu rute pulang.</p>
                </div>
            </div>
            <?php 
                    } elseif ($st == 'RETURNING') { 
            ?>
            <div class="tracking-card">
                <div class="card-header-sultan d-flex justify-content-between align-items-center flex-wrap">
                    <div class="mb-2 mb-md-0">
                        <span class="info-label">No. Surat Jalan</span>
                        <span class="info-value"><?= $row['no_sj']; ?></span>
                        <span class="badge rounded-pill <?= $bg_prioritas ?> ms-2" style="font-size: 0.65rem; padding: 4px 8px; vertical-align: text-bottom;"><?= strtoupper($prioritas); ?></span>
                    </div>
                    <span class="status-badge" style="background: #fef3c7; color: #92400e;"><i class="fas fa-truck me-1" style="transform: scaleX(-1);"></i> Rute Pulang</span>
                </div>
                <div class="card-body p-4 text-center">
                    <p class="mb-0 text-muted">Armada <strong class="text-dark"><?= $row['nopol']; ?></strong> sedang dalam perjalanan pulang ke markas. Driver: <strong><?= $row['nama_driver']; ?></strong></p>
                </div>
            </div>
            <?php 
                    } elseif ($st == 'FINISHED') { 
            ?>
            <div class="tracking-card" style="opacity: 0.7;">
                <div class="card-header-sultan d-flex justify-content-between align-items-center flex-wrap">
                    <div class="mb-2 mb-md-0">
                        <span class="info-label">No. Surat Jalan</span>
                        <span class="info-value"><?= $row['no_sj']; ?></span>
                        <span class="badge rounded-pill <?= $bg_prioritas ?> ms-2" style="font-size: 0.65rem; padding: 4px 8px; vertical-align: text-bottom;"><?= strtoupper($prioritas); ?></span>
                    </div>
                    <span class="status-badge bg-delivered"><i class="fas fa-flag-checkered me-1"></i> Selesai Penuh</span>
                </div>
                <div class="card-body p-4 text-center">
                    <p class="mb-0 text-muted">Misi pengiriman ke <strong class="text-dark"><?= $row['penerima']; ?></strong> telah selesai sepenuhnya. Armada & driver kembali stand-by.</p>
                </div>
            </div>
            <?php 
                    }
                } 
            } else {
                echo '<div class="alert alert-info border-0 shadow-sm" style="border-radius: 15px;"><i class="fas fa-info-circle me-2"></i> Belum ada data pengiriman.</div>';
            }
            ?>

        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>