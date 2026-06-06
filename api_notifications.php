<?php
session_start();
require 'koneksi.php';

header('Content-Type: application/json');

if (!isset($_SESSION['log'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

$action = $_GET['action'] ?? 'fetch';
$id_login = $_SESSION['id_login'] ?? 0;
$email = $_SESSION['email'] ?? '';
$role = $_SESSION['role'] ?? '';

// Tutup sesi untuk penulisan agar polling AJAX tidak memblokir navigasi/request PHP lain!
session_write_close();


if ($action == 'fetch') {
    // Fetch unread notifications for this user or global (id_login = 0 or NULL)
    // Ordered by newest first
    $sql = "SELECT * FROM notifications 
            WHERE (id_login = '$id_login' OR id_login = 0 OR id_login IS NULL) 
              AND is_read = 0 
            ORDER BY created_at DESC 
            LIMIT 10";
            
    $result = mysqli_query($conn, $sql);
    $notifs = [];
    while ($row = mysqli_fetch_assoc($result)) {
        // Calculate time ago
        $time_diff = time() - strtotime($row['created_at']);
        $time_str = "Baru saja";
        if ($time_diff > 86400) $time_str = floor($time_diff/86400) . " hari lalu";
        elseif ($time_diff > 3600) $time_str = floor($time_diff/3600) . " jam lalu";
        elseif ($time_diff > 60) $time_str = floor($time_diff/60) . " mnt lalu";
        
        $row['time_ago'] = $time_str;
        $notifs[] = $row;
    }
    
    // Also get total unread count
    $sql_count = "SELECT COUNT(id) as total FROM notifications 
                  WHERE (id_login = '$id_login' OR id_login = 0 OR id_login IS NULL) 
                    AND is_read = 0";
    $count_res = mysqli_query($conn, $sql_count);
    $count_row = mysqli_fetch_assoc($count_res);
    $total_unread = $count_row['total'];

    // --- TAMBAHAN: SYSTEM ALERTS (Dynamic State-based Notifications) ---
    // Tampilkan jika user adalah ADMIN atau MANAGER (Bukan Driver)
    if ($role != 'DRIVER') {
        // 1. Cek Surat Jalan DRAFT
        $q_draft = mysqli_query($conn, "SELECT COUNT(id) as total FROM surat_jalan WHERE status='DRAFT'");
        $draft_count = mysqli_fetch_assoc($q_draft)['total'] ?? 0;
        if ($draft_count > 0) {
            $notifs[] = [
                'id' => 'sys_1',
                'judul' => 'Sistem: Tugas DRAFT',
                'pesan' => "Ada $draft_count Surat Jalan berstatus DRAFT yang menunggu proses pengiriman.",
                'tipe' => 'warning',
                'link' => 'status_pengiriman.php',
                'time_ago' => 'System Alert'
            ];
            $total_unread++;
        }

        // 2. Cek Invoice UNPAID
        $q_unpaid = mysqli_query($conn, "SELECT COUNT(id) as total FROM invoice WHERE status='UNPAID'");
        $unpaid_count = mysqli_fetch_assoc($q_unpaid)['total'] ?? 0;
        if ($unpaid_count > 0) {
            $notifs[] = [
                'id' => 'sys_2',
                'judul' => 'Sistem: Invoice UNPAID',
                'pesan' => "Terdapat $unpaid_count Invoice yang berstatus UNPAID. Segera follow up klien.",
                'tipe' => 'danger',
                'link' => 'manajemen_invoice.php',
                'time_ago' => 'System Alert'
            ];
            $total_unread++;
        }

        // 3. Cek Stok Kritis
        $q_kritis = mysqli_query($conn, "SELECT COUNT(id) as total FROM stok_barang WHERE stok <= 10");
        $kritis_count = mysqli_fetch_assoc($q_kritis)['total'] ?? 0;
        if ($kritis_count > 0) {
            $notifs[] = [
                'id' => 'sys_3',
                'judul' => 'Sistem: Stok Kritis',
                'pesan' => "Terdapat $kritis_count jenis barang yang stoknya kritis (<= 10).",
                'tipe' => 'danger',
                'link' => 'stok_barang.php',
                'time_ago' => 'System Alert'
            ];
            $total_unread++;
        }
        
        // 4. Armada On Trip
        $q_ontrip = mysqli_query($conn, "SELECT COUNT(id) as total FROM armada WHERE status='ON TRIP'");
        $ontrip_count = mysqli_fetch_assoc($q_ontrip)['total'] ?? 0;
        if ($ontrip_count > 0) {
            $notifs[] = [
                'id' => 'sys_4',
                'judul' => 'Sistem: Sedang Pengiriman',
                'pesan' => "Ada $ontrip_count Armada sedang dalam perjalanan (ON TRIP).",
                'tipe' => 'info',
                'link' => 'status_pengiriman.php',
                'time_ago' => 'System Alert'
            ];
            $total_unread++;
        }
    }

    echo json_encode([
        'status' => 'success',
        'unread_count' => $total_unread,
        'data' => $notifs
    ]);
} 
elseif ($action == 'read') {
    $id_notif = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    if ($id_notif > 0) {
        $sql = "UPDATE notifications SET is_read = 1 WHERE id = $id_notif AND (id_login = '$id_login' OR id_login = 0 OR id_login IS NULL)";
        if (mysqli_query($conn, $sql)) {
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => mysqli_error($conn)]);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid ID']);
    }
}
elseif ($action == 'read_all') {
    $sql = "UPDATE notifications SET is_read = 1 WHERE (id_login = '$id_login' OR id_login = 0 OR id_login IS NULL) AND is_read = 0";
    if (mysqli_query($conn, $sql)) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => mysqli_error($conn)]);
    }
}
?>
