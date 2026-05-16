<?php
session_start();
require 'koneksi.php';

if (!isset($_SESSION['log'])) {
    header('location:login.php');
    exit();
}
$role = isset($_SESSION['role']) ? strtoupper($_SESSION['role']) : 'STAF';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Dashboard Pembayaran - Global Logistics</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet" />
    
    <style>
        :root {
            --sidebar-width: 260px;
            --primary-blue: #1a367e;
            --accent-yellow: #ffcc00;
            --bg-light: #f8fafc;
        }

        body { font-family: 'Poppins', sans-serif; background-color: var(--bg-light); overflow-x: hidden; }

        /* --- CSS WAJIB LAYOUT --- */
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

        .table-container { 
            background: white; border-radius: 16px; padding: 25px; 
            box-shadow: 0 4px 20px rgba(0,0,0,0.05); border: 1px solid #edf2f7;
        }
        .table thead th { 
            background-color: #f1f5f9; border: none; color: #64748b; 
            font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; padding: 15px; 
        }
        .table tbody td { padding: 15px; vertical-align: middle; border-bottom: 1px solid #f1f5f9; font-size: 0.9rem; color: #334155; }
        
        .badge-unpaid { background: #fee2e2; color: #ef4444; border: 1px solid #fca5a5; padding: 6px 12px; border-radius: 8px; font-weight: 700; font-size: 0.75rem; letter-spacing: 0.5px; }
        .badge-paid { background: #d1fae5; color: #059669; border: 1px solid #6ee7b7; padding: 6px 12px; border-radius: 8px; font-weight: 700; font-size: 0.75rem; letter-spacing: 0.5px; }

        .btn-lunas { background: #10b981; color: white; border: none; border-radius: 8px; padding: 6px 14px; font-weight: 600; font-size: 0.8rem; transition: 0.2s; }
        .btn-lunas:hover { background: #059669; transform: translateY(-1px); box-shadow: 0 4px 10px rgba(16,185,129,0.2); }
    </style>
</head>
<body>

    <?php include 'sidebar.php'; ?>

    <div id="main-content">
        <?php include 'navbar.php'; ?>

        <div class="container-fluid p-4 flex-grow-1 mb-5">
            <div class="d-flex align-items-center justify-content-between mb-4">
                <div>
                    <h3 class="fw-bold mb-1" style="color: #1a367e;">Dashboard Pembayaran (Invoice)</h3>
                    <p class="text-muted small mb-0">Kelola status tagihan. Driver hanya akan berangkat setelah Invoice berstatus PAID (Lunas).</p>
                </div>
            </div>

            <div class="table-container">
                <div class="table-responsive">
                    <table class="table table-hover align-middle" id="dataTableInvoice" width="100%">
                        <thead>
                            <tr>
                                <th>No. Invoice</th>
                                <th>Tgl Terbit</th>
                                <th>Surat Jalan (SJ)</th>
                                <th>Klien Tujuan</th>
                                <th class="text-end">Total Tagihan</th>
                                <th class="text-center">Status</th>
                                <?php if($role != 'DRIVER'): ?>
                                <th class="text-center">Aksi</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $query = "SELECT i.*, sj.no_sj as sj_terkait FROM invoice i LEFT JOIN surat_jalan sj ON i.id_sj = sj.id ORDER BY i.id DESC";
                            $res = mysqli_query($conn, $query);
                            while($row = mysqli_fetch_assoc($res)){
                            ?>
                            <tr>
                                <td><span class="fw-bold text-dark"><?= htmlspecialchars($row['no_invoice']); ?></span></td>
                                <td><?= date('d M Y', strtotime($row['tgl_invoice'])); ?></td>
                                <td><span class="text-primary fw-medium"><?= htmlspecialchars($row['sj_terkait']); ?></span></td>
                                <td><?= htmlspecialchars($row['nama_klien']); ?></td>
                                <td class="text-end fw-bold text-dark">Rp <?= number_format($row['total_tagihan'], 0, ',', '.'); ?></td>
                                <td class="text-center">
                                    <?php if($row['status'] == 'UNPAID'): ?>
                                        <span class="badge-unpaid"><i class="fas fa-clock me-1"></i> BELUM LUNAS</span>
                                    <?php elseif($row['status'] == 'PAID'): ?>
                                        <span class="badge-paid"><i class="fas fa-check-circle me-1"></i> LUNAS</span>
                                    <?php endif; ?>
                                </td>
                                <?php if($role != 'DRIVER'): ?>
                                <td class="text-center">
                                    <?php if($row['status'] == 'UNPAID'): ?>
                                    <form action="proses_bayar_invoice.php" method="POST" class="d-inline">
                                        <input type="hidden" name="id_invoice" value="<?= $row['id']; ?>">
                                        <button type="submit" name="konfirmasi_lunas" class="btn btn-lunas" onclick="return confirm('Konfirmasi bahwa Klien telah melunasi tagihan ini?')">
                                            Konfirmasi Lunas
                                        </button>
                                    </form>
                                    <?php else: ?>
                                    <button class="btn btn-light btn-sm text-muted" disabled><i class="fas fa-check"></i> Selesai</button>
                                    <?php endif; ?>
                                </td>
                                <?php endif; ?>
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
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#dataTableInvoice').DataTable({
                "language": { "search": "Cari Tagihan:", "lengthMenu": "Tampil _MENU_" },
                "order": [[0, 'desc']]
            });
        });

        document.getElementById('sidebarToggle')?.addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('show');
        });
    </script>
</body>
</html>