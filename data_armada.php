<?php
session_start();
require 'koneksi.php'; 

if (!isset($_SESSION['log'])) {
    header('location:index.php');
    exit();
}

// Tangkap Role User (Jika Driver, fitur ubah data disembunyikan)
$role = isset($_SESSION['role']) ? strtoupper($_SESSION['role']) : 'STAF';

// =========================================================================
// 1. LOGIKA TAMBAH UNIT BARU (Hanya dieksekusi jika bukan Driver)
// =========================================================================
if (isset($_POST['tambah_armada']) && $role != 'DRIVER') {
    $nopol     = mysqli_real_escape_string($conn, $_POST['nopol']);
    $tipe      = mysqli_real_escape_string($conn, $_POST['tipe']);
    $kategori_kendaraan = mysqli_real_escape_string($conn, $_POST['kategori_kendaraan']);
    $kapasitas = mysqli_real_escape_string($conn, $_POST['kapasitas']);
    
    $query = mysqli_query($conn, "INSERT INTO armada (nopol, tipe, kategori_kendaraan, kapasitas, status) VALUES ('$nopol', '$tipe', '$kategori_kendaraan', '$kapasitas', 'READY')");
    if($query) {
        echo "<script>alert('Armada berhasil ditambahkan!'); window.location.href='data_armada.php';</script>";
    }
}

// =========================================================================
// 2. LOGIKA EDIT UNIT (Hanya dieksekusi jika bukan Driver)
// =========================================================================
if (isset($_POST['update_armada']) && $role != 'DRIVER') {
    $id_armada = $_POST['id_armada'];
    $nopol     = mysqli_real_escape_string($conn, $_POST['nopol']);
    $tipe      = mysqli_real_escape_string($conn, $_POST['tipe']);
    $kategori_kendaraan = mysqli_real_escape_string($conn, $_POST['kategori_kendaraan']);
    $kapasitas = mysqli_real_escape_string($conn, $_POST['kapasitas']);
    $status    = mysqli_real_escape_string($conn, $_POST['status']);

    $update = mysqli_query($conn, "UPDATE armada SET nopol='$nopol', tipe='$tipe', kategori_kendaraan='$kategori_kendaraan', kapasitas='$kapasitas', status='$status' WHERE id='$id_armada'");
    if ($update) {
        echo "<script>alert('Data armada berhasil diupdate!'); window.location.href='data_armada.php';</script>";
    }
}

// =========================================================================
// 3. LOGIKA HAPUS UNIT (Hanya dieksekusi jika bukan Driver)
// =========================================================================
if (isset($_GET['hapus']) && $role != 'DRIVER') {
    $id = $_GET['hapus'];
    mysqli_query($conn, "DELETE FROM armada WHERE id='$id'");
    echo "<script>window.location.href='data_armada.php';</script>";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Manajemen Armada - Global Logistics</title>
    
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
        
        @media (max-width: 768px) {
            #sidebar { margin-left: calc(var(--sidebar-width) * -1); }
            #sidebar.show { margin-left: 0; }
            #main-content { width: 100%; margin-left: 0; }
            .top-navbar { padding: 15px; }
        }

        .sidebar-header { padding: 25px 20px; background: rgba(0,0,0,0.15); border-bottom: 1px solid rgba(255,255,255,0.05); }
        .sidebar-menu { padding: 20px 0; flex-grow: 1; overflow-y: auto; }
        .menu-label { color: rgba(255,255,255,0.4); font-size: 0.7rem; font-weight: 700; letter-spacing: 1px; padding: 0 25px; margin-bottom: 10px; display: block; }
        #sidebar ul li a { padding: 12px 25px; color: rgba(255,255,255,0.7); text-decoration: none; display: block; font-size: 0.85rem; transition: 0.3s; }
        #sidebar ul li a:hover, #sidebar ul li.active > a { color: #fff; background: rgba(255,255,255,0.1); border-left: 4px solid var(--accent-yellow); }
        #sidebar ul li a i { width: 25px; text-align: center; }
        .sidebar-footer { padding: 20px 25px; background: rgba(0,0,0,0.2); }

        /* --- TABLE PERCANTIK --- */
        .table-container { 
            background: white; border-radius: 16px; padding: 25px; 
            box-shadow: 0 4px 20px rgba(0,0,0,0.05); border: 1px solid #edf2f7;
        }
        .table thead th { 
            background-color: #f1f5f9; border: none; color: #64748b; 
            font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; padding: 15px; 
        }
        .table tbody td { padding: 18px 15px; vertical-align: middle; border-bottom: 1px solid #f1f5f9; font-size: 0.9rem; color: #334155; }

        /* --- IKON TRUK MODERN --- */
        .truck-icon-box {
            width: 48px; height: 48px;
            background: #f0f7ff;
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            color: var(--primary-blue);
            font-size: 1.4rem;
            transition: all 0.3s ease;
        }
        tr:hover .truck-icon-box { background: var(--primary-blue); color: white; transform: scale(1.05); }

        /* --- BADGE STATUS --- */
        .badge-status { padding: 6px 14px; border-radius: 10px; font-size: 0.75rem; font-weight: 700; display: inline-block; }
        .status-ready { background: #ecfdf5; color: #059669; border: 1px solid #d1fae5; }
        .status-maintenance { background: #fff1f2; color: #e11d48; border: 1px solid #ffe4e6; }
        .status-trip { background: #eff6ff; color: #2563eb; border: 1px solid #dbeafe; }

        /* --- BUTTONS --- */
        .btn-action { 
            width: 32px; height: 32px; padding: 0; display: inline-flex; 
            align-items: center; justify-content: center; border-radius: 8px; 
            transition: 0.2s; border: 1px solid #e2e8f0; background: white;
        }
        .btn-action-edit:hover { background: #eff6ff; color: #2563eb; border-color: #bfdbfe; }
        .btn-action-delete:hover { background: #fff1f2; color: #e11d48; border-color: #fecaca; }

        .page-item.active .page-link { background-color: var(--primary-blue); border-color: var(--primary-blue); }
    </style>
</head>
<body>

    <?php include 'sidebar.php'; ?>

    <div id="main-content">
        <?php include 'navbar.php'; ?>

        <div class="container-fluid p-4 flex-grow-1">
            <div class="d-flex align-items-center justify-content-between mb-4">
                <div>
                    <h3 class="fw-bold mb-1" style="color: #1a367e;">Database Armada</h3>
                    <p class="text-muted small mb-0">List unit kendaraan operasional yang terdaftar di sistem.</p>
                </div>
                
                <?php if($role != 'DRIVER'): ?>
                <button class="btn btn-primary shadow-sm fw-bold" data-bs-toggle="modal" data-bs-target="#modalTambah" style="border-radius: 10px; padding: 10px 24px;">
                    <i class="fas fa-plus me-2"></i> Tambah Unit
                </button>
                <?php endif; ?>
            </div>

            <div class="table-container">
                <div class="table-responsive">
                    <table class="table table-hover align-middle" id="dataTableArmada" width="100%">
                        <thead>
                            <tr>
                                <th width="80">Unit</th>
                                <th>No. Polisi</th>
                                <th>Tipe Kendaraan</th>
                                <th>Kategori Kendaraan</th>
                                <th>Kapasitas</th>
                                <th>Status</th>
                                <?php if($role != 'DRIVER'): ?>
                                <th class="text-center">Aksi</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $res = mysqli_query($conn, "SELECT * FROM armada ORDER BY id DESC");
                            while($row = mysqli_fetch_assoc($res)){
                                $st = $row['status'];
                                
                                // Penentuan Badge & Icon Rapi
                                if($st == 'MAINTENANCE') {
                                    $badge = 'status-maintenance';
                                    $icon = 'fa-tools'; 
                                } elseif($st == 'ON TRIP') {
                                    $badge = 'status-trip';
                                    $icon = 'fa-truck-fast'; 
                                } elseif($st == 'RETURNING') {
                                    $badge = 'bg-warning bg-opacity-25 text-warning border border-warning';
                                    $icon = 'fa-undo-alt'; 
                                } else {
                                    $badge = 'status-ready';
                                    $icon = 'fa-truck-moving'; 
                                }
                            ?>
                            <tr>
                                <td>
                                    <div class="truck-icon-box">
                                        <i class="fas <?= $icon; ?>"></i>
                                    </div>
                                </td>
                                <td><span class="fw-bold text-dark" style="letter-spacing: 0.5px;"><?= $row['nopol']; ?></span></td>
                                <td><?= $row['tipe']; ?></td>
                                <td><span class="badge bg-secondary"><?= isset($row['kategori_kendaraan']) ? $row['kategori_kendaraan'] : '-'; ?></span></td>
                                <td><i class="fas fa-weight-hanging me-2 text-muted"></i><?= $row['kapasitas']; ?></td>
                                <td><span class="badge-status <?= $badge; ?>"><?= $st; ?></span></td>
                                
                                <?php if($role != 'DRIVER'): ?>
                                <td class="text-center">
                                    <button class="btn btn-action btn-action-edit me-1" data-bs-toggle="modal" data-bs-target="#modalEdit<?= $row['id']; ?>" title="Edit Unit">
                                        <i class="fas fa-pen-to-square"></i>
                                    </button>
                                    <a href="data_armada.php?hapus=<?= $row['id']; ?>" class="btn btn-action btn-action-delete" onclick="return confirm('Hapus unit ini?')" title="Hapus Unit">
                                        <i class="fas fa-trash-can"></i>
                                    </a>
                                </td>
                                <?php endif; ?>
                            </tr>

                            <?php if($role != 'DRIVER'): ?>
                            <div class="modal fade" id="modalEdit<?= $row['id']; ?>" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content" style="border-radius: 16px; border: none;">
                                        <form method="POST">
                                            <div class="modal-header border-0 pt-4 px-4">
                                                <h5 class="fw-bold text-dark">Update Informasi Unit</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body px-4">
                                                <input type="hidden" name="id_armada" value="<?= $row['id']; ?>">
                                                <div class="mb-3">
                                                    <label class="small fw-bold text-muted mb-2">Nomor Polisi</label>
                                                    <input type="text" name="nopol" class="form-control" style="border-radius: 10px;" value="<?= $row['nopol']; ?>" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="small fw-bold text-muted mb-2">Tipe Kendaraan</label>
                                                    <input type="text" name="tipe" class="form-control" style="border-radius: 10px;" value="<?= $row['tipe']; ?>" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="small fw-bold text-muted mb-2">Kategori Kendaraan</label>
                                                    <select name="kategori_kendaraan" class="form-select" style="border-radius: 10px;" required>
                                                        <option value="Motor Roda 2" <?= (isset($row['kategori_kendaraan']) && $row['kategori_kendaraan'] == 'Motor Roda 2') ? 'selected' : ''; ?>>Motor Roda 2</option>
                                                        <option value="Mobil Roda 4" <?= (isset($row['kategori_kendaraan']) && $row['kategori_kendaraan'] == 'Mobil Roda 4') ? 'selected' : ''; ?>>Mobil Roda 4</option>
                                                        <option value="Truk Roda 6" <?= (isset($row['kategori_kendaraan']) && $row['kategori_kendaraan'] == 'Truk Roda 6') ? 'selected' : ''; ?>>Truk Roda 6</option>
                                                        <option value="Truk Besar Roda 10+" <?= (isset($row['kategori_kendaraan']) && $row['kategori_kendaraan'] == 'Truk Besar Roda 10+') ? 'selected' : ''; ?>>Truk Besar Roda 10+</option>
                                                    </select>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="small fw-bold text-muted mb-2">Kapasitas Muatan</label>
                                                    <input type="text" name="kapasitas" class="form-control" style="border-radius: 10px;" value="<?= $row['kapasitas']; ?>" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="small fw-bold text-muted mb-2">Status Operasional</label>
                                                    <?php if($st == 'ON TRIP' || $st == 'RETURNING'): ?>
                                                        <input type="text" class="form-control" value="<?= $st; ?> (Dikelola Sistem)" disabled style="border-radius: 10px;">
                                                        <input type="hidden" name="status" value="<?= $st; ?>">
                                                    <?php else: ?>
                                                        <select name="status" class="form-select" style="border-radius: 10px;">
                                                            <option value="READY" <?= ($st == 'READY') ? 'selected' : ''; ?>>READY (Siap Jalan)</option>
                                                            <option value="MAINTENANCE" <?= ($st == 'MAINTENANCE') ? 'selected' : ''; ?>>MAINTENANCE (Perbaikan)</option>
                                                        </select>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <div class="modal-footer border-0 pb-4 px-4">
                                                <button type="submit" name="update_armada" class="btn btn-primary w-100 fw-bold" style="border-radius: 10px; padding: 12px;">Update Data</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>

                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <?php if($role != 'DRIVER'): ?>
    <div class="modal fade" id="modalTambah" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius: 16px; border: none;">
                <form method="POST">
                    <div class="modal-header border-0 pt-4 px-4">
                        <h5 class="fw-bold text-dark">Daftarkan Unit Baru</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body px-4">
                        <div class="mb-3">
                            <label class="small fw-bold text-muted mb-2">Nomor Polisi</label>
                            <input type="text" name="nopol" class="form-control" placeholder="B 1234 ABC" required style="border-radius: 10px;">
                        </div>
                        <div class="mb-3">
                            <label class="small fw-bold text-muted mb-2">Tipe Kendaraan</label>
                            <input type="text" name="tipe" class="form-control" placeholder="Hino Ranger / Wingbox" required style="border-radius: 10px;">
                        </div>
                        <div class="mb-3">
                            <label class="small fw-bold text-muted mb-2">Kategori Kendaraan</label>
                            <select name="kategori_kendaraan" class="form-select" style="border-radius: 10px;" required>
                                <option value="" disabled selected>Pilih Kategori</option>
                                <option value="Motor Roda 2">Motor Roda 2</option>
                                <option value="Mobil Roda 4">Mobil Roda 4</option>
                                <option value="Truk Roda 6">Truk Roda 6</option>
                                <option value="Truk Besar Roda 10+">Truk Besar Roda 10+</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="small fw-bold text-muted mb-2">Kapasitas Muatan</label>
                            <input type="text" name="kapasitas" class="form-control" placeholder="20 Ton" required style="border-radius: 10px;">
                        </div>
                    </div>
                    <div class="modal-footer border-0 pb-4 px-4">
                        <button type="submit" name="tambah_armada" class="btn btn-primary w-100 fw-bold" style="border-radius: 10px; padding: 12px;">Simpan Armada</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#dataTableArmada').DataTable({
                "language": { "search": "Cari Unit:", "lengthMenu": "Tampil _MENU_" }
            });
        });

        // Script Sidebar Toggle untuk Mobile
        document.getElementById('sidebarToggle')?.addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('show');
        });
    </script>
</body>
</html>