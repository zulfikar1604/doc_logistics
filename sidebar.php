<?php 
// 1. Ambil nama file halaman yang sedang dibuka saat ini
$current_page = basename($_SERVER['PHP_SELF']); 

// 2. Buat variabel pengecekan untuk menu dokumentasi (dropdown)
$is_docs_active = ($current_page == 'buat_surat_jalan.php' || $current_page == 'invoice_pengiriman.php');

// 3. Ambil ROLE user untuk filter menu
$role = isset($_SESSION['role']) ? strtoupper($_SESSION['role']) : 'STAF';
?>

<nav id="sidebar">
    <div class="sidebar-header text-center">
        <h5 class="text-white fw-bold mb-0" style="letter-spacing: 1px;">PT Elektronik Global</h5>
        <hr class="bg-light opacity-25 mx-3 mt-3 mb-0">
    </div>
    
    <div class="sidebar-menu">
        <span class="menu-label">UTAMA</span>
        <ul class="list-unstyled components">
            <?php if($role != 'DRIVER'): ?>
            <li class="<?php echo ($current_page == 'index.php') ? 'active' : ''; ?>">
                <a href="index.php"><i class="fas fa-chart-line text-warning"></i> Dashboard Utama</a>
            </li>
            <?php else: ?>
            <li class="<?php echo ($current_page == 'dashboard_driver.php') ? 'active' : ''; ?>">
                <a href="dashboard_driver.php"><i class="fas fa-route text-success"></i> Dashboard Saya</a>
            </li>
            <?php endif; ?>
        </ul>

        <?php if($role != 'DRIVER'): ?>
        <span class="menu-label">OPERASIONAL</span>
        <ul class="list-unstyled components">
            <li>
                <a href="#collapseDocs" data-bs-toggle="collapse" aria-expanded="<?php echo $is_docs_active ? 'true' : 'false'; ?>" class="dropdown-toggle">
                    <i class="fas fa-file-invoice"></i> Input Dokumen
                </a>
                <ul class="collapse list-unstyled submenu <?php echo $is_docs_active ? 'show' : ''; ?>" id="collapseDocs">
                    <li class="<?php echo ($current_page == 'buat_surat_jalan.php') ? 'active' : ''; ?>">
                        <a href="buat_surat_jalan.php">Surat Jalan (DO)</a>
                    </li>
                    <li class="<?php echo ($current_page == 'invoice_pengiriman.php') ? 'active' : ''; ?>">
                        <a href="invoice_pengiriman.php">Invoice Pengiriman</a>
                    </li>
                </ul>
            </li>
            <li class="<?php echo ($current_page == 'stok_barang.php') ? 'active' : ''; ?>">
                <a href="stok_barang.php"><i class="fas fa-boxes text-white"></i> Stok Barang</a>
            </li>
            <li class="<?php echo ($current_page == 'status_pengiriman.php') ? 'active' : ''; ?>">
                <a href="status_pengiriman.php"><i class="fas fa-shipping-fast text-info"></i> Status Pengiriman</a>
            </li>
            <li class="<?php echo ($current_page == 'live_tracking.php') ? 'active' : ''; ?>">
                <a href="live_tracking.php"><i class="fas fa-map-marked-alt text-success"></i> Live Tracking</a>
            </li>
        </ul>
        <?php endif; ?>

        <span class="menu-label">ARSIP</span>
        <ul class="list-unstyled components">
            <li class="<?php echo ($current_page == 'manajemen_dokumen.php') ? 'active' : ''; ?>">
                <a href="manajemen_dokumen.php">
                    <i class="fas fa-archive"></i> 
                    <?php echo ($role == 'DRIVER') ? 'Riwayat Orderan' : 'Arsip & Riwayat'; ?>
                </a>
            </li>
            <?php if($role != 'DRIVER'): ?>
            <li class="<?php echo ($current_page == 'manajemen_invoice.php') ? 'active' : ''; ?>">
                <a href="manajemen_invoice.php"><i class="fas fa-file-invoice-dollar"></i> Arsip Invoice</a>
            </li>
            <?php endif; ?>
        </ul>

        <span class="menu-label">MASTER DATA</span>
        <ul class="list-unstyled components">
            <li class="<?php echo ($current_page == 'data_armada.php') ? 'active' : ''; ?>">
                <a href="data_armada.php"><i class="fas fa-truck"></i> Data Armada</a>
            </li>
            
            <?php if($role != 'DRIVER'): ?>
            <li class="<?php echo ($current_page == 'data_driver.php') ? 'active' : ''; ?>">
                <a href="data_driver.php"><i class="fas fa-users"></i> Data Driver</a>
            </li>
            <li class="<?php echo ($current_page == 'kategori_barang.php') ? 'active' : ''; ?>">
                <a href="kategori_barang.php"><i class="fas fa-tags text-primary"></i> Data Kategori</a>
            </li>
            <li class="<?php echo ($current_page == 'master_points.php') ? 'active' : ''; ?>">
                <a href="master_points.php"><i class="fas fa-map-pin text-danger"></i> Master Checkpoint</a>
            </li>
            <li class="<?php echo ($current_page == 'manajemen_rute.php') ? 'active' : ''; ?>">
                <a href="manajemen_rute.php"><i class="fas fa-route text-info"></i> Manajemen Rute</a>
            </li>
            <?php endif; ?>
            
            <?php if($role == 'MANAGER'): ?>
            <li class="<?php echo ($current_page == 'user_management.php') ? 'active' : ''; ?>">
                <a href="user_management.php"><i class="fas fa-user-shield text-danger"></i> User Management</a>
            </li>
            <li class="<?php echo ($current_page == 'arsip_karyawan_resign.php') ? 'active' : ''; ?>">
                <a href="arsip_karyawan_resign.php"><i class="fas fa-user-times text-secondary"></i> Karyawan Resign</a>
            </li>
            <?php endif; ?>
        </ul>
    </div>
    
    <div class="sidebar-footer">
        <div class="small text-white-50 mb-1">Logged in as:</div>
        <div class="fw-bold text-white mb-2">
            <?php echo $role; ?>
        </div>
        <a href="keluar.php" class="text-danger text-decoration-none small d-block" style="padding:0; background:transparent;">
            <i class="fas fa-sign-out-alt me-1"></i> Keluar Sistem
        </a>
    </div>
</nav>