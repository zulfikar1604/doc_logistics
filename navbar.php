<?php
// Cek foto profil dari database untuk ditampilkan di Navbar
$nav_email = $_SESSION['email'] ?? '';
$nav_nama = $_SESSION['nama_lengkap'] ?? 'Admin';
// Set default avatar jika belum ada foto
$nav_foto_url = "https://ui-avatars.com/api/?name=".urlencode($nav_nama)."&background=1a367e&color=fff&rounded=true";

// Jika koneksi tersedia dan email ada, tarik foto asli dari database
if (!empty($nav_email) && isset($conn)) {
    $q_nav_foto = mysqli_query($conn, "SELECT foto FROM login WHERE email = '$nav_email'");
    if ($q_nav_foto && mysqli_num_rows($q_nav_foto) > 0) {
        $d_nav_foto = mysqli_fetch_assoc($q_nav_foto);
        if (!empty($d_nav_foto['foto'])) {
            // Timpa dengan foto dari folder assets/img/
            $nav_foto_url = "assets/img/" . $d_nav_foto['foto'];
        }
    }
}
?>
<nav class="top-navbar d-flex justify-content-between align-items-center">
    <div class="d-flex align-items-center">
        <button type="button" class="btn btn-light d-md-none me-3" id="sidebarToggle">
            <i class="fas fa-bars"></i>
        </button>
        
        <div class="search-bar d-none d-lg-block">
            <div class="input-group input-group-sm" style="width: 300px;">
                <span class="input-group-text bg-light border-0"><i class="fas fa-search text-muted"></i></span>
                <input type="text" class="form-control bg-light border-0" placeholder="Cari dokumen atau driver..." style="font-size: 0.8rem;">
            </div>
        </div>
    </div>

    <div class="d-flex align-items-center">
        
        <div class="dropdown me-3">
            <a href="#" class="text-muted position-relative" id="notifDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="fas fa-bell fa-lg"></i>
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.6rem; padding: 0.35em 0.65em;">
                    3
                </span>
            </a>
            <ul class="dropdown-menu dropdown-menu-end border-0 shadow-sm mt-3 animate slideIn" aria-labelledby="notifDropdown" style="width: 280px; border-radius: 12px;">
                <li><h6 class="dropdown-header fw-bold text-dark">Notifikasi Terbaru</h6></li>
                <li><a class="dropdown-item py-2" href="#" style="font-size: 0.8rem;">
                    <i class="fas fa-check-circle text-success me-2"></i> Dokumen #8823 Berhasil Terbit
                </a></li>
                <li><a class="dropdown-item py-2" href="#" style="font-size: 0.8rem;">
                    <i class="fas fa-exclamation-triangle text-warning me-2"></i> Driver Ahmad butuh approval
                </a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item small text-center text-primary fw-bold" href="#">Lihat Semua Notifikasi</a></li>
            </ul>
        </div>

        <div class="dropdown">
            <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle text-dark" id="userMenu" data-bs-toggle="dropdown" aria-expanded="false">
                <div class="text-end me-3 d-none d-md-block">
                    <p class="mb-0 small fw-bold text-dark">
                        <?php echo isset($_SESSION['nama_lengkap']) ? $_SESSION['nama_lengkap'] : 'Administrator'; ?>
                    </p>
                    <p class="mb-0 text-muted" style="font-size: 0.65rem; text-transform: uppercase; letter-spacing: 1px; font-weight: 600;">
                        <?php echo isset($_SESSION['role']) ? $_SESSION['role'] : 'STAFF LOGISTIK'; ?>
                    </p>
                </div>
                <img src="<?php echo $nav_foto_url; ?>" 
                     class="rounded-circle shadow-sm border border-2 border-white" 
                     width="40" height="40" alt="User Avatar" style="object-fit: cover;">
            </a>
            <ul class="dropdown-menu dropdown-menu-end border-0 shadow mt-3" aria-labelledby="userMenu" style="border-radius: 15px; min-width: 200px;">
                <li class="px-3 py-2 border-bottom mb-2 d-md-none">
                    <p class="mb-0 small fw-bold"><?php echo $_SESSION['nama_lengkap'] ?? 'User'; ?></p>
                    <small class="text-muted"><?php echo $_SESSION['role'] ?? 'Admin'; ?></small>
                </li>
                <li><a class="dropdown-item py-2 small" href="profil.php"><i class="fas fa-user-circle me-2 text-muted"></i> Profil Saya</a></li>
                <li><a class="dropdown-item py-2 small" href="pengaturan.php"><i class="fas fa-cog me-2 text-muted"></i> Pengaturan Akun</a></li>
                <li><hr class="dropdown-divider opacity-50"></li>
                <li>
                    <a class="dropdown-item py-2 small text-danger fw-bold" href="keluar.php">
                        <i class="fas fa-sign-out-alt me-2"></i> Keluar Sistem
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>