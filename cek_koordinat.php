<?php
require 'koneksi.php';
header('Content-Type: text/html; charset=utf-8');
echo "<h2>Cek Koordinat Master Points</h2>";
echo "<table border='1' cellpadding='8' style='border-collapse:collapse; font-family:monospace;'>";
echo "<tr style='background:#eee;'><th>ID</th><th>Nama Kota</th><th>Latitude</th><th>Longitude</th><th>Status</th></tr>";

$q = mysqli_query($conn, "SELECT * FROM master_points ORDER BY nama_kota ASC");
while ($r = mysqli_fetch_assoc($q)) {
    $lat = floatval($r['latitude']);
    $lng = floatval($r['longitude']);
    
    // Indonesia: lat harus negatif (-11 s/d 6), lng harus positif (95 s/d 141)
    $status = '✅ OK';
    $color = '#d4edda';
    if ($lat > 0) {
        $status = '❌ LATITUDE POSITIF! Seharusnya negatif (minus) untuk Indonesia';
        $color = '#f8d7da';
    }
    if ($lng < 0 || $lng > 180) {
        $status = '❌ LONGITUDE ANEH!';
        $color = '#f8d7da';
    }
    
    echo "<tr style='background:$color;'>";
    echo "<td>{$r['id']}</td>";
    echo "<td><strong>{$r['nama_kota']}</strong></td>";
    echo "<td>{$r['latitude']}</td>";
    echo "<td>{$r['longitude']}</td>";
    echo "<td>$status</td>";
    echo "</tr>";
}
echo "</table>";
echo "<br><p><strong>Catatan:</strong> Semua kota di Indonesia harus punya latitude NEGATIF (minus). Jika ada yang positif, itu salah dan harus diperbaiki.</p>";
?>
