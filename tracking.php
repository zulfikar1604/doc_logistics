<?php
session_start();
require 'koneksi.php';

$resi = isset($_GET['resi']) ? mysqli_real_escape_string($conn, trim($_GET['resi'])) : '';
$sj = null;
$details = [];

if (!empty($resi)) {
    // 1. Ambil data utama Surat Jalan
    $query = "SELECT sj.*, 
              l.nama_lengkap as nama_driver, d.no_hp as hp_driver,
              a.nopol, a.tipe as tipe_armada
              FROM surat_jalan sj
              LEFT JOIN driver d ON sj.id_driver = d.id
              LEFT JOIN login l ON d.id_login = l.id
              LEFT JOIN armada a ON sj.id_armada = a.id
              WHERE sj.no_sj = '$resi' LIMIT 1";
    
    $q_sj = mysqli_query($conn, $query);
    $sj = mysqli_fetch_assoc($q_sj);

    if ($sj) {
        // 2. Ambil detail barang
        $q_det = mysqli_query($conn, "SELECT * FROM surat_jalan_detail WHERE id_sj = '{$sj['id']}'");
        while ($d = mysqli_fetch_assoc($q_det)) {
            $details[] = $d;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Tracking Resi - Global Logistics</title>
    
    <!-- Google Fonts & Bootstrap -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #f1f5f9;
            color: #1e293b;
            -webkit-font-smoothing: antialiased;
        }
        
        /* HEADER MOBILE */
        .app-header {
            background: linear-gradient(135deg, #1a367e 0%, #0d1b3e 100%);
            padding: 25px 20px 40px;
            color: white;
            border-bottom-left-radius: 24px;
            border-bottom-right-radius: 24px;
            box-shadow: 0 4px 20px rgba(26,54,126,0.2);
            position: relative;
            overflow: hidden;
        }
        .app-header::after {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 70%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.08) 0%, transparent 60%);
            pointer-events: none;
        }

        .main-content {
            margin-top: -30px;
            padding: 0 15px 30px;
            position: relative;
            z-index: 10;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }

        .glass-card {
            background: #ffffff;
            border-radius: 16px;
            padding: 20px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.05);
            margin-bottom: 16px;
            border: 1px solid rgba(0,0,0,0.02);
        }

        .status-hero {
            text-align: center;
            margin-bottom: 10px;
        }
        
        .status-icon {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 30px;
            margin: 0 auto 15px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
        }

        /* Status Colors */
        .status-DRAFT .status-icon { background: #e2e8f0; color: #64748b; }
        .status-ON-TRIP .status-icon { background: linear-gradient(135deg, #3b82f6, #2563eb); color: white; box-shadow: 0 8px 20px rgba(59,130,246,0.3); }
        .status-RETURNING .status-icon { background: linear-gradient(135deg, #f59e0b, #d97706); color: white; box-shadow: 0 8px 20px rgba(245,158,11,0.3); }
        .status-DELIVERED .status-icon { background: linear-gradient(135deg, #10b981, #059669); color: white; box-shadow: 0 8px 20px rgba(16,185,129,0.3); }
        .status-FINISHED .status-icon { background: linear-gradient(135deg, #10b981, #059669); color: white; box-shadow: 0 8px 20px rgba(16,185,129,0.3); }

        .info-label {
            font-size: 0.75rem;
            font-weight: 700;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 4px;
        }
        .info-value {
            font-size: 0.95rem;
            font-weight: 700;
            color: #1e293b;
        }

        .divider {
            height: 1px;
            background: #f1f5f9;
            margin: 15px 0;
        }

        .item-list {
            padding: 0;
            margin: 0;
            list-style: none;
        }
        .item-list li {
            padding: 12px 15px;
            background: #f8fafc;
            border-radius: 12px;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .item-list li:last-child {
            margin-bottom: 0;
        }
        
        .quick-login-banner {
            background: #fffbeb;
            border: 1px solid #fef3c7;
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 20px;
            text-align: center;
        }
    </style>
</head>
<body>

    <div class="app-header text-center">
        <h5 class="fw-bold mb-1" style="letter-spacing: 1px;">GLOBAL<span style="color:#ffcc00;">LOGISTICS</span></h5>
        <p class="mb-0" style="font-size:0.8rem; opacity:0.8;">Live Delivery Tracking</p>
    </div>

    <div class="main-content">
        
        <?php if (!$sj && !empty($resi)): ?>
            <!-- Resi Tidak Ditemukan -->
            <div class="glass-card text-center py-5">
                <i class="fas fa-search-minus mb-3" style="font-size: 40px; color: #cbd5e1;"></i>
                <h5 class="fw-bold text-dark">Resi Tidak Ditemukan</h5>
                <p class="text-muted small">Nomor Surat Jalan "<b><?= htmlspecialchars($resi) ?></b>" tidak terdaftar di sistem kami.</p>
                <a href="tracking.php" class="btn btn-primary rounded-pill mt-3 px-4">Cari Ulang</a>
            </div>
        <?php elseif (!$sj && empty($resi)): ?>
            <!-- Form Pencarian (Jika akses tanpa param) -->
            <div class="glass-card py-5">
                <div class="text-center mb-4">
                    <i class="fas fa-box-open mb-3" style="font-size: 40px; color: #1a367e;"></i>
                    <h5 class="fw-bold text-dark">Lacak Pengiriman</h5>
                    <p class="text-muted small">Masukkan nomor Surat Jalan Anda</p>
                </div>
                <form action="tracking.php" method="GET">
                    <div class="input-group mb-3">
                        <span class="input-group-text bg-light border-0"><i class="fas fa-qrcode text-muted"></i></span>
                        <input type="text" name="resi" class="form-control bg-light border-0 form-control-lg" placeholder="Contoh: SJ-2026..." required style="font-size:0.9rem;">
                    </div>
                    <button type="submit" class="btn btn-primary w-100 rounded-pill fw-bold">Lacak Sekarang</button>
                </form>
            </div>
        <?php else: ?>
            <!-- HASIL TRACKING -->

            <?php 
                $st = str_replace(' ', '-', $sj['status']); 
                $icon = 'fa-clock';
                $status_text = 'Menunggu Diproses';
                
                if ($sj['status'] == 'ON TRIP') { $icon = 'fa-truck-fast'; $status_text = 'Sedang Dalam Perjalanan'; }
                if ($sj['status'] == 'RETURNING') { $icon = 'fa-arrow-rotate-left'; $status_text = 'Driver Kembali ke Pool'; }
                if ($sj['status'] == 'DELIVERED') { $icon = 'fa-check-circle'; $status_text = 'Paket Telah Diterima'; }
                if ($sj['status'] == 'FINISHED') { $icon = 'fa-flag-checkered'; $status_text = 'Selesai (Arsip)'; }
            ?>

            <!-- Status Utama -->
            <div class="glass-card status-<?= $st ?>">
                <div class="status-hero">
                    <div class="status-icon">
                        <i class="fas <?= $icon ?>"></i>
                    </div>
                    <div class="badge bg-dark rounded-pill px-3 py-1 mb-2" style="font-size: 0.75rem; letter-spacing: 1px;">
                        <?= htmlspecialchars($sj['no_sj']) ?>
                    </div>
                    <h4 class="fw-bold mb-1"><?= $status_text ?></h4>
                    <p class="text-muted small mb-0"><i class="far fa-calendar-alt me-1"></i> <?= date('d M Y', strtotime($sj['tgl_kirim'])) ?></p>
                </div>
            </div>

            <!-- Pesan Pintasan Untuk Driver yang sedang Login -->
            <?php if (isset($_SESSION['log']) && $_SESSION['role'] == 'DRIVER'): ?>
                <div class="quick-login-banner shadow-sm">
                    <div class="fw-bold text-dark mb-1"><i class="fas fa-info-circle text-warning me-1"></i> Anda login sebagai Driver</div>
                    <div class="small text-muted mb-3">Ingin langsung update status pengiriman ini?</div>
                    <a href="dashboard_driver.php" class="btn btn-warning btn-sm fw-bold rounded-pill w-100 shadow-sm">
                        Buka Dashboard Driver
                    </a>
                </div>
            <?php endif; ?>

            <!-- Alamat & Tujuan -->
            <div class="glass-card">
                <h6 class="fw-bold mb-3"><i class="fas fa-map-marker-alt text-danger me-2"></i> Detail Tujuan</h6>
                
                <div class="info-label">Penerima (Consignee)</div>
                <div class="info-value mb-3 text-primary"><?= htmlspecialchars($sj['penerima']) ?></div>
                
                <div class="info-label">Alamat Lengkap</div>
                <div class="info-value" style="font-weight: 500; font-size: 0.9rem; line-height: 1.5;">
                    <?= nl2br(htmlspecialchars($sj['alamat_tujuan'])) ?>
                </div>
                <div class="badge bg-light text-dark mt-2 border"><i class="fas fa-city me-1 text-muted"></i> <?= htmlspecialchars($sj['kota_tujuan']) ?></div>
            </div>

            <!-- Info Driver -->
            <div class="glass-card">
                <h6 class="fw-bold mb-3"><i class="fas fa-user-shield text-info me-2"></i> Petugas Pengiriman</h6>
                
                <div class="d-flex align-items-center gap-3">
                    <div style="width: 45px; height: 45px; background: #e0f2fe; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #0284c7; font-size: 20px;">
                        <i class="fas fa-user"></i>
                    </div>
                    <div>
                        <div class="info-value"><?= htmlspecialchars($sj['nama_driver']) ?></div>
                        <div class="text-muted" style="font-size: 0.8rem;"><i class="fas fa-phone-alt me-1"></i> <?= htmlspecialchars($sj['hp_driver'] ?? '-') ?></div>
                    </div>
                </div>

                <div class="divider"></div>

                <div class="d-flex align-items-center gap-3">
                    <div style="width: 45px; height: 45px; background: #fef3c7; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #d97706; font-size: 20px;">
                        <i class="fas fa-truck"></i>
                    </div>
                    <div>
                        <div class="info-value"><?= htmlspecialchars($sj['nopol']) ?></div>
                        <div class="text-muted" style="font-size: 0.8rem;"><i class="fas fa-tag me-1"></i> Tipe: <?= htmlspecialchars($sj['tipe_armada']) ?></div>
                    </div>
                </div>
            </div>

            <!-- List Barang -->
            <div class="glass-card mb-5">
                <h6 class="fw-bold mb-3"><i class="fas fa-box-open text-success me-2"></i> Rincian Paket</h6>
                <ul class="item-list">
                    <?php 
                    $total_qty = 0;
                    foreach($details as $d): 
                        $total_qty += $d['jumlah'];
                    ?>
                    <li>
                        <div>
                            <div class="fw-bold text-dark" style="font-size: 0.9rem;"><?= htmlspecialchars($d['nama_barang']) ?></div>
                            <div class="text-muted" style="font-size: 0.75rem;"><?= htmlspecialchars($d['keterangan'] ?? '') ?></div>
                        </div>
                        <div class="badge bg-success rounded-pill px-3 py-2">
                            <?= $d['jumlah'] ?> <?= htmlspecialchars($d['satuan']) ?>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <div class="text-center mt-3 pt-3 border-top">
                    <div class="info-label">Total Quantity</div>
                    <h5 class="fw-bold text-primary mb-0"><?= $total_qty ?></h5>
                </div>
            </div>

            <!-- Footer -->
            <div class="text-center mb-4">
                <div style="font-size: 0.75rem; color: #94a3b8;">
                    Powered by <br>
                    <span class="fw-bold text-dark">PT Global Logistics System</span>
                </div>
            </div>
            
        <?php endif; ?>

    </div>
</body>
</html>
