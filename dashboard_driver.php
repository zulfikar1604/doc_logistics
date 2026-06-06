<?php
session_start();
require 'koneksi.php'; 

if (!isset($_SESSION['log']) || $_SESSION['role'] != 'DRIVER') {
    header('location:index.php');
    exit();
}

date_default_timezone_set('Asia/Jakarta');

$email_driver = mysqli_real_escape_string($conn, $_SESSION['email']);
$nama_driver  = !empty($_SESSION['nama_lengkap']) ? $_SESSION['nama_lengkap'] : explode('@', $email_driver)[0];

$q_driver_info = mysqli_query($conn, "SELECT d.id, d.status_kerja FROM driver d JOIN login l ON d.id_login = l.id WHERE l.email = '$email_driver'");
$driver_info = mysqli_fetch_assoc($q_driver_info);
$id_supir_asli = $driver_info ? $driver_info['id'] : 0;
$status_supir = $driver_info ? $driver_info['status_kerja'] : 'UNREGISTERED';

// ==========================================
// LOGIKA: CHECK-IN CHECKPOINT FIXED ROUTE
// ==========================================
if (isset($_POST['checkin_route'])) {
    $id_sj = mysqli_real_escape_string($conn, $_POST['id_sj']);
    $id_route_detail = mysqli_real_escape_string($conn, $_POST['id_route_detail']);
    $id_master_point = mysqli_real_escape_string($conn, $_POST['id_master_point']);
    $tipe_cp = mysqli_real_escape_string($conn, $_POST['tipe_checkpoint']);
    $catatan = mysqli_real_escape_string($conn, $_POST['catatan']);
    $waktu = date('Y-m-d H:i:s');
    
    mysqli_begin_transaction($conn);
    try {
        // Insert checkpoint record
        mysqli_query($conn, "INSERT INTO sj_checkpoints (id_sj, id_route_detail, id_master_point, tipe_checkpoint, waktu, catatan) 
                             VALUES ('$id_sj', '$id_route_detail', '$id_master_point', '$tipe_cp', '$waktu', '$catatan')");
        $inserted_id = mysqli_insert_id($conn);
        
        // Update last checkpoint reference on Surat Jalan
        if ($tipe_cp == 'PERGI') {
            mysqli_query($conn, "UPDATE surat_jalan SET last_checkpoint_id='$inserted_id' WHERE id='$id_sj'");
        } else {
            mysqli_query($conn, "UPDATE surat_jalan SET last_checkpoint_return_id='$inserted_id' WHERE id='$id_sj'");
        }
        // Insert Notification for Admin
        $notif_judul = "Driver Check-in Lokasi";
        $notif_pesan = "Driver $nama_driver telah sampai di titik checkpoint rute Surat Jalan #$id_sj.";
        if (function_exists('buat_notifikasi')) {
            buat_notifikasi($conn, 'MANAGER,ADMIN', 0, $notif_judul, $notif_pesan, 'info', "live_tracking.php?order=$id_sj");
        }

        mysqli_commit($conn);
        echo "<script>alert('Berhasil Check-in di Checkpoint!'); window.location.href='dashboard_driver.php';</script>";
    } catch (Exception $e) {
        mysqli_rollback($conn);
        echo "<script>alert('Gagal check-in: ".$e->getMessage()."'); window.location.href='dashboard_driver.php';</script>";
    }
    exit();
}

// ==========================================
// LOGIKA: SELESAIKAN TRIP (GANTI JADI PULANG)
// ==========================================
if (isset($_POST['selesaikan_trip'])) {
    $id_sj = mysqli_real_escape_string($conn, $_POST['id_sj']);
    $id_armada = mysqli_real_escape_string($conn, $_POST['id_armada']);
    
    mysqli_begin_transaction($conn);
    try {
        mysqli_query($conn, "UPDATE surat_jalan SET status='DELIVERED' WHERE id='$id_sj'");
        mysqli_query($conn, "UPDATE driver SET status_kerja='RETURNING' WHERE id='$id_supir_asli'");
        mysqli_query($conn, "UPDATE armada SET status='RETURNING' WHERE id='$id_armada'");
        
        // Increment odometer for Pergi route distance
        $q_dist = mysqli_query($conn, "
            SELECT sj.id_route, r.total_jarak_km, r.nama_rute, a.nopol, a.odometer, a.odometer_terakhir_servis 
            FROM surat_jalan sj 
            LEFT JOIN routes r ON sj.id_route = r.id 
            LEFT JOIN armada a ON sj.id_armada = a.id 
            WHERE sj.id = '$id_sj'
        ");
        if ($q_dist && mysqli_num_rows($q_dist) > 0) {
            $d_dist = mysqli_fetch_assoc($q_dist);
            $jarak = floatval($d_dist['total_jarak_km']);
            $nopol = $d_dist['nopol'];
            
            // Update odometer
            mysqli_query($conn, "UPDATE armada SET odometer = odometer + $jarak WHERE id = '$id_armada'");
            
            // Fetch updated readings to check threshold
            $odo_now = (int)$d_dist['odometer'] + $jarak;
            $odo_servis = (int)$d_dist['odometer_terakhir_servis'];
            if (($odo_now - $odo_servis) >= 10000) {
                if (function_exists('buat_notifikasi')) {
                    buat_notifikasi(
                        $conn, 
                        'MANAGER,ADMIN', 
                        0, 
                        "Armada Butuh Servis!", 
                        "Armada $nopol telah menempuh " . number_format($odo_now - $odo_servis, 0, ',', '.') . " km sejak servis terakhir. Segera lakukan perawatan rutin.", 
                        "warning", 
                        "data_armada.php"
                    );
                }
            }
        }

        // Insert Notification
        $notif_judul = "Bongkar Muat Selesai";
        $notif_pesan = "Driver $nama_driver telah menyelesaikan pengiriman (DELIVERED) untuk Surat Jalan #$id_sj dan sedang dalam perjalanan pulang.";
        if (function_exists('buat_notifikasi')) {
            buat_notifikasi($conn, 'MANAGER,ADMIN', 0, $notif_judul, $notif_pesan, 'success', 'status_pengiriman.php');
        }

        mysqli_commit($conn);
        echo "<script>alert('Bongkar muat selesai! Armada memulai rute pulang.'); window.location.href='dashboard_driver.php';</script>";
    } catch (Exception $e) {
        mysqli_rollback($conn);
        echo "<script>alert('Gagal: ".$e->getMessage()."'); window.location.href='dashboard_driver.php';</script>";
    }
    exit();
}

// ==========================================
// LOGIKA: TIBA DI GUDANG (AVAILABLE)
// ==========================================
if (isset($_POST['tiba_di_gudang'])) {
    $id_sj = mysqli_real_escape_string($conn, $_POST['id_sj']);
    $id_armada = mysqli_real_escape_string($conn, $_POST['id_armada']);
    
    mysqli_begin_transaction($conn);
    try {
        mysqli_query($conn, "UPDATE surat_jalan SET status='FINISHED' WHERE id='$id_sj'");
        mysqli_query($conn, "UPDATE driver SET status_kerja='AVAILABLE' WHERE id='$id_supir_asli'");
        mysqli_query($conn, "UPDATE armada SET status='READY' WHERE id='$id_armada'");
        
        // Increment odometer for Pulang route distance
        $q_dist = mysqli_query($conn, "
            SELECT sj.id_return_route, r.total_jarak_km, r.nama_rute, a.nopol, a.odometer, a.odometer_terakhir_servis 
            FROM surat_jalan sj 
            LEFT JOIN routes r ON sj.id_return_route = r.id 
            LEFT JOIN armada a ON sj.id_armada = a.id 
            WHERE sj.id = '$id_sj'
        ");
        if ($q_dist && mysqli_num_rows($q_dist) > 0) {
            $d_dist = mysqli_fetch_assoc($q_dist);
            $jarak = floatval($d_dist['total_jarak_km']);
            $nopol = $d_dist['nopol'];
            
            // Update odometer
            mysqli_query($conn, "UPDATE armada SET odometer = odometer + $jarak WHERE id = '$id_armada'");
            
            // Fetch updated readings to check threshold
            $odo_now = (int)$d_dist['odometer'] + $jarak;
            $odo_servis = (int)$d_dist['odometer_terakhir_servis'];
            if (($odo_now - $odo_servis) >= 10000) {
                // Ubah status armada menjadi MAINTENANCE karena butuh servis
                mysqli_query($conn, "UPDATE armada SET status='MAINTENANCE' WHERE id='$id_armada'");

                if (function_exists('buat_notifikasi')) {
                    buat_notifikasi(
                        $conn, 
                        'MANAGER,ADMIN', 
                        0, 
                        "Armada Butuh Servis!", 
                        "Armada $nopol telah menempuh " . number_format($odo_now - $odo_servis, 0, ',', '.') . " km sejak servis terakhir. Segera lakukan perawatan rutin.", 
                        "warning", 
                        "data_armada.php"
                    );
                }
            }
        }

        // Insert Notification
        $notif_judul = "Armada Telah Kembali";
        $notif_pesan = "Driver $nama_driver dan Armada telah tiba kembali di Markas. Tugas #$id_sj sepenuhnya selesai.";
        if (function_exists('buat_notifikasi')) {
            buat_notifikasi($conn, 'MANAGER,ADMIN', 0, $notif_judul, $notif_pesan, 'success', 'status_pengiriman.php');
        }

        mysqli_commit($conn);
        $pesan_alert = "Tugas selesai sepenuhnya! Armada dan supir kembali stand-by.";
        if (isset($odo_now) && ($odo_now - $odo_servis) >= 10000) {
            $pesan_alert = "Tugas selesai! Armada otomatis diset ke MAINTENANCE karena telah menempuh >10.000 KM sejak servis terakhir.";
        }
        echo "<script>alert('$pesan_alert'); window.location.href='dashboard_driver.php';</script>";
    } catch (Exception $e) {
        mysqli_rollback($conn);
        echo "<script>alert('Gagal: ".$e->getMessage()."'); window.location.href='dashboard_driver.php';</script>";
    }
    exit();
}

// ==========================================
// AMBIL DATA AKTIF
// ==========================================
$tugas_aktif = null;
$q_aktif = mysqli_query($conn, "SELECT sj.*, a.id as id_armada, a.nopol, a.tipe 
                                FROM surat_jalan sj JOIN armada a ON sj.id_armada = a.id
                                WHERE sj.id_driver = '$id_supir_asli' AND (sj.status = 'ON TRIP' OR (sj.status = 'DELIVERED' AND '$status_supir' = 'RETURNING'))
                                ORDER BY sj.id DESC LIMIT 1");
if ($row = mysqli_fetch_assoc($q_aktif)) {
    $tugas_aktif = $row;
}

$tipe_aktif = 'PERGI';
$route_data = [];
$next_cp = null;
$semua_cp_selesai = false;

if ($tugas_aktif) {
    $tipe_aktif = ($tugas_aktif['status'] == 'DELIVERED' && $status_supir == 'RETURNING') ? 'PULANG' : 'PERGI';
    $id_route = ($tipe_aktif == 'PULANG') ? $tugas_aktif['id_return_route'] : $tugas_aktif['id_route'];
    
    if ($id_route) {
        $q_cp = mysqli_query($conn, "
            SELECT rd.id as id_rd, rd.sort_order, mp.id as id_mp, mp.nama_kota, mp.latitude, mp.longitude,
                   sc.id as is_checked_in, sc.waktu, sc.catatan
            FROM route_details rd
            JOIN master_points mp ON rd.id_master_point = mp.id
            LEFT JOIN sj_checkpoints sc ON sc.id_route_detail = rd.id AND sc.id_sj = '{$tugas_aktif['id']}' AND sc.tipe_checkpoint = '$tipe_aktif'
            WHERE rd.id_route = '$id_route'
            ORDER BY rd.sort_order ASC
        ");
        
        while ($cp = mysqli_fetch_assoc($q_cp)) {
            $route_data[] = $cp;
            if (!$cp['is_checked_in'] && !$next_cp) {
                $next_cp = $cp; // Find first unchecked checkpoint
            }
        }
        
        if (!$next_cp && count($route_data) > 0) $semua_cp_selesai = true;
    }
}

// ==========================================
// AMBIL RIWAYAT TUGAS SELESAI
// ==========================================
$q_riwayat = mysqli_query($conn, "SELECT no_sj, kota_tujuan, tgl_kirim, status FROM surat_jalan WHERE id_driver='$id_supir_asli' AND status IN ('DELIVERED', 'FINISHED') ORDER BY id DESC LIMIT 5");
$riwayat_tugas = [];
while ($row = mysqli_fetch_assoc($q_riwayat)) {
    $riwayat_tugas[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Driver Dashboard</title>
    <link rel="icon" type="image/png" href="assets/img/icon-192-nobg.png?v=4">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        :root {
            --primary-blue: #1a367e;
            --accent-yellow: #ffcc00;
            --bg-light: #f4f7f6;
        }
        body { font-family: 'Poppins', sans-serif; background-color: var(--bg-light); overflow-x: hidden; }
        
        /* --- CSS WAJIB LAYOUT --- */
        
        @media (max-width: 768px) {
            #sidebar.show { margin-left: 0; }
        }

        #sidebar ul li a:hover, #sidebar ul li.active > a { color: #fff; background: rgba(255,255,255,0.1); border-left: 4px solid var(--accent-yellow); }
        #sidebar ul li a i { width: 25px; text-align: center; }

        .card-sultan { background: white; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.04); border: 1px solid #edf2f7; overflow: hidden; margin-top: 20px; }
        .card-header-sultan { background: #f8fafc; padding: 15px 20px; border-bottom: 1px solid #e2e8f0; font-weight: 700; }
        #map-driver { height: 350px; width: 100%; border-bottom: 2px solid #e2e8f0; }
        
        .cp-list { list-style: none; padding: 0; margin: 0; }
        .cp-item { display: flex; padding: 15px; border-bottom: 1px solid #f1f5f9; align-items: center; }
        .cp-item:last-child { border-bottom: none; }
        .cp-status { width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 15px; flex-shrink: 0; }
        .cp-status.done { background: #d1fae5; color: #10b981; }
        .cp-status.next { background: #dbeafe; color: #2563eb; border: 2px solid #bfdbfe; }
        .cp-status.wait { background: #f1f5f9; color: #94a3b8; }
        
        .btn-checkin { background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; border: none; padding: 15px; border-radius: 14px; font-weight: 800; font-size: 1.1rem; width: 100%; box-shadow: 0 8px 20px rgba(16,185,129,0.3); }
        .btn-finish { background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); color: white; border: none; padding: 15px; border-radius: 14px; font-weight: 800; font-size: 1.1rem; width: 100%; box-shadow: 0 8px 20px rgba(59,130,246,0.3); }
    </style>
</head>
<body>

    <?php include 'sidebar.php'; ?>

    <div id="main-content">
        <?php include 'navbar.php'; ?>

        <div class="container-fluid p-4 mb-5">
            <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
                <h3 class="fw-bold mb-2" style="color: #1a367e;">Dashboard Driver</h3>
                <div id="liveClock" class="fw-bold bg-white px-3 py-2 shadow-sm border mb-2" style="border-radius:8px; font-size:1rem; letter-spacing:1px; color:#1a367e;">--:--:--</div>
            </div>
        
        <?php if($status_supir == 'UNREGISTERED'): ?>
            <div class="alert alert-danger mt-4" style="border-radius:16px;">
                <h5 class="fw-bold"><i class="fas fa-exclamation-triangle me-2"></i>Akun Belum Terdaftar</h5>
                <p class="mb-0 small">Admin belum melengkapi profil operasional Anda. Silakan hubungi admin.</p>
            </div>
        <?php elseif(!$tugas_aktif): ?>
            <div class="text-center mt-5 pt-4 opacity-50">
                <i class="fas fa-mug-hot fa-4x mb-3 text-secondary"></i>
                <h4 class="fw-bold text-secondary">Standby / Available</h4>
                <p class="small">Belum ada tugas pengiriman saat ini. Silakan istirahat.</p>
            </div>
        <?php else: ?>
            
            <div class="card-sultan mt-3">
                <div class="card-header-sultan d-flex justify-content-between align-items-center">
                    <div>
                        <div class="small text-muted">No. SJ: <?= $tugas_aktif['no_sj']; ?></div>
                        <h6 class="mb-0 fw-bold text-primary"><?= $tugas_aktif['kota_tujuan']; ?></h6>
                    </div>
                    <?php if($tipe_aktif == 'PULANG'): ?>
                    <span class="badge bg-warning text-dark px-3 py-2" style="border-radius:8px;">PULANG</span>
                    <?php else: ?>
                    <span class="badge bg-primary px-3 py-2" style="border-radius:8px;">PERGI</span>
                    <?php endif; ?>
                </div>

                <div id="map-driver"></div>

                <div class="p-3">
                    <?php if($next_cp): ?>
                        <!-- Form Check In -->
                        <div class="alert alert-info border-0 shadow-sm" style="border-radius:14px; background:#eff6ff;">
                            <h6 class="fw-bold mb-1 text-primary">Target Checkpoint Berikutnya:</h6>
                            <h4 class="fw-bold text-dark mb-0"><i class="fas fa-map-marker-alt text-danger me-2"></i><?= $next_cp['nama_kota']; ?></h4>
                        </div>
                        
                        <form method="POST">
                            <input type="hidden" name="id_sj" value="<?= $tugas_aktif['id']; ?>">
                            <input type="hidden" name="id_route_detail" value="<?= $next_cp['id_rd']; ?>">
                            <input type="hidden" name="id_master_point" value="<?= $next_cp['id_mp']; ?>">
                            <input type="hidden" name="tipe_checkpoint" value="<?= $tipe_aktif; ?>">
                            
                            <textarea name="catatan" class="form-control mb-3" rows="2" placeholder="Catatan opsional (misal: cuaca buruk, macet)..." style="border-radius:12px; background:#f8fafc; border-color:#e2e8f0;"></textarea>
                            
                            <button type="submit" name="checkin_route" class="btn-checkin" onclick="return confirm('Yakin sudah sampai di <?= addslashes($next_cp['nama_kota']); ?>?')">
                                <i class="fas fa-check-circle me-2"></i>SAYA SUDAH SAMPAI
                            </button>
                        </form>
                    
                    <?php elseif($semua_cp_selesai): ?>
                        <!-- Semua CP selesai, tombol ganti status -->
                        <div class="alert alert-success border-0 shadow-sm text-center" style="border-radius:14px;">
                            <i class="fas fa-flag-checkered fa-2x mb-2"></i>
                            <h5 class="fw-bold mb-0">Semua Checkpoint Selesai!</h5>
                        </div>
                        
                        <form method="POST">
                            <input type="hidden" name="id_sj" value="<?= $tugas_aktif['id']; ?>">
                            <input type="hidden" name="id_armada" value="<?= $tugas_aktif['id_armada']; ?>">
                            
                            <?php if($tipe_aktif == 'PERGI'): ?>
                                <button type="submit" name="selesaikan_trip" class="btn-finish" onclick="return confirm('Konfirmasi serah terima barang selesai dan mulai perjalanan pulang?')">
                                    <i class="fas fa-box-open me-2"></i>BONGKAR MUAT SELESAI
                                </button>
                            <?php else: ?>
                                <button type="submit" name="tiba_di_gudang" class="btn-finish bg-success" onclick="return confirm('Konfirmasi armada tiba di markas dengan selamat?')">
                                    <i class="fas fa-home me-2"></i>TIBA DI MARKAS PUSAT
                                </button>
                            <?php endif; ?>
                        </form>
                    <?php endif; ?>
                </div>
                
                <div class="card-header-sultan bg-light mt-2 border-top"><i class="fas fa-list-ul me-2 text-muted"></i>Daftar Checkpoint</div>
                <ul class="cp-list">
                    <?php foreach($route_data as $cp): ?>
                        <li class="cp-item">
                            <?php if($cp['is_checked_in']): ?>
                                <div class="cp-status done"><i class="fas fa-check"></i></div>
                                <div>
                                    <h6 class="mb-0 fw-bold text-dark text-decoration-line-through opacity-75"><?= htmlspecialchars($cp['nama_kota']); ?></h6>
                                    <small class="text-success fw-bold"><?= date('H:i', strtotime($cp['waktu'])); ?></small>
                                </div>
                            <?php elseif($next_cp && $next_cp['id_rd'] == $cp['id_rd']): ?>
                                <div class="cp-status next"><i class="fas fa-truck"></i></div>
                                <div>
                                    <h6 class="mb-0 fw-bold text-primary"><?= htmlspecialchars($cp['nama_kota']); ?></h6>
                                    <small class="text-primary fw-bold">Menuju lokasi...</small>
                                </div>
                            <?php else: ?>
                                <div class="cp-status wait"><i class="fas fa-ellipsis-h"></i></div>
                                <div>
                                    <h6 class="mb-0 fw-bold text-muted"><?= htmlspecialchars($cp['nama_kota']); ?></h6>
                                    <small class="text-muted opacity-50">Menunggu</small>
                                </div>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

        <?php endif; ?>

        <!-- RIWAYAT TUGAS SELESAI -->
        <?php if($status_supir != 'UNREGISTERED' && count($riwayat_tugas) > 0): ?>
        <div class="card-sultan mt-4">
            <div class="card-header-sultan d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold text-dark"><i class="fas fa-history text-secondary me-2"></i>Riwayat Tugas Terakhir</h6>
            </div>
            <div class="p-0">
                <ul class="cp-list">
                    <?php foreach($riwayat_tugas as $rw): ?>
                    <li class="cp-item">
                        <div class="cp-status done"><i class="fas fa-check"></i></div>
                        <div class="flex-grow-1">
                            <div class="fw-bold" style="font-size:0.9rem;"><?= htmlspecialchars($rw['kota_tujuan']); ?></div>
                            <div class="small text-muted"><?= htmlspecialchars($rw['no_sj']); ?> &bull; <?= date('d M Y', strtotime($rw['tgl_kirim'])); ?></div>
                        </div>
                        <div class="ms-2">
                            <span class="badge bg-light text-dark border"><?= $rw['status']; ?></span>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        <?php endif; ?>
    </div>
    </div> <!-- close main-content -->

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    
    <script>
        // Script Sidebar Toggle untuk Mobile
        document.getElementById('sidebarToggle')?.addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('show');
        });
    </script>

    <script>
        // Live Clock Function
        function updateClock() {
            var now = new Date();
            var h = String(now.getHours()).padStart(2, '0');
            var m = String(now.getMinutes()).padStart(2, '0');
            var s = String(now.getSeconds()).padStart(2, '0');
            document.getElementById('liveClock').textContent = h + ":" + m + ":" + s;
        }
        setInterval(updateClock, 1000);
        updateClock();
    </script>

    <?php if($tugas_aktif && isset($route_data) && count($route_data)>0): ?>
    <script>
        var map = L.map('map-driver').setView([-6.9, 110.0], 6);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {attribution:'&copy; OSM'}).addTo(map);
        
        var latlngs = [];
        var activeLat = null, activeLng = null;
        
        <?php foreach($route_data as $idx => $cp): ?>
            latlngs.push([<?= $cp['latitude']; ?>, <?= $cp['longitude']; ?>]);
            
            var isDone = <?= $cp['is_checked_in'] ? 'true' : 'false'; ?>;
            var isNext = <?= ($next_cp && $next_cp['id_rd'] == $cp['id_rd']) ? 'true' : 'false'; ?>;
            
            var color = isDone ? '#10b981' : (isNext ? '#3b82f6' : '#94a3b8');
            var iconHtml = '<div style="width:24px;height:24px;border-radius:50%;background:'+color+';color:white;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:10px;border:2px solid white;box-shadow:0 2px 5px rgba(0,0,0,0.2);">'+(<?= $idx+1 ?>)+'</div>';
            
            var icon = L.divIcon({ className: '', html: iconHtml, iconSize: [24,24], iconAnchor: [12,12] });
            L.marker([<?= $cp['latitude']; ?>, <?= $cp['longitude']; ?>], {icon:icon}).addTo(map);
            
            if (isNext) { activeLat = <?= $cp['latitude']; ?>; activeLng = <?= $cp['longitude']; ?>; }
        <?php endforeach; ?>
        
        if (latlngs.length >= 2) {
            var pl = L.polyline(latlngs, {color:'#1a367e', weight:4, opacity:0.6, dashArray:'10 6'}).addTo(map);
            map.fitBounds(pl.getBounds().pad(0.2));
            if(activeLat) { map.setView([activeLat, activeLng], 10); } // Zoom to next cp
        }
    </script>
    <?php endif; ?>
</body>
</html>