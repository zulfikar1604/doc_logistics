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

if ((isset($_GET['edit']) || isset($_GET['buat'])) && $role != 'ADMIN') {
    header('location:manajemen_rute.php');
    exit();
}

// Ambil semua rute aktif
$q_routes = mysqli_query($conn, "SELECT r.*, (SELECT COUNT(*) FROM route_details WHERE id_route=r.id) as jml_checkpoint FROM routes r WHERE r.is_active=1 ORDER BY r.tipe_rute ASC, r.nama_rute ASC");

// Ambil master points aktif untuk dropdown
$q_mp = mysqli_query($conn, "SELECT * FROM master_points WHERE is_active=1 ORDER BY nama_kota ASC");
$master_points = [];
while($mp = mysqli_fetch_assoc($q_mp)) $master_points[] = $mp;

// Mode edit?
$edit_mode = false;
$edit_route = null;
$edit_details = [];
if (isset($_GET['edit'])) {
    $edit_mode = true;
    $eid = mysqli_real_escape_string($conn, $_GET['edit']);
    $edit_route = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM routes WHERE id='$eid'"));
    $q_ed = mysqli_query($conn, "SELECT rd.*, mp.nama_kota, mp.latitude, mp.longitude FROM route_details rd JOIN master_points mp ON rd.id_master_point=mp.id WHERE rd.id_route='$eid' ORDER BY rd.sort_order ASC");
    while($ed = mysqli_fetch_assoc($q_ed)) $edit_details[] = $ed;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Manajemen Rute - Global Logistics</title>
    <link rel="icon" type="image/png" href="assets/img/icon-192-nobg.png?v=4">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        :root { --primary-blue: #1a367e; --accent-yellow: #ffcc00; --bg-light: #f4f7f6; }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: var(--bg-light); overflow-x: hidden; }
        #sidebar ul li a:hover, #sidebar ul li.active > a { color: #fff; background: rgba(255,255,255,0.1); border-left: 4px solid var(--accent-yellow); }
        #sidebar ul li a i { width: 25px; text-align: center; }
        .card-sultan { background: white; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.04); border: 1px solid rgba(0,0,0,0.05); overflow: hidden; }
        .header-sultan { background: #f8fafc; border-bottom: 1px solid #e2e8f0; padding: 20px 25px; }
        .form-control-sultan { border-radius: 10px; border: 1.5px solid #e0e6ed; padding: 10px 15px; background-color: #fcfdfe; font-size: 0.9rem; }
        .table thead th { background-color: #f1f5f9; border: none; color: #64748b; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; padding: 14px; }
        .table tbody td { padding: 14px; vertical-align: middle; border-bottom: 1px solid #f1f5f9; font-size: 0.88rem; }
        .btn-sultan { background: linear-gradient(135deg, var(--primary-blue) 0%, #2b59ff 100%); color: white; border: none; border-radius: 10px; padding: 10px 24px; font-weight: 600; transition: all 0.3s; font-size: 0.88rem; }
        .btn-sultan:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(43,89,255,0.2); color: white; }
        .badge-pergi { background: #dbeafe; color: #1d4ed8; padding: 5px 12px; border-radius: 8px; font-weight: 700; font-size: 0.72rem; }
        .badge-pulang { background: #fef3c7; color: #b45309; padding: 5px 12px; border-radius: 8px; font-weight: 700; font-size: 0.72rem; }
        .checkpoint-list { list-style: none; padding: 0; margin: 0; }
        .checkpoint-item { background: #f8fafc; border: 1.5px solid #e2e8f0; border-radius: 12px; padding: 12px 16px; margin-bottom: 8px; cursor: grab; display: flex; align-items: center; gap: 12px; transition: all 0.2s; }
        .checkpoint-item:hover { border-color: #3b82f6; box-shadow: 0 4px 12px rgba(59,130,246,0.1); }
        .checkpoint-item:active, .checkpoint-item.sortable-chosen { cursor: grabbing; background: #eff6ff; border-color: #3b82f6; box-shadow: 0 8px 20px rgba(59,130,246,0.15); }
        .checkpoint-item .drag-handle { color: #94a3b8; font-size: 1.1rem; }
        .checkpoint-item .cp-number { width: 28px; height: 28px; border-radius: 50%; background: var(--primary-blue); color: white; display: flex; align-items: center; justify-content: center; font-size: 0.75rem; font-weight: 800; flex-shrink: 0; }
        .checkpoint-item .cp-name { font-weight: 700; color: #1e293b; flex-grow: 1; }
        .summary-box { background: linear-gradient(135deg, #1a367e 0%, #2b59ff 100%); border-radius: 16px; padding: 20px; color: white; }
        .summary-box .label { font-size: 0.72rem; text-transform: uppercase; letter-spacing: 1px; opacity: 0.7; margin-bottom: 4px; }
        .summary-box .value { font-size: 1.4rem; font-weight: 800; }
        #map-route { height: 320px; border-radius: 14px; border: 2px solid #e2e8f0; }
        .ghost { opacity: 0.4; }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <div id="main-content">
        <?php include 'navbar.php'; ?>
        <div class="container-fluid p-4 flex-grow-1 mb-5">

            <?php if(!$edit_mode && !isset($_GET['buat'])): ?>
            <!-- ============ DAFTAR RUTE ============ -->
            <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap">
                <div>
                    <h3 class="fw-bold mb-1" style="color:#1a367e;">Manajemen Rute</h3>
                    <p class="text-muted small mb-0">Kelola rute perjalanan dan checkpoint. Rute pulang otomatis tergenerate.</p>
                </div>
                <?php if ($role == 'ADMIN'): ?>
                <a href="manajemen_rute.php?buat=1" class="btn btn-sultan mt-2 mt-md-0"><i class="fas fa-plus me-2"></i>Buat Rute Baru</a>
                <?php endif; ?>
            </div>

            <div class="card-sultan p-0">
                <div class="header-sultan"><h6 class="fw-bold mb-0"><i class="fas fa-route text-primary me-2"></i>Daftar Rute Aktif</h6></div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead><tr><th>#</th><th>Nama Rute</th><th>Tipe</th><th class="text-center">Checkpoint</th><th class="text-end">Jarak (KM)</th><th class="text-end">Biaya Rute</th><th class="text-center">Aksi</th></tr></thead>
                        <tbody>
                            <?php $no=1; while($r = mysqli_fetch_assoc($q_routes)): ?>
                            <tr>
                                <td class="fw-bold text-muted"><?= $no++; ?></td>
                                <td><span class="fw-bold text-dark"><?= htmlspecialchars($r['nama_rute']); ?></span></td>
                                <td><?php if($r['tipe_rute']=='PERGI'): ?><span class="badge-pergi"><i class="fas fa-arrow-right me-1"></i>Pergi</span><?php else: ?><span class="badge-pulang"><i class="fas fa-arrow-left me-1"></i>Pulang</span><?php endif; ?></td>
                                <td class="text-center"><span class="badge bg-light text-dark border" style="border-radius:8px;font-weight:700;"><?= $r['jml_checkpoint']; ?> Kota</span></td>
                                <td class="text-end fw-bold"><?= number_format($r['total_jarak_km'],1,',','.'); ?> km</td>
                                <td class="text-end fw-bold text-primary">Rp <?= number_format($r['total_biaya_rute'],0,',','.'); ?></td>
                                <td class="text-center">
                                    <?php if($r['tipe_rute']=='PERGI'): ?>
                                        <?php if ($role == 'ADMIN'): ?>
                                        <a href="manajemen_rute.php?edit=<?= $r['id']; ?>" class="btn btn-sm btn-light border me-1" title="Edit"><i class="fas fa-edit text-primary"></i></a>
                                        <a href="proses_rute.php?hapus_rute=<?= $r['id']; ?>" class="btn btn-sm btn-light border" title="Hapus" onclick="return confirm('Hapus rute ini beserta rute pulangnya?')"><i class="fas fa-trash text-danger"></i></a>
                                        <?php else: ?>
                                        <span class="text-muted small"><i class="fas fa-eye me-1"></i>Detail (Read-only)</span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                    <span class="text-muted small"><i class="fas fa-lock me-1"></i>Auto</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <?php elseif(isset($_GET['buat']) || $edit_mode): ?>
            <!-- ============ FORM BUAT / EDIT RUTE ============ -->
            <div class="mb-4">
                <a href="manajemen_rute.php" class="btn btn-sm btn-light border shadow-sm mb-2" style="border-radius:8px;"><i class="fas fa-arrow-left me-2"></i>Kembali</a>
                <h3 class="fw-bold mb-0" style="color:#1a367e;"><?= $edit_mode ? 'Edit Rute' : 'Buat Rute Baru'; ?></h3>
                <p class="text-muted small">Pilih checkpoint dari master data, lalu atur urutannya dengan drag-and-drop.</p>
            </div>

            <form method="POST" action="proses_rute.php" id="formRute">
                <?php if($edit_mode): ?>
                <input type="hidden" name="id_route" value="<?= $edit_route['id']; ?>">
                <?php endif; ?>
                
                <div class="row">
                    <!-- Kolom Kiri: Form + Checkpoint List -->
                    <div class="col-xl-6 col-lg-6 mb-4">
                        <div class="card-sultan p-4 mb-4">
                            <label class="form-label fw-bold small text-muted text-uppercase">Nama Rute</label>
                            <input type="text" name="nama_rute" class="form-control form-control-sultan" placeholder="Contoh: Jakarta → Ponorogo" value="<?= $edit_mode ? htmlspecialchars($edit_route['nama_rute']) : ''; ?>" required>
                        </div>

                        <div class="card-sultan p-4 mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="fw-bold mb-0"><i class="fas fa-map-signs text-primary me-2"></i>Checkpoint Rute</h6>
                            </div>
                            <div class="d-flex gap-2 mb-3">
                                <select id="selectKota" class="form-select form-control-sultan flex-grow-1">
                                    <option value="">-- Pilih Kota --</option>
                                    <?php foreach($master_points as $mp): ?>
                                    <option value="<?= $mp['id']; ?>" data-nama="<?= htmlspecialchars($mp['nama_kota']); ?>" data-lat="<?= $mp['latitude']; ?>" data-lng="<?= $mp['longitude']; ?>"><?= htmlspecialchars($mp['nama_kota']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="button" class="btn btn-sultan" id="btnTambahCP" style="white-space:nowrap;"><i class="fas fa-plus me-1"></i>Tambah</button>
                            </div>

                            <div class="alert alert-light border small py-2" style="border-radius:10px;">
                                <i class="fas fa-hand-pointer text-primary me-1"></i> Drag & drop untuk mengubah urutan checkpoint.
                            </div>

                            <ul class="checkpoint-list" id="checkpointList">
                                <?php if($edit_mode): foreach($edit_details as $idx => $ed): ?>
                                <li class="checkpoint-item" data-id="<?= $ed['id_master_point']; ?>" data-lat="<?= $ed['latitude']; ?>" data-lng="<?= $ed['longitude']; ?>">
                                    <i class="fas fa-grip-vertical drag-handle"></i>
                                    <div class="cp-number"><?= $idx+1; ?></div>
                                    <div class="cp-name"><?= htmlspecialchars($ed['nama_kota']); ?></div>
                                    <input type="hidden" name="checkpoint[]" value="<?= $ed['id_master_point']; ?>">
                                    <input type="number" name="jarak[]" class="form-control form-control-sm jarak-input" style="width:90px;border-radius:8px;text-align:center;" value="<?= $ed['jarak_dari_sebelumnya']; ?>" placeholder="KM" step="0.1" min="0">
                                    <span class="small text-muted">km</span>
                                    <button type="button" class="btn btn-sm text-danger btn-hapus-cp" title="Hapus"><i class="fas fa-times"></i></button>
                                </li>
                                <?php endforeach; endif; ?>
                            </ul>
                        </div>

                        <!-- Summary -->
                        <div class="summary-box mb-4">
                            <div class="row text-center">
                                <div class="col-4">
                                    <div class="label">Checkpoint</div>
                                    <div class="value" id="summaryCP">0</div>
                                </div>
                                <div class="col-4">
                                    <div class="label">Total Jarak</div>
                                    <div class="value"><span id="summaryKM">0</span> km</div>
                                </div>
                                <div class="col-4">
                                    <div class="label">Biaya Rute</div>
                                    <div class="value">Rp <span id="summaryBiaya">0</span></div>
                                </div>
                            </div>
                        </div>

                        <button type="submit" name="<?= $edit_mode ? 'update_rute' : 'simpan_rute'; ?>" class="btn btn-sultan w-100 py-3">
                            <i class="fas fa-save me-2"></i><?= $edit_mode ? 'Perbarui Rute' : 'Simpan Rute + Generate Rute Pulang'; ?>
                        </button>
                    </div>

                    <!-- Kolom Kanan: Peta Preview -->
                    <div class="col-xl-6 col-lg-6 mb-4">
                        <div class="card-sultan p-4 h-100">
                            <h6 class="fw-bold mb-3"><i class="fas fa-map text-success me-2"></i>Preview Rute di Peta</h6>
                            <div id="map-route"></div>
                            <div class="small text-muted mt-2"><i class="fas fa-info-circle me-1"></i> Peta otomatis memperbarui saat checkpoint ditambah/diubah urutannya.</div>
                        </div>
                    </div>
                </div>
            </form>
            <?php endif; ?>

        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

    <script>
    <?php if(isset($_GET['buat']) || $edit_mode): ?>
    // ====================================
    // MAP SETUP
    // ====================================
    var map = L.map('map-route').setView([-6.9, 110.0], 6);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '&copy; OpenStreetMap' }).addTo(map);

    var markers = [];
    var polyline = null;

    function updateMap() {
        // Clear old
        markers.forEach(m => map.removeLayer(m));
        markers = [];
        if (polyline) map.removeLayer(polyline);

        var items = document.querySelectorAll('#checkpointList .checkpoint-item');
        var latlngs = [];

        items.forEach(function(item, idx) {
            var lat = parseFloat(item.dataset.lat);
            var lng = parseFloat(item.dataset.lng);
            var name = item.querySelector('.cp-name').textContent;
            latlngs.push([lat, lng]);

            var color = idx === 0 ? '#f59e0b' : (idx === items.length - 1 ? '#ef4444' : '#3b82f6');
            var icon = L.divIcon({
                className: '',
                html: '<div style="width:32px;height:32px;border-radius:50%;background:'+color+';color:white;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:12px;border:3px solid white;box-shadow:0 2px 8px rgba(0,0,0,0.3);">'+(idx+1)+'</div>',
                iconSize: [32, 32], iconAnchor: [16, 16]
            });
            var m = L.marker([lat, lng], {icon: icon}).addTo(map).bindPopup('<strong>'+name+'</strong><br>Checkpoint #'+(idx+1));
            markers.push(m);
        });

        if (latlngs.length >= 2) {
            polyline = L.polyline(latlngs, { color: '#1a367e', weight: 4, opacity: 0.7, dashArray: '10 6' }).addTo(map);
            map.fitBounds(polyline.getBounds().pad(0.15));
        } else if (latlngs.length === 1) {
            map.setView(latlngs[0], 10);
        }
    }

    function updateNumbers() {
        var items = document.querySelectorAll('#checkpointList .checkpoint-item');
        items.forEach(function(item, idx) {
            item.querySelector('.cp-number').textContent = idx + 1;
        });
    }

    function updateSummary() {
        var items = document.querySelectorAll('#checkpointList .checkpoint-item');
        var totalKM = 0;
        items.forEach(function(item) {
            var input = item.querySelector('.jarak-input');
            totalKM += parseFloat(input.value) || 0;
        });
        document.getElementById('summaryCP').textContent = items.length;
        document.getElementById('summaryKM').textContent = totalKM.toFixed(1);
        document.getElementById('summaryBiaya').textContent = new Intl.NumberFormat('id-ID').format(totalKM * 5000);
    }

    // Sortable.js initialization
    var sortable = Sortable.create(document.getElementById('checkpointList'), {
        handle: '.drag-handle',
        animation: 200,
        ghostClass: 'ghost',
        onEnd: function() {
            updateNumbers();
            updateMap();
            updateSummary();
        }
    });

    // Add checkpoint
    document.getElementById('btnTambahCP').addEventListener('click', function() {
        var sel = document.getElementById('selectKota');
        var opt = sel.options[sel.selectedIndex];
        if (!opt.value) return alert('Pilih kota terlebih dahulu!');

        var id = opt.value, nama = opt.dataset.nama, lat = opt.dataset.lat, lng = opt.dataset.lng;

        // Check duplicate
        var existing = document.querySelectorAll('#checkpointList .checkpoint-item');
        for (var i = 0; i < existing.length; i++) {
            if (existing[i].dataset.id == id) return alert('Kota ini sudah ada di daftar!');
        }

        var num = existing.length + 1;
        var li = document.createElement('li');
        li.className = 'checkpoint-item';
        li.dataset.id = id;
        li.dataset.lat = lat;
        li.dataset.lng = lng;
        li.innerHTML = '<i class="fas fa-grip-vertical drag-handle"></i>' +
            '<div class="cp-number">'+num+'</div>' +
            '<div class="cp-name">'+nama+'</div>' +
            '<input type="hidden" name="checkpoint[]" value="'+id+'">' +
            '<input type="number" name="jarak[]" class="form-control form-control-sm jarak-input" style="width:90px;border-radius:8px;text-align:center;" value="0" placeholder="KM" step="0.1" min="0">' +
            '<span class="small text-muted">km</span>' +
            '<button type="button" class="btn btn-sm text-danger btn-hapus-cp" title="Hapus"><i class="fas fa-times"></i></button>';

        document.getElementById('checkpointList').appendChild(li);
        sel.value = '';
        updateMap();
        updateSummary();
    });

    // Delete checkpoint
    $(document).on('click', '.btn-hapus-cp', function() {
        $(this).closest('.checkpoint-item').remove();
        updateNumbers();
        updateMap();
        updateSummary();
    });

    // Distance input change
    $(document).on('input', '.jarak-input', function() { updateSummary(); });

    // Init on page load
    updateMap();
    updateSummary();
    <?php endif; ?>
    </script>
</body>
</html>
