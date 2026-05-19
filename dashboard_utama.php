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
            --sidebar-width: 260px;
            --primary-blue: #1e1b4b; /* Deep Indigo Sultan */
            --accent-yellow: #fbbf24;
            --bg-light: #f8fafc;
        }

        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: var(--bg-light); overflow-x: hidden; color: #334155; }

        /* --- KUNCI ANTI TABRAKAN (Sidebar & Main Content) --- */
        #sidebar {
            width: var(--sidebar-width); position: fixed; top: 0; left: 0; height: 100vh;
            background: linear-gradient(180deg, var(--primary-blue) 0%, #0f172a 100%);
            z-index: 1000; display: flex; flex-direction: column; transition: all 0.3s;
        }
        #main-content { margin-left: var(--sidebar-width); width: calc(100% - var(--sidebar-width)); min-height: 100vh; transition: all 0.3s; }
        
        @media (max-width: 768px) {
            #sidebar { margin-left: calc(var(--sidebar-width) * -1); }
            #sidebar.show { margin-left: 0; }
            #main-content { width: 100%; margin-left: 0; }
        }

        .sidebar-header { padding: 25px 20px; background: rgba(0,0,0,0.15); border-bottom: 1px solid rgba(255,255,255,0.05); }
        .sidebar-menu { padding: 20px 0; flex-grow: 1; overflow-y: auto; }
        .menu-label { color: rgba(255,255,255,0.4); font-size: 0.7rem; font-weight: 700; letter-spacing: 1px; padding: 0 25px; margin-bottom: 10px; display: block; }
        #sidebar ul li a { padding: 12px 25px; color: rgba(255,255,255,0.7); text-decoration: none; display: block; font-size: 0.85rem; transition: 0.3s; }
        #sidebar ul li a:hover, #sidebar ul li.active > a { color: #fff; background: rgba(255,255,255,0.1); border-left: 4px solid var(--accent-yellow); }
        #sidebar ul li a i { width: 25px; text-align: center; }
        .sidebar-footer { padding: 20px 25px; background: rgba(0,0,0,0.2); }

        .top-navbar { background: white; padding: 15px 30px; box-shadow: 0 4px 12px rgba(0,0,0,0.02); position: sticky; top: 0; z-index: 998; }

        /* --- DASHBOARD WIDGETS STYLE --- */
        .live-header { background: linear-gradient(135deg, #1e1b4b 0%, #312e81 100%); border-radius: 24px; padding: 30px; color: white; box-shadow: 0 20px 40px rgba(30,27,75,0.15); margin-bottom: 30px; position: relative; overflow: hidden; }
        .live-header::after { content: ''; position: absolute; top: -50%; right: -10%; width: 50%; height: 200%; background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 60%); transform: rotate(30deg); pointer-events: none; }
        
        .clock-widget { font-size: 2.5rem; font-weight: 800; letter-spacing: -1px; text-shadow: 0 4px 10px rgba(0,0,0,0.2); line-height: 1;}
        .date-widget { font-size: 1rem; font-weight: 500; opacity: 0.8; letter-spacing: 0.5px;}

        .sultan-card { background: white; border-radius: 20px; padding: 25px; border: 1px solid rgba(0,0,0,0.03); box-shadow: 0 10px 30px rgba(0,0,0,0.03); transition: transform 0.3s, box-shadow 0.3s; height: 100%;}
        .sultan-card:hover { transform: translateY(-5px); box-shadow: 0 15px 40px rgba(0,0,0,0.06); }
        
        .stat-icon { width: 50px; height: 50px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 1.3rem; margin-bottom: 15px; }
        .stat-val { font-size: 1.8rem; font-weight: 800; color: #0f172a; line-height: 1.2; }
        .stat-title { font-size: 0.8rem; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; }
        
        .fin-card { background: linear-gradient(135deg, #059669 0%, #047857 100%); color: white; }
        .fin-card.danger { background: linear-gradient(135deg, #e11d48 0%, #be123c 100%); }
        .fin-card .stat-val, .fin-card .stat-title { color: white; }
        
        .badge-luxe { padding: 6px 12px; border-radius: 8px; font-weight: 700; font-size: 0.7rem; }
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

            <!-- ROW 1: FINANCIAL OVERVIEW -->
            <h5 class="fw-bold mb-3"><i class="fas fa-wallet text-success me-2"></i> Ringkasan Finansial</h5>
            <div class="row g-4 mb-5">
                <div class="col-md-6">
                    <div class="sultan-card fin-card">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div class="stat-icon bg-white bg-opacity-25 text-white"><i class="fas fa-chart-line"></i></div>
                            <div class="badge bg-white bg-opacity-25 text-white">Lunas (PAID)</div>
                        </div>
                        <div class="stat-title opacity-75">Total Pendapatan Diterima</div>
                        <div class="stat-val mt-1">Rp <?= number_format($total_revenue, 0, ',', '.') ?></div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="sultan-card fin-card danger">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div class="stat-icon bg-white bg-opacity-25 text-white"><i class="fas fa-file-invoice-dollar"></i></div>
                            <div class="badge bg-white bg-opacity-25 text-white">Menunggu (UNPAID)</div>
                        </div>
                        <div class="stat-title opacity-75">Total Piutang Berjalan</div>
                        <div class="stat-val mt-1">Rp <?= number_format($total_piutang, 0, ',', '.') ?></div>
                    </div>
                </div>
            </div>

            <!-- ROW 2: OPERATIONAL & ASSETS -->
            <h5 class="fw-bold mb-3"><i class="fas fa-cogs text-primary me-2"></i> Status Operasional & Aset</h5>
            <div class="row g-4 mb-5">
                
                <!-- Quick SJ Stats -->
                <div class="col-lg-3 col-md-6">
                    <div class="sultan-card text-center p-4">
                        <div class="stat-val text-primary text-center mb-1"><?= $sj_total ?></div>
                        <div class="stat-title text-center">Total Pengiriman</div>
                        <div class="mt-4 pt-3 border-top text-start">
                            <div class="d-flex justify-content-between mb-2 small"><span class="text-muted">On Trip</span><span class="fw-bold text-primary"><?= $sj_ontrip ?></span></div>
                            <div class="d-flex justify-content-between mb-2 small"><span class="text-muted">Delivered</span><span class="fw-bold text-success"><?= $sj_delivered ?></span></div>
                            <div class="d-flex justify-content-between small"><span class="text-muted">Finished</span><span class="fw-bold text-success"><?= $sj_finished ?></span></div>
                        </div>
                    </div>
                </div>

                <!-- Driver Chart -->
                <div class="col-lg-3 col-md-6">
                    <div class="sultan-card text-center p-3 d-flex flex-column justify-content-center">
                        <div class="stat-title mb-2">Kesiapan Driver (<?= $driver_total ?>)</div>
                        <div style="height: 180px; position: relative; margin: 0 auto; width: 100%;">
                            <canvas id="driverChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Armada Chart -->
                <div class="col-lg-3 col-md-6">
                    <div class="sultan-card text-center p-3 d-flex flex-column justify-content-center">
                        <div class="stat-title mb-2">Utilitas Armada (<?= $armada_total ?>)</div>
                        <div style="height: 180px; position: relative; margin: 0 auto; width: 100%;">
                            <canvas id="armadaChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="col-lg-3 col-md-6">
                    <div class="sultan-card bg-dark text-white d-flex flex-column justify-content-center" style="background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%) !important;">
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
            <div class="sultan-card mb-4">
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