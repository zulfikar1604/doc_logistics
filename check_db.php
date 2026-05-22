<?php
require 'koneksi.php';

function desc($conn, $table) {
    echo "=== $table ===\n";
    $q = mysqli_query($conn, "DESCRIBE $table");
    while($r = mysqli_fetch_assoc($q)) {
        echo $r['Field'] . "\n";
    }
}

desc($conn, 'login');
desc($conn, 'stok_barang');
desc($conn, 'kategori_barang');
desc($conn, 'surat_jalan_detail');
?>
