<?php
// cek_kedaluwarsa.php
// File ini di-include di koneksi.php agar berjalan secara global
// Berfungsi untuk membatalkan invoice UNPAID yang umurnya sudah lebih dari 2 jam

if (isset($conn)) {
    // Cari invoice yang belum dibayar dan sudah dibuat lebih dari 2 jam lalu
    $q_expired = mysqli_query($conn, "SELECT id, id_sj, no_invoice FROM invoice WHERE status = 'UNPAID' AND created_at < DATE_SUB(NOW(), INTERVAL 2 HOUR)");
    
    if ($q_expired && mysqli_num_rows($q_expired) > 0) {
        while ($inv = mysqli_fetch_assoc($q_expired)) {
            $id_invoice = $inv['id'];
            $id_sj = $inv['id_sj'];
            
            // 1. Batalkan Invoice
            mysqli_query($conn, "UPDATE invoice SET status = 'CANCEL' WHERE id = '$id_invoice'");
            
            // 2. Jika ada Surat Jalan terkait, batalkan juga dan bebaskan armada/driver
            if (!empty($id_sj)) {
                $q_sj = mysqli_query($conn, "SELECT id_driver, id_armada, no_sj FROM surat_jalan WHERE id = '$id_sj'");
                if ($sj = mysqli_fetch_assoc($q_sj)) {
                    $id_driver = $sj['id_driver'];
                    $id_armada = $sj['id_armada'];
                    
                    // Batalkan Surat Jalan
                    mysqli_query($conn, "UPDATE surat_jalan SET status = 'CANCELLED' WHERE id = '$id_sj'");
                    
                    // Bebaskan Driver dan Armada (hanya jika statusnya BOOKED atau WAITING, jangan ubah jika ternyata sudah ON TRIP)
                    mysqli_query($conn, "UPDATE driver SET status_kerja = 'AVAILABLE' WHERE id = '$id_driver' AND status_kerja = 'BOOKED'");
                    mysqli_query($conn, "UPDATE armada SET status = 'READY' WHERE id = '$id_armada' AND status = 'BOOKED'");
                    
                    // (Opsional) Buat notifikasi bahwa sistem membatalkan tugas
                    if (function_exists('buat_notifikasi')) {
                        $pesan = "Sistem: Invoice {$inv['no_invoice']} (SJ: {$sj['no_sj']}) kedaluwarsa setelah 2 jam belum dibayar. Tugas dibatalkan otomatis.";
                        buat_notifikasi($conn, 'MANAGER,ADMIN', 0, "Invoice Kedaluwarsa", $pesan, "danger", "manajemen_invoice.php");
                        
                        // Notif ke driver
                        $q_drv = mysqli_query($conn, "SELECT id_login FROM driver WHERE id='$id_driver'");
                        if ($d_drv = mysqli_fetch_assoc($q_drv)) {
                            buat_notifikasi($conn, '', $d_drv['id_login'], "Tugas Dibatalkan", "Tugas Surat Jalan {$sj['no_sj']} dibatalkan oleh sistem karena invoice kedaluwarsa.", "danger", "dashboard_driver.php");
                        }
                    }
                }
            }
        }
    }
}
?>
