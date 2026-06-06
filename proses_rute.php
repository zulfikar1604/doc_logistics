<?php
session_start();
require 'koneksi.php';

if (!isset($_SESSION['log'])) {
    header('location:index.php');
    exit();
}

$role = isset($_SESSION['role']) ? strtoupper($_SESSION['role']) : 'MANAGER';
if ($role != 'ADMIN') {
    if (isset($_POST['simpan_rute']) || isset($_POST['update_rute']) || isset($_GET['hapus_rute']) || (isset($_POST['aksi']) && ($_POST['aksi'] == 'update_urutan'))) {
        if (isset($_POST['aksi'])) {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized action']);
            exit();
        } else {
            echo "<script>alert('Anda tidak memiliki akses untuk melakukan tindakan ini!'); window.location.href='manajemen_rute.php';</script>";
            exit();
        }
    }
}

// =============================================
// FUNGSI HAVERSINE — Menghitung jarak (KM) antara 2 koordinat GPS
// Menggunakan rumus Great-circle distance
// =============================================
function haversine($lat1, $lon1, $lat2, $lon2) {
    $R = 6371; // Radius bumi dalam kilometer
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    $a = sin($dLat / 2) * sin($dLat / 2) +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
         sin($dLon / 2) * sin($dLon / 2);
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    return round($R * $c, 2);
}

// =============================================
// FUNGSI RECALCULATE ROUTE DENGAN HAVERSINE
// Loop semua checkpoint berurutan, hitung jarak antar titik,
// update jarak_dari_sebelumnya per detail, dan total di header rute
// =============================================
function recalculate_route_haversine($conn, $id_route) {
    // Ambil semua checkpoint berurutan beserta koordinatnya
    $q = mysqli_query($conn, "SELECT rd.id, rd.sort_order, mp.latitude, mp.longitude 
                              FROM route_details rd 
                              JOIN master_points mp ON rd.id_master_point = mp.id 
                              WHERE rd.id_route='$id_route' 
                              ORDER BY rd.sort_order ASC");
    
    $points = [];
    while ($row = mysqli_fetch_assoc($q)) {
        $points[] = $row;
    }
    
    $total_jarak = 0;
    
    for ($i = 0; $i < count($points); $i++) {
        if ($i == 0) {
            // Titik pertama: jarak = 0
            mysqli_query($conn, "UPDATE route_details SET jarak_dari_sebelumnya=0 WHERE id='{$points[$i]['id']}'");
        } else {
            // Hitung jarak dari titik sebelumnya menggunakan Haversine
            $jarak = haversine(
                $points[$i-1]['latitude'], $points[$i-1]['longitude'],
                $points[$i]['latitude'], $points[$i]['longitude']
            );
            $total_jarak += $jarak;
            mysqli_query($conn, "UPDATE route_details SET jarak_dari_sebelumnya='$jarak' WHERE id='{$points[$i]['id']}'");
        }
    }
    
    // Update total jarak & biaya di header rute
    $q2 = mysqli_query($conn, "SELECT biaya_per_km FROM routes WHERE id='$id_route'");
    $d2 = mysqli_fetch_assoc($q2);
    $biaya_km = $d2['biaya_per_km'] ? $d2['biaya_per_km'] : 5000;
    
    $total_biaya = $total_jarak * $biaya_km;
    mysqli_query($conn, "UPDATE routes SET total_jarak_km='$total_jarak', total_biaya_rute='$total_biaya' WHERE id='$id_route'");
    
    // Sync return route (rute pulang)
    $q_ret = mysqli_query($conn, "SELECT id FROM routes WHERE id_rute_induk='$id_route' AND tipe_rute='PULANG'");
    if ($ret = mysqli_fetch_assoc($q_ret)) {
        // Recalculate rute pulang juga
        recalculate_return_route($conn, $id_route, $ret['id']);
    }
    
    // Sync financial (invoice terkait)
    sync_financial($conn, $id_route, $total_biaya);
    
    return $total_jarak;
}

// =============================================
// FUNGSI RECALCULATE RUTE PULANG (REVERSE)
// =============================================
function recalculate_return_route($conn, $id_route_pergi, $id_route_pulang) {
    // Hapus detail lama rute pulang
    mysqli_query($conn, "DELETE FROM route_details WHERE id_route='$id_route_pulang'");
    
    // Clone checkpoint dari rute pergi dalam urutan terbalik
    $q_cp = mysqli_query($conn, "SELECT * FROM route_details WHERE id_route='$id_route_pergi' ORDER BY sort_order DESC");
    $sort_r = 1;
    while ($cp = mysqli_fetch_assoc($q_cp)) {
        mysqli_query($conn, "INSERT INTO route_details (id_route, id_master_point, sort_order, jarak_dari_sebelumnya) 
                             VALUES ('$id_route_pulang', '{$cp['id_master_point']}', '$sort_r', '0')");
        $sort_r++;
    }
    
    // Recalculate jarak rute pulang dengan Haversine
    $q_pulang = mysqli_query($conn, "SELECT rd.id, rd.sort_order, mp.latitude, mp.longitude 
                                     FROM route_details rd 
                                     JOIN master_points mp ON rd.id_master_point = mp.id 
                                     WHERE rd.id_route='$id_route_pulang' 
                                     ORDER BY rd.sort_order ASC");
    $points = [];
    while ($row = mysqli_fetch_assoc($q_pulang)) {
        $points[] = $row;
    }
    
    $total_jarak = 0;
    for ($i = 0; $i < count($points); $i++) {
        if ($i == 0) {
            mysqli_query($conn, "UPDATE route_details SET jarak_dari_sebelumnya=0 WHERE id='{$points[$i]['id']}'");
        } else {
            $jarak = haversine(
                $points[$i-1]['latitude'], $points[$i-1]['longitude'],
                $points[$i]['latitude'], $points[$i]['longitude']
            );
            $total_jarak += $jarak;
            mysqli_query($conn, "UPDATE route_details SET jarak_dari_sebelumnya='$jarak' WHERE id='{$points[$i]['id']}'");
        }
    }
    
    // Update header rute pulang
    $q2 = mysqli_query($conn, "SELECT biaya_per_km FROM routes WHERE id='$id_route_pulang'");
    $d2 = mysqli_fetch_assoc($q2);
    $biaya_km = $d2['biaya_per_km'] ? $d2['biaya_per_km'] : 5000;
    $total_biaya = $total_jarak * $biaya_km;
    
    mysqli_query($conn, "UPDATE routes SET total_jarak_km='$total_jarak', total_biaya_rute='$total_biaya' WHERE id='$id_route_pulang'");
}

// =============================================
// AJAX: UPDATE SORT ORDER (Drag-and-Drop)
// Setelah user reorder checkpoint, recalculate jarak otomatis
// =============================================
if (isset($_POST['aksi']) && $_POST['aksi'] == 'update_urutan') {
    header('Content-Type: application/json');
    $id_route = mysqli_real_escape_string($conn, $_POST['id_route']);
    $order    = $_POST['order']; // array of route_detail IDs in new order

    foreach ($order as $index => $id_detail) {
        $id_detail = mysqli_real_escape_string($conn, $id_detail);
        $sort = $index + 1;
        mysqli_query($conn, "UPDATE route_details SET sort_order='$sort' WHERE id='$id_detail' AND id_route='$id_route'");
    }

    // Recalculate jarak otomatis dengan Haversine
    $total_jarak = recalculate_route_haversine($conn, $id_route);

    // Ambil data terbaru untuk response
    $r = mysqli_fetch_assoc(mysqli_query($conn, "SELECT total_jarak_km, total_biaya_rute FROM routes WHERE id='$id_route'"));
    
    // Ambil jarak per-segment untuk update UI
    $q_segments = mysqli_query($conn, "SELECT rd.id, rd.jarak_dari_sebelumnya FROM route_details rd WHERE rd.id_route='$id_route' ORDER BY rd.sort_order ASC");
    $segments = [];
    while ($seg = mysqli_fetch_assoc($q_segments)) {
        $segments[] = $seg;
    }
    
    echo json_encode([
        'status' => 'ok', 
        'message' => 'Urutan diperbarui & jarak dihitung ulang',
        'total_jarak' => $r['total_jarak_km'],
        'total_biaya' => $r['total_biaya_rute'],
        'segments' => $segments
    ]);
    exit();
}

// =============================================
// SIMPAN RUTE BARU (dengan kalkulasi Haversine otomatis)
// =============================================
if (isset($_POST['simpan_rute'])) {
    $nama      = mysqli_real_escape_string($conn, $_POST['nama_rute']);
    $biaya_km  = isset($_POST['tarif_per_km']) ? floatval($_POST['tarif_per_km']) : 5000;
    $checkpoints = $_POST['checkpoint']; // array of master_point IDs

    mysqli_begin_transaction($conn);
    try {
        // Insert route header (jarak & biaya sementara 0, nanti dihitung Haversine)
        mysqli_query($conn, "INSERT INTO routes (nama_rute, tipe_rute, biaya_per_km) VALUES ('$nama', 'PERGI', '$biaya_km')");
        $id_route = mysqli_insert_id($conn);

        // Insert checkpoints (jarak sementara 0)
        for ($i = 0; $i < count($checkpoints); $i++) {
            $id_mp = mysqli_real_escape_string($conn, $checkpoints[$i]);
            $sort  = $i + 1;
            mysqli_query($conn, "INSERT INTO route_details (id_route, id_master_point, sort_order, jarak_dari_sebelumnya) VALUES ('$id_route', '$id_mp', '$sort', '0')");
        }

        // HAVERSINE: Hitung jarak otomatis untuk semua checkpoint
        recalculate_route_haversine($conn, $id_route);
        
        // Ambil total jarak yang sudah dihitung
        $r_total = mysqli_fetch_assoc(mysqli_query($conn, "SELECT total_jarak_km, total_biaya_rute FROM routes WHERE id='$id_route'"));
        $total_jarak = $r_total['total_jarak_km'];
        $total_biaya = $r_total['total_biaya_rute'];

        // Auto-generate return route (reverse)
        $nama_pulang = str_replace('→', '←', $nama);
        if ($nama_pulang == $nama) $nama_pulang = $nama . ' (Pulang)';
        
        mysqli_query($conn, "INSERT INTO routes (nama_rute, tipe_rute, id_rute_induk, biaya_per_km, total_jarak_km, total_biaya_rute) VALUES ('$nama_pulang', 'PULANG', '$id_route', '$biaya_km', '0', '0')");
        $id_return = mysqli_insert_id($conn);

        // Link return route to parent
        mysqli_query($conn, "UPDATE routes SET id_rute_induk='$id_return' WHERE id='$id_route'");

        // Recalculate rute pulang (clone reverse + Haversine)
        recalculate_return_route($conn, $id_route, $id_return);

        mysqli_commit($conn);
        echo "<script>alert('Rute berhasil dibuat! Jarak dihitung otomatis oleh Haversine.'); window.location.href='manajemen_rute.php';</script>";
    } catch (Exception $e) {
        mysqli_rollback($conn);
        echo "<script>alert('Gagal: " . $e->getMessage() . "'); window.location.href='manajemen_rute.php';</script>";
    }
    exit();
}

// =============================================
// UPDATE RUTE (dengan kalkulasi Haversine otomatis)
// =============================================
if (isset($_POST['update_rute'])) {
    $id_route    = mysqli_real_escape_string($conn, $_POST['id_route']);
    $nama        = mysqli_real_escape_string($conn, $_POST['nama_rute']);
    $biaya_km    = isset($_POST['tarif_per_km']) ? floatval($_POST['tarif_per_km']) : 5000;
    $checkpoints = $_POST['checkpoint'];

    mysqli_begin_transaction($conn);
    try {
        mysqli_query($conn, "UPDATE routes SET nama_rute='$nama', biaya_per_km='$biaya_km' WHERE id='$id_route'");
        
        // Delete old details and re-insert
        mysqli_query($conn, "DELETE FROM route_details WHERE id_route='$id_route'");
        
        for ($i = 0; $i < count($checkpoints); $i++) {
            $id_mp = mysqli_real_escape_string($conn, $checkpoints[$i]);
            $sort  = $i + 1;
            mysqli_query($conn, "INSERT INTO route_details (id_route, id_master_point, sort_order, jarak_dari_sebelumnya) VALUES ('$id_route', '$id_mp', '$sort', '0')");
        }

        // HAVERSINE: Hitung jarak otomatis
        recalculate_route_haversine($conn, $id_route);
        
        $r_total = mysqli_fetch_assoc(mysqli_query($conn, "SELECT total_jarak_km, total_biaya_rute FROM routes WHERE id='$id_route'"));
        $total_biaya = $r_total['total_biaya_rute'];

        // Update return route too
        $q_ret = mysqli_query($conn, "SELECT id FROM routes WHERE id_rute_induk='$id_route' AND tipe_rute='PULANG'");
        if ($ret = mysqli_fetch_assoc($q_ret)) {
            $id_return = $ret['id'];
            $nama_pulang = str_replace('→', '←', $nama);
            if ($nama_pulang == $nama) $nama_pulang = $nama . ' (Pulang)';
            
            mysqli_query($conn, "UPDATE routes SET nama_rute='$nama_pulang', biaya_per_km='$biaya_km' WHERE id='$id_return'");
            
            // Recalculate rute pulang (clone reverse + Haversine)
            recalculate_return_route($conn, $id_route, $id_return);
        }

        // Financial Sync: update related invoices
        sync_financial($conn, $id_route, $total_biaya);

        mysqli_commit($conn);
        echo "<script>alert('Rute berhasil diperbarui! Jarak dihitung ulang oleh Haversine.'); window.location.href='manajemen_rute.php';</script>";
    } catch (Exception $e) {
        mysqli_rollback($conn);
        echo "<script>alert('Gagal: " . $e->getMessage() . "'); history.back();</script>";
    }
    exit();
}

// =============================================
// HAPUS RUTE (Soft Delete)
// =============================================
if (isset($_GET['hapus_rute'])) {
    $id = mysqli_real_escape_string($conn, $_GET['hapus_rute']);
    mysqli_query($conn, "UPDATE routes SET is_active=0 WHERE id='$id'");
    // Also deactivate return route
    mysqli_query($conn, "UPDATE routes SET is_active=0 WHERE id_rute_induk='$id'");
    header('location:manajemen_rute.php');
    exit();
}

// =============================================
// AJAX: GET ROUTE DETAILS (for edit form)
// =============================================
if (isset($_GET['get_details'])) {
    header('Content-Type: application/json');
    $id = mysqli_real_escape_string($conn, $_GET['get_details']);
    $q = mysqli_query($conn, "SELECT rd.*, mp.nama_kota, mp.latitude, mp.longitude 
                              FROM route_details rd 
                              JOIN master_points mp ON rd.id_master_point = mp.id 
                              WHERE rd.id_route='$id' 
                              ORDER BY rd.sort_order ASC");
    $data = [];
    while ($r = mysqli_fetch_assoc($q)) $data[] = $r;
    echo json_encode($data);
    exit();
}

// =============================================
// AJAX: RECALCULATE ALL EXISTING ROUTES
// Utility endpoint untuk recalculate semua rute existing
// =============================================
if (isset($_GET['recalculate_all'])) {
    header('Content-Type: application/json');
    $q_all = mysqli_query($conn, "SELECT id FROM routes WHERE tipe_rute='PERGI' AND is_active=1");
    $count = 0;
    while ($r = mysqli_fetch_assoc($q_all)) {
        recalculate_route_haversine($conn, $r['id']);
        $count++;
    }
    echo json_encode(['status' => 'ok', 'message' => "$count rute berhasil dihitung ulang dengan Haversine"]);
    exit();
}

// =============================================
// HELPER: SYNC FINANCIAL (Invoice)
// =============================================
function sync_financial($conn, $id_route, $total_biaya_rute) {
    // Find all surat_jalan using this route
    $q_sj = mysqli_query($conn, "SELECT sj.id, i.id as id_invoice FROM surat_jalan sj LEFT JOIN invoice i ON i.id_sj = sj.id WHERE sj.id_route='$id_route'");
    while ($sj = mysqli_fetch_assoc($q_sj)) {
        if ($sj['id_invoice']) {
            // Update the shipping cost line in invoice_detail
            $desc_pattern = "Biaya Pengiriman%";
            $q_det = mysqli_query($conn, "SELECT id FROM invoice_detail WHERE id_invoice='{$sj['id_invoice']}' AND deskripsi LIKE '$desc_pattern' LIMIT 1");
            if ($det = mysqli_fetch_assoc($q_det)) {
                mysqli_query($conn, "UPDATE invoice_detail SET tarif='$total_biaya_rute', qty='1', subtotal='$total_biaya_rute' WHERE id='{$det['id']}'");
            }
            // Recalculate invoice total
            $q_sum = mysqli_query($conn, "SELECT SUM(subtotal) as sub FROM invoice_detail WHERE id_invoice='{$sj['id_invoice']}' AND deskripsi NOT LIKE 'PPN%'");
            $sum = mysqli_fetch_assoc($q_sum);
            $subtotal = $sum['sub'] ? $sum['sub'] : 0;
            $ppn = $subtotal * 0.11;
            $grand = $subtotal + $ppn;
            
            // Update PPN line
            mysqli_query($conn, "UPDATE invoice_detail SET tarif='$ppn', subtotal='$ppn' WHERE id_invoice='{$sj['id_invoice']}' AND deskripsi LIKE 'PPN%'");
            // Update invoice header
            mysqli_query($conn, "UPDATE invoice SET total_tagihan='$grand' WHERE id='{$sj['id_invoice']}'");
        }
    }
}
?>
