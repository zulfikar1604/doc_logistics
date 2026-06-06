<?php
session_start();
require 'koneksi.php'; 

if (!isset($_SESSION['log'])) {
    header('location:index.php');
    exit();
}
$role = isset($_SESSION['role']) ? strtoupper($_SESSION['role']) : 'MANAGER';
if ($role == 'DRIVER') {
    header('location:dashboard_driver.php');
    exit();
}

// =========================================================================
// 1. LOGIKA TAMBAH UNIT BARU (Hanya dieksekusi jika bukan Driver)
// =========================================================================
if (isset($_POST['tambah_armada']) && $role == 'ADMIN') {
    $nopol     = mysqli_real_escape_string($conn, $_POST['nopol']);
    $tipe      = mysqli_real_escape_string($conn, $_POST['tipe']);
    $kategori_kendaraan = mysqli_real_escape_string($conn, $_POST['kategori_kendaraan']);
    $kapasitas = mysqli_real_escape_string($conn, $_POST['kapasitas']);
    $odometer  = (int)($_POST['odometer'] ?? 0);
    $odometer_terakhir_servis = (int)($_POST['odometer_terakhir_servis'] ?? 0);
    
    $status_awal = 'READY';
    $pesan_tambahan = '';
    if (($odometer - $odometer_terakhir_servis) >= 10000) {
        $status_awal = 'MAINTENANCE';
        $pesan_tambahan = '\\n\\nCatatan: Status diubah menjadi MAINTENANCE karena butuh servis rutin (>10.000 KM).';
    }
    
    $query = mysqli_query($conn, "INSERT INTO armada (nopol, tipe, kategori_kendaraan, kapasitas, status, odometer, odometer_terakhir_servis) VALUES ('$nopol', '$tipe', '$kategori_kendaraan', '$kapasitas', '$status_awal', '$odometer', '$odometer_terakhir_servis')");
    if($query) {
        echo "<script>alert('Armada berhasil ditambahkan!$pesan_tambahan'); window.location.href='data_armada.php';</script>";
    }
}

// =========================================================================
// 2. LOGIKA EDIT UNIT (Hanya dieksekusi jika bukan Driver)
// =========================================================================
if (isset($_POST['update_armada']) && $role == 'ADMIN') {
    $id_armada = $_POST['id_armada'];
    $nopol     = mysqli_real_escape_string($conn, $_POST['nopol']);
    $tipe      = mysqli_real_escape_string($conn, $_POST['tipe']);
    $kategori_kendaraan = mysqli_real_escape_string($conn, $_POST['kategori_kendaraan']);
    $kapasitas = mysqli_real_escape_string($conn, $_POST['kapasitas']);
    $status    = mysqli_real_escape_string($conn, $_POST['status']);
    $odometer  = (int)$_POST['odometer'];
    $odometer_terakhir_servis = (int)$_POST['odometer_terakhir_servis'];

    $pesan_tambahan = '';
    if ($status == 'READY' && ($odometer - $odometer_terakhir_servis) >= 10000) {
        $status = 'MAINTENANCE';
        $pesan_tambahan = '\\n\\nCatatan: Status otomatis dialihkan ke MAINTENANCE karena odometer menunjukkan armada butuh servis rutin.';
    }

    $update = mysqli_query($conn, "UPDATE armada SET nopol='$nopol', tipe='$tipe', kategori_kendaraan='$kategori_kendaraan', kapasitas='$kapasitas', status='$status', odometer='$odometer', odometer_terakhir_servis='$odometer_terakhir_servis' WHERE id='$id_armada'");
    if ($update) {
        echo "<script>alert('Data armada berhasil diupdate!$pesan_tambahan'); window.location.href='data_armada.php';</script>";
    }
}

// =========================================================================
// 3. LOGIKA HAPUS UNIT (Hanya dieksekusi jika bukan Driver)
// =========================================================================
if (isset($_GET['hapus']) && $role == 'ADMIN') {
    $id = (int)$_GET['hapus'];
    
    // Check if armada has active shipments
    $q_active = mysqli_query($conn, "SELECT COUNT(*) as active_count FROM surat_jalan WHERE id_armada='$id' AND status IN ('ON TRIP', 'RETURNING', 'DELIVERED', 'DRAFT')");
    $d_active = mysqli_fetch_assoc($q_active);
    if ($d_active['active_count'] > 0) {
        echo "<script>alert('Gagal! Armada ini masih terikat dengan surat jalan aktif atau belum selesai. Selesaikan/batalkan surat jalan terlebih dahulu.'); window.location.href='data_armada.php';</script>";
        exit();
    }
    
    mysqli_query($conn, "DELETE FROM armada WHERE id='$id'");
    echo "<script>alert('Data armada berhasil dihapus!'); window.location.href='data_armada.php';</script>";
    exit();
}

// =========================================================================
// 4. LOGIKA CATAT SERVIS (Hanya dieksekusi jika bukan Driver)
// =========================================================================
if (isset($_GET['catat_servis']) && $role == 'ADMIN') {
    $id = (int)$_GET['catat_servis'];
    mysqli_query($conn, "UPDATE armada SET odometer_terakhir_servis = odometer WHERE id='$id'");
    echo "<script>alert('Servis berhasil dicatat! Odometer terakhir servis disinkronisasikan.'); window.location.href='data_armada.php';</script>";
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Manajemen Armada - Global Logistics</title>
    <link rel="icon" type="image/png" href="assets/img/icon-192-nobg.png?v=4">
    
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet" />
    
    <style>
        :root {
            --primary-blue: #1a367e;
            --accent-yellow: #ffcc00;
            --bg-light: #f8fafc;
        }

        body { font-family: 'Poppins', sans-serif; background-color: var(--bg-light); overflow-x: hidden; }

        /* --- CSS WAJIB LAYOUT --- */
        
        @media (max-width: 768px) {
            #sidebar.show { margin-left: 0; }
        }

        #sidebar ul li a:hover, #sidebar ul li.active > a { color: #fff; background: rgba(255,255,255,0.1); border-left: 4px solid var(--accent-yellow); }
        #sidebar ul li a i { width: 25px; text-align: center; }

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
                
                <?php if($role == 'ADMIN'): ?>
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
                                <th class="text-end">Odometer</th>
                                <th class="text-end">Sejak Servis</th>
                                <th>Status</th>
                                <?php if($role == 'ADMIN'): ?>
                                <th class="text-center">Aksi</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $modals_html = '';
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
                                
                                $odo = (int)$row['odometer'];
                                $odo_servis = (int)$row['odometer_terakhir_servis'];
                                $selisih_odo = $odo - $odo_servis;
                                $needs_service = ($selisih_odo >= 10000);
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
                                <td class="text-end fw-bold text-dark"><?= number_format($odo, 0, ',', '.'); ?> km</td>
                                <td class="text-end">
                                    <span class="fw-bold <?= $needs_service ? 'text-danger' : 'text-muted'; ?>">
                                        <?= number_format($selisih_odo, 0, ',', '.'); ?> km
                                    </span>
                                    <?php if ($needs_service): ?>
                                        <span class="badge bg-danger ms-1 text-white" style="font-size: 0.68rem; padding: 3px 6px; border-radius: 6px;"><i class="fas fa-exclamation-triangle me-1"></i>Butuh Servis</span>
                                    <?php endif; ?>
                                </td>
                                <td><span class="badge-status <?= $badge; ?>"><?= $st; ?></span></td>
                                
                                <?php if($role == 'ADMIN'): ?>
                                <td class="text-center">
                                    <button class="btn btn-action btn-action-edit me-1" data-bs-toggle="modal" data-bs-target="#modalEdit<?= $row['id']; ?>" title="Edit Unit">
                                        <i class="fas fa-pen-to-square"></i>
                                    </button>
                                    <a href="data_armada.php?catat_servis=<?= $row['id']; ?>" class="btn btn-action text-success border-success me-1" onclick="return confirm('Catat bahwa unit ini telah diservis? Ini akan me-reset indikator servis truk.')" title="Catat Servis Selesai">
                                        <i class="fas fa-wrench"></i>
                                    </a>
                                    <a href="data_armada.php?hapus=<?= $row['id']; ?>" class="btn btn-action btn-action-delete" onclick="return confirm('Hapus unit ini?')" title="Hapus Unit">
                                        <i class="fas fa-trash-can"></i>
                                    </a>
                                </td>
                                <?php endif; ?>
                            </tr>

                            <?php if($role == 'ADMIN'): ob_start(); ?>
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
                                                <div class="row">
                                                    <div class="col-6 mb-3">
                                                        <label class="small fw-bold text-muted mb-2">Odometer Saat Ini</label>
                                                        <input type="number" name="odometer" class="form-control" style="border-radius: 10px;" value="<?= (int)($row['odometer'] ?? 0); ?>" required min="0">
                                                    </div>
                                                    <div class="col-6 mb-3">
                                                        <label class="small fw-bold text-muted mb-2">Terakhir Servis</label>
                                                        <input type="number" name="odometer_terakhir_servis" class="form-control" style="border-radius: 10px;" value="<?= (int)($row['odometer_terakhir_servis'] ?? 0); ?>" required min="0">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="modal-footer border-0 pb-4 px-4">
                                                <button type="submit" name="update_armada" class="btn btn-primary w-100 fw-bold" style="border-radius: 10px; padding: 12px;">Update Data</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <?php $modals_html .= ob_get_clean(); endif; ?>

                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <?php if($role == 'ADMIN'): ?>
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
                        <div class="row">
                            <div class="col-6 mb-3">
                                <label class="small fw-bold text-muted mb-2">Odometer Awal</label>
                                <input type="number" name="odometer" class="form-control" placeholder="0" value="0" required style="border-radius: 10px;" min="0">
                            </div>
                            <div class="col-6 mb-3">
                                <label class="small fw-bold text-muted mb-2">Terakhir Servis</label>
                                <input type="number" name="odometer_terakhir_servis" class="form-control" placeholder="0" value="0" required style="border-radius: 10px;" min="0">
                            </div>
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

    <?= isset($modals_html) ? $modals_html : ''; ?>

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