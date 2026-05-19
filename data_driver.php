<?php
session_start();
require 'koneksi.php'; // Wajib panggil koneksi database

// Cek keamanan session
if (!isset($_SESSION['log'])) {
    header('location:index.php');
    exit();
}

// 1. LOGIKA TAMBAH DRIVER (Menangkap data dari Modal Tambah)
if (isset($_POST['simpan_driver'])) {
    $id_login    = mysqli_real_escape_string($conn, $_POST['id_login']);
    $kode_driver = mysqli_real_escape_string($conn, $_POST['kode_driver']);
    $lisensi_sim = mysqli_real_escape_string($conn, $_POST['lisensi_sim']);
    $berlaku_sim = mysqli_real_escape_string($conn, $_POST['berlaku_sim']);
    $no_telepon  = mysqli_real_escape_string($conn, $_POST['no_telepon']);

    $insert = mysqli_query($conn, "INSERT INTO driver (id_login, kode_driver, lisensi_sim, berlaku_sim, no_telepon, status_kerja) 
                                   VALUES ('$id_login', '$kode_driver', '$lisensi_sim', '$berlaku_sim', '$no_telepon', 'AVAILABLE')");
    if ($insert) {
        echo "<script>alert('Data operasional driver berhasil dilengkapi!'); window.location.href='data_driver.php';</script>";
    } else {
        echo "<script>alert('Gagal menyimpan data!'); window.location.href='data_driver.php';</script>";
    }
}

// 2. LOGIKA EDIT DRIVER (Menangkap data dari Modal Edit)
if (isset($_POST['update_driver'])) {
    $id_driver    = mysqli_real_escape_string($conn, $_POST['id_driver']);
    $kode_driver  = mysqli_real_escape_string($conn, $_POST['kode_driver']);
    $lisensi_sim  = mysqli_real_escape_string($conn, $_POST['lisensi_sim']);
    $berlaku_sim  = mysqli_real_escape_string($conn, $_POST['berlaku_sim']);
    $no_telepon   = mysqli_real_escape_string($conn, $_POST['no_telepon']);
    $status_kerja = mysqli_real_escape_string($conn, $_POST['status_kerja']);

    $update = mysqli_query($conn, "UPDATE driver SET 
                                   kode_driver='$kode_driver', lisensi_sim='$lisensi_sim', 
                                   berlaku_sim='$berlaku_sim', no_telepon='$no_telepon', status_kerja='$status_kerja' 
                                   WHERE id='$id_driver'");
    if ($update) {
        echo "<script>alert('Data driver berhasil diupdate!'); window.location.href='data_driver.php';</script>";
    } else {
        echo "<script>alert('Gagal mengupdate data!'); window.location.href='data_driver.php';</script>";
    }
}

// 3. LOGIKA HAPUS DRIVER
if (isset($_GET['hapus'])) {
    $id_driver = $_GET['hapus'];
    $hapus = mysqli_query($conn, "DELETE FROM driver WHERE id='$id_driver'");
    if ($hapus) {
        echo "<script>alert('Data operasional driver berhasil dihapus!'); window.location.href='data_driver.php';</script>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Data Driver - Global Logistics</title>
    
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

        /* --- CSS LAYOUT --- */
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

        /* --- CONTAINER & TABLE --- */
        .driver-container { background: white; border-radius: 20px; padding: 25px; box-shadow: 0 10px 30px rgba(0,0,0,0.03); border: 1px solid rgba(0,0,0,0.05);}
        .table thead th { background-color: #f8fafc; border: none; color: #475569; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; padding: 15px; border-bottom: 2px solid #e2e8f0 !important; }
        .table tbody td { padding: 15px; vertical-align: middle; border-bottom: 1px solid #f1f5f9; font-size: 0.9rem;}

        .driver-profile {
            width: 45px; height: 45px; background: #e2e8f0; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            color: var(--primary-blue); border: 2px solid #fff;
            box-shadow: 0 0 10px rgba(0,0,0,0.05); font-size: 1.2rem;
        }

        .badge-sim { padding: 5px 12px; border-radius: 8px; font-size: 0.75rem; font-weight: 700; background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0;}
        .status-badge { padding: 6px 15px; border-radius: 50px; font-size: 0.7rem; font-weight: 800; letter-spacing: 0.5px;}
        .status-active { background: #d1fae5; color: #065f46; border: 1px solid #10b981; } 
        .status-off { background: #fef3c7; color: #92400e; border: 1px solid #f59e0b; } 
        
        .page-item.active .page-link { background-color: var(--primary-blue); border-color: var(--primary-blue); }
    </style>
</head>
<body>

    <?php include 'sidebar.php'; ?>

    <div id="main-content">
        
        <?php include 'navbar.php'; ?>

        <div class="container-fluid p-4 flex-grow-1 mb-5">
            <div class="d-flex align-items-center justify-content-between flex-wrap mt-2 mb-4">
                <div>
                    <h3 class="fw-bold mb-1" style="color: #1a367e;">Database Driver</h3>
                    <p class="text-muted small mb-0">Kelola data operasional personil pengiriman PT Global Logistics.</p>
                </div>
                <button class="btn btn-primary shadow-sm mt-3 mt-md-0 fw-medium" style="border-radius: 10px; padding: 10px 20px;" data-bs-toggle="modal" data-bs-target="#modalTambahDriver">
                    <i class="fas fa-id-badge me-2"></i> Lengkapi Data Driver
                </button>
            </div>

            <div class="driver-container">
                <div class="table-responsive">
                    <table class="table table-hover align-middle" id="dataTableDriver" width="100%">
                        <thead>
                            <tr>
                                <th width="60">Foto</th>
                                <th>Nama Driver</th>
                                <th>Lisensi (SIM)</th>
                                <th>No. Telepon</th>
                                <th>Status Kerja</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $query_driver = mysqli_query($conn, "
                                SELECT driver.*, login.nama_lengkap, login.email 
                                FROM driver 
                                JOIN login ON driver.id_login = login.id 
                                ORDER BY driver.id DESC
                            ");
                            
                            while ($row = mysqli_fetch_assoc($query_driver)) {
                                $status = empty($row['status_kerja']) ? 'AVAILABLE' : $row['status_kerja'];
                                
                                // Penentuan Label/Badge Rapi
                                if ($status == 'ON TRIP') {
                                    $badge_status = 'status-active';
                                } elseif ($status == 'RETURNING') {
                                    $badge_status = 'bg-warning text-dark border border-warning';
                                } elseif ($status == 'AVAILABLE') {
                                    $badge_status = 'bg-primary bg-opacity-10 text-primary border border-primary';
                                } else {
                                    $badge_status = 'status-off'; // Maintenance / OFF
                                }

                                $tgl_berlaku = new DateTime($row['berlaku_sim']);
                                $sekarang = new DateTime();
                                
                                if ($tgl_berlaku < $sekarang) {
                                    $sim_warning = '<div class="small text-danger mt-1 fw-bold" style="font-size: 0.65rem;"><i class="fas fa-exclamation-circle me-1"></i> SIM EXPIRED!</div>';
                                } else {
                                    $sim_warning = '<div class="small text-muted mt-1" style="font-size: 0.65rem;">Berlaku s/d: ' . date('d M Y', strtotime($row['berlaku_sim'])) . '</div>';
                                }
                                
                                $nama_tampil = !empty($row['nama_lengkap']) ? $row['nama_lengkap'] : explode('@', $row['email'])[0];
                            ?>
                            <tr>
                                <td>
                                    <div class="driver-profile"><i class="fas fa-user"></i></div>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark"><?php echo ucwords($nama_tampil); ?></div>
                                    <div class="small text-muted">ID: <?php echo $row['kode_driver']; ?></div>
                                </td>
                                <td>
                                    <span class="badge-sim"><?php echo $row['lisensi_sim']; ?></span>
                                    <?php echo $sim_warning; ?>
                                </td>
                                <td class="fw-medium text-secondary"><?php echo $row['no_telepon']; ?></td>
                                <td><span class="status-badge <?php echo $badge_status; ?>"><?php echo $status; ?></span></td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-light border text-primary shadow-sm" title="Edit" data-bs-toggle="modal" data-bs-target="#modalEditDriver<?php echo $row['id']; ?>">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-light border text-info shadow-sm" title="Detail" data-bs-toggle="modal" data-bs-target="#modalDetailDriver<?php echo $row['id']; ?>">
                                        <i class="fas fa-id-card"></i>
                                    </button>
                                    <a href="data_driver.php?hapus=<?php echo $row['id']; ?>" class="btn btn-sm btn-light border text-danger shadow-sm" title="Hapus" onclick="return confirm('Yakin ingin menghapus data operasional <?php echo $nama_tampil; ?>?')"><i class="fas fa-trash"></i></a>
                                </td>
                            </tr>

                            <div class="modal fade" id="modalEditDriver<?php echo $row['id']; ?>" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content" style="border-radius: 20px;">
                                        <form action="" method="POST">
                                            <div class="modal-header border-0 pt-4 px-4">
                                                <h5 class="modal-title fw-bold"><i class="fas fa-edit text-primary me-2"></i>Edit Data Driver</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body px-4">
                                                <input type="hidden" name="id_driver" value="<?php echo $row['id']; ?>">
                                                
                                                <div class="mb-3">
                                                    <label class="small fw-bold mb-1">Nama Driver</label>
                                                    <input type="text" class="form-control bg-light" value="<?php echo ucwords($nama_tampil); ?>" disabled style="border-radius: 10px;">
                                                </div>
                                                <div class="mb-3">
                                                    <label class="small fw-bold mb-1">Kode Pegawai</label>
                                                    <input type="text" name="kode_driver" class="form-control" value="<?php echo $row['kode_driver']; ?>" style="border-radius: 10px;" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="small fw-bold mb-1">Jenis Lisensi (SIM)</label>
                                                    <input type="text" name="lisensi_sim" class="form-control" value="<?php echo $row['lisensi_sim']; ?>" style="border-radius: 10px;" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="small fw-bold mb-1">Masa Berlaku SIM</label>
                                                    <input type="date" name="berlaku_sim" class="form-control" value="<?php echo $row['berlaku_sim']; ?>" style="border-radius: 10px;" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="small fw-bold mb-1">Nomor Telepon / WA</label>
                                                    <input type="text" name="no_telepon" class="form-control" value="<?php echo $row['no_telepon']; ?>" style="border-radius: 10px;" required>
                                                </div>
                                                <div class="mb-4">
                                                    <label class="small fw-bold mb-1">Status Kerja</label>
                                                    <?php if($row['status_kerja'] == 'ON TRIP' || $row['status_kerja'] == 'RETURNING'): ?>
                                                        <input type="text" class="form-control" value="<?= $row['status_kerja']; ?> (Dikelola Sistem)" disabled style="border-radius: 10px;">
                                                        <input type="hidden" name="status_kerja" value="<?= $row['status_kerja']; ?>">
                                                    <?php else: ?>
                                                        <select name="status_kerja" class="form-select" style="border-radius: 10px;">
                                                            <option value="AVAILABLE" <?php if($row['status_kerja'] == 'AVAILABLE') echo 'selected'; ?>>AVAILABLE (Tersedia)</option>
                                                            <option value="OFF" <?php if($row['status_kerja'] == 'OFF') echo 'selected'; ?>>OFF (Cuti / Libur)</option>
                                                        </select>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <div class="modal-footer border-0 pb-4 px-4">
                                                <button type="button" class="btn btn-light fw-medium" data-bs-dismiss="modal" style="border-radius: 10px;">Batal</button>
                                                <button type="submit" name="update_driver" class="btn btn-primary fw-medium px-4" style="border-radius: 10px;">Update Data</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="modal fade" id="modalDetailDriver<?php echo $row['id']; ?>" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content" style="border-radius: 20px; border: none; overflow: hidden;">
                                        <div class="modal-header pt-4 px-4" style="background: linear-gradient(45deg, #1a367e, #2b59ff); color: white; border: none;">
                                            <h5 class="modal-title fw-bold"><i class="fas fa-address-card me-2"></i> Profil Driver</h5>
                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        
                                        <div class="modal-body p-4 text-center">
                                            <div class="driver-profile mx-auto mb-3 shadow" style="width: 90px; height: 90px; font-size: 2.5rem; background: #f8fafc; color: #1a367e;">
                                                <i class="fas fa-user-tie"></i>
                                            </div>
                                            
                                            <h4 class="fw-bold mb-0 text-dark"><?php echo ucwords($nama_tampil); ?></h4>
                                            <div class="badge bg-light text-secondary border mt-2 mb-3 px-3 py-2" style="letter-spacing: 1px;">
                                                ID: <?php echo $row['kode_driver']; ?>
                                            </div>
                                            
                                            <hr class="opacity-10 mb-4">
                                            
                                            <div class="row text-start g-3">
                                                <div class="col-6">
                                                    <label class="small text-muted fw-bold text-uppercase" style="font-size: 0.65rem;">Nomor HP / WA</label>
                                                    <div class="fw-medium text-dark"><i class="fas fa-phone-alt text-success me-2"></i><?php echo $row['no_telepon']; ?></div>
                                                </div>
                                                <div class="col-6">
                                                    <label class="small text-muted fw-bold text-uppercase" style="font-size: 0.65rem;">Alamat Email</label>
                                                    <div class="fw-medium text-dark text-truncate"><i class="fas fa-envelope text-warning me-2"></i><?php echo $row['email']; ?></div>
                                                </div>
                                                <div class="col-6">
                                                    <label class="small text-muted fw-bold text-uppercase" style="font-size: 0.65rem;">Lisensi SIM</label>
                                                    <div class="fw-medium text-dark"><i class="fas fa-id-badge text-primary me-2"></i><?php echo $row['lisensi_sim']; ?></div>
                                                </div>
                                                <div class="col-6">
                                                    <label class="small text-muted fw-bold text-uppercase" style="font-size: 0.65rem;">Masa Berlaku SIM</label>
                                                    <div class="fw-medium text-dark"><i class="far fa-calendar-alt text-danger me-2"></i><?php echo date('d F Y', strtotime($row['berlaku_sim'])); ?></div>
                                                </div>
                                                <div class="col-12 mt-4 text-center">
                                                    <label class="small text-muted fw-bold text-uppercase d-block mb-2" style="font-size: 0.65rem;">Status Operasional Saat Ini</label>
                                                    <span class="status-badge <?php echo $badge_status; ?> px-4 py-2" style="font-size: 0.85rem;"><?php echo $status; ?></span>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="modal-footer border-0 pb-4 px-4 justify-content-center">
                                            <button type="button" class="btn btn-light fw-medium w-100 shadow-sm" data-bs-dismiss="modal" style="border-radius: 10px;">Tutup Profil</button>
                                        </div>
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

    <div class="modal fade" id="modalTambahDriver" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content" style="border-radius: 20px;">
                <form action="" method="POST">
                    <div class="modal-header border-0 pt-4 px-4">
                        <h5 class="modal-title fw-bold"><i class="fas fa-id-badge text-primary me-2"></i>Lengkapi Data Driver</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body px-4">
                        <p class="small text-muted mb-4">Pilih akun User yang sudah didaftarkan dengan Role 'Driver' untuk melengkapi data operasionalnya.</p>
                        
                        <div class="mb-3">
                            <label class="small fw-bold mb-1">Pilih Akun Driver</label>
                            <select name="id_login" class="form-select" style="border-radius: 10px;" required>
                                <option value="" selected disabled>-- Pilih Akun (Kosong jika belum dibuat di Manajemen Pengguna) --</option>
                                <?php
                                $q_akun = mysqli_query($conn, "SELECT id, nama_lengkap, email FROM login WHERE role='driver' AND id NOT IN (SELECT id_login FROM driver)");
                                while($akun = mysqli_fetch_assoc($q_akun)){
                                    $nama_akun = !empty($akun['nama_lengkap']) ? $akun['nama_lengkap'] : $akun['email'];
                                    echo "<option value='".$akun['id']."'>".$nama_akun."</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="small fw-bold mb-1">Kode Pegawai</label>
                            <input type="text" name="kode_driver" class="form-control" placeholder="Contoh: DRV-008" style="border-radius: 10px;" required>
                        </div>
                        <div class="mb-3">
                            <label class="small fw-bold mb-1">Jenis Lisensi (SIM)</label>
                            <input type="text" name="lisensi_sim" class="form-control" placeholder="Contoh: SIM B1 UMUM" style="border-radius: 10px;" required>
                        </div>
                        <div class="mb-3">
                            <label class="small fw-bold mb-1">Masa Berlaku SIM</label>
                            <input type="date" name="berlaku_sim" class="form-control" style="border-radius: 10px;" required>
                        </div>
                        <div class="mb-4">
                            <label class="small fw-bold mb-1">Nomor Telepon / WA</label>
                            <input type="text" name="no_telepon" class="form-control" placeholder="Contoh: 0812-xxxx-xxxx" style="border-radius: 10px;" required>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pb-4 px-4">
                        <button type="button" class="btn btn-light fw-medium" data-bs-dismiss="modal" style="border-radius: 10px;">Batal</button>
                        <button type="submit" name="simpan_driver" class="btn btn-primary fw-medium px-4" style="border-radius: 10px;">Simpan Data</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    
    <script>
        $(document).ready(function() {
            $('#dataTableDriver').DataTable({
                "language": {
                    "search": "Cari Driver:",
                    "lengthMenu": "Tampil _MENU_",
                    "info": "Menampilkan _START_ s/d _END_ dari _TOTAL_ driver",
                    "paginate": {
                        "previous": "<i class='fas fa-chevron-left'></i>",
                        "next": "<i class='fas fa-chevron-right'></i>"
                    }
                }
            });
        });
    </script>
</body>
</html>