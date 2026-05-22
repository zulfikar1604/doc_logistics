<?php
session_start();
require 'koneksi.php';

if (!isset($_SESSION['log'])) { header('location:index.php'); exit(); }

$id_tracking = isset($_GET['order']) ? $_GET['order'] : '';

$query_tracking = mysqli_query($conn, "
    SELECT sj.id, sj.no_sj, sj.kota_tujuan, sj.alamat_tujuan, sj.tgl_kirim, sj.status, sj.id_route, sj.id_return_route,
           l.nama_lengkap as nama_driver, a.nopol 
    FROM surat_jalan sj 
    JOIN driver d ON sj.id_driver = d.id 
    JOIN login l ON d.id_login = l.id 
    JOIN armada a ON sj.id_armada = a.id
    WHERE sj.status IN ('ON TRIP','DELIVERED')
    ORDER BY sj.status ASC, sj.id DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Live Tracking - Global Logistics</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        :root { --primary-blue: #1a367e; --accent-yellow: #ffcc00; --bg-light: #f4f7f6; }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: var(--bg-light); overflow-x: hidden; }
        #sidebar ul li a:hover, #sidebar ul li.active > a { color: #fff; background: rgba(255,255,255,0.1); border-left: 4px solid var(--accent-yellow); }
        #sidebar ul li a i { width: 25px; text-align: center; }
        .card-sultan { background: white; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.04); border: 1px solid rgba(0,0,0,0.05); overflow: hidden;}
        .header-sultan { background: #f8fafc; border-bottom: 1px solid #e2e8f0; padding: 20px 25px; }
        .status-pill { border-radius: 999px; padding: 6px 12px; font-weight: 800; font-size: 10px; }
        .anim-blink { animation: blinker 1.5s linear infinite; }
        @keyframes blinker { 50% { opacity: 0.3; } }
        .vehicle-item { padding: 15px; border-radius: 12px; border: 1px solid #edf2f7; margin-bottom: 12px; transition: all 0.2s; background: white; text-decoration: none; color: inherit; display: block;}
        .vehicle-item:hover { border-color: var(--primary-blue); box-shadow: 0 5px 15px rgba(26,54,126,0.05); }
        .vehicle-item.active { background: var(--primary-blue); color: white; border-color: var(--primary-blue); }
        .vehicle-item.active .text-muted, .vehicle-item.active .text-primary { color: rgba(255,255,255,0.8) !important; }
        .vehicle-item.active .fw-bold { color: white !important; }
        #map-tracking { height: 500px; border-radius: 0 0 20px 20px; }
        .timeline-container { background: #f8fafc; border-radius: 16px; border: 1px solid #e2e8f0; padding: 25px; }
        .timeline-sultan { border-left: 3px solid #e5e7eb; padding-left: 25px; list-style: none; margin-left: 15px; margin-bottom: 0; padding-top: 5px; }
        .timeline-sultan li { margin-bottom: 25px; position: relative; }
        .timeline-sultan li:last-child { margin-bottom: 0; }
        .timeline-sultan li::before { content: ''; position: absolute; left: -34px; top: 0; width: 16px; height: 16px; border-radius: 50%; background: #cbd5e1; border: 3px solid #fff; box-shadow: 0 0 0 3px #e2e8f0; }
        .timeline-sultan li.checked::before { background: #10b981; box-shadow: 0 0 0 3px #a7f3d0; }
        .timeline-sultan li.current::before { background: #3b82f6; box-shadow: 0 0 0 3px #bfdbfe; animation: blinker 1.5s linear infinite; }
        .timeline-sultan li.start::before { background: #f59e0b; box-shadow: 0 0 0 3px #fde68a; }
        .time-badge { font-size: 11px; font-weight: 800; background: white; padding: 4px 10px; border-radius: 8px; color: #64748b; margin-bottom: 5px; display: inline-block; border: 1px solid #e2e8f0;}
        .tab-route { border: none; background: #f1f5f9; color: #64748b; font-weight: 700; padding: 8px 20px; border-radius: 10px; font-size: 0.82rem; margin-right: 8px; cursor: pointer; transition: 0.2s; }
        .tab-route.active { background: var(--primary-blue); color: white; }
        @keyframes truckPulse { 0%,100% { transform: scale(1); } 50% { transform: scale(1.15); } }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <div id="main-content">
        <?php include 'navbar.php'; ?>
        <div class="container-fluid p-4 flex-grow-1 mb-5">

            <?php if (empty($id_tracking)): ?>
            <!-- ========== OVERVIEW: List + Map Semua ========== -->
            <div class="d-flex align-items-center justify-content-between mt-2 mb-4">
                <div>
                    <h3 class="fw-bold mb-1" style="color:#1a367e;">Live Tracking Armada</h3>
                    <p class="text-muted small mb-0">Pilih armada untuk melihat rute spesifik di peta.</p>
                </div>
            </div>
            <div class="row">
                <div class="col-xl-5 col-lg-6 mb-4">
                    <div class="card-sultan p-4 h-100">
                        <h6 class="fw-bold mb-3"><i class="fas fa-list-ul text-primary me-2"></i>Pengiriman Aktif</h6>
                        <?php if(mysqli_num_rows($query_tracking) > 0): ?>
                            <?php while($trip = mysqli_fetch_assoc($query_tracking)): ?>
                            <a href="live_tracking.php?order=<?= $trip['id']; ?>" class="vehicle-item">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <h6 class="mb-0 fw-bold text-dark"><i class="fas fa-truck text-muted me-2"></i><?= $trip['nopol']; ?></h6>
                                    <?php if($trip['status']=='ON TRIP'): ?>
                                    <span class="badge bg-primary bg-opacity-10 text-primary status-pill anim-blink"><i class="fas fa-broadcast-tower me-1"></i>ON WAY</span>
                                    <?php else: ?>
                                    <span class="badge bg-success bg-opacity-10 text-success status-pill">DELIVERED</span>
                                    <?php endif; ?>
                                </div>
                                <div class="small mt-2" style="font-size:0.8rem;">
                                    <div class="mb-1"><span class="text-muted">No. SJ:</span> <span class="fw-bold text-primary"><?= $trip['no_sj']; ?></span></div>
                                    <div class="mb-1"><span class="text-muted">Supir:</span> <?= $trip['nama_driver']; ?></div>
                                    <div><i class="fas fa-map-marker-alt text-danger me-1"></i>Tujuan: <?= $trip['kota_tujuan']; ?></div>
                                </div>
                            </a>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="text-center mt-5 pt-5 opacity-50">
                                <i class="fas fa-truck-loading fa-3x mb-3"></i>
                                <p class="small fw-bold">Belum Ada Pengiriman Aktif</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-xl-7 col-lg-6 mb-4">
                    <div class="card-sultan overflow-hidden h-100">
                        <div class="header-sultan bg-dark text-white p-3 border-0">
                            <h6 class="mb-0 fw-bold"><i class="fas fa-route text-warning me-2"></i>Peta Nasional (Standby)</h6>
                        </div>
                        <div id="map-overview" style="height:500px;"></div>
                    </div>
                </div>
            </div>

            <?php else:
                // ========== DETAIL: Rute Spesifik ==========
                $q_detail = mysqli_query($conn, "SELECT sj.*, l.nama_lengkap as nama_driver, a.nopol, a.tipe, a.kategori_kendaraan, d.status_kerja
                    FROM surat_jalan sj JOIN driver d ON sj.id_driver=d.id JOIN login l ON d.id_login=l.id JOIN armada a ON sj.id_armada=a.id
                    WHERE sj.id='$id_tracking'");
                $dt = mysqli_fetch_assoc($q_detail);
                
                if (!$dt) {
                    echo '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i>Data pengiriman tidak ditemukan.</div>';
                } else {
                
                // Check if route columns exist
                $has_route = isset($dt['id_route']) && !empty($dt['id_route']);
                
                $tipe_aktif = 'PERGI';
                $id_route_aktif = null;
                $checkpoints = [];
                
                if ($has_route) {
                    $tipe_aktif = ($dt['status'] == 'DELIVERED' && $dt['status_kerja'] == 'RETURNING') ? 'PULANG' : 'PERGI';
                    $id_route_aktif = ($tipe_aktif == 'PULANG' && isset($dt['id_return_route'])) ? $dt['id_return_route'] : $dt['id_route'];
                    
                    if ($id_route_aktif) {
                        $q_cp = mysqli_query($conn, "
                            SELECT rd.id as id_rd, rd.sort_order, mp.nama_kota, mp.latitude, mp.longitude,
                                   sc.id as sc_id, sc.waktu, sc.catatan
                            FROM route_details rd
                            JOIN master_points mp ON rd.id_master_point=mp.id
                            LEFT JOIN sj_checkpoints sc ON sc.id_route_detail=rd.id AND sc.id_sj='$id_tracking' AND sc.tipe_checkpoint='$tipe_aktif'
                            WHERE rd.id_route='$id_route_aktif'
                            ORDER BY rd.sort_order ASC
                        ");
                        if ($q_cp) {
                            while($cp = mysqli_fetch_assoc($q_cp)) $checkpoints[] = $cp;
                        }
                    }
                }
            ?>
            <div class="mt-2 d-flex justify-content-between align-items-center mb-4 flex-wrap">
                <div>
                    <a href="live_tracking.php" class="btn btn-sm btn-light border shadow-sm mb-2" style="border-radius:8px;"><i class="fas fa-arrow-left me-2"></i>Kembali</a>
                    <h3 class="fw-bold mb-0" style="color:#1a367e;">Detail Perjalanan</h3>
                    <p class="text-muted small"><?= $dt['no_sj']; ?> — <?= $dt['nopol']; ?></p>
                </div>
                <?php if($dt['status']=='DELIVERED'): ?>
                <div class="badge bg-success bg-opacity-10 text-success p-3 shadow-sm" style="border-radius:12px;font-size:0.9rem;"><i class="fas fa-check-circle me-1"></i>PENGIRIMAN SELESAI</div>
                <?php else: ?>
                <div class="badge bg-primary bg-opacity-10 text-primary p-3 shadow-sm anim-blink" style="border-radius:12px;font-size:0.9rem;"><i class="fas fa-truck-moving me-1"></i>SEDANG DI JALAN</div>
                <?php endif; ?>
            </div>

            <?php if($has_route): ?>
            <!-- Tab Pergi/Pulang -->
            <div class="mb-3">
                <button class="tab-route <?= $tipe_aktif=='PERGI'?'active':''; ?>" onclick="switchRoute('PERGI')"><i class="fas fa-arrow-right me-1"></i>Rute Pergi</button>
                <button class="tab-route <?= $tipe_aktif=='PULANG'?'active':''; ?>" onclick="switchRoute('PULANG')"><i class="fas fa-arrow-left me-1"></i>Rute Pulang</button>
            </div>

            <!-- Peta -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card-sultan overflow-hidden">
                        <div class="header-sultan bg-dark text-white p-3 border-0 d-flex justify-content-between align-items-center">
                            <h6 class="mb-0 fw-bold"><i class="fas fa-route text-warning me-2"></i>Peta Rute (<?= $tipe_aktif; ?>)</h6>
                            <span class="small fw-bold text-warning">Target: <?= $dt['kota_tujuan']; ?></span>
                        </div>
                        <div id="map-tracking"></div>
                        <div class="card-footer bg-white p-4">
                            <div class="row align-items-center text-center text-md-start">
                                <div class="col-md-3 mb-3 mb-md-0 border-end">
                                    <div class="small text-muted fw-bold text-uppercase mb-1">Armada</div>
                                    <h6 class="fw-bold mb-0"><?= $dt['tipe']; ?></h6>
                                    <div class="badge bg-dark mt-1 px-3"><?= $dt['nopol']; ?></div>
                                </div>
                                <div class="col-md-3 mb-3 mb-md-0 border-end">
                                    <div class="small text-muted fw-bold text-uppercase mb-1">Supir</div>
                                    <h6 class="fw-bold mb-0 text-primary"><i class="fas fa-user-circle me-1"></i><?= $dt['nama_driver']; ?></h6>
                                </div>
                                <div class="col-md-3 mb-3 mb-md-0 border-end">
                                    <div class="small text-muted fw-bold text-uppercase mb-1">Penerima</div>
                                    <h6 class="fw-bold mb-0"><?= $dt['penerima']; ?></h6>
                                </div>
                                <div class="col-md-3">
                                    <div class="small text-muted fw-bold text-uppercase mb-1">Status Driver</div>
                                    <h6 class="fw-bold mb-0 text-success"><?= $dt['status_kerja']; ?></h6>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Timeline Checkpoint -->
            <div class="row">
                <div class="col-12">
                    <div class="timeline-container shadow-sm">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h5 class="fw-bold mb-0"><i class="fas fa-history text-primary me-2"></i>Checkpoint Rute</h5>
                            <button onclick="location.reload()" class="btn btn-sm btn-light border"><i class="fas fa-sync-alt me-1 text-muted"></i>Refresh</button>
                        </div>
                        <ul class="timeline-sultan">
                            <?php 
                            $last_checked = -1;
                            foreach($checkpoints as $idx => $cp) {
                                $is_checked = !empty($cp['sc_id']);
                                if ($is_checked) $last_checked = $idx;
                            }
                            
                            foreach(array_reverse($checkpoints) as $cp):
                                $is_checked = !empty($cp['sc_id']);
                                $rev_idx = $cp['sort_order'] - 1;
                                $is_current = ($rev_idx == $last_checked);
                                $class = $is_checked ? ($is_current ? 'current' : 'checked') : '';
                                if ($rev_idx == 0 && !$is_checked) $class = 'start';
                            ?>
                            <li class="<?= $class; ?>">
                                <?php if($is_checked): ?>
                                <div class="time-badge"><i class="far fa-clock me-1"></i><?= date('H:i | d M Y', strtotime($cp['waktu'])); ?></div><br>
                                <?php endif; ?>
                                <i class="fas fa-map-marker-alt <?= $is_checked ? 'text-success' : 'text-muted'; ?> mb-1 mt-1"></i>
                                <span class="fw-bold text-dark fs-6"><?= htmlspecialchars($cp['nama_kota']); ?></span>
                                <span class="text-muted small ms-2">(Checkpoint #<?= $cp['sort_order']; ?>)</span>
                                <?php if(!empty($cp['catatan'])): ?>
                                <div class="small text-muted mt-1"><i class="fas fa-quote-left opacity-50 me-1"></i><?= htmlspecialchars($cp['catatan']); ?></div>
                                <?php endif; ?>
                                <?php if(!$is_checked): ?>
                                <div class="small text-muted mt-1 opacity-50"><i class="fas fa-hourglass-half me-1"></i>Menunggu check-in</div>
                                <?php endif; ?>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <!-- Fallback: Old data tanpa rute -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card-sultan overflow-hidden">
                        <div class="header-sultan bg-dark text-white p-3 border-0">
                            <h6 class="mb-0 fw-bold"><i class="fas fa-route text-warning me-2"></i>Peta Rute (Legacy)</h6>
                        </div>
                        <?php
                        $lokasi_gudang = "Jl. IPN Kb. Nanas No.2, Jatinegara, Jakarta Timur";
                        $origin_map = urlencode($lokasi_gudang);
                        $dest_map = urlencode($dt['alamat_tujuan'] . ", " . $dt['kota_tujuan']);
                        $embed_map = "https://maps.google.com/maps?saddr={$origin_map}&daddr={$dest_map}&output=embed";
                        ?>
                        <iframe width="100%" height="400" frameborder="0" scrolling="no" src="<?= $embed_map; ?>" style="filter:contrast(1.1) opacity(0.9);"></iframe>
                    </div>
                </div>
            </div>
            <div class="alert alert-warning border-0 shadow-sm" style="border-radius:14px;">
                <i class="fas fa-info-circle me-2"></i>Pengiriman ini menggunakan sistem lama (tanpa Fixed Route). Untuk fitur peta interaktif, buat Surat Jalan baru dengan Rute yang sudah ditentukan.
            </div>
            <?php endif; // end has_route ?>
            <?php } // end else ($dt exists) ?>
            <?php endif; // end empty($id_tracking) ?>

        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <script>
    <?php if(empty($id_tracking)): ?>
    // Overview map
    var mapOv = L.map('map-overview').setView([-6.9, 110.0], 6);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {attribution:'&copy; OSM'}).addTo(mapOv);
    
    // Load active trips
    $.getJSON('api_tracking.php?action=get_active_trips', function(trips) {
        trips.forEach(function(t) {
            if (t.position) {
                var icon = L.divIcon({
                    className: '',
                    html: '<div style="width:36px;height:36px;border-radius:50%;background:#1a367e;color:white;display:flex;align-items:center;justify-content:center;font-size:14px;border:3px solid white;box-shadow:0 3px 10px rgba(0,0,0,0.3);animation:truckPulse 2s infinite;"><i class="fas fa-truck"></i></div>',
                    iconSize:[36,36], iconAnchor:[18,18]
                });
                L.marker([t.position.lat, t.position.lng], {icon:icon}).addTo(mapOv)
                    .bindPopup('<strong>'+t.nopol+'</strong><br>'+t.driver+'<br><small>Posisi: '+t.position.kota+'</small>');
            }
        });
    });

    <?php elseif(isset($has_route) && $has_route): ?>
    // Detail map with route
    var mapDetail = L.map('map-tracking').setView([-6.9, 110.0], 6);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {attribution:'&copy; OSM'}).addTo(mapDetail);

    var checkpoints = <?= json_encode($checkpoints); ?>;
    var latlngs = [];
    var lastCheckedIdx = -1;

    checkpoints.forEach(function(cp, idx) {
        var lat = parseFloat(cp.latitude);
        var lng = parseFloat(cp.longitude);
        latlngs.push([lat, lng]);

        var isChecked = cp.sc_id != null;
        if (isChecked) lastCheckedIdx = idx;

        var color = isChecked ? '#10b981' : '#94a3b8';
        if (idx === 0) color = '#f59e0b';
        if (idx === checkpoints.length - 1 && !isChecked) color = '#ef4444';

        var icon = L.divIcon({
            className: '',
            html: '<div style="width:30px;height:30px;border-radius:50%;background:'+color+';color:white;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:11px;border:3px solid white;box-shadow:0 2px 8px rgba(0,0,0,0.3);">'+(idx+1)+'</div>',
            iconSize:[30,30], iconAnchor:[15,15]
        });
        L.marker([lat, lng], {icon:icon}).addTo(mapDetail)
            .bindPopup('<strong>'+cp.nama_kota+'</strong><br>Checkpoint #'+(idx+1) + (isChecked ? '<br><small class="text-success">✓ Checked in</small>' : ''));
    });

    if (latlngs.length >= 2) {
        L.polyline(latlngs, {color:'#1a367e', weight:4, opacity:0.6, dashArray:'10 6'}).addTo(mapDetail);
        mapDetail.fitBounds(L.latLngBounds(latlngs).pad(0.15));
    }

    if (lastCheckedIdx >= 0) {
        var vLat = parseFloat(checkpoints[lastCheckedIdx].latitude);
        var vLng = parseFloat(checkpoints[lastCheckedIdx].longitude);
        var truckIcon = L.divIcon({
            className: '',
            html: '<div style="width:42px;height:42px;border-radius:50%;background:linear-gradient(135deg,#1a367e,#2b59ff);color:white;display:flex;align-items:center;justify-content:center;font-size:18px;border:3px solid #ffcc00;box-shadow:0 4px 15px rgba(26,54,126,0.4);animation:truckPulse 2s infinite;"><i class="fas fa-truck"></i></div>',
            iconSize:[42,42], iconAnchor:[21,21]
        });
        L.marker([vLat, vLng], {icon:truckIcon, zIndexOffset:1000}).addTo(mapDetail)
            .bindPopup('<strong>Posisi Kendaraan Saat Ini</strong><br>'+checkpoints[lastCheckedIdx].nama_kota).openPopup();
    }

    function switchRoute(tipe) { location.reload(); }
    setTimeout(function() { location.reload(); }, 30000);
    <?php endif; ?>
    </script>
</body>
</html>