<?php
// 1. Mulai session untuk mendeteksi session mana yang sedang aktif
session_start();

// 2. Kosongkan semua variabel session (seperti $_SESSION['log'], $_SESSION['role'], dll)
session_unset();

// 3. Hancurkan session dari server secara total
session_destroy();

// 4. (Opsional tapi Direkomendasikan) Hapus cookie session di browser pengguna
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 5. Arahkan kembali ke halaman login
header('location:login.php');
exit();
?>