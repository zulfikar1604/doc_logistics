<?php
require 'koneksi.php';
header('Content-Type: text/html; charset=utf-8');

echo "<h2>🔧 Auto-Fix: Koordinat Kota Indonesia</h2>";

// Koordinat yang BENAR untuk kota-kota Indonesia
$correct_coords = [
    'Pelabuhan Merak' => ['lat' => -6.0120, 'lng' => 105.9330],
    'Bandar Lampung'  => ['lat' => -5.3971, 'lng' => 105.2668],
    'Subang'          => ['lat' => -6.5708, 'lng' => 107.7583],
    'Palembang'       => ['lat' => -2.9761, 'lng' => 104.7754],
    'Jambi'           => ['lat' => -1.6101, 'lng' => 103.6131],
    'Padang'          => ['lat' => -0.9471, 'lng' => 100.4172],
    'Pekanbaru'       => ['lat' => 0.5071, 'lng' => 101.4478],
    'Batam'           => ['lat' => 1.0456, 'lng' => 104.0305],
    'Medan'           => ['lat' => 3.5952, 'lng' => 98.6722],
    'Aceh'            => ['lat' => 5.5483, 'lng' => 95.3238],
    'Banda Aceh'      => ['lat' => 5.5483, 'lng' => 95.3238],
];

echo "<table border='1' cellpadding='8' style='border-collapse:collapse; font-family:sans-serif;'>";
echo "<tr style='background:#eee;'><th>Nama Kota</th><th>Lat Lama</th><th>Lng Lama</th><th>→ Lat Baru</th><th>→ Lng Baru</th><th>Status</th></tr>";

$q = mysqli_query($conn, "SELECT * FROM master_points ORDER BY nama_kota ASC");
$fixed = 0;
while ($r = mysqli_fetch_assoc($q)) {
    $nama = trim($r['nama_kota']);
    
    // Cek apakah kota ini ada di daftar koreksi
    if (isset($correct_coords[$nama])) {
        $new_lat = $correct_coords[$nama]['lat'];
        $new_lng = $correct_coords[$nama]['lng'];
        $old_lat = floatval($r['latitude']);
        $old_lng = floatval($r['longitude']);
        
        // Cek apakah perlu diperbaiki (beda signifikan)
        if (abs($old_lat - $new_lat) > 0.5 || abs($old_lng - $new_lng) > 0.5) {
            $id = $r['id'];
            mysqli_query($conn, "UPDATE master_points SET latitude='$new_lat', longitude='$new_lng' WHERE id='$id'");
            echo "<tr style='background:#fff3cd;'>";
            echo "<td><strong>$nama</strong></td>";
            echo "<td style='color:red;'>$old_lat</td><td style='color:red;'>$old_lng</td>";
            echo "<td style='color:green;font-weight:bold;'>$new_lat</td><td style='color:green;font-weight:bold;'>$new_lng</td>";
            echo "<td>🔧 DIPERBAIKI!</td>";
            echo "</tr>";
            $fixed++;
        } else {
            echo "<tr style='background:#d4edda;'>";
            echo "<td>$nama</td><td>{$r['latitude']}</td><td>{$r['longitude']}</td><td colspan='2'>-</td><td>✅ Sudah benar</td></tr>";
        }
    } else {
        // Kota yang tidak ada di daftar koreksi - cek apakah latitude-nya masuk akal
        $lat = floatval($r['latitude']);
        $status = ($lat > 6 || $lat < -11) ? '⚠️ Cek manual!' : '✅ OK';
        $bg = ($lat > 6 || $lat < -11) ? '#f8d7da' : '#d4edda';
        echo "<tr style='background:$bg;'>";
        echo "<td>$nama</td><td>{$r['latitude']}</td><td>{$r['longitude']}</td><td colspan='2'>-</td><td>$status</td></tr>";
    }
}
echo "</table>";
echo "<br><p style='font-size:1.2rem;'><strong>✅ $fixed kota diperbaiki koordinatnya.</strong></p>";

if ($fixed > 0) {
    echo "<p>Sekarang buka <a href='manajemen_rute.php'><strong>Manajemen Rute</strong></a> dan coba lagi. Rute existing perlu di-recalculate: <a href='proses_rute.php?recalculate_all'><strong>Klik di sini untuk recalculate semua rute</strong></a></p>";
}
?>
