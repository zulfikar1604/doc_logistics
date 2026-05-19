<?php
session_start();
require 'koneksi.php'; 

if (!isset($_SESSION['log'])) {
    header('location:index.php');
    exit();
}

// --- 1. LOGIKA SIMPAN BARANG BARU ---
if (isset($_POST['tambah_stok'])) {
    $nama_barang = mysqli_real_escape_string($conn, $_POST['nama_barang']);
    $kategori = mysqli_real_escape_string($conn, $_POST['kategori_select']);
    $stok        = mysqli_real_escape_string($conn, $_POST['stok']);
    $satuan      = mysqli_real_escape_string($conn, $_POST['satuan']);
    $harga       = mysqli_real_escape_string($conn, $_POST['harga']); // TAMBAHAN: Harga
    
    $query = mysqli_query($conn, "INSERT INTO stok_barang (nama_barang, kategori, stok, satuan, harga) VALUES ('$nama_barang', '$kategori', '$stok', '$satuan', '$harga')");
    
    if($query) {
        echo "<script>alert('Barang berhasil ditambahkan!'); window.location.href='stok_barang.php';</script>";
    } else {
        echo "<script>alert('Gagal menambah barang: " . mysqli_error($conn) . "');</script>";
    }
}

// --- 2. LOGIKA EDIT BARANG ---
if (isset($_POST['edit_stok'])) {
    $id_barang   = mysqli_real_escape_string($conn, $_POST['id_barang']);
    $nama_barang = mysqli_real_escape_string($conn, $_POST['nama_barang']);
    $kategori = mysqli_real_escape_string($conn, $_POST['kategori_select']);
    $stok        = mysqli_real_escape_string($conn, $_POST['stok']);
    $satuan      = mysqli_real_escape_string($conn, $_POST['satuan']);
    $harga       = mysqli_real_escape_string($conn, $_POST['harga']); // TAMBAHAN: Harga

    $query = mysqli_query($conn, "UPDATE stok_barang SET nama_barang='$nama_barang', kategori='$kategori', stok='$stok', satuan='$satuan', harga='$harga' WHERE id='$id_barang'");
    
    if($query) {
        echo "<script>alert('Data barang berhasil diupdate!'); window.location.href='stok_barang.php';</script>";
    } else {
        echo "<script>alert('Gagal update barang: " . mysqli_error($conn) . "');</script>";
    }
}

// --- 3. LOGIKA HAPUS BARANG ---
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    mysqli_query($conn, "DELETE FROM stok_barang WHERE id='$id'");
    header('location:stok_barang.php');
}

// Ambil data stok untuk ditampilkan di tabel
$query_stok = mysqli_query($conn, "SELECT * FROM stok_barang ORDER BY id DESC");

// Ambil data kategori dari tabel kategori_barang
$query_kategori = mysqli_query($conn, "SELECT * FROM kategori_barang ORDER BY nama_kategori ASC");
$all_kategori = [];
while ($kat = mysqli_fetch_assoc($query_kategori)) {
    $all_kategori[] = $kat['nama_kategori'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Stok Barang - PT Elektronik Global</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet" />
    
    <style>
        :root {
            --sidebar-width: 260px;
            --primary-blue: #1a367e;
            --accent-yellow: #ffcc00;
            --bg-light: #f4f7f6;
        }

        body { font-family: 'Poppins', sans-serif; background-color: var(--bg-light); overflow-x: hidden; }

        /* --- LAYOUT ANTI TABRAKAN --- */
        #sidebar {
            width: var(--sidebar-width); position: fixed; top: 0; left: 0; height: 100vh;
            background: linear-gradient(180deg, var(--primary-blue) 0%, #0d1b3e 100%);
            z-index: 999; display: flex; flex-direction: column;
        }
        #main-content { margin-left: var(--sidebar-width); width: calc(100% - var(--sidebar-width)); min-height: 100vh; display: flex; flex-direction: column; }
        .sidebar-header { padding: 25px 20px; background: rgba(0,0,0,0.15); border-bottom: 1px solid rgba(255,255,255,0.05); }
        .sidebar-menu { padding: 20px 0; flex-grow: 1; overflow-y: auto; }
        .menu-label { color: rgba(255,255,255,0.4); font-size: 0.7rem; font-weight: 700; letter-spacing: 1px; padding: 0 25px; margin-bottom: 10px; display: block; }
        #sidebar ul li a { padding: 12px 25px; color: rgba(255,255,255,0.7); text-decoration: none; display: block; font-size: 0.85rem; transition: 0.3s; }
        #sidebar ul li a:hover, #sidebar ul li.active > a { color: #fff; background: rgba(255,255,255,0.1); border-left: 4px solid var(--accent-yellow); }
        #sidebar ul li a i { width: 25px; text-align: center; }
        .sidebar-footer { padding: 20px 25px; background: rgba(0,0,0,0.2); }
        .top-navbar { background: white; padding: 15px 30px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); position: sticky; top: 0; z-index: 998; }

        /* --- STYLE KARTU STOK --- */
        .stock-card { background: white; border-radius: 20px; padding: 25px; box-shadow: 0 10px 30px rgba(0,0,0,0.03); border: 1px solid rgba(0,0,0,0.05); }
        .item-icon { width: 50px; height: 50px; background: #f0f4ff; border-radius: 12px; display: flex; align-items: center; justify-content: center; color: var(--primary-blue); font-size: 1.5rem; }
        
        .badge-stock { padding: 6px 12px; border-radius: 8px; font-weight: 700; font-size: 0.7rem; }
        .status-aman { background: #d1fae5; color: #065f46; }
        .status-menipis { background: #fee2e2; color: #991b1b; }
        
        .form-control-sultan { border-radius: 10px; border: 1.5px solid #e2e8f0; padding: 10px 15px; }
    </style>
</head>
<body>

    <?php include 'sidebar.php'; ?>

    <div id="main-content">
        <?php include 'navbar.php'; ?>

        <div class="container-fluid p-4 flex-grow-1">
            <div class="d-flex align-items-center justify-content-between mb-4">
                <div>
                    <h3 class="fw-bold mb-1" style="color: #1a367e;">Manajemen Stok Barang</h3>
                    <p class="text-muted small">Kelola data inventaris dan harga barang elektronik gudang.</p>
                </div>
                <button class="btn btn-primary shadow-sm fw-bold" style="border-radius: 10px; padding: 10px 20px;" data-bs-toggle="modal" data-bs-target="#modalTambah">
                    <i class="fas fa-plus me-2"></i> Tambah Barang Baru
                </button>
            </div>

            <div class="stock-card">
                <div class="table-responsive">
                    <table class="table table-hover align-middle" id="dataTableStok">
                        <thead>
                            <tr class="text-muted small uppercase">
                                <th>Barang</th>
                                <th>Kategori</th>
                                <th>Harga / Satuan</th>
                                <th width="100">Stok</th>
                                <th>Satuan</th>
                                <th>Status</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = mysqli_fetch_assoc($query_stok)) : 
                                $status = ($row['stok'] > 10) ? 'AMAN' : 'MENIPIS';
                                $badge = ($status == 'AMAN') ? 'status-aman' : 'status-menipis';
                                $harga_rp = "Rp " . number_format((isset($row['harga']) ? $row['harga'] : 0), 0, ',', '.');
                            ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="item-icon me-3"><i class="fas fa-microchip"></i></div>
                                        <div>
                                            <div class="fw-bold text-dark"><?= $row['nama_barang']; ?></div>
                                            <small class="text-muted">Update: <?= date('d/m/Y', strtotime($row['updated_at'])); ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td><span class="badge bg-light text-dark border"><?= $row['kategori']; ?></span></td>
                                <td class="fw-bold text-success"><?= $harga_rp; ?></td>
                                <td class="fw-bold fs-5"><?= $row['stok']; ?></td>
                                <td><?= $row['satuan']; ?></td>
                                <td><span class="badge-stock <?= $badge; ?>"><?= $status; ?></span></td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-light border text-primary me-1" data-bs-toggle="modal" data-bs-target="#modalEdit<?= $row['id']; ?>" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <a href="stok_barang.php?hapus=<?= $row['id']; ?>" class="btn btn-sm btn-light border text-danger" onclick="return confirm('Hapus barang ini?')" title="Hapus">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>

                            <div class="modal fade" id="modalEdit<?= $row['id']; ?>" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content" style="border-radius: 20px;">
                                        <form method="POST">
                                            <div class="modal-header border-0 pt-4 px-4">
                                                <h5 class="fw-bold">Edit Data Barang</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body px-4">
                                                <input type="hidden" name="id_barang" value="<?= $row['id']; ?>">
                                                
                                                <div class="mb-3">
                                                    <label class="small fw-bold text-muted mb-2">Nama Barang</label>
                                                    <input type="text" name="nama_barang" class="form-control form-control-sultan" value="<?= $row['nama_barang']; ?>" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="small fw-bold text-muted mb-2">Kategori</label>
                                                    <select name="kategori_select" class="form-select form-control-sultan" required>
                                                        <?php foreach($all_kategori as $k): ?>
                                                            <option value="<?= htmlspecialchars($k); ?>" <?= ($row['kategori'] == $k) ? 'selected' : ''; ?>><?= htmlspecialchars($k); ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="small fw-bold text-muted mb-2">Harga / Satuan (Rp)</label>
                                                    <input type="number" name="harga" class="form-control form-control-sultan" value="<?= isset($row['harga']) ? $row['harga'] : 0; ?>" required>
                                                </div>
                                                <div class="row">
                                                    <div class="col-6 mb-3">
                                                        <label class="small fw-bold text-muted mb-2">Jumlah Stok</label>
                                                        <input type="number" name="stok" class="form-control form-control-sultan" value="<?= $row['stok']; ?>" required>
                                                    </div>
                                                    <div class="col-6 mb-3">
                                                        <label class="small fw-bold text-muted mb-2">Satuan</label>
                                                        <select name="satuan" class="form-select form-control-sultan">
                                                            <option value="Unit" <?= ($row['satuan'] == 'Unit') ? 'selected' : ''; ?>>Unit</option>
                                                            <option value="Box" <?= ($row['satuan'] == 'Box') ? 'selected' : ''; ?>>Box</option>
                                                            <option value="Pcs" <?= ($row['satuan'] == 'Pcs') ? 'selected' : ''; ?>>Pcs</option>
                                                            <option value="Set" <?= ($row['satuan'] == 'Set') ? 'selected' : ''; ?>>Set</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="modal-footer border-0 pb-4 px-4">
                                                <button type="submit" name="edit_stok" class="btn btn-warning w-100 fw-bold" style="border-radius: 10px; padding: 12px; color: #000;">Simpan Perubahan</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalTambah" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius: 20px;">
                <form method="POST">
                    <div class="modal-header border-0 pt-4 px-4">
                        <h5 class="fw-bold">Input Barang Baru</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body px-4">
                        <div class="mb-3">
                            <label class="small fw-bold text-muted mb-2">Nama Barang</label>
                            <input type="text" name="nama_barang" class="form-control form-control-sultan" placeholder="Contoh: Mesin Cuci LG 2026" required>
                        </div>
                        <div class="mb-3">
                            <label class="small fw-bold text-muted mb-2">Kategori</label>
                            <select name="kategori_select" class="form-select form-control-sultan" required>
                                <option value="" disabled selected>Pilih Kategori</option>
                                <?php foreach($all_kategori as $k): ?>
                                    <option value="<?= htmlspecialchars($k); ?>"><?= htmlspecialchars($k); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="small fw-bold text-muted mb-2">Harga / Satuan (Rp)</label>
                            <input type="number" name="harga" class="form-control form-control-sultan" placeholder="Contoh: 3500000" required>
                        </div>
                        <div class="row">
                            <div class="col-6 mb-3">
                                <label class="small fw-bold text-muted mb-2">Jumlah Stok Awal</label>
                                <input type="number" name="stok" class="form-control form-control-sultan" value="0" required>
                            </div>
                            <div class="col-6 mb-3">
                                <label class="small fw-bold text-muted mb-2">Satuan</label>
                                <select name="satuan" class="form-select form-control-sultan">
                                    <option value="Unit">Unit</option>
                                    <option value="Box">Box</option>
                                    <option value="Pcs">Pcs</option>
                                    <option value="Set">Set</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pb-4 px-4">
                        <button type="submit" name="tambah_stok" class="btn btn-primary w-100 fw-bold" style="border-radius: 10px; padding: 12px;">Simpan ke Gudang</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#dataTableStok').DataTable({
                "language": { "search": "Cari Barang:", "lengthMenu": "Tampil _MENU_" }
            });
        });
    </script>
</body>
</html>