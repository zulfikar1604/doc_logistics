<?php
session_start();
require 'koneksi.php';

// Pastikan user sudah login sebelum memproses data
if (!isset($_SESSION['log'])) {
    header('location:login.php');
    exit();
}

if (isset($_POST['tgl_kirim'])) {
    // 1. Tangkap Data Utama (Header)
    // Nomor Surat Jalan dibuat otomatis di form, kita tangkap di sini
    // Format asli di form: #SJ-20260412-001, kita bersihkan tanda # jika ingin disimpan murni
    $no_sj          = mysqli_real_escape_string($conn, $_POST['no_sj'] ?? '#SJ-'.date('Ymd').'-001');
    $tgl_kirim      = mysqli_real_escape_string($conn, $_POST['tgl_kirim']);
    $prioritas      = mysqli_real_escape_string($conn, $_POST['prioritas']);
    $id_driver      = mysqli_real_escape_string($conn, $_POST['driver_id']);
    $id_armada      = mysqli_real_escape_string($conn, $_POST['kendaraan_id']);
    $penerima       = mysqli_real_escape_string($conn, $_POST['penerima']);
    $kota_tujuan    = mysqli_real_escape_string($conn, $_POST['kota_tujuan']);
    $alamat_tujuan  = mysqli_real_escape_string($conn, $_POST['alamat_tujuan']);

    // Mulai Transaksi Database (Agar jika salah satu gagal, semua dibatalkan)
    mysqli_begin_transaction($conn);

    try {
        // 2. Simpan ke Tabel Induk (surat_jalan)
        $query_sj = "INSERT INTO surat_jalan (no_sj, tgl_kirim, prioritas, id_driver, id_armada, penerima, kota_tujuan, alamat_tujuan, status) 
                     VALUES ('$no_sj', '$tgl_kirim', '$prioritas', '$id_driver', '$id_armada', '$penerima', '$kota_tujuan', '$alamat_tujuan', 'ON TRIP')";
        
        $insert_sj = mysqli_query($conn, $query_sj);

        // Ambil ID yang baru saja digenerate oleh database
        $id_sj_terbaru = mysqli_insert_id($conn);

        // 3. Simpan Rincian Barang (Looping Array dari Form)
        $nama_barang = $_POST['nama_barang']; // Ini berbentuk array karena name="nama_barang[]"
        $jumlah      = $_POST['jumlah'];
        $satuan      = $_POST['satuan'];
        $keterangan  = $_POST['keterangan'];

        for ($i = 0; $i < count($nama_barang); $i++) {
            // Pastikan baris barang tidak kosong
            if (!empty($nama_barang[$i])) {
                $item_nama  = mysqli_real_escape_string($conn, $nama_barang[$i]);
                $item_qty   = mysqli_real_escape_string($conn, $jumlah[$i]);
                $item_sat   = mysqli_real_escape_string($conn, $satuan[$i]);
                $item_ket   = mysqli_real_escape_string($conn, $keterangan[$i]);

                $query_detail = "INSERT INTO surat_jalan_detail (id_sj, nama_barang, jumlah, satuan, keterangan) 
                                 VALUES ('$id_sj_terbaru', '$item_nama', '$item_qty', '$item_sat', '$item_ket')";
                mysqli_query($conn, $query_detail);
            }
        }

        // 4. Update Status Driver menjadi 'ON TRIP'
        mysqli_query($conn, "UPDATE driver SET status_kerja = 'ON TRIP' WHERE id = '$id_driver'");

        // Jika semua berhasil, simpan permanen
        mysqli_commit($conn);

        echo "<script>
                alert('Berhasil! Surat Jalan $no_sj telah diterbitkan.');
                window.location.href='manajemen_dokumen.php';
              </script>";

    } catch (Exception $e) {
        // Jika ada error, batalkan semua perubahan data
        mysqli_rollback($conn);
        echo "<script>
                alert('Gagal menyimpan data: " . $e->getMessage() . "');
                window.location.href='buat_surat_jalan.php';
              </script>";
    }

} else {
    // Jika diakses tanpa submit form
    header('location:buat_surat_jalan.php');
}
?>