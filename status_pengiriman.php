<?php
session_start();
// PERBAIKAN FATAL: Keamanan Session disamakan
if (!isset($_SESSION['log'])) {
    header('location:login.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Status Pengiriman - Global Logistics</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --sidebar-width: 260px;
            --primary-blue: #1a367e;
            --accent-yellow: #ffcc00;
            --bg-light: #f4f7f6;
        }

        body { font-family: 'Poppins', sans-serif; background-color: var(--bg-light); overflow-x: hidden; }

        /* --- CSS WAJIB LAYOUT ANTI TABRAKAN --- */
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
        .submenu a { padding-left: 50px !important; font-size: 0.8rem !important; }
        .sidebar-footer { padding: 20px 25px; background: rgba(0,0,0,0.2); }
        .top-navbar { background: white; padding: 15px 30px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); position: sticky; top: 0; z-index: 998; }

        /* --- TRACKING CARD SULTAN --- */
        .tracking-card {
            background: white;
            border-radius: 20px;
            border: 1px solid rgba(0,0,0,0.05);
            box-shadow: 0 10px 30px rgba(0,0,0,0.03);
            margin-bottom: 30px;
            transition: transform 0.3s;
        }
        .tracking-card:hover { transform: translateY(-5px); }

        .card-header-sultan {
            background: #f8fafc;
            border-bottom: 1px solid #edf2f7;
            padding: 20px 25px;
            border-radius: 20px 20px 0 0;
        }

        /* --- TIMELINE DESIGN (Responsive Fixes) --- */
        .timeline-steps { 
            display: flex; justify-content: center; flex-wrap: nowrap; 
            padding: 20px 0; overflow-x: auto; 
        }
        .timeline-step { 
            align-items: center; display: flex; flex-direction: column; 
            position: relative; margin: 10px 0; flex: 1; text-align: center;
        }
        
        .inner-circle { 
            border-radius: 50%; height: 1.5rem; width: 1.5rem; 
            border: 3px solid #e2e8f0; background-color: #fff; 
            margin: 0 auto 10px; position: relative; z-index: 2; 
        }
        .timeline-step.active .inner-circle { background-color: var(--primary-blue); border-color: var(--primary-blue); box-shadow: 0 0 0 5px rgba(26, 54, 126, 0.1); }
        .timeline-step.completed .inner-circle { background-color: #10b981; border-color: #10b981; }

        /* Garis penghubung timeline */
        .timeline-step:not(:last-child):after { 
            content: ""; display: block; position: absolute; 
            border-top: 3px solid #e2e8f0; width: 100%; top: 10px; left: 50%; z-index: 1; 
        }
        .timeline-step.completed:not(:last-child):after { border-color: #10b981; }

        .status-badge { padding: 6px 15px; border-radius: 50px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; }
        .bg-on-trip { background: #e0f2fe; color: #0369a1; }
        .bg-delivered { background: #dcfce7; color: #166534; }
        
        .info-label { font-size: 0.75rem; color: #64748b; font-weight: 600; text-transform: uppercase; margin-bottom: 2px; display: block;}
        .info-value { font-size: 0.95rem; color: #1e293b; font-weight: 700; }
    </style>
</head>
<body>

    <?php include 'sidebar.php'; ?>

    <div id="main-content">
        
        <?php include 'navbar.php'; ?>

        <div class="container-fluid p-4 flex-grow-1 mb-5">
            <div class="d-flex align-items-center justify-content-between flex-wrap mb-4">
                <div>
                    <h3 class="fw-bold mb-1" style="color: #1a367e;">Status Pengiriman</h3>
                    <p class="text-muted small mb-0">Pantau perjalanan armada secara real-time.</p>
                </div>
                <button class="btn btn-primary shadow-sm mt-2 mt-md-0" style="border-radius: 12px; padding: 10px 20px; font-weight: 500;">
                    <i class="fas fa-sync-alt me-2"></i> Refresh Data
                </button>
            </div>

            <div class="tracking-card">
                <div class="card-header-sultan d-flex justify-content-between align-items-center flex-wrap">
                    <div class="mb-2 mb-md-0">
                        <span class="info-label">No. Surat Jalan</span>
                        <span class="info-value text-primary">#SJ-20260331-001</span>
                    </div>
                    <span class="status-badge bg-on-trip"><i class="fas fa-truck me-1"></i> Sedang Dikirim</span>
                </div>
                
                <div class="card-body p-4">
                    <div class="timeline-steps">
                        <div class="timeline-step completed">
                            <div class="inner-circle"></div>
                            <p class="small fw-bold mb-0">Manifest</p>
                            <p class="text-muted" style="font-size: 0.7rem;">31 Mar, 08:00</p>
                        </div>
                        <div class="timeline-step active">
                            <div class="inner-circle"></div>
                            <p class="small fw-bold mb-0">On Trip</p>
                            <p class="text-muted" style="font-size: 0.7rem;">In Transit (Cirebon)</p>
                        </div>
                        <div class="timeline-step">
                            <div class="inner-circle"></div>
                            <p class="small fw-bold mb-0">Destination</p>
                            <p class="text-muted" style="font-size: 0.7rem;">Pending</p>
                        </div>
                        <div class="timeline-step">
                            <div class="inner-circle"></div>
                            <p class="small fw-bold mb-0">Delivered</p>
                            <p class="text-muted" style="font-size: 0.7rem;">Pending</p>
                        </div>
                    </div>

                    <hr class="opacity-25 my-4">

                    <div class="row text-center">
                        <div class="col-6 col-md-3 border-end mb-3 mb-md-0">
                            <span class="info-label">Driver</span>
                            <span class="info-value">Ahmad Junaidi</span>
                        </div>
                        <div class="col-6 col-md-3 border-end mb-3 mb-md-0">
                            <span class="info-label">Kendaraan</span>
                            <span class="info-value">B 1234 GLO (Hino)</span>
                        </div>
                        <div class="col-6 col-md-3 border-end">
                            <span class="info-label">Tujuan</span>
                            <span class="info-value">Semarang, Jateng</span>
                        </div>
                        <div class="col-6 col-md-3">
                            <span class="info-label">Estimasi Tiba</span>
                            <span class="info-value text-success">Hari ini, 21:00</span>
                        </div>
                    </div>
                </div>
                
                <div class="card-footer bg-white border-top-0 pb-4 px-4 d-flex justify-content-end">
                    <button class="btn btn-sm btn-outline-primary me-2 fw-medium" style="border-radius: 8px;">
                        <i class="fas fa-map-marker-alt me-1"></i> Lihat Map
                    </button>
                    <button class="btn btn-sm btn-light border fw-medium" style="border-radius: 8px;">
                        <i class="fas fa-phone-alt me-1"></i> Hubungi Driver
                    </button>
                </div>
            </div>

            <div class="tracking-card" style="opacity: 0.85;">
                <div class="card-header-sultan d-flex justify-content-between align-items-center flex-wrap">
                    <div class="mb-2 mb-md-0">
                        <span class="info-label">No. Surat Jalan</span>
                        <span class="info-value">#SJ-20260330-088</span>
                    </div>
                    <span class="status-badge bg-delivered"><i class="fas fa-check-circle me-1"></i> Berhasil Diterima</span>
                </div>
                <div class="card-body p-4 text-center">
                    <p class="mb-0 text-muted">Pengiriman ini telah selesai dan diterima oleh <strong class="text-dark">PT. Maju Jaya</strong> pada 30 Maret 2026.</p>
                </div>
            </div>

        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>