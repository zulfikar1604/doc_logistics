<?php
session_start();
require 'koneksi.php'; 

if (!isset($_SESSION['log'])) {
    header('location:index.php');
    exit();
}

// Logika Hapus Invoice
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    mysqli_query($conn, "DELETE FROM invoice WHERE id='$id'");
    echo "<script>window.location.href='manajemen_invoice.php';</script>";
}

// Logika Tandai Lunas
if (isset($_GET['lunas'])) {
    $id = mysqli_real_escape_string($conn, $_GET['lunas']);
    mysqli_query($conn, "UPDATE invoice SET status='PAID' WHERE id='$id'");
    echo "<script>alert('Invoice berhasil ditandai LUNAS!'); window.location.href='manajemen_invoice.php';</script>";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Manajemen Invoice - Global Logistics</title>
    
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
        #sidebar ul li a:hover, #sidebar ul li.active > a { color: #fff; background: rgba(255,255,255,0.1); border-left: 4px solid var(--accent-yellow); }
        #sidebar ul li a i { width: 25px; text-align: center; }
        .submenu a { padding-left: 50px !important; font-size: 0.8rem !important; }

    </style>
</head>
<body>

    <?php include 'sidebar.php'; ?>

    <div id="main-content">
        <?php include 'navbar.php'; ?>

        <div class="container-fluid p-4 flex-grow-1">
            <div class="d-flex align-items-center justify-content-between mb-4">
                <div>
                    <h3 class="fw-bold mb-1" style="color: #1a367e;">Arsip Invoice</h3>
                    <p class="text-muted small mb-0">Daftar seluruh tagihan pengiriman klien.</p>
                </div>
                <a href="invoice_pengiriman.php" class="btn btn-primary shadow-sm fw-bold" style="border-radius: 10px;">
                    <i class="fas fa-plus me-2"></i> Buat Invoice Baru
                </a>
            </div>

            <div class="table-container">
                <div class="table-responsive">
                    <table class="table table-hover align-middle" id="dataTableInvoice" width="100%">
                        <thead>
                            <tr>
                                <th>No. Invoice</th>
                                <th>Tanggal</th>
                                <th>Klien</th>
                                <th>Total Tagihan</th>
                                <th>Status</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $res = mysqli_query($conn, "SELECT * FROM invoice ORDER BY id DESC");
                            while($row = mysqli_fetch_assoc($res)){
                                $status = $row['status'];
                                $badge = ($status == 'PAID') ? 'bg-paid' : 'bg-unpaid';
                            ?>
                            <tr>
                                <td class="fw-bold text-primary"><?= $row['no_invoice']; ?></td>
                                <td><?= date('d M Y', strtotime($row['tgl_invoice'])); ?></td>
                                <td><?= $row['nama_klien']; ?></td>
                                <td class="fw-bold text-dark">Rp <?= number_format($row['total_tagihan'], 0, ',', '.'); ?></td>
                                <td><span class="badge-status <?= $badge; ?>"><?= $status; ?></span></td>
                                <td class="text-center">
                                    <?php if($status == 'UNPAID'): ?>
                                    <a href="manajemen_invoice.php?lunas=<?= $row['id']; ?>" class="btn btn-sm btn-success shadow-sm me-1" title="Tandai Lunas" onclick="return confirm('Tandai tagihan ini sebagai LUNAS?')"><i class="fas fa-check"></i></a>
                                    <?php endif; ?>
                                    <a href="cetak_invoice.php?id=<?= $row['id']; ?>" target="_blank" class="btn btn-sm btn-light border text-primary shadow-sm me-1" title="Lihat/Cetak PDF"><i class="fas fa-print"></i></a>
                                    <a href="manajemen_invoice.php?hapus=<?= $row['id']; ?>" class="btn btn-sm btn-light border text-danger shadow-sm" onclick="return confirm('Hapus invoice ini?')"><i class="fas fa-trash"></i></a>
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
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#dataTableInvoice').DataTable();
        });
    </script>
</body>
</html>