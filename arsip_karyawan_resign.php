<?php
session_start();
require 'koneksi.php'; 

if (!isset($_SESSION['log']) || ($_SESSION['role'] != 'ADMIN' && $_SESSION['role'] != 'MANAGER')) {
    header('location:index.php');
    exit();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Riwayat Karyawan Resign - Global Logistics</title>
    <link rel="icon" type="image/png" href="assets/img/icon-192-nobg.png?v=4">
    
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet" />
    
    <style>
        :root {
            --primary-blue: #1a367e;
            --accent-yellow: #ffcc00;
            --bg-light: #f4f7f6;
        }

        body { font-family: 'Poppins', sans-serif; background-color: var(--bg-light); overflow-x: hidden; }

        /* --- CSS WAJIB LAYOUT --- */
        #sidebar ul li a:hover, #sidebar ul li.active > a { color: #fff; background: rgba(255,255,255,0.1); border-left: 4px solid var(--accent-yellow); }
        #sidebar ul li a i { width: 25px; text-align: center; }

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
        .role-manager { background: #e0f2fe; color: #0369a1; border-color: #bae6fd;}
        .role-driver { background: #fef3c7; color: #92400e; border-color: #fde68a;}

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
                    <h3 class="fw-bold mb-1" style="color: #1a367e;">Riwayat Karyawan Resign</h3>
                    <p class="text-muted small mb-0">Arsip data karyawan yang pernah bekerja di PT Elektronik Global (SPJ).</p>
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
                                <th>Role Terakhir</th>
                                <th>Tanggal Resign</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $query = mysqli_query($conn, "SELECT * FROM karyawan_resign ORDER BY tanggal_resign DESC");
                            
                            $avatar_colors = ['#64748b', '#94a3b8', '#cbd5e1']; // Warna mute untuk yang sudah resign

                            while ($row = mysqli_fetch_assoc($query)) {
                                $nama = !empty($row['nama_lengkap']) ? $row['nama_lengkap'] : explode('@', $row['email'])[0];
                                $inisial = strtoupper(substr($nama, 0, 2));
                                $bg_color = $avatar_colors[strlen($nama) % count($avatar_colors)];

                                // Penentuan Class Badge Role
                                $role = strtolower($row['role']);
                                $role_class = 'role-driver';
                                if ($role == 'manager') $role_class = 'role-manager';
                                if ($role == 'admin') $role_class = 'role-admin';
                                if ($role == 'driver') $role_class = 'role-driver';
                            ?>
                            <tr>
                                <td><div class="avatar-circle" style="background: <?php echo $bg_color; ?>;"><?php echo $inisial; ?></div></td>
                                <td><div class="fw-bold text-secondary"><?php echo ucwords($nama); ?></div></td>
                                <td>
                                    <div class="small fw-medium text-secondary"><?php echo $row['username']; ?></div>
                                    <div class="small text-muted"><?php echo $row['email']; ?></div>
                                </td>
                                <td><span class="badge-role <?php echo $role_class; ?>"><?php echo $row['role']; ?></span></td>
                                <td><span class="small text-muted fw-medium"><i class="fas fa-calendar-alt me-1"></i> <?php echo date('d M Y, H:i', strtotime($row['tanggal_resign'])); ?></span></td>
                            </tr>
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
                "language": { "search": "Cari Karyawan:", "lengthMenu": "Tampil _MENU_" },
                "ordering": true,
                "order": [[4, "desc"]] // Mengurutkan berdasarkan tanggal resign terbaru
            });
        });
    </script>
</body>
</html>