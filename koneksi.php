<?php
$conn = mysqli_connect("localhost", "root", "", "doclogistics");

// Cek koneksi biar gak lieur
if (mysqli_connect_errno()) {
    echo "Koneksi database gagal : " . mysqli_connect_error();
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