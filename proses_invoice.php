<?php
session_start();
require 'koneksi.php';

// Pastikan user sudah login
if (!isset($_SESSION['log'])) {
    header('location:index.php');
    exit();
}

if (isset($_POST['nama_klien'])) {
    // 1. Tangkap Data Header Invoice
    // Nomor invoice dibuat otomatis berdasarkan tanggal dan random number
    $no_invoice   = "#INV-" . date('Ymd') . "-" . rand(100, 999);
    $tgl_invoice  = date('Y-m-d');
    $nama_klien   = mysqli_real_escape_string($conn, $_POST['nama_klien']);
    $alamat_klien = mysqli_real_escape_string($conn, $_POST['alamat_klien']);
    $metode_bayar = mysqli_real_escape_string($conn, $_POST['metode_bayar']);
    $jatuh_tempo  = mysqli_real_escape_string($conn, $_POST['jatuh_tempo']);

    // Mulai Transaksi Database
    mysqli_begin_transaction($conn);

    try {
        // 2. Simpan ke Tabel Induk (invoice)
        // Kita hitung total kotor dulu dari array untuk disimpan di header
        $total_tagihan = 0;
        $tarif_arr = $_POST['tarif'];
        $qty_arr   = $_POST['qty'];

        for ($i = 0; $i < count($tarif_arr); $i++) {
            $total_tagihan += ($tarif_arr[$i] * $qty_arr[$i]);
        }
        
        // Tambahkan PPN 11%
        $grand_total = $total_tagihan + ($total_tagihan * 0.11);

        $query_inv = "INSERT INTO invoice (no_invoice, tgl_invoice, nama_klien, alamat_klien, metode_bayar, jatuh_tempo, total_tagihan, status) 
                      VALUES ('$no_invoice', '$tgl_invoice', '$nama_klien', '$alamat_klien', '$metode_bayar', '$jatuh_tempo', '$grand_total', 'UNPAID')";
        
        mysqli_query($conn, $query_inv);
        $id_invoice_baru = mysqli_insert_id($conn);

        // 3. Simpan Rincian Biaya (invoice_detail)
        $deskripsi_arr = $_POST['deskripsi'];

        for ($i = 0; $i < count($deskripsi_arr); $i++) {
            if (!empty($deskripsi_arr[$i])) {
                $desc  = mysqli_real_escape_string($conn, $deskripsi_arr[$i]);
                $trf   = mysqli_real_escape_string($conn, $tarif_arr[$i]);
                $qty   = mysqli_real_escape_string($conn, $qty_arr[$i]);
                $sub   = $trf * $qty;

                $query_det = "INSERT INTO invoice_detail (id_invoice, deskripsi, tarif, qty, subtotal) 
                              VALUES ('$id_invoice_baru', '$desc', '$trf', '$qty', '$sub')";
                mysqli_query($conn, $query_det);
            }
        }

        // Komit Transaksi
        mysqli_commit($conn);

        // Buat Notifikasi ke Manager
        if (function_exists('buat_notifikasi')) {
            buat_notifikasi($conn, 'MANAGER', 0, "Invoice Baru Diterbitkan", "Invoice $no_invoice untuk $nama_klien berhasil dibuat.", "success", "manajemen_invoice.php");
        }

        // Berhasil! Langsung arahkan ke halaman cetak atau daftar invoice
        echo "<script>
                alert('Invoice $no_invoice berhasil diterbitkan!');
                window.location.href='manajemen_invoice.php'; 
              </script>";

    } catch (Exception $e) {
        mysqli_rollback($conn);
        echo "<script>
                alert('Gagal menyimpan invoice: " . $e->getMessage() . "');
                window.location.href='invoice_pengiriman.php';
              </script>";
    }

} else {
    header('location:invoice_pengiriman.php');
}
?>