<?php
session_start();
require 'koneksi.php';

header('Content-Type: application/json');

if (!isset($_SESSION['log'])) {
    echo json_encode(['results' => []]);
    exit();
}

$q = isset($_GET['q']) ? trim(mysqli_real_escape_string($conn, $_GET['q'])) : '';

if (strlen($q) < 2) {
    echo json_encode(['results' => []]);
    exit();
}

$results = [];

// 1. Cari Surat Jalan (no_sj, kota_tujuan)
$r = mysqli_query($conn, "SELECT sj.id, sj.no_sj, sj.kota_tujuan, sj.status, l.nama_lengkap as driver
                           FROM surat_jalan sj
                           LEFT JOIN driver d ON sj.id_driver = d.id
                           LEFT JOIN login l ON d.id_login = l.id
                           WHERE sj.no_sj LIKE '%$q%' OR sj.kota_tujuan LIKE '%$q%'
                           LIMIT 4");
while ($row = mysqli_fetch_assoc($r)) {
    $results[] = [
        'type'     => 'surat_jalan',
        'category' => 'Surat Jalan',
        'title'    => $row['no_sj'],
        'subtitle' => 'Tujuan: ' . $row['kota_tujuan'] . ' · Driver: ' . ($row['driver'] ?? '-') . ' · ' . $row['status'],
        'link'     => 'status_pengiriman.php',
    ];
}

// 2. Cari Driver (nama)
$r = mysqli_query($conn, "SELECT l.nama_lengkap, d.no_hp, d.status_kerja
                           FROM driver d
                           JOIN login l ON d.id_login = l.id
                           WHERE l.nama_lengkap LIKE '%$q%' OR d.no_hp LIKE '%$q%'
                           LIMIT 3");
while ($row = mysqli_fetch_assoc($r)) {
    $results[] = [
        'type'     => 'driver',
        'category' => 'Driver',
        'title'    => $row['nama_lengkap'],
        'subtitle' => 'HP: ' . $row['no_hp'] . ' · Status: ' . $row['status_kerja'],
        'link'     => 'data_driver.php',
    ];
}

// 3. Cari Barang Stok
$r = mysqli_query($conn, "SELECT nama_barang, stok, satuan, kategori 
                           FROM stok_barang 
                           WHERE nama_barang LIKE '%$q%' OR kategori LIKE '%$q%'
                           LIMIT 3");
while ($row = mysqli_fetch_assoc($r)) {
    $results[] = [
        'type'     => 'barang',
        'category' => 'Stok Barang',
        'title'    => $row['nama_barang'],
        'subtitle' => 'Kategori: ' . $row['kategori'] . ' · Stok: ' . $row['stok'] . ' ' . $row['satuan'],
        'link'     => 'stok_barang.php',
    ];
}

// 4. Cari Armada (nopol, tipe)
$r = mysqli_query($conn, "SELECT nopol, tipe, status 
                           FROM armada 
                           WHERE nopol LIKE '%$q%' OR tipe LIKE '%$q%'
                           LIMIT 3");
while ($row = mysqli_fetch_assoc($r)) {
    $results[] = [
        'type'     => 'armada',
        'category' => 'Armada',
        'title'    => $row['nopol'] . ' — ' . $row['tipe'],
        'subtitle' => 'Status: ' . $row['status'],
        'link'     => 'data_armada.php',
    ];
}

// 5. Cari Invoice (no_invoice, nama_klien)
$r = mysqli_query($conn, "SELECT no_invoice, nama_klien, status, total_tagihan
                           FROM invoice
                           WHERE no_invoice LIKE '%$q%' OR nama_klien LIKE '%$q%'
                           LIMIT 3");
while ($row = mysqli_fetch_assoc($r)) {
    $results[] = [
        'type'     => 'invoice',
        'category' => 'Invoice',
        'title'    => $row['no_invoice'] . ' — ' . $row['nama_klien'],
        'subtitle' => 'Rp ' . number_format($row['total_tagihan'], 0, ',', '.') . ' · ' . $row['status'],
        'link'     => 'manajemen_invoice.php',
    ];
}

echo json_encode(['results' => $results]);
?>
