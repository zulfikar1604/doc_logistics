<?php
session_start();
require 'koneksi.php';

// Pastikan yang mengakses file ini hanya Admin yang sudah login
if (!isset($_SESSION['log']) || $_SESSION['role'] != 'MANAGER') {
    echo "<script>alert('Akses Ditolak!'); window.location.href='index.php';</script>";
    exit();
}

if (isset($_POST['simpan_user'])) {
    // 1. Tangkap semua data dari form modal
    // Gunakan real_escape_string untuk mencegah error jika ada tanda petik (') pada nama
    $nama_lengkap = mysqli_real_escape_string($conn, $_POST['nama_lengkap']);
    $username     = mysqli_real_escape_string($conn, $_POST['username']);
    $email        = mysqli_real_escape_string($conn, $_POST['email']);
    $password     = mysqli_real_escape_string($conn, $_POST['password']);
    $role         = mysqli_real_escape_string($conn, $_POST['role']);

    // 2. Cek apakah email atau username sudah pernah didaftarkan sebelumnya
    $cek_email = mysqli_query($conn, "SELECT email FROM login WHERE email='$email' OR username='$username'");
    
    if (mysqli_num_rows($cek_email) > 0) {
        // Jika sudah ada yang pakai, kembalikan dengan pesan error
        echo "<script>alert('Gagal! Email atau Username sudah digunakan oleh user lain.'); window.location.href='user_management.php';</script>";
    } else {
        // 3. Masukkan data ke tabel login
        // Password di-hash untuk keamanan
        $hashed_pw = password_hash($password, PASSWORD_DEFAULT);
        $query_insert = "INSERT INTO login (nama_lengkap, username, email, password, role) 
                         VALUES ('$nama_lengkap', '$username', '$email', '$hashed_pw', '$role')";
        
        $eksekusi = mysqli_query($conn, $query_insert);

        // 4. Berikan notifikasi sukses dan kembalikan ke halaman manajemen user
        if ($eksekusi) {
            echo "<script>alert('Berhasil! Pengguna baru telah didaftarkan.'); window.location.href='user_management.php';</script>";
        } else {
            echo "<script>alert('Error Database: " . mysqli_error($conn) . "'); window.location.href='user_management.php';</script>";
        }
    }
} else {
    // Jika file dibuka langsung tanpa lewat form
    header('location:user_management.php');
}
?>