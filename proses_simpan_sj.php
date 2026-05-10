<?php
session_start();
require 'koneksi.php';

if (!isset($_SESSION['log'])) {
    header('location:login.php');
    exit();
}

if (isset($_POST['tgl_kirim'])) {
    $no_sj          = mysqli_real_escape_string($conn, $_POST['no_sj'] ?? '#SJ-'.date('Ymd').'-001');
    $tgl_kirim      = mysqli_real_escape_string($conn, $_POST['tgl_kirim']);
    $prioritas      = mysqli_real_escape_string($conn, $_POST['prioritas']);
    $id_driver      = mysqli_real_escape_string($conn, $_POST['driver_id']);
    $id_armada      = mysqli_real_escape_string($conn, $_POST['kendaraan_id']);
    $penerima       = mysqli_real_escape_string($conn, $_POST['penerima']);
    $kota_tujuan    = mysqli_real_escape_string($conn, $_POST['kota_tujuan']);
    $zona_pengiriman= mysqli_real_escape_string($conn, $_POST['zona_pengiriman']);
    $alamat_tujuan  = mysqli_real_escape_string($conn, $_POST['alamat_tujuan']);

    // Tentukan Tarif Ongkir berdasarkan Zona
    $tarif_per_satuan = 0;
    if ($zona_pengiriman == '1') {
        $tarif_per_satuan = 50000;
    } elseif ($zona_pengiriman == '2') {
        $tarif_per_satuan = 150000;
    } elseif ($zona_pengiriman == '3') {
        $tarif_per_satuan = 300000;
    }

    mysqli_begin_transaction($conn);

    try {
        // 1. Simpan Surat Jalan (Status DRAFT - Menunggu Pembayaran)
        $query_sj = "INSERT INTO surat_jalan (no_sj, tgl_kirim, prioritas, id_driver, id_armada, penerima, kota_tujuan, alamat_tujuan, status) 
                     VALUES ('$no_sj', '$tgl_kirim', '$prioritas', '$id_driver', '$id_armada', '$penerima', '$kota_tujuan', '$alamat_tujuan', 'DRAFT')";
        mysqli_query($conn, $query_sj);
        $id_sj_terbaru = mysqli_insert_id($conn);

        // 2. Kalkulasi Total dan Simpan Rincian SJ
        $nama_barang = $_POST['nama_barang'];
        $jumlah      = $_POST['jumlah'];
        $satuan      = $_POST['satuan'];
        $keterangan  = $_POST['keterangan'];

        $total_qty = 0;
        $total_harga_barang = 0;
        
        // Array untuk menyimpan detail invoice barang
        $invoice_items = [];

        for ($i = 0; $i < count($nama_barang); $i++) {
            if (!empty($nama_barang[$i])) {
                $item_nama  = mysqli_real_escape_string($conn, $nama_barang[$i]);
                $item_qty   = (int)mysqli_real_escape_string($conn, $jumlah[$i]);
                $item_sat   = mysqli_real_escape_string($conn, $satuan[$i]);
                $item_ket   = mysqli_real_escape_string($conn, $keterangan[$i]);

                // Insert Surat Jalan Detail
                mysqli_query($conn, "INSERT INTO surat_jalan_detail (id_sj, nama_barang, jumlah, satuan, keterangan) VALUES ('$id_sj_terbaru', '$item_nama', '$item_qty', '$item_sat', '$item_ket')");

                // Hitung Harga Barang dari Database Stok
                $q_harga = mysqli_query($conn, "SELECT harga FROM stok_barang WHERE nama_barang = '$item_nama' LIMIT 1");
                $data_harga = mysqli_fetch_assoc($q_harga);
                $harga_satuan = $data_harga ? (float)$data_harga['harga'] : 0;
                
                $subtotal_item = $harga_satuan * $item_qty;
                
                $total_qty += $item_qty;
                $total_harga_barang += $subtotal_item;

                $invoice_items[] = [
                    'deskripsi' => "Barang: $item_nama",
                    'tarif' => $harga_satuan,
                    'qty' => $item_qty,
                    'subtotal' => $subtotal_item
                ];
            }
        }

        // 3. Kalkulasi Ongkir dan PPN
        $ongkos_kirim = $tarif_per_satuan * $total_qty;
        $subtotal_sebelum_pajak = $total_harga_barang + $ongkos_kirim;
        $ppn = $subtotal_sebelum_pajak * 0.11;
        $total_tagihan_akhir = $subtotal_sebelum_pajak + $ppn;

        // 4. Generate Invoice Baru
        $no_invoice = '#INV-'.date('Ymd').'-'.rand(100,999);
        $tgl_invoice = date('Y-m-d');
        
        $query_inv = "INSERT INTO invoice (id_sj, no_invoice, tgl_invoice, nama_klien, alamat_klien, total_tagihan, status) 
                      VALUES ('$id_sj_terbaru', '$no_invoice', '$tgl_invoice', '$penerima', '$alamat_tujuan', '$total_tagihan_akhir', 'UNPAID')";
        mysqli_query($conn, $query_inv);
        $id_invoice_terbaru = mysqli_insert_id($conn);

        // 5. Simpan Invoice Detail
        // 5.a Barang-barang
        foreach ($invoice_items as $item) {
            mysqli_query($conn, "INSERT INTO invoice_detail (id_invoice, deskripsi, tarif, qty, subtotal) VALUES ('$id_invoice_terbaru', '{$item['deskripsi']}', '{$item['tarif']}', '{$item['qty']}', '{$item['subtotal']}')");
        }
        
        // 5.b Ongkos Kirim
        $desc_ongkir = "Biaya Pengiriman (Zona $zona_pengiriman)";
        mysqli_query($conn, "INSERT INTO invoice_detail (id_invoice, deskripsi, tarif, qty, subtotal) VALUES ('$id_invoice_terbaru', '$desc_ongkir', '$tarif_per_satuan', '$total_qty', '$ongkos_kirim')");
        
        // 5.c PPN
        $desc_ppn = "PPN 11%";
        mysqli_query($conn, "INSERT INTO invoice_detail (id_invoice, deskripsi, tarif, qty, subtotal) VALUES ('$id_invoice_terbaru', '$desc_ppn', '$ppn', '1', '$ppn')");

        // 6. Update Status Driver & Armada menjadi BOOKED (Dipesan, belum berangkat)
        mysqli_query($conn, "UPDATE driver SET status_kerja = 'BOOKED' WHERE id = '$id_driver'");
        mysqli_query($conn, "UPDATE armada SET status = 'BOOKED' WHERE id = '$id_armada'");

        mysqli_commit($conn);

        echo "<script>
                alert('Berhasil! Surat Jalan diterbitkan dengan status DRAFT. Invoice otomatis terbuat. Silakan lunasi Invoice terlebih dahulu agar driver bisa berangkat.');
                window.location.href='invoice_pengiriman.php';
              </script>";

    } catch (Exception $e) {
        mysqli_rollback($conn);
        echo "<script>
                alert('Gagal menyimpan data: " . $e->getMessage() . "');
                window.location.href='buat_surat_jalan.php';
              </script>";
    }

} else {
    header('location:buat_surat_jalan.php');
}
?>