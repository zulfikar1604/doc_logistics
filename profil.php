<?php
session_start();
require 'koneksi.php';

if (!isset($_SESSION['log'])) {
    header('location:login.php');
    exit();
}

$email_user = $_SESSION['email'];
$role = strtoupper($_SESSION['role']);
$pesan_update = "";

// --- LOGIKA 1: UPLOAD FOTO PROFILE ---
if (isset($_POST['update_foto'])) {
    $nama_file = $_FILES['foto_profil']['name'];
    $ukuran_file = $_FILES['foto_profil']['size'];
    $error = $_FILES['foto_profil']['error'];
    $tmp_name = $_FILES['foto_profil']['tmp_name'];

    $ekstensi_valid = ['jpg', 'jpeg', 'png'];
    $ekstensi_file = explode('.', $nama_file);
    $ekstensi_file = strtolower(end($ekstensi_file));

    if (!in_array($ekstensi_file, $ekstensi_valid)) {
        $pesan_update = "<div class='alert alert-danger rounded-4 small'>Format file harus JPG, JPEG, atau PNG!</div>";
    } elseif ($ukuran_file > 2000000) {
        $pesan_update = "<div class='alert alert-danger rounded-4 small'>Ukuran file terlalu besar (Max 2MB)!</div>";
    } else {
        $nama_file_baru = uniqid() . '.' . $ekstensi_file;
        $q_lama = mysqli_query($conn, "SELECT foto FROM login WHERE email = '$email_user'");
        $data_lama = mysqli_fetch_assoc($q_lama);
        if(!empty($data_lama['foto']) && file_exists("assets/img/".$data_lama['foto'])){
            unlink("assets/img/".$data_lama['foto']);
        }
        move_uploaded_file($tmp_name, 'assets/img/' . $nama_file_baru);
        mysqli_query($conn, "UPDATE login SET foto = '$nama_file_baru' WHERE email = '$email_user'");
        $pesan_update = "<div class='alert alert-success rounded-4 small'>Foto profil berhasil diperbarui!</div>";
    }
}

// --- LOGIKA 2: UPDATE NAMA LENGKAP ---
if (isset($_POST['update_nama'])) {
    $nama_baru = mysqli_real_escape_string($conn, $_POST['nama_lengkap']);
    $update = mysqli_query($conn, "UPDATE login SET nama_lengkap = '$nama_baru' WHERE email = '$email_user'");
    if ($update) {
        $_SESSION['nama_lengkap'] = $nama_baru;
        $pesan_update = "<div class='alert alert-success rounded-4 small'>Nama berhasil diperbarui!</div>";
    }
}

// Ambil data user terbaru
$q_user = mysqli_query($conn, "SELECT * FROM login WHERE email = '$email_user'");
$user = mysqli_fetch_assoc($q_user);

$foto_tampil = (!empty($user['foto'])) ? "assets/img/".$user['foto'] : "https://ui-avatars.com/api/?name=".urlencode($user['nama_lengkap'])."&background=1a367e&color=fff&size=150";

$driver_data = null;
if ($role == 'DRIVER') {
    $q_driver = mysqli_query($conn, "SELECT * FROM driver WHERE id_login = '{$user['id']}'");
    $driver_data = mysqli_fetch_assoc($q_driver);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Profil Saya - Global Logistics</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* --- CSS MASTER (Sesuai Permintaan) --- */
        :root {
            --sidebar-width: 260px;
            --primary-blue: #1a367e;
            --accent-yellow: #ffcc00;
            --bg-light: #f8fafc;
        }

        body { font-family: 'Poppins', sans-serif; background-color: var(--bg-light); overflow-x: hidden; }

        /* --- CSS WAJIB LAYOUT --- */
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

        /* --- CSS KHUSUS PROFIL --- */
        .card-sultan { background: white; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.03); border: 1px solid rgba(0,0,0,0.05); }
        .profile-header { background: linear-gradient(135deg, #1a367e 0%, #2b59ff 100%); border-radius: 20px 20px 0 0; padding: 40px 20px 60px 20px; text-align: center; color: white; position: relative; }
        
        .avatar-wrapper { position: relative; width: 120px; height: 120px; margin: -60px auto 15px auto; }
        .profile-avatar { width: 100%; height: 100%; border-radius: 50%; border: 4px solid white; box-shadow: 0 10px 20px rgba(0,0,0,0.1); background: white; object-fit: cover; }
        .upload-btn { position: absolute; bottom: 5px; right: 5px; background: var(--accent-yellow); color: #1a367e; width: 35px; height: 35px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; border: 3px solid white; transition: 0.3s; }
        .upload-btn:hover { background: #1a367e; color: white; transform: scale(1.1); }
        
        .info-label { font-size: 0.75rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 5px; }
        .info-value { font-size: 1rem; font-weight: 600; color: #1e293b; margin-bottom: 20px; }
        .btn-edit-inline { color: var(--primary-blue); cursor: pointer; margin-left: 8px; font-size: 0.9rem; transition: 0.2s; }
        .btn-edit-inline:hover { color: #2b59ff; }
    </style>
</head>
<body>

    <?php include 'sidebar.php'; ?>

    <div id="main-content">
        <?php include 'navbar.php'; ?>

        <div class="container-fluid p-4">
            <h3 class="fw-bold mb-4" style="color: #1a367e;">Profil Saya</h3>
            
            <div class="row">
                <div class="col-lg-4 mb-4">
                    <div class="card-sultan text-center pb-4">
                        <div class="profile-header">
                            <span class="badge bg-white text-primary px-3 py-2 rounded-pill fw-bold small"><?= $role; ?></span>
                        </div>
                        
                        <div class="avatar-wrapper">
                            <img src="<?= $foto_tampil; ?>" class="profile-avatar">
                            <label for="inputFoto" class="upload-btn" title="Ganti Foto">
                                <i class="fas fa-camera"></i>
                            </label>
                        </div>

                        <form method="POST" enctype="multipart/form-data" id="formFoto">
                            <input type="file" name="foto_profil" id="inputFoto" class="d-none" onchange="document.getElementById('formFoto').submit();">
                            <input type="hidden" name="update_foto" value="1">
                        </form>

                        <div class="px-4"><?= $pesan_update; ?></div>
                        
                        <h4 class="fw-bold mb-1 mt-2">
                            <?= $user['nama_lengkap']; ?>
                            <i class="fas fa-pen-to-square btn-edit-inline" data-bs-toggle="modal" data-bs-target="#modalEditNama" title="Ubah Nama"></i>
                        </h4>
                        <p class="text-muted small mb-3"><?= $user['email']; ?></p>
                        <a href="pengaturan.php" class="btn btn-light border btn-sm rounded-pill px-4 fw-bold text-primary"><i class="fas fa-cog me-1"></i> Pengaturan Akun</a>
                    </div>
                </div>

                <div class="col-lg-8">
                    <div class="card-sultan p-4 h-100">
                        <h5 class="fw-bold mb-4"><i class="fas fa-address-card text-primary me-2"></i> Informasi Detail</h5>
                        <div class="row">
                            <div class="col-md-6"><div class="info-label">Nama Lengkap</div><div class="info-value"><?= $user['nama_lengkap']; ?></div></div>
                            <div class="col-md-6"><div class="info-label">Alamat Email</div><div class="info-value"><?= $user['email']; ?></div></div>
                            <div class="col-md-6"><div class="info-label">Hak Akses (Role)</div><div class="info-value"><?= $role; ?></div></div>
                            <div class="col-md-6"><div class="info-label">Status Akun</div><div class="info-value"><span class="badge bg-success bg-opacity-10 text-success px-3 py-2 rounded-pill small fw-bold">Aktif</span></div></div>
                        </div>

                        <?php if($driver_data): ?>
                        <hr class="opacity-10 my-4">
                        <h5 class="fw-bold mb-4"><i class="fas fa-truck text-warning me-2"></i> Data Operasional Supir</h5>
                        <div class="row">
                            <div class="col-md-6"><div class="info-label">ID Driver</div><div class="info-value"><?= $driver_data['kode_driver']; ?></div></div>
                            <div class="col-md-6"><div class="info-label">Nomor Telepon</div><div class="info-value"><?= $driver_data['no_telepon']; ?></div></div>
                            <div class="col-md-6"><div class="info-label">Lisensi SIM</div><div class="info-value"><?= $driver_data['lisensi_sim']; ?></div></div>
                            <div class="col-md-6"><div class="info-label">Masa Berlaku SIM</div><div class="info-value"><?= date('d F Y', strtotime($driver_data['berlaku_sim'])); ?></div></div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalEditNama" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius: 20px;">
                <form method="POST">
                    <div class="modal-header border-0 pt-4 px-4">
                        <h5 class="fw-bold">Ubah Nama Lengkap</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body px-4">
                        <div class="mb-3">
                            <label class="small fw-bold text-muted mb-2">Nama Baru</label>
                            <input type="text" name="nama_lengkap" class="form-control" style="border-radius: 12px; padding: 12px;" value="<?= $user['nama_lengkap']; ?>" required>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pb-4 px-4">
                        <button type="submit" name="update_nama" class="btn btn-primary w-100 fw-bold" style="border-radius: 12px; padding: 12px;">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script> document.getElementById('sidebarToggle')?.addEventListener('click', function() { document.getElementById('sidebar').classList.toggle('show'); }); </script>
</body>
</html>