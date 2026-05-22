<?php
session_start();
require 'koneksi.php'; 

if (!isset($_SESSION['log'])) {
    header('location:index.php');
    exit();
}

$role = isset($_SESSION['role']) ? strtoupper($_SESSION['role']) : 'STAF';
if ($role == 'DRIVER') {
    header('location:dashboard_driver.php');
    exit();
}

// 1. TAMBAH KATEGORI
if (isset($_POST['tambah_kategori'])) {
    $nama_kategori = mysqli_real_escape_string($conn, $_POST['nama_kategori']);
    $query = mysqli_query($conn, "INSERT INTO kategori_barang (nama_kategori) VALUES ('$nama_kategori')");
    if($query) {
        echo "<script>alert('Kategori berhasil ditambahkan!'); window.location.href='kategori_barang.php';</script>";
    } else {
        echo "<script>alert('Gagal! Kategori mungkin sudah ada.'); window.location.href='kategori_barang.php';</script>";
    }
}

// 2. EDIT KATEGORI
if (isset($_POST['edit_kategori'])) {
    $id_kategori = mysqli_real_escape_string($conn, $_POST['id_kategori']);
    $nama_kategori_lama = mysqli_real_escape_string($conn, $_POST['nama_kategori_lama']);
    $nama_kategori = mysqli_real_escape_string($conn, $_POST['nama_kategori']);

    $update = mysqli_query($conn, "UPDATE kategori_barang SET nama_kategori='$nama_kategori' WHERE id='$id_kategori'");
    if ($update) {
        // Update juga nama kategori di stok_barang
        mysqli_query($conn, "UPDATE stok_barang SET kategori='$nama_kategori' WHERE kategori='$nama_kategori_lama'");
        echo "<script>alert('Data kategori berhasil diupdate!'); window.location.href='kategori_barang.php';</script>";
    } else {
        echo "<script>alert('Gagal update kategori!'); window.location.href='kategori_barang.php';</script>";
    }
}

// 3. HAPUS KATEGORI
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    mysqli_query($conn, "DELETE FROM kategori_barang WHERE id='$id'");
    echo "<script>alert('Kategori berhasil dihapus!'); window.location.href='kategori_barang.php';</script>";
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Data Kategori - Global Logistics</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet" />
    
    <style>
        :root {
            /* [CLEANED by sidebar-global.css] */--primary-blue: #1a367e;
            --accent-yellow: #ffcc00;
            --bg-light: #f8fafc;
        }

        body { font-family: 'Poppins', sans-serif; background-color: var(--bg-light); overflow-x: hidden; }

        /* --- CSS WAJIB LAYOUT --- */
        /* [CLEANED by sidebar-global.css] */
        /* [CLEANED by sidebar-global.css] */
        /* [CLEANED by sidebar-global.css] */
        
        @media (max-width: 768px) {
            /* [CLEANED by sidebar-global.css] */
            #sidebar.show { margin-left: 0; }
            /* [CLEANED by sidebar-global.css] */
            /* [CLEANED by sidebar-global.css] */
        }

        /* [CLEANED by sidebar-global.css] */
        /* [CLEANED by sidebar-global.css] */
        /* [CLEANED by sidebar-global.css] */
        /* [CLEANED by sidebar-global.css] */
        #sidebar ul li a:hover, #sidebar ul li.active > a { color: #fff; background: rgba(255,255,255,0.1); border-left: 4px solid var(--accent-yellow); }
        #sidebar ul li a i { width: 25px; text-align: center; }
        /* [CLEANED by sidebar-global.css] */

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
                    <h3 class="fw-bold mb-1" style="color: #1a367e;">Kategori Barang</h3>
                    <p class="text-muted small mb-0">Kelola master data kategori inventaris barang.</p>
                </div>
                
                <button class="btn btn-primary shadow-sm fw-bold" data-bs-toggle="modal" data-bs-target="#modalTambah" style="border-radius: 10px; padding: 10px 24px;">
                    <i class="fas fa-plus me-2"></i> Tambah Kategori
                </button>
            </div>

            <div class="table-container">
                <div class="table-responsive">
                    <table class="table table-hover align-middle" id="dataTableKategori" width="100%">
                        <thead>
                            <tr>
                                <th width="80" class="text-center">No</th>
                                <th>Nama Kategori</th>
                                <th class="text-center" width="150">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $res = mysqli_query($conn, "SELECT * FROM kategori_barang ORDER BY nama_kategori ASC");
                            $no = 1;
                            while($row = mysqli_fetch_assoc($res)){
                            ?>
                            <tr>
                                <td class="text-center"><?= $no++; ?></td>
                                <td><span class="fw-bold text-dark" style="letter-spacing: 0.5px;"><?= htmlspecialchars($row['nama_kategori']); ?></span></td>
                                
                                <td class="text-center">
                                    <button class="btn btn-action btn-action-edit me-1" data-bs-toggle="modal" data-bs-target="#modalEdit<?= $row['id']; ?>" title="Edit Kategori">
                                        <i class="fas fa-pen-to-square"></i>
                                    </button>
                                    <a href="kategori_barang.php?hapus=<?= $row['id']; ?>" class="btn btn-action btn-action-delete" onclick="return confirm('Hapus kategori ini? Data barang dengan kategori ini mungkin akan kehilangan kategorinya jika tidak diubah.')" title="Hapus Kategori">
                                        <i class="fas fa-trash-can"></i>
                                    </a>
                                </td>
                            </tr>

                            <div class="modal fade" id="modalEdit<?= $row['id']; ?>" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content" style="border-radius: 16px; border: none;">
                                        <form method="POST">
                                            <div class="modal-header border-0 pt-4 px-4">
                                                <h5 class="fw-bold text-dark">Update Kategori</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body px-4">
                                                <input type="hidden" name="id_kategori" value="<?= $row['id']; ?>">
                                                <input type="hidden" name="nama_kategori_lama" value="<?= htmlspecialchars($row['nama_kategori']); ?>">
                                                <div class="mb-3">
                                                    <label class="small fw-bold text-muted mb-2">Nama Kategori</label>
                                                    <input type="text" name="nama_kategori" class="form-control" style="border-radius: 10px;" value="<?= htmlspecialchars($row['nama_kategori']); ?>" required>
                                                </div>
                                            </div>
                                            <div class="modal-footer border-0 pb-4 px-4">
                                                <button type="submit" name="edit_kategori" class="btn btn-primary w-100 fw-bold" style="border-radius: 10px; padding: 12px;">Update Data</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalTambah" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius: 16px; border: none;">
                <form method="POST">
                    <div class="modal-header border-0 pt-4 px-4">
                        <h5 class="fw-bold text-dark">Daftarkan Kategori Baru</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body px-4">
                        <div class="mb-3">
                            <label class="small fw-bold text-muted mb-2">Nama Kategori</label>
                            <input type="text" name="nama_kategori" class="form-control" placeholder="Misal: Elektronik Dapur" required style="border-radius: 10px;">
                        </div>
                    </div>
                    <div class="modal-footer border-0 pb-4 px-4">
                        <button type="submit" name="tambah_kategori" class="btn btn-primary w-100 fw-bold" style="border-radius: 10px; padding: 12px;">Simpan Kategori</button>
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
            $('#dataTableKategori').DataTable({
                "language": { "search": "Cari Kategori:", "lengthMenu": "Tampil _MENU_" }
            });
        });

        document.getElementById('sidebarToggle')?.addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('show');
        });
    </script>
</body>
</html>
