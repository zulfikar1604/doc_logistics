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

// =============================================
// PROSES CRUD MASTER POINTS
// =============================================

// TAMBAH KOTA BARU
if (isset($_POST['tambah_kota']) && $_SESSION['role'] == 'ADMIN') {
    $nama  = mysqli_real_escape_string($conn, $_POST['nama_kota']);
    $lat   = mysqli_real_escape_string($conn, $_POST['latitude']);
    $lng   = mysqli_real_escape_string($conn, $_POST['longitude']);
    mysqli_query($conn, "INSERT INTO master_points (nama_kota, latitude, longitude) VALUES ('$nama', '$lat', '$lng')");
    echo "<script>alert('Kota berhasil ditambahkan!'); window.location.href='master_points.php';</script>";
    exit();
}

// UPDATE KOTA
if (isset($_POST['update_kota']) && $_SESSION['role'] == 'ADMIN') {
    $id    = mysqli_real_escape_string($conn, $_POST['id']);
    $nama  = mysqli_real_escape_string($conn, $_POST['nama_kota']);
    $lat   = mysqli_real_escape_string($conn, $_POST['latitude']);
    $lng   = mysqli_real_escape_string($conn, $_POST['longitude']);
    mysqli_query($conn, "UPDATE master_points SET nama_kota='$nama', latitude='$lat', longitude='$lng' WHERE id='$id'");
    echo "<script>alert('Data kota berhasil diperbarui!'); window.location.href='master_points.php';</script>";
    exit();
}

// TOGGLE AKTIF / NONAKTIF
if (isset($_GET['toggle']) && $_SESSION['role'] == 'ADMIN') {
    $id = mysqli_real_escape_string($conn, $_GET['toggle']);
    $q  = mysqli_query($conn, "SELECT is_active FROM master_points WHERE id='$id'");
    $d  = mysqli_fetch_assoc($q);
    $new = ($d['is_active'] == 1) ? 0 : 1;
    mysqli_query($conn, "UPDATE master_points SET is_active='$new' WHERE id='$id'");
    header('location:master_points.php');
    exit();
}

// HAPUS KOTA
if (isset($_GET['hapus']) && $_SESSION['role'] == 'ADMIN') {
    $id = mysqli_real_escape_string($conn, $_GET['hapus']);
    // Cek apakah kota sedang dipakai di rute
    $cek = mysqli_query($conn, "SELECT COUNT(*) as total FROM route_details WHERE id_master_point='$id'");
    $data_cek = mysqli_fetch_assoc($cek);
    if ($data_cek['total'] > 0) {
        echo "<script>alert('Kota ini sedang digunakan di rute aktif. Tidak bisa dihapus, nonaktifkan saja.'); window.location.href='master_points.php';</script>";
        exit();
    }
    mysqli_query($conn, "DELETE FROM master_points WHERE id='$id'");
    echo "<script>alert('Kota berhasil dihapus!'); window.location.href='master_points.php';</script>";
    exit();
}

// AMBIL DATA UNTUK EDIT
$edit_data = null;
if (isset($_GET['edit'])) {
    $id_edit = mysqli_real_escape_string($conn, $_GET['edit']);
    $q_edit  = mysqli_query($conn, "SELECT * FROM master_points WHERE id='$id_edit'");
    $edit_data = mysqli_fetch_assoc($q_edit);
}

// AMBIL SEMUA DATA
$query_kota = mysqli_query($conn, "SELECT * FROM master_points ORDER BY nama_kota ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Master Checkpoint - Global Logistics</title>
    <link rel="icon" type="image/png" href="assets/img/icon-192-nobg.png?v=4">
    
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    
    <style>
        :root {
            --primary-blue: #1a367e;
            --accent-yellow: #ffcc00;
            --bg-light: #f4f7f6;
        }

        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: var(--bg-light); overflow-x: hidden; }

        #sidebar ul li a:hover, #sidebar ul li.active > a { color: #fff; background: rgba(255,255,255,0.1); border-left: 4px solid var(--accent-yellow); }
        #sidebar ul li a i { width: 25px; text-align: center; }

        .card-sultan { background: white; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.04); border: 1px solid rgba(0,0,0,0.05); overflow: hidden;}
        .header-sultan { background: #f8fafc; border-bottom: 1px solid #e2e8f0; padding: 20px 25px; }
        .form-control-sultan { border-radius: 10px; border: 1.5px solid #e0e6ed; padding: 10px 15px; background-color: #fcfdfe; font-size: 0.9rem; }
        


        #map-preview { height: 280px; border-radius: 14px; border: 2px solid #e2e8f0; z-index: 1; }

        .btn-sultan { background: linear-gradient(135deg, var(--primary-blue) 0%, #2b59ff 100%); color: white; border: none; border-radius: 10px; padding: 10px 24px; font-weight: 600; transition: all 0.3s; font-size: 0.88rem; }
        .btn-sultan:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(43, 89, 255, 0.2); color: white; }

        .coord-badge { background: #eff6ff; color: #2563eb; padding: 4px 10px; border-radius: 6px; font-size: 0.78rem; font-weight: 600; font-family: 'Courier New', monospace; }
    </style>
</head>
<body>

    <?php include 'sidebar.php'; ?>

    <div id="main-content">
        <?php include 'navbar.php'; ?>

        <div class="container-fluid p-4 flex-grow-1 mb-5">
            
            <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap">
                <div>
                    <h3 class="fw-bold mb-1" style="color: #1a367e;">Master Checkpoint (Kota)</h3>
                    <p class="text-muted small mb-0">Kelola data koordinat kota yang digunakan sebagai checkpoint rute pengiriman.</p>
                </div>
                <?php if ($role == 'ADMIN'): ?>
                <button class="btn btn-sultan mt-2 mt-md-0" data-bs-toggle="modal" data-bs-target="#modalTambah">
                    <i class="fas fa-plus me-2"></i> Tambah Kota Baru
                </button>
                <?php endif; ?>
            </div>

            <div class="row">
                <!-- Kolom Kiri: Tabel Data -->
                <div class="col-xl-7 col-lg-6 mb-4">
                    <div class="card-sultan p-0">
                        <div class="header-sultan d-flex justify-content-between align-items-center">
                            <h6 class="fw-bold mb-0"><i class="fas fa-globe-asia text-primary me-2"></i> Daftar Kota Terdaftar</h6>
                            <span class="badge bg-primary bg-opacity-10 text-primary" style="border-radius:8px; padding:6px 12px; font-weight:700; font-size:0.75rem;">
                                <?= mysqli_num_rows($query_kota); ?> Kota
                            </span>
                        </div>
                        <div class="table-responsive px-3 pb-3">
                            <table class="table table-hover mb-0 align-middle">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Nama Kota</th>
                                        <th>Koordinat</th>
                                        <th class="text-center">Status</th>
                                        <?php if ($role == 'ADMIN'): ?>
                                        <th class="text-center">Aksi</th>
                                        <?php endif; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $no = 1; mysqli_data_seek($query_kota, 0); while($k = mysqli_fetch_assoc($query_kota)): ?>
                                    <tr>
                                        <td class="fw-bold text-muted"><?= $no++; ?></td>
                                        <td>
                                            <span class="fw-bold text-dark"><i class="fas fa-map-pin text-danger me-1 opacity-50"></i> <?= htmlspecialchars($k['nama_kota']); ?></span>
                                        </td>
                                        <td>
                                            <span class="coord-badge"><?= $k['latitude']; ?>, <?= $k['longitude']; ?></span>
                                        </td>
                                        <td class="text-center">
                                            <?php if($k['is_active']): ?>
                                                <span class="badge bg-success"><i class="fas fa-check-circle me-1"></i>Aktif</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger"><i class="fas fa-times-circle me-1"></i>Nonaktif</span>
                                            <?php endif; ?>
                                        </td>
                                        <?php if ($role == 'ADMIN'): ?>
                                        <td>
                                            <div class="d-flex justify-content-center gap-2">
                                                <a href="master_points.php?edit=<?= $k['id']; ?>" class="btn btn-sm btn-light text-primary" title="Edit"><i class="fas fa-edit"></i></a>
                                                <a href="master_points.php?toggle=<?= $k['id']; ?>" class="btn btn-sm btn-light text-warning" title="Toggle Aktif" onclick="return confirm('Toggle status kota ini?')"><i class="fas fa-power-off"></i></a>
                                                <a href="master_points.php?hapus=<?= $k['id']; ?>" class="btn btn-sm btn-light text-danger" title="Hapus" onclick="return confirm('Yakin hapus kota ini? Pastikan tidak digunakan di rute manapun.')"><i class="fas fa-trash"></i></a>
                                            </div>
                                        </td>
                                        <?php endif; ?>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Kolom Kanan: Peta Preview Semua Kota -->
                <div class="col-xl-5 col-lg-6 mb-4">
                    <div class="card-sultan p-4 h-100">
                        <h6 class="fw-bold mb-3"><i class="fas fa-map-marked-alt text-success me-2"></i> Peta Sebaran Checkpoint</h6>
                        <div id="map-all" style="height: 460px; border-radius: 14px; border: 2px solid #e2e8f0;"></div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- MODAL TAMBAH KOTA -->
    <div class="modal fade" id="modalTambah" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content" style="border-radius: 24px; border: none;">
                <form method="POST">
                    <div class="modal-header border-0 pt-4 px-4">
                        <h5 class="fw-bold mb-0 text-dark"><i class="fas fa-plus-circle text-primary me-2"></i>Tambah Kota Baru</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="alert alert-info small py-2 border-0" style="border-radius:10px;">
                            <i class="fas fa-info-circle me-1"></i> Klik pada peta di bawah untuk auto-fill koordinat, atau masukkan secara manual.
                        </div>
                        <div class="row">
                            <div class="col-12 mb-3">
                                <label class="form-label fw-bold small text-muted">NAMA KOTA / TITIK</label>
                                <input type="text" name="nama_kota" class="form-control form-control-sultan" placeholder="Contoh: Semarang" required>
                            </div>
                            <div class="col-6 mb-3">
                                <label class="form-label fw-bold small text-muted">LATITUDE</label>
                                <input type="text" name="latitude" id="add-lat" class="form-control form-control-sultan" placeholder="-6.966610" required>
                            </div>
                            <div class="col-6 mb-3">
                                <label class="form-label fw-bold small text-muted">LONGITUDE</label>
                                <input type="text" name="longitude" id="add-lng" class="form-control form-control-sultan" placeholder="110.419630" required>
                            </div>
                            <div class="col-12">
                                <div id="map-preview" style="height:260px;"></div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pb-4 px-4 pt-0">
                        <button type="submit" name="tambah_kota" class="btn btn-sultan w-100"><i class="fas fa-save me-2"></i>Simpan Kota</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL EDIT KOTA -->
    <?php if($edit_data): ?>
    <div class="modal fade show" id="modalEdit" tabindex="-1" style="display:block; background:rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content" style="border-radius: 24px; border: none;">
                <form method="POST">
                    <input type="hidden" name="id" value="<?= $edit_data['id']; ?>">
                    <div class="modal-header border-0 pt-4 px-4">
                        <h5 class="fw-bold mb-0 text-dark"><i class="fas fa-edit text-warning me-2"></i>Edit Kota</h5>
                        <a href="master_points.php" class="btn-close"></a>
                    </div>
                    <div class="modal-body p-4">
                        <div class="row">
                            <div class="col-12 mb-3">
                                <label class="form-label fw-bold small text-muted">NAMA KOTA</label>
                                <input type="text" name="nama_kota" class="form-control form-control-sultan" value="<?= htmlspecialchars($edit_data['nama_kota']); ?>" required>
                            </div>
                            <div class="col-6 mb-3">
                                <label class="form-label fw-bold small text-muted">LATITUDE</label>
                                <input type="text" name="latitude" id="edit-lat" class="form-control form-control-sultan" value="<?= $edit_data['latitude']; ?>" required>
                            </div>
                            <div class="col-6 mb-3">
                                <label class="form-label fw-bold small text-muted">LONGITUDE</label>
                                <input type="text" name="longitude" id="edit-lng" class="form-control form-control-sultan" value="<?= $edit_data['longitude']; ?>" required>
                            </div>
                            <div class="col-12">
                                <div id="map-edit" style="height:260px; border-radius:14px; border:2px solid #e2e8f0;"></div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pb-4 px-4 pt-0">
                        <button type="submit" name="update_kota" class="btn btn-sultan w-100"><i class="fas fa-save me-2"></i>Perbarui Data</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <script>
    // ====================================
    // PETA UTAMA: Semua Kota
    // ====================================
    var mapAll = L.map('map-all').setView([-6.9, 110.0], 6);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap'
    }).addTo(mapAll);

    <?php 
    mysqli_data_seek($query_kota, 0); 
    while($mk = mysqli_fetch_assoc($query_kota)): 
        if($mk['is_active']):
    ?>
    L.circleMarker([<?= $mk['latitude']; ?>, <?= $mk['longitude']; ?>], {
        radius: 7, fillColor: '#3b82f6', color: '#1e40af', weight: 2, opacity: 1, fillOpacity: 0.8
    }).addTo(mapAll).bindPopup('<strong><?= addslashes($mk['nama_kota']); ?></strong><br><small><?= $mk['latitude']; ?>, <?= $mk['longitude']; ?></small>');
    <?php endif; endwhile; ?>

    // ====================================
    // PETA MODAL TAMBAH: Klik untuk ambil koordinat
    // ====================================
    var mapPreview, markerPreview;
    document.getElementById('modalTambah').addEventListener('shown.bs.modal', function() {
        setTimeout(function() {
            if (!mapPreview) {
                mapPreview = L.map('map-preview').setView([-6.9, 110.0], 6);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; OpenStreetMap'
                }).addTo(mapPreview);

                mapPreview.on('click', function(e) {
                    var lat = e.latlng.lat.toFixed(7);
                    var lng = e.latlng.lng.toFixed(7);
                    document.getElementById('add-lat').value = lat;
                    document.getElementById('add-lng').value = lng;
                    
                    if (markerPreview) mapPreview.removeLayer(markerPreview);
                    markerPreview = L.marker([lat, lng]).addTo(mapPreview)
                        .bindPopup('Lokasi dipilih: ' + lat + ', ' + lng).openPopup();
                });
            } else {
                mapPreview.invalidateSize();
            }
        }, 300);
    });

    // ====================================
    // PETA MODAL EDIT
    // ====================================
    <?php if($edit_data): ?>
    setTimeout(function() {
        var mapEdit = L.map('map-edit').setView([<?= $edit_data['latitude']; ?>, <?= $edit_data['longitude']; ?>], 10);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap'
        }).addTo(mapEdit);

        var markerEdit = L.marker([<?= $edit_data['latitude']; ?>, <?= $edit_data['longitude']; ?>]).addTo(mapEdit)
            .bindPopup('<strong><?= addslashes($edit_data['nama_kota']); ?></strong>').openPopup();

        mapEdit.on('click', function(e) {
            var lat = e.latlng.lat.toFixed(7);
            var lng = e.latlng.lng.toFixed(7);
            document.getElementById('edit-lat').value = lat;
            document.getElementById('edit-lng').value = lng;
            markerEdit.setLatLng([lat, lng]).bindPopup('Lokasi baru: ' + lat + ', ' + lng).openPopup();
        });
    }, 500);
    <?php endif; ?>
    </script>

</body>
</html>
