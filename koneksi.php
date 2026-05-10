<?php
$conn = mysqli_connect("localhost", "root", "", "doclogistics");

// Cek koneksi biar gak lieur
if (mysqli_connect_errno()) {
    echo "Koneksi database gagal : " . mysqli_connect_error();
}
?>