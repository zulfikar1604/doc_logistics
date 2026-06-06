<?php
session_start();
require 'koneksi.php';

// Pastikan yang mengakses file ini hanya Admin yang sudah login
if (!isset($_SESSION['log']) || $_SESSION['role'] != 'ADMIN') {
    echo "<script>alert('Akses Ditolak! Hanya Admin yang dapat mengedit user.'); window.location.href='index.php';</script>";
    exit();
}

if (isset($_POST['update'])) {
    $id_user = mysqli_real_escape_string($conn, $_POST['id_user']);
    $nama = mysqli_real_escape_string($conn, $_POST['nama']);
    $role = mysqli_real_escape_string($conn, $_POST['role']);

    // Update data di tabel login
    $query_update = "UPDATE login SET nama_lengkap='$nama', role='$role' WHERE id='$id_user'";
    
    $eksekusi = mysqli_query($conn, $query_update);

    if ($eksekusi) {
        echo "<script>alert('Berhasil! Data pengguna telah diperbarui.'); window.location.href='user_management.php';</script>";
    } else {
        echo "<script>alert('Error Database: " . mysqli_error($conn) . "'); window.location.href='user_management.php';</script>";
    }
} else {
    header('location:user_management.php');
}
?>
