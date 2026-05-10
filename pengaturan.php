<?php
session_start();
require 'koneksi.php';

if (!isset($_SESSION['log'])) {
    header('location:login.php');
    exit();
}

$email_user = $_SESSION['email'];
$role = isset($_SESSION['role']) ? strtoupper($_SESSION['role']) : 'STAF';
$pesan = "";

// Logika Ganti Password
if (isset($_POST['ganti_password'])) {
    $password_baru = $_POST['password_baru'];
    $konfirmasi = $_POST['konfirmasi_password'];

    if ($password_baru == $konfirmasi) {
        $update = mysqli_query($conn, "UPDATE login SET password = '$password_baru' WHERE email = '$email_user'");
        if ($update) {
            $pesan = "<div class='alert alert-success rounded-4 fw-bold'><i class='fas fa-check-circle me-2'></i> Password berhasil diperbarui!</div>";
        } else {
            $pesan = "<div class='alert alert-danger rounded-4 fw-bold'><i class='fas fa-times-circle me-2'></i> Gagal memperbarui password!</div>";
        }
    } else {
        $pesan = "<div class='alert alert-warning rounded-4 fw-bold'><i class='fas fa-exclamation-triangle me-2'></i> Konfirmasi password tidak cocok!</div>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Pengaturan Akun - Global Logistics</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* --- CSS WAJIB LAYOUT (MASTER) --- */
        :root {
            --sidebar-width: 260px;
            --primary-blue: #1a367e;
            --accent-yellow: #ffcc00;
            --bg-light: #f8fafc;
        }

        body { font-family: 'Poppins', sans-serif; background-color: var(--bg-light); overflow-x: hidden; }

        #sidebar {
            width: var(--sidebar-width); position: fixed; top: 0; left: 0; height: 100vh;
            background: linear-gradient(180deg, var(--primary-blue) 0%, #0d1b3e 100%);
            z-index: 1000; display: flex; flex-direction: column; transition: all 0.3s;
        }
        #main-content {
            margin-left: var(--sidebar-width); width: calc(100% - var(--sidebar-width)); 
            min-height: 100vh; display: flex; flex-direction: column; transition: all 0.3s;
        }
        .top-navbar { background: white; padding: 15px 30px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); position: sticky; top: 0; z-index: 998; }
        
        @media (max-width: 768px) {
            #sidebar { margin-left: calc(var(--sidebar-width) * -1); }
            #sidebar.show { margin-left: 0; }
            #main-content { width: 100%; margin-left: 0; }
            .top-navbar { padding: 15px; }
        }

        .sidebar-header { padding: 25px 20px; background: rgba(0,0,0,0.15); border-bottom: 1px solid rgba(255,255,255,0.05); }
        .sidebar-menu { padding: 20px 0; flex-grow: 1; overflow-y: auto; }
        .menu-label { color: rgba(255,255,255,0.4); font-size: 0.7rem; font-weight: 700; letter-spacing: 1px; padding: 0 25px; margin-bottom: 10px; display: block; }
        #sidebar ul li a { padding: 12px 25px; color: rgba(255,255,255,0.7); text-decoration: none; display: block; font-size: 0.85rem; transition: 0.3s; }
        #sidebar ul li a:hover, #sidebar ul li.active > a { color: #fff; background: rgba(255,255,255,0.1); border-left: 4px solid var(--accent-yellow); }
        #sidebar ul li a i { width: 25px; text-align: center; }
        .sidebar-footer { padding: 20px 25px; background: rgba(0,0,0,0.2); }

        /* --- CSS KHUSUS PENGATURAN --- */
        .card-sultan { background: white; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.03); border: 1px solid rgba(0,0,0,0.05); }
        .form-control-sultan { border-radius: 12px; padding: 12px 15px; border: 2px solid #e2e8f0; background: #f8fafc; font-weight: 500; }
        .form-control-sultan:focus { border-color: #3b82f6; background: white; box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1); }
    </style>
</head>
<body>

    <?php include 'sidebar.php'; ?>

    <div id="main-content">
        <?php include 'navbar.php'; ?>

        <div class="container-fluid p-4">
            <h3 class="fw-bold mb-4" style="color: #1a367e;">Pengaturan Akun</h3>
            
            <?= $pesan; ?>

            <div class="row">
                <div class="col-lg-4 mb-4">
                    <div class="card-sultan p-3">
                        <div class="list-group list-group-flush border-0">
                            <a href="pengaturan.php" class="list-group-item list-group-item-action border-0 fw-bold text-primary bg-primary bg-opacity-10 rounded-3 mb-1">
                                <i class="fas fa-lock me-2"></i> Keamanan Akun
                            </a>
                            <a href="profil.php" class="list-group-item list-group-item-action border-0 text-muted fw-medium rounded-3 mb-1">
                                <i class="fas fa-user-circle me-2"></i> Lihat Profil Saya
                            </a>
                        </div>
                    </div>
                </div>

                <div class="col-lg-8">
                    <div class="card-sultan p-4 h-100">
                        <h5 class="fw-bold mb-4"><i class="fas fa-key text-warning me-2"></i> Ubah Password</h5>
                        <p class="text-muted small mb-4">Pastikan password kamu menggunakan kombinasi huruf dan angka agar akun tetap aman dari peretasan.</p>
                        
                        <form method="POST">
                            <div class="mb-3">
                                <label class="small fw-bold text-dark mb-2">Password Baru</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0" style="border-radius: 12px 0 0 12px;"><i class="fas fa-key text-muted"></i></span>
                                    <input type="password" name="password_baru" class="form-control form-control-sultan border-start-0" style="border-radius: 0 12px 12px 0;" placeholder="Masukkan password baru" required>
                                </div>
                            </div>
                            <div class="mb-4">
                                <label class="small fw-bold text-dark mb-2">Konfirmasi Password Baru</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0" style="border-radius: 12px 0 0 12px;"><i class="fas fa-check-double text-muted"></i></span>
                                    <input type="password" name="konfirmasi_password" class="form-control form-control-sultan border-start-0" style="border-radius: 0 12px 12px 0;" placeholder="Ketik ulang password baru" required>
                                </div>
                            </div>
                            <hr class="opacity-10 mb-4">
                            <div class="text-end">
                                <button type="submit" name="ganti_password" class="btn btn-primary fw-bold px-4 rounded-pill shadow-sm" style="padding: 12px 25px;">
                                    Simpan Password Baru
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script> document.getElementById('sidebarToggle')?.addEventListener('click', function() { document.getElementById('sidebar').classList.toggle('show'); }); </script>
</body>
</html>