<?php
session_start();
require 'koneksi.php';

// Pastikan user sudah login
if (!isset($_SESSION['log'])) {
    header('location:index.php');
    exit();
}

// Tangkap Role User
$role = isset($_SESSION['role']) ? strtoupper($_SESSION['role']) : 'STAF';

// --- FETCH SEMUA DATA STATISTIK UTAMA ---

// 1. Finansial
$q_rev = mysqli_query($conn, "SELECT IFNULL(SUM(total_tagihan),0) as total FROM invoice WHERE status = 'PAID'");
$total_revenue = mysqli_fetch_assoc($q_rev)['total'];

$q_unpaid = mysqli_query($conn, "SELECT IFNULL(SUM(total_tagihan),0) as total FROM invoice WHERE status = 'UNPAID'");
$total_piutang = mysqli_fetch_assoc($q_unpaid)['total'];

// 2. Armada (Kendaraan)
$armada_total = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM armada"));
$armada_ready = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM armada WHERE status = 'READY'"));
$armada_ontrip = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM armada WHERE status IN ('ON TRIP', 'RETURNING')"));
$armada_maint = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM armada WHERE status = 'MAINTENANCE'"));

// 3. Driver
$driver_total = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM driver"));
$driver_avail = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM driver WHERE status_kerja = 'AVAILABLE'"));
$driver_ontrip = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM driver WHERE status_kerja IN ('ON TRIP', 'RETURNING')"));
$driver_off = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM driver WHERE status_kerja = 'OFF'"));

// 4. Operasional (Surat Jalan)
$sj_draft = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM surat_jalan WHERE status='DRAFT'"));
$sj_ontrip = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM surat_jalan WHERE status IN ('ON TRIP', 'RETURNING')"));
$sj_delivered = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM surat_jalan WHERE status='DELIVERED'"));
$sj_finished = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM surat_jalan WHERE status='FINISHED'"));
$sj_total = $sj_draft + $sj_ontrip + $sj_delivered + $sj_finished;

// 5. User, SKU, & Kategori
$user_total = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM login"));
$sku_total = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM stok_barang"));
$kategori_total = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM kategori_barang"));

// 6. Fast & Slow Moving Items
$q_fast = mysqli_query($conn, "SELECT nama_barang, SUM(jumlah) as total_kirim FROM surat_jalan_detail GROUP BY nama_barang ORDER BY total_kirim DESC LIMIT 5");
$fast_moving = [];
while ($r = mysqli_fetch_assoc($q_fast)) { $fast_moving[] = $r; }

$q_slow = mysqli_query($conn, "SELECT nama_barang, SUM(jumlah) as total_kirim FROM surat_jalan_detail GROUP BY nama_barang ORDER BY total_kirim ASC LIMIT 5");
$slow_moving = [];
while ($r = mysqli_fetch_assoc($q_slow)) { $slow_moving[] = $r; }

// 7. Barang Kritis (Low Stock)
$q_kritis = mysqli_query($conn, "SELECT nama_barang, stok FROM stok_barang WHERE stok <= 10 ORDER BY stok ASC LIMIT 5");
$barang_kritis = [];
while ($r = mysqli_fetch_assoc($q_kritis)) { $barang_kritis[] = $r; }
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Elektronik Global | Global Logistics</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Chart.js for Interactive Charts -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        :root {
            --primary-blue: #1a367e;
            --accent-yellow: #ffcc00;
            --bg-light: #f4f7fc;
        }

        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: var(--bg-light); overflow-x: hidden; color: #334155; }

        /* --- DASHBOARD HEADER --- */
        .live-header {
            background: linear-gradient(135deg, var(--primary-blue) 0%, #0d1b3e 100%);
            border-radius: 20px; padding: 28px 32px; color: white;
            box-shadow: 0 16px 40px rgba(26,54,126,0.18);
            margin-bottom: 28px; position: relative; overflow: hidden;
        }
        .live-header::after {
            content: ''; position: absolute; top: -60%; right: -5%;
            width: 40%; height: 220%;
            background: radial-gradient(circle, rgba(255,255,255,0.08) 0%, transparent 65%);
            pointer-events: none;
        }
        .clock-widget { font-size: 2.2rem; font-weight: 800; letter-spacing: -1px; line-height: 1; }
        .date-widget   { font-size: 0.88rem; font-weight: 500; opacity: 0.75; margin-top: 4px; }

        /* --- CARDS --- */
        .modern-card {
            background: #ffffff; border-radius: 18px; padding: 22px;
            border: 1px solid rgba(0,0,0,0.04);
            box-shadow: 0 4px 20px rgba(0,0,0,0.04);
            transition: transform 0.25s, box-shadow 0.25s;
            height: 100%; position: relative; overflow: hidden;
        }
        .modern-card:hover { transform: translateY(-4px); box-shadow: 0 12px 32px rgba(0,0,0,0.09); }

        .border-left-success { border-left: 5px solid #10b981 !important; }
        .border-left-primary  { border-left: 5px solid #3b82f6 !important; }
        .border-left-warning  { border-left: 5px solid #f59e0b !important; }
        .border-left-danger   { border-left: 5px solid #ef4444 !important; }
        .border-left-info     { border-left: 5px solid #0ea5e9 !important; }

        .icon-box {
            width: 48px; height: 48px; border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.3rem; color: white; flex-shrink: 0;
            box-shadow: 0 4px 10px rgba(0,0,0,0.12);
        }
        .icon-box.bg-success { background: linear-gradient(135deg, #10b981, #059669) !important; }
        .icon-box.bg-primary { background: linear-gradient(135deg, #3b82f6, #2563eb) !important; }
        .icon-box.bg-warning { background: linear-gradient(135deg, #f59e0b, #d97706) !important; }
        .icon-box.bg-danger  { background: linear-gradient(135deg, #ef4444, #dc2626) !important; }
        .icon-box.bg-info    { background: linear-gradient(135deg, #0ea5e9, #0284c7) !important; }

        .modern-card .stat-title {
            font-size: 0.7rem; font-weight: 700; color: #94a3b8;
            text-transform: uppercase; letter-spacing: 1px;
        }
        .modern-card .stat-val {
            font-size: 2rem; font-weight: 800; color: #0f172a;
            line-height: 1.1; margin: 10px 0 4px; letter-spacing: -1px;
        }

        .modern-card.light-green   { background: #f0fdf4; border-left: 5px solid #10b981 !important; }
        .modern-card.light-danger  { background: #fef2f2; border-left: 5px solid #ef4444 !important; }
        .modern-card.light-warning { background: #fffbeb; border-left: 5px solid #f59e0b !important; }
        .modern-card.light-blue    { background: #eff6ff; border-left: 5px solid #3b82f6 !important; }

        .badge-luxe { padding: 5px 11px; border-radius: 8px; font-weight: 700; font-size: 0.68rem; }

        /* Progress Bar */
        .progress-modern { height: 7px; border-radius: 10px; background: #f1f5f9; overflow: hidden; margin-top: 14px; }
        .progress-modern .progress-bar { border-radius: 10px; }

        /* Moving items list */
        .item-row {
            display: flex; align-items: center; justify-content: space-between;
            padding: 9px 12px; border-radius: 10px;
            margin-bottom: 6px; background: #f8fafc;
        }
        .item-row:last-child { margin-bottom: 0; }
        .item-row .item-name { font-size: 0.82rem; font-weight: 600; color: #1e293b; }
    </style>
</head>
<body>

    <?php include 'sidebar.php'; ?>

    <div id="main-content">
        
        <?php include 'navbar.php'; ?>

        <div class="container-fluid p-4">
            
            <!-- HEADER: LIVE CLOCK & GREETINGS -->
            <div class="live-header d-flex flex-column flex-md-row justify-content-between align-items-center gap-4">
                <div>
                    <h2 class="fw-bold mb-1">Elektronik Global</h2>
                    <p class="mb-0" style="opacity: 0.8;">Pantau seluruh aset finansial & operasional secara real-time.</p>
                </div>
                <div class="text-md-end text-center bg-white bg-opacity-10 p-3 rounded-4" style="backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.1);">
                    <div class="clock-widget" id="liveClock">00:00:00</div>
                    <div class="date-widget" id="liveDate">Memuat tanggal...</div>
                </div>
            </div>

            <!-- ROW 1: 4 STAT CARDS SIMETRIS -->
            <div class="row g-3 mb-4">
                <div class="col-lg-3 col-md-6">
                    <div class="modern-card light-green">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="stat-title text-success">Revenue (Paid)</div>
                                <div class="stat-val text-success" style="font-size:1.5rem;">Rp <?= number_format($total_revenue, 0, ',', '.') ?></div>
                                <div class="small text-muted mt-1">Pendapatan diterima</div>
                            </div>
                            <div class="icon-box bg-success"><i class="fas fa-money-bill-wave"></i></div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="modern-card light-danger">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="stat-title text-danger">Piutang (Unpaid)</div>
                                <div class="stat-val text-danger" style="font-size:1.5rem;">Rp <?= number_format($total_piutang, 0, ',', '.') ?></div>
                                <div class="small text-muted mt-1">Belum dibayar klien</div>
                            </div>
                            <div class="icon-box bg-danger"><i class="fas fa-file-invoice-dollar"></i></div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="modern-card light-blue">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="stat-title text-primary">Total Pengguna</div>
                                <div class="stat-val text-primary"><?= $user_total ?></div>
                                <div class="small text-muted mt-1">Akun terdaftar</div>
                            </div>
                            <div class="icon-box bg-primary"><i class="fas fa-users"></i></div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="modern-card light-warning">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="stat-title" style="color:#d97706;">SKU / Kategori</div>
                                <div class="stat-val text-warning"><?= $sku_total ?> <small style="font-size:1rem;color:#94a3b8;font-weight:500;">/ <?= $kategori_total ?></small></div>
                                <div class="small text-muted mt-1">Jenis barang & kategori</div>
                            </div>
                            <div class="icon-box bg-warning"><i class="fas fa-box-open"></i></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ROW 2: OPERATIONAL & ASSETS -->
            <div class="row g-4 mb-5">
                
                <!-- Quick SJ Stats -->
                <div class="col-lg-3 col-md-6">
                    <div class="modern-card border-left-success">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="stat-title">ORDER FULFILLMENT</div>
                                <?php 
                                    $pct_selesai = ($sj_total > 0) ? round(($sj_finished + $sj_delivered) / $sj_total * 100, 1) : 0;
                                ?>
                                <div class="stat-val text-success"><?= $pct_selesai ?>%</div>
                                <div class="small text-muted mt-2"><?= ($sj_finished + $sj_delivered) ?> selesai dari <?= $sj_total ?> order</div>
                            </div>
                            <div class="icon-box bg-success"><i class="fas fa-check-circle"></i></div>
                        </div>
                        <div class="progress-modern">
                            <div class="progress-bar bg-success" role="progressbar" style="width: <?= $pct_selesai ?>%"></div>
                        </div>
                    </div>
                </div>

                <!-- Driver Chart -->
                <div class="col-lg-3 col-md-6">
                    <div class="modern-card text-center p-3 d-flex flex-column justify-content-center border-left-info">
                        <div class="stat-title mb-2">Kesiapan Driver (<?= $driver_total ?>)</div>
                        <div style="height: 180px; position: relative; margin: 0 auto; width: 100%;">
                            <canvas id="driverChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Armada Chart -->
                <div class="col-lg-3 col-md-6">
                    <div class="modern-card text-center p-3 d-flex flex-column justify-content-center border-left-warning">
                        <div class="stat-title mb-2">Utilitas Armada (<?= $armada_total ?>)</div>
                        <div style="height: 180px; position: relative; margin: 0 auto; width: 100%;">
                            <canvas id="armadaChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="col-lg-3 col-md-6">
                    <div class="modern-card bg-dark text-white d-flex flex-column justify-content-center border-0" style="background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%) !important;">
                        <h6 class="fw-bold mb-4 text-center">Akses Cepat</h6>
                        <div class="d-grid gap-2">
                            <?php if($role != 'DRIVER'): ?>
                                <a href="buat_surat_jalan.php" class="btn btn-primary rounded-3 fw-medium"><i class="fas fa-plus-circle me-2"></i> Surat Jalan Baru</a>
                                <a href="live_tracking.php" class="btn btn-outline-light rounded-3 fw-medium"><i class="fas fa-satellite-dish me-2"></i> Live Tracking</a>
                                <a href="status_pengiriman.php" class="btn btn-outline-light rounded-3 fw-medium"><i class="fas fa-truck-moving me-2"></i> Monitor Armada</a>
                            <?php else: ?>
                                <a href="dashboard_driver.php" class="btn btn-success rounded-3 fw-medium"><i class="fas fa-route me-2"></i> Lihat Tugas</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

            </div>

            <!-- ROW 3: RECENT ACTIVITIES -->
            <div class="modern-card mb-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="fw-bold mb-0"><i class="fas fa-history text-secondary me-2"></i> Aktivitas Pengiriman Terkini</h5>
                    <a href="manajemen_dokumen.php" class="btn btn-light border btn-sm fw-bold rounded-pill px-3">Arsip Lengkap</a>
                </div>
                <div class="table-responsive">
                    <table class="table align-middle table-hover">
                        <thead class="text-muted small">
                            <tr>
                                <th>NOMOR SJ & TANGGAL</th>
                                <th>DRIVER & UNIT</th>
                                <th>TUJUAN</th>
                                <th>STATUS TERAKHIR</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $sql = "SELECT sj.*, l.nama_lengkap as nama_driver, a.nopol, a.tipe 
                                    FROM surat_jalan sj 
                                    JOIN driver d ON sj.id_driver = d.id 
                                    JOIN login l ON d.id_login = l.id 
                                    JOIN armada a ON sj.id_armada = a.id
                                    ORDER BY sj.id DESC LIMIT 6";
                            $query = mysqli_query($conn, $sql);
                            while($row = mysqli_fetch_assoc($query)){
                            ?>
                            <tr>
                                <td>
                                    <div class="fw-bold text-primary" style="font-size: 0.9rem; letter-spacing: 0.5px;"><?= $row['no_sj']; ?></div>
                                    <small class="text-muted" style="font-size: 0.75rem;"><i class="far fa-calendar-alt me-1"></i><?= date('d M Y', strtotime($row['tgl_kirim'])); ?></small>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark" style="font-size: 0.85rem;"><i class="fas fa-user-circle text-muted me-1"></i><?= $row['nama_driver']; ?></div>
                                    <small class="text-muted" style="font-size: 0.75rem;"><i class="fas fa-truck text-secondary me-1"></i><?= $row['nopol']; ?> (<?= $row['tipe']; ?>)</small>
                                </td>
                                <td style="font-size: 0.85rem; font-weight: 500;"><i class="fas fa-map-marker-alt text-danger me-1"></i><?= $row['kota_tujuan']; ?></td>
                                <td>
                                    <?php 
                                        $st = $row['status'];
                                        if ($st == 'ON TRIP') { $bc = 'bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25'; }
                                        elseif ($st == 'RETURNING') { $bc = 'bg-warning bg-opacity-25 text-dark border border-warning border-opacity-50'; }
                                        elseif ($st == 'DELIVERED') { $bc = 'bg-success bg-opacity-10 text-success border border-success border-opacity-25'; }
                                        elseif ($st == 'FINISHED') { $bc = 'bg-success text-white shadow-sm'; }
                                        else { $bc = 'bg-secondary bg-opacity-10 text-secondary border'; }
                                    ?>
                                    <span class="badge badge-luxe <?= $bc ?>">
                                        <?= $st ?: 'N/A'; ?>
                                    </span>
                                </td>
                            </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- ROW 4: FAST, SLOW & CRITICAL — Equal 3-col -->
            <div class="row g-3 mb-4">
                <div class="col-lg-4">
                    <div class="modern-card" style="min-height:260px;">
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <div class="icon-box bg-success" style="width:34px;height:34px;border-radius:10px;font-size:0.9rem;"><i class="fas fa-arrow-trend-up"></i></div>
                            <h6 class="fw-bold mb-0 text-success" style="font-size:0.85rem;">Fast Moving (Top 5)</h6>
                        </div>
                        <?php if(empty($fast_moving)): ?>
                            <div class="text-muted text-center py-4" style="font-size:0.82rem;">Belum ada data pengiriman</div>
                        <?php else: ?>
                            <?php foreach($fast_moving as $fm): ?>
                            <div class="item-row">
                                <span class="item-name"><i class="fas fa-box text-success me-2" style="font-size:0.75rem;"></i><?= htmlspecialchars($fm['nama_barang']) ?></span>
                                <span class="badge rounded-pill" style="background:#dcfce7;color:#15803d;font-size:0.7rem;"><?= $fm['total_kirim'] ?></span>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="modern-card" style="min-height:260px;">
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <div class="icon-box" style="width:34px;height:34px;border-radius:10px;font-size:0.9rem;background:linear-gradient(135deg,#64748b,#475569);"><i class="fas fa-arrow-trend-down"></i></div>
                            <h6 class="fw-bold mb-0 text-secondary" style="font-size:0.85rem;">Slow Moving (Bottom 5)</h6>
                        </div>
                        <?php if(empty($slow_moving)): ?>
                            <div class="text-muted text-center py-4" style="font-size:0.82rem;">Belum ada data pengiriman</div>
                        <?php else: ?>
                            <?php foreach($slow_moving as $sm): ?>
                            <div class="item-row">
                                <span class="item-name"><i class="fas fa-box text-secondary me-2" style="font-size:0.75rem;"></i><?= htmlspecialchars($sm['nama_barang']) ?></span>
                                <span class="badge rounded-pill" style="background:#f1f5f9;color:#64748b;font-size:0.7rem;"><?= $sm['total_kirim'] ?></span>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="modern-card border-left-danger" style="min-height:260px;">
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <div class="icon-box bg-danger" style="width:34px;height:34px;border-radius:10px;font-size:0.9rem;"><i class="fas fa-exclamation-triangle"></i></div>
                            <h6 class="fw-bold mb-0 text-danger" style="font-size:0.85rem;">Stok Kritis (&le; 10)</h6>
                        </div>
                        <?php if(empty($barang_kritis)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-check-circle text-success mb-2" style="font-size:1.8rem;"></i>
                                <div class="text-success fw-bold" style="font-size:0.82rem;">Semua stok aman</div>
                            </div>
                        <?php else: ?>
                            <?php foreach($barang_kritis as $bk): ?>
                            <div class="item-row" style="background:#fef2f2;">
                                <span class="item-name text-danger"><i class="fas fa-exclamation-circle me-2" style="font-size:0.75rem;"></i><?= htmlspecialchars($bk['nama_barang']) ?></span>
                                <span class="badge rounded-pill bg-danger" style="font-size:0.7rem;">Sisa <?= $bk['stok'] ?></span>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // --- SCRIPT LIVE CLOCK ---
        function updateClock() {
            const now = new Date();
            const timeOptions = { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false };
            const dateOptions = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
            
            document.getElementById('liveClock').textContent = now.toLocaleTimeString('id-ID', timeOptions);
            document.getElementById('liveDate').textContent = now.toLocaleDateString('id-ID', dateOptions);
        }
        setInterval(updateClock, 1000);
        updateClock();

        // --- SCRIPT CHART.JS ---
        // Driver Chart
        const ctxDriver = document.getElementById('driverChart').getContext('2d');
        new Chart(ctxDriver, {
            type: 'doughnut',
            data: {
                labels: ['Available', 'On Trip', 'Off'],
                datasets: [{
                    data: [<?= $driver_avail ?>, <?= $driver_ontrip ?>, <?= $driver_off ?>],
                    backgroundColor: ['#10b981', '#3b82f6', '#94a3b8'],
                    borderWidth: 0,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '70%',
                layout: { padding: { bottom: 10 } },
                plugins: {
                    legend: { position: 'bottom', labels: { boxWidth: 10, padding: 10, font: { size: 10, family: "'Plus Jakarta Sans'" } } }
                }
            }
        });

        // Armada Chart
        const ctxArmada = document.getElementById('armadaChart').getContext('2d');
        new Chart(ctxArmada, {
            type: 'doughnut',
            data: {
                labels: ['Ready', 'On Trip', 'Maintenance'],
                datasets: [{
                    data: [<?= $armada_ready ?>, <?= $armada_ontrip ?>, <?= $armada_maint ?>],
                    backgroundColor: ['#10b981', '#3b82f6', '#ef4444'],
                    borderWidth: 0,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '70%',
                layout: { padding: { bottom: 10 } },
                plugins: {
                    legend: { position: 'bottom', labels: { boxWidth: 10, padding: 10, font: { size: 10, family: "'Plus Jakarta Sans'" } } }
                }
            }
        });

        // Sidebar Toggle
        document.getElementById('sidebarToggle')?.addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('show');
        });
    </script>
</body>
</html>