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
        
        <!-- GLOBAL SEARCH -->
        <div class="search-bar d-none d-lg-block position-relative" style="width:320px;">
            <div class="input-group input-group-sm">
                <span class="input-group-text border-0" style="background:#f1f5f9; border-radius:10px 0 0 10px;">
                    <i class="fas fa-search text-muted"></i>
                </span>
                <input type="text" id="globalSearch" 
                       class="form-control border-0" 
                       placeholder="Cari dokumen, driver, barang..." 
                       autocomplete="off"
                       style="background:#f1f5f9; font-size:0.82rem; border-radius:0 10px 10px 0; box-shadow:none;">
            </div>
            <!-- DROPDOWN HASIL PENCARIAN -->
            <div id="searchResults" class="position-absolute w-100 bg-white rounded-3 shadow-lg border-0 mt-1 d-none"
                 style="z-index:9999; max-height:380px; overflow-y:auto; top:100%; left:0;">
                <div id="searchResultsInner" class="p-2"></div>
            </div>
        </div>
    </div>

    <div class="d-flex align-items-center">
        
        <div class="dropdown me-3">
            <a href="#" class="text-muted position-relative" id="notifDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="fas fa-bell fa-lg"></i>
                <span id="notif-badge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger d-none" style="font-size: 0.6rem; padding: 0.35em 0.65em;">
                    0
                </span>
            </a>
            <ul class="dropdown-menu dropdown-menu-end border-0 shadow-sm mt-3 animate slideIn" aria-labelledby="notifDropdown" style="width: 320px; border-radius: 12px; max-height: 400px; overflow-y: auto;">
                <li><h6 class="dropdown-header fw-bold text-dark d-flex justify-content-between align-items-center">
                    Notifikasi Terbaru
                    <a href="#" id="mark-all-read" class="small text-primary text-decoration-none" style="font-size: 0.7rem;">Tandai Dibaca</a>
                </h6></li>
                <div id="notif-list">
                    <li><a class="dropdown-item py-3 text-center text-muted" href="#" style="font-size: 0.8rem;">
                        Belum ada notifikasi baru
                    </a></li>
                </div>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item small text-center text-primary fw-bold" href="#">Lihat Semua Notifikasi</a></li>
            </ul>
        </div>

        <div class="dropdown">
            <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle text-dark" id="userMenu" data-bs-toggle="dropdown" aria-expanded="false">
                <div class="text-end me-3 d-none d-md-block">
                    <p class="mb-0 small fw-bold text-dark">
                        <?php echo !empty($_SESSION['nama_lengkap']) ? $_SESSION['nama_lengkap'] : 'Administrator'; ?>
                    </p>
                    <p class="mb-0 text-muted" style="font-size: 0.65rem; text-transform: uppercase; letter-spacing: 1px; font-weight: 600;">
                        <?php echo !empty($_SESSION['role']) ? $_SESSION['role'] : 'STAFF LOGISTIK'; ?>
                    </p>
                </div>
                <img src="<?php echo $nav_foto_url; ?>" 
                     class="rounded-circle shadow-sm border border-2 border-white" 
                     width="40" height="40" alt="User Avatar" style="object-fit: cover;">
            </a>
            <ul class="dropdown-menu dropdown-menu-end border-0 shadow mt-3" aria-labelledby="userMenu" style="border-radius: 15px; min-width: 200px;">
                <li class="px-3 py-2 border-bottom mb-2">
                    <p class="mb-0 small fw-bold text-dark"><?php echo !empty($_SESSION['nama_lengkap']) ? $_SESSION['nama_lengkap'] : 'Administrator'; ?></p>
                    <small class="text-muted" style="font-size: 0.7rem; text-transform: uppercase;"><?php echo !empty($_SESSION['role']) ? $_SESSION['role'] : 'Admin'; ?></small>
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    function fetchNotifications() {
        fetch('api_notifications.php?action=fetch')
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    // Update Badge
                    const badge = document.getElementById('notif-badge');
                    if (data.unread_count > 0) {
                        badge.textContent = data.unread_count;
                        badge.classList.remove('d-none');
                    } else {
                        badge.classList.add('d-none');
                    }

                    // Update List
                    const notifList = document.getElementById('notif-list');
                    if (data.data.length > 0) {
                        let html = '';
                        data.data.forEach(notif => {
                            let icon = 'fa-info-circle text-info';
                            if (notif.tipe === 'success') icon = 'fa-check-circle text-success';
                            if (notif.tipe === 'warning') icon = 'fa-exclamation-triangle text-warning';
                            if (notif.tipe === 'danger') icon = 'fa-times-circle text-danger';

                            html += `
                                <li><a class="dropdown-item py-2 notif-item" href="${notif.link || '#'}" data-id="${notif.id}" style="font-size: 0.8rem; white-space: normal;">
                                    <div class="d-flex">
                                        <div class="me-2 mt-1"><i class="fas ${icon}"></i></div>
                                        <div>
                                            <div class="fw-bold text-dark mb-1">${notif.judul}</div>
                                            <div class="text-muted" style="font-size: 0.75rem;">${notif.pesan}</div>
                                            <div class="text-primary mt-1" style="font-size: 0.65rem;"><i class="far fa-clock me-1"></i>${notif.time_ago}</div>
                                        </div>
                                    </div>
                                </a></li>
                            `;
                        });
                        notifList.innerHTML = html;
                    } else {
                        notifList.innerHTML = `<li><a class="dropdown-item py-3 text-center text-muted" href="#" style="font-size: 0.8rem;">Belum ada notifikasi baru</a></li>`;
                    }
                }
            })
            .catch(error => console.error('Error fetching notifications:', error));
    }

    // Initial fetch
    fetchNotifications();

    // Poll every 3 seconds for real-time feel
    setInterval(fetchNotifications, 3000);

    // Mark as read when clicking a notification
    document.getElementById('notif-list').addEventListener('click', function(e) {
        const item = e.target.closest('.notif-item');
        if (item) {
            const id = item.getAttribute('data-id');
            fetch('api_notifications.php?action=read', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'id=' + id
            });
            // Let the link navigation proceed natively
        }
    });

    // Mark all as read
    document.getElementById('mark-all-read').addEventListener('click', function(e) {
        e.preventDefault();
        fetch('api_notifications.php?action=read_all')
            .then(() => fetchNotifications());
    });
});
</script>

<script>
// ============================================================
// GLOBAL LIVE SEARCH — Navbar
// ============================================================
(function() {
    const searchInput = document.getElementById('globalSearch');
    const searchBox   = document.getElementById('searchResults');
    const searchInner = document.getElementById('searchResultsInner');
    let debounceTimer;

    if (!searchInput) return;

    searchInput.addEventListener('input', function() {
        const q = this.value.trim();
        clearTimeout(debounceTimer);

        if (q.length < 2) {
            searchBox.classList.add('d-none');
            return;
        }

        debounceTimer = setTimeout(() => {
            fetch('api_search.php?q=' + encodeURIComponent(q))
                .then(r => r.json())
                .then(data => {
                    if (data.results && data.results.length > 0) {
                        let html = '';
                        data.results.forEach(item => {
                            const icons = {
                                'surat_jalan': 'fa-file-invoice text-primary',
                                'driver': 'fa-user text-success',
                                'barang': 'fa-box text-warning',
                                'armada': 'fa-truck text-info',
                                'invoice': 'fa-file-invoice-dollar text-danger',
                            };
                            const icon = icons[item.type] || 'fa-search text-muted';
                            html += `
                            <a href="${item.link}" class="d-flex align-items-center gap-3 p-2 rounded-2 text-decoration-none search-result-item"
                               style="transition:background 0.15s; color:#1e293b;">
                                <div style="width:34px;height:34px;border-radius:8px;background:#f1f5f9;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                    <i class="fas ${icon}" style="font-size:0.85rem;"></i>
                                </div>
                                <div style="min-width:0;">
                                    <div class="fw-semibold text-truncate" style="font-size:0.83rem;">${item.title}</div>
                                    <div class="text-muted text-truncate" style="font-size:0.72rem;">${item.subtitle}</div>
                                </div>
                                <div class="ms-auto flex-shrink-0">
                                    <span class="badge rounded-pill px-2" style="font-size:0.62rem;background:#e2e8f0;color:#64748b;">${item.category}</span>
                                </div>
                            </a>`;
                        });
                        searchInner.innerHTML = html;
                    } else {
                        searchInner.innerHTML = `<div class="text-center text-muted py-3" style="font-size:0.82rem;">
                            <i class="fas fa-search-minus mb-1 d-block" style="font-size:1.4rem;opacity:0.3;"></i>
                            Tidak ada hasil untuk "<b>${q}</b>"
                        </div>`;
                    }
                    searchBox.classList.remove('d-none');
                })
                .catch(() => {});
        }, 300);
    });

    // Style hover result items
    document.addEventListener('mouseover', function(e) {
        const item = e.target.closest('.search-result-item');
        if (item) item.style.background = '#f8fafc';
    });
    document.addEventListener('mouseout', function(e) {
        const item = e.target.closest('.search-result-item');
        if (item) item.style.background = '';
    });

    // Close on outside click
    document.addEventListener('click', function(e) {
        if (!searchInput.contains(e.target) && !searchBox.contains(e.target)) {
            searchBox.classList.add('d-none');
        }
    });

    searchInput.addEventListener('focus', function() {
        if (searchInner.innerHTML.trim() !== '' && this.value.length >= 2) {
            searchBox.classList.remove('d-none');
        }
    });
})();
</script>