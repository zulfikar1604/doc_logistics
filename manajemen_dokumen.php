<?php
session_start();
require 'koneksi.php'; 

// Cek Keamanan Session
if (!isset($_SESSION['log'])) {
    header('location:index.php');
    exit();
}

// Tangkap Role User
$role = isset($_SESSION['role']) ? strtoupper($_SESSION['role']) : 'STAF';

// Logika Filter Tanggal (Jika user mengisi filter)
$filter_query = "";
if (isset($_GET['tgl_mulai']) && isset($_GET['tgl_selesai']) && !empty($_GET['tgl_mulai'])) {
    $mulai = $_GET['tgl_mulai'];
    $selesai = $_GET['tgl_selesai'];
    $filter_query .= " AND tgl_kirim BETWEEN '$mulai' AND '$selesai' ";
}

if ($role == 'DRIVER') {
    $email_driver = mysqli_real_escape_string($conn, $_SESSION['email']);
    $q_driver = mysqli_query($conn, "SELECT d.id FROM driver d JOIN login l ON d.id_login = l.id WHERE l.email = '$email_driver'");
    if ($drv = mysqli_fetch_assoc($q_driver)) {
        $filter_query .= " AND sj.id_driver = '" . $drv['id'] . "' ";
    } else {
        $filter_query .= " AND sj.id_driver = '0' "; // prevent showing others
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Manajemen Dokumen - PT. Global Logistics</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet" />
    
    <style>
        :root {
            --primary-blue: #1a367e;
            --accent-yellow: #ffcc00;
            --bg-light: #f4f7f6;
        }

        body { font-family: 'Poppins', sans-serif; background-color: var(--bg-light); overflow-x: hidden; }

        /* --- CSS WAJIB LAYOUT --- */
        
        @media (max-width: 768px) {
            #sidebar.show { margin-left: 0; }
        }

        #sidebar ul li a:hover, #sidebar ul li.active > a { color: #fff; background: rgba(255,255,255,0.1); border-left: 4px solid var(--accent-yellow); }
        #sidebar ul li a i { width: 25px; text-align: center; }
        .submenu a { padding-left: 50px !important; font-size: 0.8rem !important; }

        /* --- DESAIN CARD & FILTER SULTAN --- */
        .card-filter { border: none; border-radius: 20px; background: #ffffff; box-shadow: 0 10px 30px rgba(0,0,0,0.03); margin-bottom: 30px; padding: 20px; border: 1px solid rgba(0,0,0,0.05); }
        .section-title { font-weight: 700; color: var(--primary-blue); margin-bottom: 20px; display: flex; align-items: center; }
        .section-title i { margin-right: 10px; color: var(--accent-yellow); }
        
        .form-control-sultan { border-radius: 10px; border: 1.5px solid #e2e8f0; padding: 10px 15px; }
        .form-control-sultan:focus { border-color: var(--primary-blue); box-shadow: 0 0 0 3px rgba(26, 54, 126, 0.1); }
        
        .btn-filter { background: var(--primary-blue); color: white; border-radius: 10px; padding: 10px 25px; font-weight: 600; transition: all 0.3s; border: none;}
        .btn-filter:hover { background: #2b59ff; transform: translateY(-2px); box-shadow: 0 5px 15px rgba(43, 89, 255, 0.2); color: white; }

        /* --- DESAIN DATA TABLES --- */
        .table-container { background: white; border-radius: 20px; padding: 25px; box-shadow: 0 10px 30px rgba(0,0,0,0.03); border: 1px solid rgba(0,0,0,0.05);}
        .table thead th { background-color: #f8fafc; border: none; color: #475569; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 1px; padding: 15px; border-bottom: 2px solid #e2e8f0 !important; }
        .table tbody td { padding: 15px; vertical-align: middle; border-bottom: 1px solid #f1f5f9; font-size: 0.9rem; }
        
        .badge-sultan { padding: 8px 15px; border-radius: 50px; font-weight: 600; font-size: 0.75rem; letter-spacing: 0.5px; display: inline-block;}
        .bg-ontrip { background-color: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd;}
        .bg-selesai { background-color: #d1fae5; color: #065f46; border: 1px solid #a7f3d0;}
        
        .page-item.active .page-link { background-color: var(--primary-blue); border-color: var(--primary-blue); }
    </style>
</head>
<body>

    <?php include 'sidebar.php'; ?>

    <div id="main-content">
        
        <?php include 'navbar.php'; ?>

        <div class="container-fluid p-4 flex-grow-1">
            <h3 class="fw-bold mb-1" style="color: #1a367e;">Manajemen Dokumen</h3>
            <p class="text-muted small mb-4">Arsip dan riwayat pengiriman dokumen yang telah ditarik dari database.</p>

            <div class="card-filter">
                <div class="section-title">
                    <i class="fas fa-filter"></i> Filter Periode Pengiriman
                </div>
                <form action="" method="GET">
                    <div class="row align-items-end">
                        <div class="col-md-4 mb-3">
                            <label class="small mb-1 fw-bold text-muted">Dari Tanggal</label>
                            <input type="date" class="form-control form-control-sultan" name="tgl_mulai" value="<?= $_GET['tgl_mulai'] ?? '' ?>">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="small mb-1 fw-bold text-muted">Sampai Tanggal</label>
                            <input type="date" class="form-control form-control-sultan" name="tgl_selesai" value="<?= $_GET['tgl_selesai'] ?? '' ?>">
                        </div>
                        <div class="col-md-4 mb-3">
                            <button type="submit" class="btn btn-filter w-100">
                                <i class="fas fa-search me-2"></i> Tampilkan Data
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <div class="table-container mb-4">
                <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap">
                    <div class="section-title mb-0">
                        <i class="fas fa-archive"></i> Arsip Surat Jalan
                    </div>
                    
                    <?php if($role != 'DRIVER'): 
                        $qparams = [];
                        if (!empty($_GET['tgl_mulai'])) $qparams['tgl_mulai'] = $_GET['tgl_mulai'];
                        if (!empty($_GET['tgl_selesai'])) $qparams['tgl_selesai'] = $_GET['tgl_selesai'];
                        $export_url = "cetak_laporan.php" . (!empty($qparams) ? "?" . http_build_query($qparams) : "");
                    ?>
                    <a href="<?= $export_url ?>" target="_blank" class="btn btn-light border text-primary btn-sm fw-bold mt-2 mt-md-0 shadow-sm" style="border-radius: 8px; padding: 8px 16px; text-decoration: none;">
                        <i class="fas fa-print me-2"></i> Cetak / Ekspor Laporan
                    </a>
                    <?php endif; ?>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-hover align-middle" id="dataTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>No. Dokumen</th>
                                <th>Driver</th>
                                <th>Tujuan & Penerima</th>
                                <th>Status</th>
                                <?php if($role != 'DRIVER'): ?>
                                <th class="text-center">Aksi</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // AMBIL DATA DARI DATABASE (JOIN 3 TABEL)
                            $sql = "SELECT sj.*, l.nama_lengkap as nama_driver 
                                    FROM surat_jalan sj 
                                    JOIN driver d ON sj.id_driver = d.id 
                                    JOIN login l ON d.id_login = l.id 
                                    WHERE 1=1 $filter_query 
                                    ORDER BY sj.id DESC";
                            
                            $query = mysqli_query($conn, $sql);
                            while ($row = mysqli_fetch_assoc($query)) {
                                $status = $row['status'];
                                $prioritas = isset($row['prioritas']) ? $row['prioritas'] : 'Normal';
                                $bg_prioritas = ($prioritas == 'Urgent') ? 'bg-warning text-dark' : (($prioritas == 'High') ? 'bg-danger text-white' : 'bg-info text-white');
                                
                                if ($status == 'ON TRIP') { $badge_class = 'bg-ontrip'; }
                                elseif ($status == 'RETURNING') { $badge_class = 'bg-warning text-dark'; }
                                elseif ($status == 'DELIVERED') { $badge_class = 'bg-selesai'; }
                                elseif ($status == 'FINISHED') { $badge_class = 'bg-selesai'; }
                                else { $badge_class = 'bg-secondary text-white'; }
                            ?>
                            <tr>
                                <td><span class="fw-medium text-secondary"><?= date('d M Y', strtotime($row['tgl_kirim'])) ?></span></td>
                                <td>
                                    <span class="fw-bold text-primary" style="letter-spacing: 0.5px;"><?= $row['no_sj'] ?></span><br>
                                    <span class="badge rounded-pill <?= $bg_prioritas ?>" style="font-size: 0.65rem; padding: 3px 6px;"><?= strtoupper($prioritas); ?></span>
                                </td>
                                <td><i class="fas fa-user-circle text-muted me-2"></i><?= $row['nama_driver'] ?></td>
                                <td>
                                    <div class="fw-bold text-dark"><?= $row['penerima'] ?></div>
                                    <small class="text-muted"><i class="fas fa-map-marker-alt text-danger me-1"></i> <?= $row['kota_tujuan'] ?></small>
                                </td>
                                <td><span class="badge-sultan <?= $badge_class ?>"><?= $status ?: 'N/A' ?></span></td>
                                
                                <?php if($role != 'DRIVER'): ?>
                                <td>
                                    <div class="d-flex justify-content-center gap-1">
                                        <a href="cetak_surat_jalan.php?id=<?= $row['id'] ?>" target="_blank" class="btn btn-sm btn-outline-primary shadow-sm" title="Lihat Surat Jalan (DO)" style="border-radius: 6px;">
                                            <i class="fas fa-file-alt"></i>
                                        </a>
                                        <a href="cetak_label_resi.php?id=<?= $row['id'] ?>" target="_blank" class="btn btn-sm btn-dark shadow-sm" title="Cetak Label QR" style="border-radius: 6px; padding: 0.25rem 0.5rem; font-size: 0.75rem; font-weight: 600;">
                                            <i class="fas fa-qrcode"></i> Label
                                        </a>
                                        <a href="cetak_surat_jalan.php?id=<?= $row['id'] ?>&download=1" target="_blank" class="btn btn-sm btn-outline-secondary shadow-sm" title="Download PDF" style="border-radius: 6px;">
                                            <i class="fas fa-download"></i>
                                        </a>
                                    </div>
                                </td>
                                <?php endif; ?>
                            </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <footer class="py-4 bg-white mt-auto border-top">
            <div class="container-fluid px-4">
                <div class="d-flex align-items-center justify-content-between small text-muted">
                    <div class="fw-medium">Copyright &copy; PT. Global Logistics <?php echo date('Y'); ?></div>
                    <div>v2.1.0 (Live Data)</div>
                </div>
            </div>
        </footer>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    
    <script>
        $(document).ready(function() {
            $('#dataTable').DataTable({
                "language": {
                    "search": "Cari Dokumen:",
                    "lengthMenu": "Tampil _MENU_",
                    "info": "Menampilkan _START_ s/d _END_ dari _TOTAL_ dokumen",
                    "paginate": {
                        "previous": "<i class='fas fa-chevron-left'></i>",
                        "next": "<i class='fas fa-chevron-right'></i>"
                    }
                },
                "drawCallback": function() {
                    $('.dataTables_paginate > .pagination').addClass('pagination-sm');
                }
            });
        });

        // Script Sidebar Toggle untuk Mobile
        document.getElementById('sidebarToggle')?.addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('show');
        });
    </script>
</body>
</html>