<?php
session_start();
require 'koneksi.php'; // WAJIB ada koneksi ke database

// Pastikan user sudah login
if (!isset($_SESSION['log'])) {
    header('location:login.php');
    exit();
}

// Tangkap Role User
$role = isset($_SESSION['role']) ? strtoupper($_SESSION['role']) : 'STAF';

// --- HITUNG DATA REAL-TIME DARI DATABASE ---
$total_dokumen = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM surat_jalan"));
$driver_aktif = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM driver WHERE status_kerja = 'ON TRIP'"));
$armada_ready = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM armada WHERE status = 'READY'"));
$total_user = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM login"));
$unpaid_invoice = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM invoice WHERE status = 'UNPAID'"));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | Global Logistics</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --sidebar-width: 260px;
            --primary-blue: #1a367e;
            --accent-yellow: #ffcc00;
            --bg-light: #f4f7fa;
        }

        body { font-family: 'Poppins', sans-serif; background-color: var(--bg-light); overflow-x: hidden; }

        /* --- KUNCI ANTI TABRAKAN (Sidebar & Main Content) --- */
        #sidebar {
            width: var(--sidebar-width); position: fixed; top: 0; left: 0; height: 100vh;
            background: linear-gradient(180deg, var(--primary-blue) 0%, #0d1b3e 100%);
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
        .submenu a { padding-left: 50px !important; font-size: 0.8rem !important; }
        .sidebar-footer { padding: 20px 25px; background: rgba(0,0,0,0.2); }

        .top-navbar { background: white; padding: 15px 30px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); position: sticky; top: 0; z-index: 998; }

        /* --- STYLE DASHBOARD MEWAH (SULTAN) --- */
        .card-sultan { border: none; border-radius: 20px; transition: all 0.3s ease; background: white; position: relative; overflow: hidden; border: 1px solid rgba(0,0,0,0.03); }
        .card-sultan:hover { transform: translateY(-7px); box-shadow: 0 15px 35px rgba(0,0,0,0.08) !important; }
        .icon-box { width: 48px; height: 48px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; }
        
        .stat-value { font-size: 1.7rem; font-weight: 800; color: #1e293b; line-height: 1; }
        .stat-label { font-size: 0.75rem; font-weight: 600; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px; }
        
        .glass-panel { background: white; border-radius: 25px; padding: 28px; box-shadow: 0 10px 40px rgba(0,0,0,0.02); }
        .badge-luxe { padding: 6px 14px; border-radius: 10px; font-weight: 700; font-size: 0.7rem; }
    </style>
</head>
<body>

    <?php include 'sidebar.php'; ?>

    <div id="main-content">
        
        <?php include 'navbar.php'; ?>

        <div class="container-fluid p-4">
            
            <div class="row g-4 mb-5">
                <div class="col-md">
                    <div class="card card-sultan shadow-sm p-4">
                        <div class="icon-box bg-primary bg-opacity-10 text-primary mb-3"><i class="fas fa-file-contract"></i></div>
                        <p class="stat-label mb-1">Total Dokumen</p>
                        <h3 class="stat-value mb-0"><?= number_format($total_dokumen); ?></h3>
                    </div>
                </div>
                <div class="col-md">
                    <div class="card card-sultan shadow-sm p-4">
                        <div class="icon-box bg-warning bg-opacity-10 text-warning mb-3"><i class="fas fa-truck-moving"></i></div>
                        <p class="stat-label mb-1">On Trip</p>
                        <h3 class="stat-value mb-0"><?= number_format($driver_aktif); ?></h3>
                    </div>
                </div>
                <div class="col-md">
                    <div class="card card-sultan shadow-sm p-4">
                        <div class="icon-box bg-success bg-opacity-10 text-success mb-3"><i class="fas fa-warehouse"></i></div>
                        <p class="stat-label mb-1">Armada Ready</p>
                        <h3 class="stat-value mb-0"><?= number_format($armada_ready); ?></h3>
                    </div>
                </div>
                <div class="col-md">
                    <div class="card card-sultan shadow-sm p-4">
                        <div class="icon-box bg-info bg-opacity-10 text-info mb-3"><i class="fas fa-user-shield"></i></div>
                        <p class="stat-label mb-1">Akun User</p>
                        <h3 class="stat-value mb-0"><?= number_format($total_user); ?></h3>
                    </div>
                </div>
                <div class="col-md">
                    <div class="card card-sultan shadow-sm p-4" style="border-bottom: 4px solid #ef4444;">
                        <div class="icon-box bg-danger bg-opacity-10 text-danger mb-3"><i class="fas fa-receipt"></i></div>
                        <p class="stat-label mb-1">Unpaid Invoice</p>
                        <h3 class="stat-value text-danger mb-0"><?= number_format($unpaid_invoice); ?></h3>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-lg-8">
                    <div class="glass-panel shadow-sm">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h6 class="fw-bold mb-0">Pantauan Aktivitas Terkini</h6>
                            <a href="manajemen_dokumen.php" class="btn btn-dark btn-sm rounded-pill px-3" style="font-size: 0.7rem;">Lihat Semua</a>
                        </div>
                        <div class="table-responsive">
                            <table class="table align-middle">
                                <thead class="text-muted small">
                                    <tr>
                                        <th>NOMOR SJ</th>
                                        <th>DRIVER & UNIT</th>
                                        <th>TUJUAN</th>
                                        <th>STATUS</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    // Ambil 5 data pengiriman terbaru dari database
                                    $sql = "SELECT sj.*, l.nama_lengkap as nama_driver, a.nopol, a.tipe 
                                            FROM surat_jalan sj 
                                            JOIN driver d ON sj.id_driver = d.id 
                                            JOIN login l ON d.id_login = l.id 
                                            JOIN armada a ON sj.id_armada = a.id
                                            ORDER BY sj.id DESC LIMIT 5";
                                    $query = mysqli_query($conn, $sql);
                                    while($row = mysqli_fetch_assoc($query)){
                                    ?>
                                    <tr>
                                        <td>
                                            <div class="fw-bold text-primary" style="font-size: 0.85rem;"><?= $row['no_sj']; ?></div>
                                            <small class="text-muted" style="font-size: 0.7rem;"><?= date('d M Y', strtotime($row['tgl_kirim'])); ?></small>
                                        </td>
                                        <td>
                                            <div class="fw-bold" style="font-size: 0.85rem;"><?= $row['nama_driver']; ?></div>
                                            <small class="text-muted" style="font-size: 0.7rem;"><?= $row['nopol']; ?> (<?= $row['tipe']; ?>)</small>
                                        </td>
                                        <td style="font-size: 0.8rem;"><?= $row['kota_tujuan']; ?></td>
                                        <td>
                                            <span class="badge badge-luxe <?= ($row['status'] == 'ON TRIP') ? 'bg-primary bg-opacity-10 text-primary' : 'bg-success bg-opacity-10 text-success' ?>">
                                                <?= $row['status']; ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="glass-panel bg-dark text-white border-0 h-100" style="background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%) !important;">
                        <h6 class="fw-bold mb-4">Quick Actions</h6>
                        
                        <div class="d-grid gap-3">
                            <?php if($role != 'DRIVER'): ?>
                                <a href="buat_surat_jalan.php" class="btn btn-primary py-3 rounded-4 fw-bold shadow-sm">
                                    <i class="fas fa-plus-circle me-2"></i> Terbitkan SJ Baru
                                </a>
                                <a href="invoice_pengiriman.php" class="btn btn-outline-light py-3 rounded-4 fw-bold">
                                    <i class="fas fa-file-invoice-dollar me-2"></i> Buat Invoice Klien
                                </a>
                            <?php else: ?>
                                <a href="dashboard_driver.php" class="btn btn-success py-3 rounded-4 fw-bold shadow-sm" style="background: #10b981; border: none;">
                                    <i class="fas fa-route me-2"></i> Lihat Tugas Saya
                                </a>
                                <a href="manajemen_dokumen.php" class="btn btn-outline-light py-3 rounded-4 fw-bold">
                                    <i class="fas fa-history me-2"></i> Riwayat Pengiriman
                                </a>
                            <?php endif; ?>
                        </div>

                        <div class="mt-5 p-4 rounded-4 bg-white bg-opacity-10 border border-white border-opacity-10">
                            <div class="d-flex align-items-center mb-3">
                                <i class="fas fa-user-shield text-warning fs-4 me-3"></i>
                                <div>
                                    <small class="d-block opacity-50 fw-bold" style="font-size: 0.6rem;">AUTHENTICATED AS</small>
                                    <span class="fw-bold small"><?= $_SESSION['nama_lengkap'] ?? 'Administrator'; ?> <span class="text-warning">(<?= $role; ?>)</span></span>
                                </div>
                            </div>
                            <a href="keluar.php" class="btn btn-danger btn-sm w-100 rounded-pill mt-2">Keluar Sistem</a>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Script Sidebar Toggle untuk Mobile
        document.getElementById('sidebarToggle')?.addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('show');
        });
    </script>
</body>
</html>