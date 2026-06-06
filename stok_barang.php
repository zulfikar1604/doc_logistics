<?php
session_start();
require 'koneksi.php'; 

if (!isset($_SESSION['log'])) {
    header('location:index.php');
    exit();
}
$role = isset($_SESSION['role']) ? strtoupper($_SESSION['role']) : 'DRIVER';
if ($role == 'DRIVER') {
    header('location:dashboard_driver.php');
    exit();
}

// --- 1. LOGIKA SIMPAN BARANG BARU ---
if (isset($_POST['tambah_stok'])) {
    if ($role != 'ADMIN') {
        echo "<script>alert('Akses Ditolak! Hanya Admin yang dapat mendaftarkan barang.'); window.location.href='stok_barang.php';</script>";
        exit();
    }
    $nama_barang = mysqli_real_escape_string($conn, $_POST['nama_barang']);
    $kategori = mysqli_real_escape_string($conn, $_POST['kategori_select']);
    $satuan      = mysqli_real_escape_string($conn, $_POST['satuan']);
    $harga       = mysqli_real_escape_string($conn, $_POST['harga']);
    
    // Generate SKU otomatis
    $sku = 'SKU-' . date('ymd') . '-' . strtoupper(substr(uniqid(), -4));
    
    $query = mysqli_query($conn, "INSERT INTO stok_barang (sku, nama_barang, kategori, stok, satuan, harga) VALUES ('$sku', '$nama_barang', '$kategori', 0, '$satuan', '$harga')");
    
    if($query) {
        echo "<script>alert('Barang berhasil didaftarkan! SKU: $sku'); window.location.href='stok_barang.php';</script>";
    } else {
        echo "<script>alert('Gagal menambah barang: " . mysqli_error($conn) . "');</script>";
    }
}

// --- 2. LOGIKA EDIT BARANG (hanya info master, bukan stok) ---
if (isset($_POST['edit_stok'])) {
    if ($role != 'ADMIN') {
        echo "<script>alert('Akses Ditolak! Hanya Admin yang dapat mengubah data barang.'); window.location.href='stok_barang.php';</script>";
        exit();
    }
    $id_barang   = mysqli_real_escape_string($conn, $_POST['id_barang']);
    $nama_barang = mysqli_real_escape_string($conn, $_POST['nama_barang']);
    $kategori = mysqli_real_escape_string($conn, $_POST['kategori_select']);
    $satuan      = mysqli_real_escape_string($conn, $_POST['satuan']);
    $harga       = mysqli_real_escape_string($conn, $_POST['harga']);

    $query = mysqli_query($conn, "UPDATE stok_barang SET nama_barang='$nama_barang', kategori='$kategori', satuan='$satuan', harga='$harga' WHERE id='$id_barang'");
    
    if($query) {
        echo "<script>alert('Data barang berhasil diupdate!'); window.location.href='stok_barang.php';</script>";
    } else {
        echo "<script>alert('Gagal update barang: " . mysqli_error($conn) . "');</script>";
    }
}

// --- 3. LOGIKA HAPUS BARANG ---
if (isset($_GET['hapus'])) {
    if ($role != 'ADMIN') {
        echo "<script>alert('Akses Ditolak! Hanya Admin yang dapat menghapus barang.'); window.location.href='stok_barang.php';</script>";
        exit();
    }
    $id = (int)$_GET['hapus'];
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
    <title>Master Barang - PT Elektronik Global</title>
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

        /* --- LAYOUT ANTI TABRAKAN --- */
        #sidebar ul li a:hover, #sidebar ul li.active > a { color: #fff; background: rgba(255,255,255,0.1); border-left: 4px solid var(--accent-yellow); }
        #sidebar ul li a i { width: 25px; text-align: center; }

        /* --- STYLE KARTU STOK --- */
        .stock-card { background: white; border-radius: 20px; padding: 25px; box-shadow: 0 10px 30px rgba(0,0,0,0.03); border: 1px solid rgba(0,0,0,0.05); }
        
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
                    <h3 class="fw-bold mb-1" style="color: #1a367e;">Master Data Barang</h3>
                    <p class="text-muted small">Daftarkan identitas barang, SKU akan otomatis dibuat oleh sistem. Stok dikelola melalui menu <a href="penerimaan_barang.php" class="text-primary fw-bold">Penerimaan Barang</a>.</p>
                </div>
                <?php if ($role == 'ADMIN'): ?>
                <button class="btn btn-primary shadow-sm fw-bold" style="border-radius: 10px; padding: 10px 20px;" data-bs-toggle="modal" data-bs-target="#modalTambah">
                    <i class="fas fa-plus me-2"></i> Daftarkan Barang Baru
                </button>
                <?php endif; ?>
            </div>

            <div class="stock-card">
                <div class="table-responsive px-2 pb-2">
                    <table class="table table-hover align-middle" id="dataTableStok">
                        <thead>
                            <tr class="text-muted small uppercase">
                                <th>SKU</th>
                                <th>Barang</th>
                                <th>Kategori</th>
                                <th>Harga / Satuan</th>
                                <th width="100">Stok</th>
                                <th>Satuan</th>
                                <th>Status</th>
                                <?php if ($role == 'ADMIN'): ?>
                                <th class="text-center">Aksi</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $modals_html = '';
                            while($row = mysqli_fetch_assoc($query_stok)) : 
                                $status = ($row['stok'] > 10) ? 'AMAN' : 'MENIPIS';
                                $badge = ($status == 'AMAN') ? 'bg-success' : 'bg-danger';
                                $harga_rp = "Rp " . number_format((isset($row['harga']) ? $row['harga'] : 0), 0, ',', '.');
                            ?>
                            <tr>
                                <td><span class="badge bg-light text-secondary border" style="font-family: monospace; letter-spacing: 0.5px;"><?= htmlspecialchars($row['sku'] ?? '-'); ?></span></td>
                                <td>
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($row['nama_barang']); ?></div>
                                    <small class="text-muted">Update: <?= date('d/m/Y', strtotime($row['updated_at'])); ?></small>
                                </td>
                                <td><span class="badge bg-light text-dark border"><?= $row['kategori']; ?></span></td>
                                <td class="fw-bold text-success"><?= $harga_rp; ?></td>
                                <td class="fw-bold fs-5"><?= $row['stok']; ?></td>
                                <td><?= $row['satuan']; ?></td>
                                <td><span class="badge <?= $badge; ?>"><?= $status; ?></span></td>
                                <?php if ($role == 'ADMIN'): ?>
                                <td>
                                    <div class="d-flex justify-content-center gap-2">
                                        <button class="btn btn-sm btn-light text-info btn-cetak-barcode" data-sku="<?= htmlspecialchars($row['sku'] ?? ''); ?>" data-nama="<?= htmlspecialchars($row['nama_barang']); ?>" title="Cetak Barcode">
                                            <i class="fas fa-barcode"></i>
                                        </button>
                                        <button class="btn btn-sm btn-light text-primary" data-bs-toggle="modal" data-bs-target="#modalEdit<?= $row['id']; ?>" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <a href="stok_barang.php?hapus=<?= $row['id']; ?>" class="btn btn-sm btn-light text-danger" onclick="return confirm('PERHATIAN: Menghapus barang ini akan ikut menghapus seluruh riwayat penerimaan terkait.\n\nLanjutkan hapus?')" title="Hapus">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                                <?php endif; ?>
                            </tr>

                            <?php if ($role == 'ADMIN'): ob_start(); ?>
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
                                                    <label class="small fw-bold text-muted mb-2">SKU (Sistem)</label>
                                                    <input type="text" class="form-control form-control-sultan bg-light" value="<?= htmlspecialchars($row['sku'] ?? '-'); ?>" disabled>
                                                </div>
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
                                                <div class="mb-3">
                                                    <label class="small fw-bold text-muted mb-2">Satuan</label>
                                                    <select name="satuan" class="form-select form-control-sultan">
                                                        <option value="Unit" <?= ($row['satuan'] == 'Unit') ? 'selected' : ''; ?>>Unit</option>
                                                        <option value="Box" <?= ($row['satuan'] == 'Box') ? 'selected' : ''; ?>>Box</option>
                                                        <option value="Pcs" <?= ($row['satuan'] == 'Pcs') ? 'selected' : ''; ?>>Pcs</option>
                                                        <option value="Set" <?= ($row['satuan'] == 'Set') ? 'selected' : ''; ?>>Set</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="modal-footer border-0 pb-4 px-4">
                                                <button type="submit" name="edit_stok" class="btn btn-warning w-100 fw-bold" style="border-radius: 10px; padding: 12px; color: #000;">Simpan Perubahan</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <?php $modals_html .= ob_get_clean(); endif; ?>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <?= isset($modals_html) ? $modals_html : ''; ?>

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
                        <div class="mb-3">
                            <label class="small fw-bold text-muted mb-2">Satuan</label>
                            <select name="satuan" class="form-select form-control-sultan">
                                <option value="Unit">Unit</option>
                                <option value="Box">Box</option>
                                <option value="Pcs">Pcs</option>
                                <option value="Set">Set</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pb-4 px-4">
                        <button type="submit" name="tambah_stok" class="btn btn-primary w-100 fw-bold" style="border-radius: 10px; padding: 12px;">Daftarkan Barang</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#dataTableStok').DataTable({
                "language": { "search": "Cari Barang:", "lengthMenu": "Tampil _MENU_" }
            });

            // CETAK BARCODE
            $(document).on('click', '.btn-cetak-barcode', function() {
                var sku = $(this).data('sku');
                var nama = $(this).data('nama');
                if (!sku || sku === '-') {
                    alert('Barang ini belum memiliki kode SKU!');
                    return;
                }
                // Buat jendela cetak
                var win = window.open('', '_blank', 'width=420,height=350');
                win.document.write('<html><head><title>Cetak Barcode - ' + sku + '</title>');
                win.document.write('<style>body{font-family:"Plus Jakarta Sans",sans-serif;text-align:center;padding:30px;}h3{margin:0 0 5px;font-size:14px;color:#1a367e;}p{margin:0;font-size:11px;color:#666;}.sku-text{font-family:monospace;font-size:13px;font-weight:700;margin-top:8px;letter-spacing:1px;}.barcode-box{border:2px dashed #ccc;padding:20px;border-radius:12px;display:inline-block;margin-top:10px;}</style>');
                win.document.write('<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"><\/script>');
                win.document.write('</head><body>');
                win.document.write('<div class="barcode-box">');
                win.document.write('<h3>PT Elektronik Global</h3>');
                win.document.write('<p>' + nama + '</p>');
                win.document.write('<svg id="barcode-svg"></svg>');
                win.document.write('<div class="sku-text">' + sku + '</div>');
                win.document.write('</div>');
                win.document.write('<script>JsBarcode("#barcode-svg", "' + sku + '", {format:"CODE128",width:2,height:60,displayValue:false,margin:10});setTimeout(function(){window.print();},500);<\/script>');
                win.document.write('</body></html>');
                win.document.close();
            });
        });
    </script>
</body>
</html>