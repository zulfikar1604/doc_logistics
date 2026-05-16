<?php
session_start();
require 'koneksi.php';

if (!isset($_SESSION['log'])) {
    header('location:login.php');
    exit();
}

if (isset($_POST['konfirmasi_lunas'])) {
    $id_invoice = mysqli_real_escape_string($conn, $_POST['id_invoice']);
    
    // Update invoice status
    mysqli_query($conn, "UPDATE invoice SET status='PAID' WHERE id='$id_invoice'");
    
    echo "<script>alert('Invoice berhasil dikonfirmasi LUNAS!'); window.location.href='invoice_pengiriman.php';</script>";
    exit();
} else {
    header('location:invoice_pengiriman.php');
    exit();
}
?>
