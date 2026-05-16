<?php
session_start();
require 'koneksi.php';

if (!isset($_SESSION['log'])) {
    header('location:login.php');
    exit();
}

// =============================================
// AJAX: UPDATE SORT ORDER (Drag-and-Drop)
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

    // Recalculate total distance
    recalculate_route($conn, $id_route);

    echo json_encode(['status' => 'ok', 'message' => 'Urutan diperbarui']);
    exit();
}

// =============================================
// AJAX: UPDATE JARAK PER CHECKPOINT
// =============================================
if (isset($_POST['aksi']) && $_POST['aksi'] == 'update_jarak') {
    header('Content-Type: application/json');
    $id_detail = mysqli_real_escape_string($conn, $_POST['id_detail']);
    $jarak     = mysqli_real_escape_string($conn, $_POST['jarak']);
    $id_route  = mysqli_real_escape_string($conn, $_POST['id_route']);

    mysqli_query($conn, "UPDATE route_details SET jarak_dari_sebelumnya='$jarak' WHERE id='$id_detail'");
    recalculate_route($conn, $id_route);

    $r = mysqli_fetch_assoc(mysqli_query($conn, "SELECT total_jarak_km, total_biaya_rute FROM routes WHERE id='$id_route'"));
    echo json_encode(['status' => 'ok', 'total_jarak' => $r['total_jarak_km'], 'total_biaya' => $r['total_biaya_rute']]);
    exit();
}

// =============================================
// SIMPAN RUTE BARU
// =============================================
if (isset($_POST['simpan_rute'])) {
    $nama      = mysqli_real_escape_string($conn, $_POST['nama_rute']);
    $biaya_km  = 5000;
    $checkpoints = $_POST['checkpoint']; // array of master_point IDs
    $jarak_arr   = $_POST['jarak'];      // array of distances

    mysqli_begin_transaction($conn);
    try {
        // Insert route header
        mysqli_query($conn, "INSERT INTO routes (nama_rute, tipe_rute, biaya_per_km) VALUES ('$nama', 'PERGI', '$biaya_km')");
        $id_route = mysqli_insert_id($conn);

        // Insert checkpoints
        $total_jarak = 0;
        for ($i = 0; $i < count($checkpoints); $i++) {
            $id_mp = mysqli_real_escape_string($conn, $checkpoints[$i]);
            $jr    = floatval($jarak_arr[$i]);
            $sort  = $i + 1;
            $total_jarak += $jr;
            mysqli_query($conn, "INSERT INTO route_details (id_route, id_master_point, sort_order, jarak_dari_sebelumnya) VALUES ('$id_route', '$id_mp', '$sort', '$jr')");
        }

        // Update totals
        $total_biaya = $total_jarak * $biaya_km;
        mysqli_query($conn, "UPDATE routes SET total_jarak_km='$total_jarak', total_biaya_rute='$total_biaya' WHERE id='$id_route'");

        // Auto-generate return route (reverse)
        $nama_pulang = str_replace('→', '←', $nama);
        if ($nama_pulang == $nama) $nama_pulang = $nama . ' (Pulang)';
        
        mysqli_query($conn, "INSERT INTO routes (nama_rute, tipe_rute, id_rute_induk, biaya_per_km, total_jarak_km, total_biaya_rute) VALUES ('$nama_pulang', 'PULANG', '$id_route', '$biaya_km', '$total_jarak', '$total_biaya')");
        $id_return = mysqli_insert_id($conn);

        // Clone checkpoints in reverse order
        $q_cp = mysqli_query($conn, "SELECT * FROM route_details WHERE id_route='$id_route' ORDER BY sort_order DESC");
        $sort_r = 1;
        while ($cp = mysqli_fetch_assoc($q_cp)) {
            $jr_r = $cp['jarak_dari_sebelumnya'];
            mysqli_query($conn, "INSERT INTO route_details (id_route, id_master_point, sort_order, jarak_dari_sebelumnya) VALUES ('$id_return', '{$cp['id_master_point']}', '$sort_r', '$jr_r')");
            $sort_r++;
        }

        // Link return route to parent
        mysqli_query($conn, "UPDATE routes SET id_rute_induk='$id_return' WHERE id='$id_route'");

        mysqli_commit($conn);
        echo "<script>alert('Rute berhasil dibuat beserta rute pulangnya!'); window.location.href='manajemen_rute.php';</script>";
    } catch (Exception $e) {
        mysqli_rollback($conn);
        echo "<script>alert('Gagal: " . $e->getMessage() . "'); window.location.href='manajemen_rute.php';</script>";
    }
    exit();
}

// =============================================
// UPDATE RUTE
// =============================================
if (isset($_POST['update_rute'])) {
    $id_route    = mysqli_real_escape_string($conn, $_POST['id_route']);
    $nama        = mysqli_real_escape_string($conn, $_POST['nama_rute']);
    $checkpoints = $_POST['checkpoint'];
    $jarak_arr   = $_POST['jarak'];

    mysqli_begin_transaction($conn);
    try {
        mysqli_query($conn, "UPDATE routes SET nama_rute='$nama' WHERE id='$id_route'");
        
        // Delete old details and re-insert
        mysqli_query($conn, "DELETE FROM route_details WHERE id_route='$id_route'");
        
        $total_jarak = 0;
        for ($i = 0; $i < count($checkpoints); $i++) {
            $id_mp = mysqli_real_escape_string($conn, $checkpoints[$i]);
            $jr    = floatval($jarak_arr[$i]);
            $sort  = $i + 1;
            $total_jarak += $jr;
            mysqli_query($conn, "INSERT INTO route_details (id_route, id_master_point, sort_order, jarak_dari_sebelumnya) VALUES ('$id_route', '$id_mp', '$sort', '$jr')");
        }

        $biaya_km = 5000;
        $total_biaya = $total_jarak * $biaya_km;
        mysqli_query($conn, "UPDATE routes SET total_jarak_km='$total_jarak', total_biaya_rute='$total_biaya' WHERE id='$id_route'");

        // Update return route too
        $q_ret = mysqli_query($conn, "SELECT id FROM routes WHERE id_rute_induk='$id_route' AND tipe_rute='PULANG'");
        if ($ret = mysqli_fetch_assoc($q_ret)) {
            $id_return = $ret['id'];
            $nama_pulang = str_replace('→', '←', $nama);
            if ($nama_pulang == $nama) $nama_pulang = $nama . ' (Pulang)';
            
            mysqli_query($conn, "DELETE FROM route_details WHERE id_route='$id_return'");
            
            $q_cp = mysqli_query($conn, "SELECT * FROM route_details WHERE id_route='$id_route' ORDER BY sort_order DESC");
            $sort_r = 1;
            while ($cp = mysqli_fetch_assoc($q_cp)) {
                mysqli_query($conn, "INSERT INTO route_details (id_route, id_master_point, sort_order, jarak_dari_sebelumnya) VALUES ('$id_return', '{$cp['id_master_point']}', '$sort_r', '{$cp['jarak_dari_sebelumnya']}')");
                $sort_r++;
            }
            mysqli_query($conn, "UPDATE routes SET nama_rute='$nama_pulang', total_jarak_km='$total_jarak', total_biaya_rute='$total_biaya' WHERE id='$id_return'");
        }

        // Financial Sync: update related invoices
        sync_financial($conn, $id_route, $total_biaya);

        mysqli_commit($conn);
        echo "<script>alert('Rute berhasil diperbarui! Invoice terkait telah disinkronisasi.'); window.location.href='manajemen_rute.php';</script>";
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
// HELPER FUNCTIONS
// =============================================
function recalculate_route($conn, $id_route) {
    $q = mysqli_query($conn, "SELECT SUM(jarak_dari_sebelumnya) as total FROM route_details WHERE id_route='$id_route'");
    $d = mysqli_fetch_assoc($q);
    $total_km = $d['total'] ? $d['total'] : 0;
    
    $q2 = mysqli_query($conn, "SELECT biaya_per_km FROM routes WHERE id='$id_route'");
    $d2 = mysqli_fetch_assoc($q2);
    $biaya_km = $d2['biaya_per_km'] ? $d2['biaya_per_km'] : 5000;
    
    $total_biaya = $total_km * $biaya_km;
    mysqli_query($conn, "UPDATE routes SET total_jarak_km='$total_km', total_biaya_rute='$total_biaya' WHERE id='$id_route'");
    
    // Sync return route
    $q_ret = mysqli_query($conn, "SELECT id FROM routes WHERE id_rute_induk='$id_route' AND tipe_rute='PULANG'");
    if ($ret = mysqli_fetch_assoc($q_ret)) {
        mysqli_query($conn, "UPDATE routes SET total_jarak_km='$total_km', total_biaya_rute='$total_biaya' WHERE id='{$ret['id']}'");
    }
    
    sync_financial($conn, $id_route, $total_biaya);
}

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
