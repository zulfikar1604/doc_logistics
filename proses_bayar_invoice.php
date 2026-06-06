<?php
session_start();
require 'koneksi.php';

if (!isset($_SESSION['log'])) {
    header('location:index.php');
    exit();
}
$role = isset($_SESSION['role']) ? strtoupper($_SESSION['role']) : 'MANAGER';
if ($role == 'DRIVER') {
    header('location:dashboard_driver.php');
    exit();
}

if (isset($_POST['konfirmasi_lunas'])) {
    $id_invoice = mysqli_real_escape_string($conn, $_POST['id_invoice']);
    
    // Check if invoice is already paid to prevent duplicate processing
    $q_check = mysqli_query($conn, "SELECT status, no_invoice FROM invoice WHERE id='$id_invoice'");
    if ($q_check && mysqli_num_rows($q_check) > 0) {
        $d_check = mysqli_fetch_assoc($q_check);
        if ($d_check['status'] == 'PAID') {
            echo "<script>alert('Error: Invoice ini sudah lunas sebelumnya!'); window.location.href='manajemen_invoice.php';</script>";
            exit();
        }
        $no_inv = $d_check['no_invoice'];
        
        // Update invoice status
        mysqli_query($conn, "UPDATE invoice SET status='PAID' WHERE id='$id_invoice'");
        
        if (function_exists('buat_notifikasi')) {
            buat_notifikasi($conn, 'MANAGER,ADMIN', 0, "Pembayaran Diterima", "Invoice $no_inv telah dilunasi.", "success", "manajemen_invoice.php");
        }
    }
    
    echo "<script>alert('Invoice berhasil dikonfirmasi LUNAS!'); window.location.href='manajemen_invoice.php';</script>";
    exit();
} else {
    header('location:manajemen_invoice.php');
    exit();
}
?>
