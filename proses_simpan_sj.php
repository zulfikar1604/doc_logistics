<?php
session_start();
require 'koneksi.php';

if (!isset($_SESSION['log'])) {
    header('location:index.php');
    exit();
}

$role = isset($_SESSION['role']) ? strtoupper($_SESSION['role']) : 'MANAGER';
if ($role != 'ADMIN') {
    echo "<script>alert('Akses Ditolak! Hanya Admin yang dapat memproses dan menerbitkan Surat Jalan.'); window.location.href='manajemen_dokumen.php';</script>";
    exit();
}

if (isset($_POST['tgl_kirim'])) {
    $no_sj          = mysqli_real_escape_string($conn, $_POST['no_sj'] ?? 'SJ-'.date('Ymd').'-001');
    $tgl_kirim      = mysqli_real_escape_string($conn, $_POST['tgl_kirim']);
    $prioritas      = mysqli_real_escape_string($conn, $_POST['prioritas']);
    $id_driver      = mysqli_real_escape_string($conn, $_POST['driver_id']);
    $id_armada      = mysqli_real_escape_string($conn, $_POST['kendaraan_id']);
    $penerima       = mysqli_real_escape_string($conn, $_POST['penerima']);
    $kota_tujuan    = mysqli_real_escape_string($conn, $_POST['kota_tujuan']);
    $id_route       = mysqli_real_escape_string($conn, $_POST['id_route']);
    $alamat_tujuan  = mysqli_real_escape_string($conn, $_POST['alamat_tujuan']);

    // Tentukan Tarif Ongkir dan Rute Pulang berdasarkan Rute Terpilih
    $q_rute = mysqli_query($conn, "SELECT id, nama_rute, total_biaya_rute FROM routes WHERE id='$id_route'");
    $dt_rute = mysqli_fetch_assoc($q_rute);
    $ongkos_kirim = $dt_rute ? $dt_rute['total_biaya_rute'] : 0;
    $nama_rute = $dt_rute ? $dt_rute['nama_rute'] : 'Rute Manual';

    // Cari rute pulang (return route)
    $q_ret = mysqli_query($conn, "SELECT id FROM routes WHERE id_rute_induk='$id_route' AND tipe_rute='PULANG'");
    $dt_ret = mysqli_fetch_assoc($q_ret);
    $id_return_route = $dt_ret ? $dt_ret['id'] : 'NULL';

    mysqli_begin_transaction($conn);

    try {
        // 1. Simpan Surat Jalan (Status WAITING CONFIRMATION - Menunggu driver konfirmasi truk)
        $query_sj = "INSERT INTO surat_jalan (no_sj, tgl_kirim, prioritas, id_driver, id_armada, penerima, kota_tujuan, alamat_tujuan, status, id_route, id_return_route) 
                     VALUES ('$no_sj', '$tgl_kirim', '$prioritas', '$id_driver', '$id_armada', '$penerima', '$kota_tujuan', '$alamat_tujuan', 'WAITING CONFIRMATION', '$id_route', $id_return_route)";
        mysqli_query($conn, $query_sj);
        $id_sj_terbaru = mysqli_insert_id($conn);

        // 2. Kalkulasi Total dan Simpan Rincian SJ
        $id_barang_arr = $_POST['id_barang'] ?? [];
        $jumlah      = $_POST['jumlah'] ?? [];
        $satuan      = $_POST['satuan'] ?? [];
        $keterangan  = $_POST['keterangan'] ?? [];

        $total_qty = 0;
        $total_harga_barang = 0;
        $invoice_items = [];

        // Agregasi barang duplikat dalam form untuk mencegah stok negatif
        $aggregated_items = [];
        for ($i = 0; $i < count($id_barang_arr); $i++) {
            if (!empty($id_barang_arr[$i])) {
                $id_brg = (int)$id_barang_arr[$i];
                $item_qty = (int)$jumlah[$i];
                $item_sat = mysqli_real_escape_string($conn, $satuan[$i] ?? '');
                $item_ket = mysqli_real_escape_string($conn, $keterangan[$i] ?? '');
                
                if (isset($aggregated_items[$id_brg])) {
                    $aggregated_items[$id_brg]['qty'] += $item_qty;
                    if ($item_ket) {
                        $aggregated_items[$id_brg]['ket'] .= " | " . $item_ket;
                    }
                } else {
                    $aggregated_items[$id_brg] = [
                        'qty' => $item_qty,
                        'sat' => $item_sat,
                        'ket' => $item_ket
                    ];
                }
            }
        }

        foreach ($aggregated_items as $id_brg => $data) {
            $item_qty = $data['qty'];
            $item_sat = $data['sat'];
            $item_ket = $data['ket'];

            // Cek stok berdasarkan ID
            $q_stok = mysqli_query($conn, "SELECT nama_barang, harga, stok FROM stok_barang WHERE id = '$id_brg' LIMIT 1");
            $data_stok = mysqli_fetch_assoc($q_stok);
            
            if (!$data_stok) {
                throw new Exception("Barang dengan ID '$id_brg' tidak ditemukan di gudang.");
            }
            
            $item_nama = mysqli_real_escape_string($conn, $data_stok['nama_barang']);
            $harga_satuan = (float)$data_stok['harga'];

            if ($data_stok['stok'] < $item_qty) {
                throw new Exception("Stok '$item_nama' tidak mencukupi. Sisa stok: " . $data_stok['stok'] . ", diminta: " . $item_qty);
            }
            
            // Atomic UPDATE Stok
            mysqli_query($conn, "UPDATE stok_barang SET stok = stok - $item_qty WHERE id = '$id_brg' AND stok >= $item_qty");
            if (mysqli_affected_rows($conn) == 0) {
                throw new Exception("Gagal memotong stok '$item_nama' karena stok tidak mencukupi saat proses paralel.");
            }

            // Insert Surat Jalan Detail
            if (!mysqli_query($conn, "INSERT INTO surat_jalan_detail (id_sj, nama_barang, jumlah, satuan, keterangan) VALUES ('$id_sj_terbaru', '$item_nama', '$item_qty', '$item_sat', '$item_ket')")) {
                throw new Exception("Gagal menyimpan rincian surat jalan.");
            }
            
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

        // 3. Kalkulasi PPN & Biaya Prioritas
        $subtotal_sebelum_pajak = $total_harga_barang + $ongkos_kirim;
        
        $biaya_prioritas = 0;
        $desc_prioritas = "";
        if ($prioritas == 'Urgent') {
            $biaya_prioritas = $ongkos_kirim * 0.20; // Tambahan 20% dari ongkos kirim
            $desc_prioritas = "Biaya Layanan (Urgent Priority 20%)";
        } elseif ($prioritas == 'High') {
            $biaya_prioritas = $ongkos_kirim * 0.50; // Tambahan 50% dari ongkos kirim
            $desc_prioritas = "Biaya Layanan (High Priority 50%)";
        }
        
        $subtotal_sebelum_pajak += $biaya_prioritas;

        $ppn = $subtotal_sebelum_pajak * 0.11;
        $total_tagihan_akhir = $subtotal_sebelum_pajak + $ppn;

        // 4. Generate Invoice Baru
        $no_invoice = '#INV-'.date('Ymd').'-'.strtoupper(substr(uniqid(), -5));
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
        
        // 5.b Ongkos Kirim (Flat rate per trip rute)
        $desc_ongkir = "Biaya Pengiriman (Rute: $nama_rute)";
        mysqli_query($conn, "INSERT INTO invoice_detail (id_invoice, deskripsi, tarif, qty, subtotal) VALUES ('$id_invoice_terbaru', '$desc_ongkir', '$ongkos_kirim', '1', '$ongkos_kirim')");
        
        // 5.c Biaya Prioritas
        if ($biaya_prioritas > 0) {
            mysqli_query($conn, "INSERT INTO invoice_detail (id_invoice, deskripsi, tarif, qty, subtotal) VALUES ('$id_invoice_terbaru', '$desc_prioritas', '$biaya_prioritas', '1', '$biaya_prioritas')");
        }
        
        // 5.d PPN
        $desc_ppn = "PPN 11%";
        mysqli_query($conn, "INSERT INTO invoice_detail (id_invoice, deskripsi, tarif, qty, subtotal) VALUES ('$id_invoice_terbaru', '$desc_ppn', '$ppn', '1', '$ppn')");

        // 6. Update Status Driver & Armada menjadi BOOKED (Menunggu konfirmasi)
        mysqli_query($conn, "UPDATE driver SET status_kerja = 'BOOKED' WHERE id = '$id_driver'");
        mysqli_query($conn, "UPDATE armada SET status = 'BOOKED' WHERE id = '$id_armada'");

        // 7. Insert Notification
        $notif_judul = "Surat Jalan Diterbitkan";
        $notif_pesan = "Dokumen $no_sj dengan rute $nama_rute telah diterbitkan. Menunggu driver mengkonfirmasi kesiapan armada.";
        
        if (function_exists('buat_notifikasi')) {
            // Notif ke Manager
            buat_notifikasi($conn, 'MANAGER,ADMIN', 0, $notif_judul, $notif_pesan, "success", "status_pengiriman.php");
            
            // Notif ke Driver spesifik
            $q_drv = mysqli_query($conn, "SELECT id_login FROM driver WHERE id='$id_driver'");
            if ($q_drv && mysqli_num_rows($q_drv) > 0) {
                $d_drv = mysqli_fetch_assoc($q_drv);
                if (!empty($d_drv['id_login'])) {
                    buat_notifikasi($conn, '', $d_drv['id_login'], "Rute Baru Ditugaskan!", "Anda ditugaskan untuk Surat Jalan $no_sj ($nama_rute).", "info", "dashboard_driver.php");
                }
            }
        }

        mysqli_commit($conn);

        echo "<script>
                alert('Berhasil! Surat Jalan diterbitkan. Menunggu driver konfirmasi truk sebelum berangkat. Invoice otomatis terbuat dan akan batal jika tidak dibayar dalam 2 jam.');
                window.location.href='status_pengiriman.php';
              </script>";

    } catch (Exception $e) {
        mysqli_rollback($conn);
        $err_msg = addslashes($e->getMessage());
        echo "<script>
                alert('Gagal menyimpan data: $err_msg');
                window.location.href='buat_surat_jalan.php';
              </script>";
    }

} else {
    header('location:buat_surat_jalan.php');
}
?>