<?php 
// 1. Ambil nama file halaman yang sedang dibuka saat ini
$current_page = basename($_SERVER['PHP_SELF']); 

// 2. Buat variabel pengecekan untuk menu dokumentasi (dropdown)
$is_docs_active = ($current_page == 'buat_surat_jalan.php' || $current_page == 'invoice_pengiriman.php');
$is_gudang_active = ($current_page == 'stok_barang.php' || $current_page == 'penerimaan_barang.php');

// 3. Ambil ROLE user untuk filter menu
$role = isset($_SESSION['role']) ? strtoupper($_SESSION['role']) : 'MANAGER';

// 4. Nama user untuk footer
$nav_nama = isset($_SESSION['nama_lengkap']) && !empty($_SESSION['nama_lengkap']) ? $_SESSION['nama_lengkap'] : 'Administrator';
?>
<!-- INJECT GLOBAL SIDEBAR CSS — mencakup semua halaman secara otomatis -->
<link rel="stylesheet" href="css/sidebar-global.css">

<nav id="sidebar">

    <!-- HEADER BRANDING -->
    <div class="sidebar-header d-flex justify-content-between align-items-center">
        <div>
            <div class="brand-name">PT Elektronik Global</div>
            <div class="brand-sub">Global Logistics</div>
        </div>
        <!-- Tombol Tutup (Hanya Tampil di Mobile) -->
        <button type="button" class="btn btn-sm d-md-none text-white border-0" id="sidebarCloseBtn" style="background: rgba(255,255,255,0.15); border-radius: 6px;">
            <i class="fas fa-times"></i>
        </button>
    </div>

    <!-- SCROLLABLE MENU -->
    <div class="sidebar-menu">

        <!-- === MENU UTAMA === -->
        <span class="menu-label">Menu Utama</span>
        <ul class="list-unstyled components">
            <?php if($role != 'DRIVER'): ?>
            <li class="<?php echo ($current_page == 'dashboard_utama.php') ? 'active' : ''; ?>">
                <a href="dashboard_utama.php">
                    <i class="fas fa-chart-pie"></i> Dashboard Utama
                </a>
            </li>
            <?php else: ?>
            <li class="<?php echo ($current_page == 'dashboard_driver.php') ? 'active' : ''; ?>">
                <a href="dashboard_driver.php">
                    <i class="fas fa-route"></i> Dashboard Saya
                </a>
            </li>
            <?php endif; ?>
        </ul>

        <?php if($role != 'DRIVER'): ?>

        <!-- === OPERASIONAL === -->
        <span class="menu-label">Operasional</span>
        <ul class="list-unstyled components">
            <li>
                <a href="#collapseDocs" 
                   data-bs-toggle="collapse" 
                   class="dropdown-toggle"
                   aria-expanded="<?php echo $is_docs_active ? 'true' : 'false'; ?>">
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
            <li>
                <a href="#collapseGudang" 
                   data-bs-toggle="collapse" 
                   class="dropdown-toggle"
                   aria-expanded="<?php echo $is_gudang_active ? 'true' : 'false'; ?>">
                    <i class="fas fa-warehouse"></i> Manajemen Gudang
                </a>
                <ul class="collapse list-unstyled submenu <?php echo $is_gudang_active ? 'show' : ''; ?>" id="collapseGudang">
                    <li class="<?php echo ($current_page == 'stok_barang.php') ? 'active' : ''; ?>">
                        <a href="stok_barang.php">Master Data Barang</a>
                    </li>
                    <li class="<?php echo ($current_page == 'penerimaan_barang.php') ? 'active' : ''; ?>">
                        <a href="penerimaan_barang.php">Penerimaan Barang</a>
                    </li>
                </ul>
            </li>
            <li class="<?php echo ($current_page == 'status_pengiriman.php') ? 'active' : ''; ?>">
                <a href="status_pengiriman.php">
                    <i class="fas fa-shipping-fast"></i> Status Pengiriman
                </a>
            </li>
            <li class="<?php echo ($current_page == 'live_tracking.php') ? 'active' : ''; ?>">
                <a href="live_tracking.php">
                    <i class="fas fa-map-marked-alt"></i> Live Tracking
                </a>
            </li>
        </ul>

        <?php endif; ?>

        <!-- === ARSIP === -->
        <span class="menu-label">Arsip</span>
        <ul class="list-unstyled components">
            <li class="<?php echo ($current_page == 'manajemen_dokumen.php') ? 'active' : ''; ?>">
                <a href="manajemen_dokumen.php">
                    <i class="fas fa-archive"></i>
                    <?php echo ($role == 'DRIVER') ? 'Riwayat Orderan' : 'Arsip & Riwayat'; ?>
                </a>
            </li>
            <?php if($role != 'DRIVER'): ?>
            <li class="<?php echo ($current_page == 'arsip_penerimaan.php') ? 'active' : ''; ?>">
                <a href="arsip_penerimaan.php">
                    <i class="fas fa-box-open"></i> Arsip Penerimaan
                </a>
            </li>
            <li class="<?php echo ($current_page == 'manajemen_invoice.php') ? 'active' : ''; ?>">
                <a href="manajemen_invoice.php">
                    <i class="fas fa-file-invoice-dollar"></i> Arsip Invoice
                </a>
            </li>
            <?php endif; ?>
        </ul>

        <?php if($role != 'DRIVER'): ?>

        <!-- === MASTER DATA === -->
        <span class="menu-label">Master Data</span>
        <ul class="list-unstyled components">
            <li class="<?php echo ($current_page == 'data_armada.php') ? 'active' : ''; ?>">
                <a href="data_armada.php">
                    <i class="fas fa-truck"></i> Data Armada
                </a>
            </li>
            <li class="<?php echo ($current_page == 'data_driver.php') ? 'active' : ''; ?>">
                <a href="data_driver.php">
                    <i class="fas fa-users"></i> Data Driver
                </a>
            </li>
            <li class="<?php echo ($current_page == 'kategori_barang.php') ? 'active' : ''; ?>">
                <a href="kategori_barang.php">
                    <i class="fas fa-tags"></i> Data Kategori
                </a>
            </li>
            <li class="<?php echo ($current_page == 'master_points.php') ? 'active' : ''; ?>">
                <a href="master_points.php">
                    <i class="fas fa-map-pin"></i> Master Checkpoint
                </a>
            </li>
            <li class="<?php echo ($current_page == 'manajemen_rute.php') ? 'active' : ''; ?>">
                <a href="manajemen_rute.php">
                    <i class="fas fa-route"></i> Manajemen Rute
                </a>
            </li>
            <?php if($role == 'ADMIN' || $role == 'MANAGER'): ?>
            <li class="<?php echo ($current_page == 'user_management.php') ? 'active' : ''; ?>">
                <a href="user_management.php">
                    <i class="fas fa-user-shield"></i> User Management
                </a>
            </li>
            <li class="<?php echo ($current_page == 'arsip_karyawan_resign.php') ? 'active' : ''; ?>">
                <a href="arsip_karyawan_resign.php">
                    <i class="fas fa-user-times"></i> Karyawan Resign
                </a>
            </li>
            <?php endif; ?>
        </ul>

        <?php endif; ?>

    </div><!-- end sidebar-menu -->

    <!-- FOOTER SIDEBAR -->
    <div class="sidebar-footer">
        <div class="user-role"><?= htmlspecialchars($role) ?></div>
        <div class="user-name"><?= htmlspecialchars($nav_nama) ?></div>
        <a href="keluar.php" class="btn-logout" onclick="return confirm('Apakah Anda yakin ingin keluar dari sistem?');">
            <i class="fas fa-sign-out-alt"></i> Logout System
        </a>
    </div>
</nav>

<!-- SIDEBAR OVERLAY (BACKDROP UNTUK MOBILE) -->
<div id="sidebarOverlay" class="d-md-none"></div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    var sidebar = document.getElementById("sidebar");
    var overlay = document.getElementById("sidebarOverlay");
    var closeBtn = document.getElementById("sidebarCloseBtn");
    var toggleBtn = document.getElementById("sidebarToggle");
    var mainContent = document.getElementById("main-content");

    // Fix for multiple pages trying to bind click to toggleBtn
    if (toggleBtn) {
        var newToggleBtn = toggleBtn.cloneNode(true);
        toggleBtn.parentNode.replaceChild(newToggleBtn, toggleBtn);
        
        newToggleBtn.addEventListener("click", function() {
            if (window.innerWidth <= 768) {
                sidebar.classList.toggle("show");
                overlay.classList.toggle("show");
            } else {
                sidebar.classList.toggle("collapsed");
                if (mainContent) mainContent.classList.toggle("expanded");
            }
        });
    }

    if (closeBtn) {
        closeBtn.addEventListener("click", function() {
            sidebar.classList.remove("show");
            overlay.classList.remove("show");
        });
    }

    if (overlay) {
        overlay.addEventListener("click", function() {
            sidebar.classList.remove("show");
            overlay.classList.remove("show");
        });
    }

    // =====================
    // SMOOTH PAGE TRANSITIONS
    // =====================
    document.querySelectorAll('a[href]').forEach(function(link) {
        var href = link.getAttribute('href');
        // Only intercept internal links (not anchors, not external, not logout, not data-bs-toggle)
        if (
            href &&
            !href.startsWith('#') &&
            !href.startsWith('http') &&
            !href.startsWith('javascript') &&
            !href.startsWith('mailto') &&
            !link.hasAttribute('data-bs-toggle') &&
            !link.hasAttribute('data-bs-target') &&
            !link.hasAttribute('target') &&
            link.getAttribute('onclick') === null
        ) {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                document.body.classList.add('page-leaving');
                var dest = href;
                setTimeout(function() {
                    window.location.href = dest;
                }, 200);
            });
        }
    });
});

</script>