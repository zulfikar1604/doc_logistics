<?php
// Matikan mode strict reporting agar koneksi gagal tidak memicu uncaught exception / HTTP 500 Error
mysqli_report(MYSQLI_REPORT_OFF);

// Mencoba menyambungkan ke database (lokal default)
$conn = @mysqli_connect("localhost", "root", "", "doclogistics");

// Cek koneksi biar gak lieur
if (!$conn || mysqli_connect_errno()) {
    die("<!DOCTYPE html>
    <html lang='id'>
    <head>
        <meta charset='UTF-8'>
        <title>Database Connection Error</title>
        <link href='https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700&display=swap' rel='stylesheet'>
        <style>
            body { font-family: 'Plus Jakarta Sans', sans-serif; background: #0f172a; color: #f8fafc; height: 100vh; display: flex; align-items: center; justify-content: center; margin: 0; padding: 20px; box-sizing: border-box; }
            .error-box { max-width: 550px; background: #1e293b; border: 1px solid #334155; border-radius: 16px; padding: 30px; box-shadow: 0 15px 30px rgba(0,0,0,0.3); text-align: center; }
            h2 { color: #f43f5e; margin-top: 0; }
            p { line-height: 1.6; color: #cbd5e1; font-size: 15px; }
            code { background: #0f172a; padding: 4px 8px; border-radius: 6px; color: #38bdf8; font-family: monospace; font-size: 14px; }
            .hint { background: #0284c7; color: white; padding: 10px 15px; border-radius: 8px; font-weight: 600; font-size: 14px; margin-top: 20px; display: inline-block; text-decoration: none; }
        </style>
    </head>
    <body>
        <div class='error-box'>
            <h2>⚠️ Koneksi Database Gagal!</h2>
            <p>Halaman ini gagal dimuat karena sistem tidak dapat terhubung ke database MySQL.</p>
            <p><strong>Penyebab:</strong> Jika Anda baru mengunggah file ke <strong>hosting online</strong>, detail kredensial database di file <code>koneksi.php</code> masih menggunakan pengaturan localhost/lokal.</p>
            <p><strong>Solusi:</strong> Silakan buka file <code>koneksi.php</code> di hosting Anda dan ubah parameter koneksi sesuai detail database dari provider hosting Anda.</p>
            <p style='font-size: 12px; color: #94a3b8; border-top: 1px solid #334155; padding-top: 15px; margin-top: 20px;'>
                Error Detail: <code>" . mysqli_connect_error() . "</code>
            </p>
        </div>
    </body>
    </html>");
}

/**
 * FUNGSI BANTUAN UNTUK DISTRIBUSI NOTIFIKASI SECARA PERSONAL (FAN-OUT)
 * @param mysqli $conn Koneksi database
 * @param string $target_role Role target (kosongkan jika spesifik 1 orang)
 * @param int $target_id_login ID Login target (0 jika menggunakan target_role)
 * @param string $judul Judul notifikasi
 * @param string $pesan Isi pesan
 * @param string $tipe success/info/warning/danger
 * @param string $link Link halaman
 */
if (!function_exists('buat_notifikasi')) {
    function buat_notifikasi($conn, $target_role, $target_id_login, $judul, $pesan, $tipe, $link) {
        $judul = mysqli_real_escape_string($conn, $judul);
        $pesan = mysqli_real_escape_string($conn, $pesan);
        $link  = mysqli_real_escape_string($conn, $link);

        if ($target_id_login > 0) {
            // Personal Notification (e.g. spesifik 1 Driver)
            $q = "INSERT INTO notifications (id_login, judul, pesan, tipe, link) VALUES ('$target_id_login', '$judul', '$pesan', '$tipe', '$link')";
            mysqli_query($conn, $q);
        } elseif (!empty($target_role)) {
            // Fan-out Notification (supports comma-separated roles like 'MANAGER,ADMIN')
            $roles = explode(',', $target_role);
            $roles_escaped = array_map(function($r) use ($conn) {
                return "'" . mysqli_real_escape_string($conn, strtoupper(trim($r))) . "'";
            }, $roles);
            $roles_in = implode(',', $roles_escaped);

            $q_users = mysqli_query($conn, "SELECT id FROM login WHERE UPPER(role) IN ($roles_in)");
            while ($u = mysqli_fetch_assoc($q_users)) {
                $uid = $u['id'];
                $q = "INSERT INTO notifications (id_login, judul, pesan, tipe, link) VALUES ('$uid', '$judul', '$pesan', '$tipe', '$link')";
                mysqli_query($conn, $q);
            }
        } else {
            // Global Fallback (jika keduanya kosong)
            $q = "INSERT INTO notifications (id_login, judul, pesan, tipe, link) VALUES (0, '$judul', '$pesan', '$tipe', '$link')";
            mysqli_query($conn, $q);
        }
    }
}

// Jalankan pengecekan kedaluwarsa secara global di setiap page load
include_once 'cek_kedaluwarsa.php';
?>