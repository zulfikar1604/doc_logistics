<?php
session_start();
require 'koneksi.php'; 

if (!isset($_SESSION['log'])) {
    header('location:login.php');
    exit();
}

// Proses Hapus User (Logika Sederhana)
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    $hapus = mysqli_query($conn, "DELETE FROM login WHERE id='$id'");
    if ($hapus) {
        echo "<script>alert('User berhasil dihapus!'); window.location.href='user_management.php';</script>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>User Management - Global Logistics</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet" />
    
    <style>
        :root {
            --sidebar-width: 260px;
            --primary-blue: #1a367e;
            --accent-yellow: #ffcc00;
            --bg-light: #f4f7f6;
        }

        body { font-family: 'Poppins', sans-serif; background-color: var(--bg-light); overflow-x: hidden; }

        /* --- CSS WAJIB LAYOUT --- */
        #sidebar {
            width: var(--sidebar-width); position: fixed; top: 0; left: 0; height: 100vh;
            background: linear-gradient(180deg, var(--primary-blue) 0%, #0d1b3e 100%);
            z-index: 999; display: flex; flex-direction: column;
        }
        #main-content { margin-left: var(--sidebar-width); width: calc(100% - var(--sidebar-width)); min-height: 100vh; display: flex; flex-direction: column;}
        .sidebar-header { padding: 25px 20px; background: rgba(0,0,0,0.15); border-bottom: 1px solid rgba(255,255,255,0.05); }
        .sidebar-menu { padding: 20px 0; flex-grow: 1; overflow-y: auto; }
        .menu-label { color: rgba(255,255,255,0.4); font-size: 0.7rem; font-weight: 700; letter-spacing: 1px; padding: 0 25px; margin-bottom: 10px; display: block; }
        #sidebar ul li a { padding: 12px 25px; color: rgba(255,255,255,0.7); text-decoration: none; display: block; font-size: 0.85rem; transition: 0.3s; }
        #sidebar ul li a:hover, #sidebar ul li.active > a { color: #fff; background: rgba(255,255,255,0.1); border-left: 4px solid var(--accent-yellow); }
        #sidebar ul li a i { width: 25px; text-align: center; }
        .sidebar-footer { padding: 20px 25px; background: rgba(0,0,0,0.2); }
        .top-navbar { background: white; padding: 15px 30px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); position: sticky; top: 0; z-index: 998; }

        /* --- TABLE & AVATAR STYLE --- */
        .user-container { background: white; border-radius: 20px; padding: 25px; box-shadow: 0 10px 30px rgba(0,0,0,0.03); border: 1px solid rgba(0,0,0,0.05); }
        .table thead th { background-color: #f8fafc; border: none; color: #475569; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; padding: 15px; }
        .table tbody td { padding: 15px; vertical-align: middle; border-bottom: 1px solid #f1f5f9; font-size: 0.9rem;}

        .avatar-circle {
            width: 45px; height: 45px; color: white; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-weight: 700; font-size: 0.9rem; text-transform: uppercase;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }

        .badge-role { padding: 6px 12px; border-radius: 8px; font-size: 0.7rem; font-weight: 800; text-transform: uppercase; border: 1px solid transparent;}
        .role-admin { background: #fee2e2; color: #991b1b; border-color: #fca5a5;}
        .role-staff { background: #e0f2fe; color: #0369a1; border-color: #bae6fd;}
        .role-driver { background: #fef3c7; color: #92400e; border-color: #fde68a;}
        .role-user { background: #f3f4f6; color: #475569; border-color: #cbd5e1;}

        .btn-action { border-radius: 10px; width: 35px; height: 35px; display: inline-flex; align-items: center; justify-content: center; transition: 0.2s; }
        .btn-action:hover { transform: translateY(-2px); box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
    </style>
</head>
<body>

    <?php include 'sidebar.php'; ?>

    <div id="main-content">
        <?php include 'navbar.php'; ?>

        <div class="container-fluid p-4 flex-grow-1 mb-5">
            <div class="d-flex align-items-center justify-content-between flex-wrap mt-2 mb-4">
                <div>
                    <h3 class="fw-bold mb-1" style="color: #1a367e;">Manajemen Pengguna</h3>
                    <p class="text-muted small mb-0">Kelola hak akses akun tim PT Elektronik Global (SPJ).</p>
                </div>
                <button class="btn btn-dark shadow-sm mt-3 mt-md-0 fw-medium" style="border-radius: 10px; padding: 10px 20px;" data-bs-toggle="modal" data-bs-target="#modalTambah">
                    <i class="fas fa-user-plus me-2"></i> Daftarkan User
                    
                </button>
                <div class="modal fade" id="modalTambah" tabindex="-1" aria-labelledby="modalTambahLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content" style="border-radius: 20px;">
                <form action="proses_tambah_user.php" method="POST">
                    <div class="modal-header border-0 pt-4 px-4">
                        <h5 class="modal-title fw-bold" id="modalTambahLabel"><i class="fas fa-user-plus text-primary me-2"></i>Daftarkan Pengguna Baru</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body px-4">
                        <div class="mb-3">
                            <label class="small fw-bold mb-1 text-muted">Nama Lengkap</label>
                            <input type="text" name="nama_lengkap" class="form-control" placeholder="Contoh: Budi Santoso" style="border-radius: 10px;" required>
                        </div>
                        <div class="mb-3">
                            <label class="small fw-bold mb-1 text-muted">Username</label>
                            <input type="text" name="username" class="form-control" placeholder="Contoh: budi_logistics" style="border-radius: 10px;" required>
                        </div>
                        <div class="mb-3">
                            <label class="small fw-bold mb-1 text-muted">Alamat Email</label>
                            <input type="email" name="email" class="form-control" placeholder="name@company.com" style="border-radius: 10px;" required>
                        </div>
                        <div class="mb-3">
                            <label class="small fw-bold mb-1 text-muted">Password Sementara</label>
                            <input type="password" name="password" class="form-control" placeholder="Buatkan password untuk user" style="border-radius: 10px;" required>
                        </div>
                        <div class="mb-4">
                            <label class="small fw-bold mb-1 text-muted">Hak Akses (Role)</label>
                            <select name="role" class="form-select" style="border-radius: 10px;" required>
                                <option value="" selected disabled>-- Pilih Jabatan --</option>
                                <option value="admin">Admin</option>
                                <option value="driver">Driver / Sopir</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pb-4 px-4">
                        <button type="button" class="btn btn-light fw-medium" data-bs-dismiss="modal" style="border-radius: 10px;">Batal</button>
                        <button type="submit" name="simpan_user" class="btn btn-primary fw-medium px-4" style="border-radius: 10px;">Simpan Akun Baru</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
            </div>

            <div class="user-container">
                <div class="table-responsive">
                    <table class="table table-hover align-middle" id="dataTableUser" width="100%">
                        <thead>
                            <tr>
                                <th width="60">Profil</th>
                                <th>Nama Lengkap</th>
                                <th>Username / Email</th>
                                <th>Role / Jabatan</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // 1. QUERY DENGAN ORDER BY FIELD (ADMIN PALING ATAS)
                            $query = mysqli_query($conn, "SELECT * FROM login ORDER BY FIELD(role, 'admin', 'staff', 'driver', 'user'), nama_lengkap ASC");
                            
                            $avatar_colors = ['#1a367e', '#0369a1', '#f39c12', '#10b981', '#8b5cf6'];

                            while ($row = mysqli_fetch_assoc($query)) {
                                $nama = !empty($row['nama_lengkap']) ? $row['nama_lengkap'] : explode('@', $row['email'])[0];
                                $inisial = strtoupper(substr($nama, 0, 2));
                                $bg_color = $avatar_colors[strlen($nama) % count($avatar_colors)];

                                // Penentuan Class Badge Role
                                $role = strtolower($row['role']);
                                $role_class = 'role-user';
                                if ($role == 'manager') $role_class = 'role-manager';
                                if ($role == 'admin') $role_class = 'role-admin';
                                if ($role == 'driver') $role_class = 'role-driver';
                            ?>
                            <tr>
                                <td><div class="avatar-circle" style="background: <?php echo $bg_color; ?>;"><?php echo $inisial; ?></div></td>
                                <td><div class="fw-bold text-dark"><?php echo ucwords($nama); ?></div></td>
                                <td>
                                    <div class="small fw-medium text-dark"><?php echo $row['username']; ?></div>
                                    <div class="small text-muted"><?php echo $row['email']; ?></div>
                                </td>
                                <td><span class="badge-role <?php echo $role_class; ?>"><?php echo $row['role']; ?></span></td>
                                <td class="text-center">
                                    <button class="btn btn-action btn-light border text-primary me-1" title="Edit User" data-bs-toggle="modal" data-bs-target="#modalEdit<?php echo $row['id']; ?>">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <a href="user_management.php?hapus=<?php echo $row['id']; ?>" class="btn btn-action btn-light border text-danger" title="Hapus User" onclick="return confirm('Apakah Anda yakin ingin menghapus user ini?')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>

                            <div class="modal fade" id="modalEdit<?php echo $row['id']; ?>" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content" style="border-radius: 20px;">
                                        <form action="proses_edit_user.php" method="POST">
                                            <div class="modal-header border-0 pt-4 px-4">
                                                <h5 class="modal-title fw-bold">Edit Data Pengguna</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body px-4">
                                                <input type="hidden" name="id_user" value="<?php echo $row['id']; ?>">
                                                <div class="mb-3">
                                                    <label class="small fw-bold mb-1">Nama Lengkap</label>
                                                    <input type="text" name="nama" class="form-control" value="<?php echo $row['nama_lengkap']; ?>" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="small fw-bold mb-1">Role Jabatan</label>
                                                    <select name="role" class="form-select">
                                                        <option value="admin" <?php if($role == 'manager') echo 'selected'; ?>>Manager</option>
                                                        <option value="staff" <?php if($role == 'admin') echo 'selected'; ?>>Admin</option>
                                                        <option value="driver" <?php if($role == 'driver') echo 'selected'; ?>>Driver</option>
                                    
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="modal-footer border-0 pb-4 px-4">
                                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                                                <button type="submit" name="update" class="btn btn-primary px-4">Simpan Perubahan</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    
    <script>
        $(document).ready(function() {
            $('#dataTableUser').DataTable({
                "language": { "search": "Cari User:", "lengthMenu": "Tampil _MENU_" },
                "ordering": false // Mematikan auto-sort DataTables agar tetap pakai urutan Role dari SQL
            });
        });
    </script>
</body>
</html>