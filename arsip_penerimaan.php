<?php
session_start();
require 'koneksi.php'; 

// Cek Keamanan Session
if (!isset($_SESSION['log'])) {
    header('location:index.php');
    exit();
}

$role = isset($_SESSION['role']) ? strtoupper($_SESSION['role']) : 'MANAGER';

// Logika Filter Tanggal (Jika user mengisi filter)
$filter_query = "";
if (isset($_GET['tgl_mulai']) && isset($_GET['tgl_selesai']) && !empty($_GET['tgl_mulai'])) {
    $mulai = mysqli_real_escape_string($conn, $_GET['tgl_mulai']) . " 00:00:00";
    $selesai = mysqli_real_escape_string($conn, $_GET['tgl_selesai']) . " 23:59:59";
    $filter_query .= " AND created_at BETWEEN '$mulai' AND '$selesai' ";
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Arsip Penerimaan Barang - PT. Global Logistics</title>
    <link rel="icon" type="image/png" href="assets/img/icon-192-nobg.png?v=4">
    
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
        
        .page-item.active .page-link { background-color: var(--primary-blue); border-color: var(--primary-blue); }
    </style>
</head>
<body>

    <?php include 'sidebar.php'; ?>

    <div id="main-content">
        
        <?php include 'navbar.php'; ?>

        <div class="container-fluid p-4 flex-grow-1">
            <h3 class="fw-bold mb-1" style="color: #1a367e;">Arsip Penerimaan Barang</h3>
            <p class="text-muted small mb-4">Laporan riwayat lengkap histori penerimaan barang masuk ke gudang.</p>

            <div class="card-filter">
                <div class="section-title">
                    <i class="fas fa-filter"></i> Filter Tanggal Penerimaan
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
                                <i class="fas fa-search me-2"></i> Tampilkan Laporan
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <div class="table-container mb-4">
                <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap">
                    <div class="section-title mb-0">
                        <i class="fas fa-box-open"></i> Laporan Histori Penerimaan
                    </div>
                    
                    <?php 
                        $qparams = [];
                        if (!empty($_GET['tgl_mulai'])) $qparams['tgl_mulai'] = $_GET['tgl_mulai'];
                        if (!empty($_GET['tgl_selesai'])) $qparams['tgl_selesai'] = $_GET['tgl_selesai'];
                        $export_url = "cetak_penerimaan.php" . (!empty($qparams) ? "?" . http_build_query($qparams) : "");
                    ?>
                    <a href="<?= $export_url ?>" target="_blank" class="btn btn-light border text-success btn-sm fw-bold mt-2 mt-md-0 shadow-sm" style="border-radius: 8px; padding: 8px 16px; text-decoration: none;">
                        <i class="fas fa-print me-2"></i> Cetak Laporan Fisik
                    </a>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-hover align-middle" id="dataTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>Tanggal Masuk</th>
                                <th>SKU & Barang</th>
                                <th>Kategori</th>
                                <th class="text-end">Jumlah</th>
                                <th>Diterima Oleh</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $sql = "SELECT * FROM penerimaan_barang 
                                    WHERE 1=1 $filter_query 
                                    ORDER BY created_at DESC";
                            $query = mysqli_query($conn, $sql);
                            while ($row = mysqli_fetch_assoc($query)) {
                            ?>
                            <tr>
                                <td>
                                    <span class="fw-medium text-dark"><?= date('d M Y', strtotime($row['created_at'])) ?></span><br>
                                    <small class="text-muted"><i class="far fa-clock me-1"></i><?= date('H:i:s', strtotime($row['created_at'])) ?></small>
                                </td>
                                <td>
                                    <span class="fw-bold text-primary" style="letter-spacing: 0.5px;"><?= htmlspecialchars($row['sku']) ?></span><br>
                                    <span class="text-dark fw-medium"><?= htmlspecialchars($row['nama_barang']) ?></span>
                                </td>
                                <td><span class="badge bg-secondary bg-opacity-10 text-secondary"><?= htmlspecialchars($row['kategori']) ?></span></td>
                                <td class="text-end">
                                    <div class="fw-bold text-success" style="font-size: 1.1rem;">+<?= $row['jumlah'] ?></div>
                                    <small class="text-muted"><?= htmlspecialchars($row['satuan']) ?></small>
                                </td>
                                <td>
                                    <i class="fas fa-user-check text-muted me-2"></i><?= htmlspecialchars($row['diterima_oleh']) ?>
                                </td>
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
                    <div class="fw-medium">Copyright &copy; PT. Global Logistics <?= date('Y'); ?></div>
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
                    "search": "Cari Riwayat:",
                    "lengthMenu": "Tampil _MENU_",
                    "info": "Menampilkan _START_ s/d _END_ dari _TOTAL_ data",
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
    </script>
</body>
</html>
