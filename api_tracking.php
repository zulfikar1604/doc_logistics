<?php
session_start();
require 'koneksi.php';
header('Content-Type: application/json');

if (!isset($_SESSION['log'])) { echo json_encode(['error' => 'unauthorized']); exit(); }

$action = isset($_GET['action']) ? $_GET['action'] : '';

// =============================================
// GET ROUTE CHECKPOINTS + STATUS CHECK-IN
// =============================================
if ($action == 'get_route_checkpoints') {
    $id_sj = mysqli_real_escape_string($conn, $_GET['id_sj']);
    $tipe  = isset($_GET['tipe']) ? mysqli_real_escape_string($conn, $_GET['tipe']) : 'PERGI';

    // Get route ID based on type
    if ($tipe == 'PULANG') {
        $q_sj = mysqli_query($conn, "SELECT id_return_route as id_route FROM surat_jalan WHERE id='$id_sj'");
    } else {
        $q_sj = mysqli_query($conn, "SELECT id_route FROM surat_jalan WHERE id='$id_sj'");
    }
    $sj = mysqli_fetch_assoc($q_sj);
    if (!$sj || !$sj['id_route']) { echo json_encode([]); exit(); }
    $id_route = $sj['id_route'];

    // Get all checkpoints with check-in status
    $q = mysqli_query($conn, "
        SELECT rd.id as id_route_detail, rd.sort_order, rd.jarak_dari_sebelumnya,
               mp.id as id_mp, mp.nama_kota, mp.latitude, mp.longitude,
               sc.id as checkin_id, sc.waktu as checkin_waktu, sc.catatan as checkin_catatan
        FROM route_details rd
        JOIN master_points mp ON rd.id_master_point = mp.id
        LEFT JOIN sj_checkpoints sc ON sc.id_route_detail = rd.id AND sc.id_sj = '$id_sj' AND sc.tipe_checkpoint = '$tipe'
        WHERE rd.id_route = '$id_route'
        ORDER BY rd.sort_order ASC
    ");

    $data = [];
    while ($r = mysqli_fetch_assoc($q)) {
        $data[] = [
            'id_route_detail' => $r['id_route_detail'],
            'sort_order'      => (int)$r['sort_order'],
            'nama_kota'       => $r['nama_kota'],
            'latitude'        => (float)$r['latitude'],
            'longitude'       => (float)$r['longitude'],
            'jarak_km'        => (float)$r['jarak_dari_sebelumnya'],
            'checked_in'      => $r['checkin_id'] ? true : false,
            'checkin_waktu'    => $r['checkin_waktu'],
            'checkin_catatan'  => $r['checkin_catatan']
        ];
    }
    echo json_encode($data);
    exit();
}

// =============================================
// GET VEHICLE POSITION (last checkpoint)
// =============================================
if ($action == 'get_vehicle_position') {
    $id_sj = mysqli_real_escape_string($conn, $_GET['id_sj']);

    $q = mysqli_query($conn, "
        SELECT sj.last_checkpoint_id, sj.last_checkpoint_return_id, sj.status,
               d.status_kerja
        FROM surat_jalan sj
        JOIN driver dr ON sj.id_driver = dr.id
        JOIN driver d ON sj.id_driver = d.id
        WHERE sj.id = '$id_sj'
    ");
    $sj = mysqli_fetch_assoc($q);
    if (!$sj) { echo json_encode(['error' => 'not found']); exit(); }

    $checkpoint_id = $sj['last_checkpoint_id'];
    $tipe = 'PERGI';
    
    // If driver is returning, use return checkpoint
    if ($sj['status'] == 'DELIVERED' && $sj['last_checkpoint_return_id']) {
        $checkpoint_id = $sj['last_checkpoint_return_id'];
        $tipe = 'PULANG';
    }

    $result = ['status' => $sj['status'], 'tipe' => $tipe, 'position' => null];

    if ($checkpoint_id) {
        $q_pos = mysqli_query($conn, "
            SELECT mp.nama_kota, mp.latitude, mp.longitude, sc.waktu
            FROM sj_checkpoints sc
            JOIN route_details rd ON sc.id_route_detail = rd.id
            JOIN master_points mp ON rd.id_master_point = mp.id
            WHERE sc.id = '$checkpoint_id'
        ");
        if ($pos = mysqli_fetch_assoc($q_pos)) {
            $result['position'] = [
                'nama_kota' => $pos['nama_kota'],
                'latitude'  => (float)$pos['latitude'],
                'longitude' => (float)$pos['longitude'],
                'waktu'     => $pos['waktu']
            ];
        }
    }

    echo json_encode($result);
    exit();
}

// =============================================
// GET ALL ACTIVE TRIPS (for overview map)
// =============================================
if ($action == 'get_active_trips') {
    $q = mysqli_query($conn, "
        SELECT sj.id, sj.no_sj, sj.kota_tujuan, sj.last_checkpoint_id,
               l.nama_lengkap as nama_driver, a.nopol
        FROM surat_jalan sj
        JOIN driver d ON sj.id_driver = d.id
        JOIN login l ON d.id_login = l.id
        JOIN armada a ON sj.id_armada = a.id
        WHERE sj.status = 'ON TRIP' AND sj.id_route IS NOT NULL
    ");

    $trips = [];
    while ($r = mysqli_fetch_assoc($q)) {
        $pos = null;
        if ($r['last_checkpoint_id']) {
            $q_p = mysqli_query($conn, "
                SELECT mp.latitude, mp.longitude, mp.nama_kota
                FROM sj_checkpoints sc
                JOIN route_details rd ON sc.id_route_detail = rd.id
                JOIN master_points mp ON rd.id_master_point = mp.id
                WHERE sc.id = '{$r['last_checkpoint_id']}'
            ");
            $pos = mysqli_fetch_assoc($q_p);
        }
        $trips[] = [
            'id'        => $r['id'],
            'no_sj'     => $r['no_sj'],
            'tujuan'    => $r['kota_tujuan'],
            'driver'    => $r['nama_driver'],
            'nopol'     => $r['nopol'],
            'position'  => $pos ? ['lat' => (float)$pos['latitude'], 'lng' => (float)$pos['longitude'], 'kota' => $pos['nama_kota']] : null
        ];
    }
    echo json_encode($trips);
    exit();
}

echo json_encode(['error' => 'invalid action']);
?>
