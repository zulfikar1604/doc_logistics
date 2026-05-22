<?php
session_start();
require 'koneksi.php';

if (!isset($_SESSION['log'])) {
    header('location:index.php');
    exit();
}

if (isset($_POST['konfirmasi_lunas'])) {
    $id_invoice = mysqli_real_escape_string($conn, $_POST['id_invoice']);
    
    // Update invoice status
    mysqli_query($conn, "UPDATE invoice SET status='PAID' WHERE id='$id_invoice'");
    
    $q_inv = mysqli_query($conn, "SELECT no_invoice FROM invoice WHERE id='$id_invoice'");
    if ($q_inv && mysqli_num_rows($q_inv) > 0) {
        $d_inv = mysqli_fetch_assoc($q_inv);
        $no_inv = $d_inv['no_invoice'];
        if (function_exists('buat_notifikasi')) {
            buat_notifikasi($conn, 'MANAGER', 0, "Pembayaran Diterima", "Invoice $no_inv telah dilunasi.", "success", "manajemen_invoice.php");
        }
    }
    
    echo "<script>alert('Invoice berhasil dikonfirmasi LUNAS!'); window.location.href='invoice_pengiriman.php';</script>";
    exit();
} else {
    header('location:invoice_pengiriman.php');
    exit();
}
?>
