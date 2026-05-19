<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PT Elektronik Global | Document Logistics System</title>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- AOS Animation -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">

    <style>
        :root {
            --primary-dark: #0f172a;
            --primary-blue: #1e3a8a;
            --accent-blue: #3b82f6;
            --accent-gold: #fbbf24;
            --text-main: #334155;
            --text-light: #94a3b8;
            --bg-light: #f8fafc;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: var(--text-main);
            background-color: var(--bg-light);
            overflow-x: hidden;
        }

        /* --- NAVBAR --- */
        .navbar {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.05);
            padding: 15px 0;
            transition: all 0.3s ease;
        }
        .navbar-brand { font-weight: 800; color: var(--primary-dark); font-size: 1.5rem; letter-spacing: -0.5px; }
        .nav-link { font-weight: 600; color: var(--text-main) !important; margin: 0 10px; transition: color 0.3s; }
        .nav-link:hover { color: var(--accent-blue) !important; }
        .btn-login { background: var(--primary-dark); color: white; padding: 10px 25px; border-radius: 50px; font-weight: 600; transition: all 0.3s; border: 2px solid var(--primary-dark); }
        .btn-login:hover { background: transparent; color: var(--primary-dark); }

        /* --- HERO SECTION --- */
        .hero {
            min-height: 100vh;
            display: flex;
            align-items: center;
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            position: relative;
            padding-top: 80px;
        }
        .hero-shapes { position: absolute; top: 0; left: 0; width: 100%; height: 100%; overflow: hidden; z-index: 0; pointer-events: none; }
        .shape-1 { position: absolute; top: -10%; right: -5%; width: 500px; height: 500px; background: radial-gradient(circle, rgba(59,130,246,0.1) 0%, transparent 70%); border-radius: 50%; }
        .shape-2 { position: absolute; bottom: 10%; left: -10%; width: 600px; height: 600px; background: radial-gradient(circle, rgba(251,191,36,0.08) 0%, transparent 70%); border-radius: 50%; }
        
        .hero-content { position: relative; z-index: 1; }
        .hero-title { font-size: 4rem; font-weight: 800; line-height: 1.1; letter-spacing: -1.5px; color: var(--primary-dark); margin-bottom: 20px; }
        .hero-title span { background: linear-gradient(90deg, var(--accent-blue), var(--primary-blue)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .hero-subtitle { font-size: 1.25rem; color: var(--text-light); font-weight: 400; line-height: 1.6; margin-bottom: 40px; }
        
        .hero-image-wrapper { position: relative; z-index: 1; text-align: right; }
        .glass-card { background: rgba(255, 255, 255, 0.7); backdrop-filter: blur(20px); border: 1px solid rgba(255,255,255,0.5); border-radius: 30px; padding: 40px; box-shadow: 0 25px 50px rgba(0,0,0,0.05); }

        /* --- PROFIL PERUSAHAAN --- */
        .section-padding { padding: 100px 0; }
        .section-title { font-size: 2.5rem; font-weight: 800; color: var(--primary-dark); margin-bottom: 20px; letter-spacing: -1px; }
        .section-subtitle { font-size: 1.1rem; color: var(--accent-blue); font-weight: 700; text-transform: uppercase; letter-spacing: 2px; margin-bottom: 10px; }
        
        .feature-box { background: white; padding: 40px; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.03); transition: transform 0.3s; height: 100%; border: 1px solid rgba(0,0,0,0.02); }
        .feature-box:hover { transform: translateY(-10px); box-shadow: 0 20px 40px rgba(0,0,0,0.08); }
        .feature-icon { width: 70px; height: 70px; border-radius: 20px; display: flex; align-items: center; justify-content: center; font-size: 2rem; margin-bottom: 25px; }

        /* --- ALUR APLIKASI (HOW IT WORKS) --- */
        .bg-dark-section { background: var(--primary-dark); color: white; position: relative; overflow: hidden; }
        .timeline-step { text-align: center; position: relative; padding: 20px; }
        .step-number { width: 60px; height: 60px; background: rgba(255,255,255,0.1); border: 2px solid var(--accent-blue); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; font-weight: 800; color: var(--accent-blue); margin: 0 auto 20px; position: relative; z-index: 2; }
        .timeline-line { position: absolute; top: 50px; left: 50%; width: 100%; height: 2px; background: dashed 2px rgba(255,255,255,0.2); z-index: 1; }
        .timeline-step:last-child .timeline-line { display: none; }

        /* --- DEWAN EKSEKUTIF --- */
        .team-card { background: white; border-radius: 20px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.03); border: 1px solid rgba(0,0,0,0.02); transition: all 0.3s; }
        .team-card:hover { transform: translateY(-10px); }
        .team-img-wrapper { height: 300px; background: #e2e8f0; position: relative; display: flex; align-items: center; justify-content: center; overflow: hidden; }
        .team-img-wrapper i { font-size: 6rem; color: #cbd5e1; }
        .team-info { padding: 30px; text-align: center; }
        .team-name { font-size: 1.25rem; font-weight: 800; color: var(--primary-dark); margin-bottom: 5px; }
        .team-role { font-size: 0.9rem; font-weight: 600; color: var(--accent-blue); text-transform: uppercase; letter-spacing: 1px; }

        /* --- FOOTER / KONTAK --- */
        .footer { background: #0b1120; color: rgba(255,255,255,0.7); padding: 80px 0 30px; }
        .footer-title { color: white; font-weight: 700; font-size: 1.2rem; margin-bottom: 25px; }
        .contact-item { display: flex; margin-bottom: 15px; align-items: flex-start; }
        .contact-icon { color: var(--accent-blue); font-size: 1.2rem; width: 30px; margin-top: 3px; }
        .social-links a { display: inline-flex; align-items: center; justify-content: center; width: 40px; height: 40px; background: rgba(255,255,255,0.05); color: white; border-radius: 50%; margin-right: 10px; transition: all 0.3s; text-decoration: none; }
        .social-links a:hover { background: var(--accent-blue); transform: translateY(-3px); }

        /* Responsive Fixes */
        @media (max-width: 991px) {
            .hero-title { font-size: 3rem; }
            .timeline-line { display: none; }
            .timeline-step { margin-bottom: 40px; }
        }
    </style>
</head>
<body>

    <!-- NAVBAR -->
    <nav class="navbar navbar-expand-lg fixed-top">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="#">
                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2" style="width:40px; height:40px; font-size:20px;">
                    <i class="fas fa-truck-fast"></i>
                </div>
                Elektronik Global
            </a>
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav mx-auto">
                    <li class="nav-item"><a class="nav-link" href="#tentang">Tentang Kami</a></li>
                    <li class="nav-item"><a class="nav-link" href="#alur">Alur Sistem</a></li>
                    <li class="nav-item"><a class="nav-link" href="#eksekutif">Eksekutif</a></li>
                    <li class="nav-item"><a class="nav-link" href="#kontak">Kontak</a></li>
                </ul>
                <div class="d-flex">
                    <a href="login.php" class="btn btn-login"><i class="fas fa-sign-in-alt me-2"></i> Portal Internal</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- HERO SECTION -->
    <section class="hero">
        <div class="hero-shapes">
            <div class="shape-1"></div>
            <div class="shape-2"></div>
        </div>
        <div class="container hero-content">
            <div class="row align-items-center">
                <div class="col-lg-6" data-aos="fade-right" data-aos-duration="1000">
                    <div class="badge bg-white text-primary px-3 py-2 rounded-pill shadow-sm mb-4 fw-bold border border-primary border-opacity-10">
                        <i class="fas fa-bolt text-warning me-2"></i> Transformasi Digital Logistik
                    </div>
                    <h1 class="hero-title">Masa Depan <span>Distribusi Elektronik</span> Ada di Tangan Anda.</h1>
                    <p class="hero-subtitle">Sistem manajemen armada & Surat Jalan otomatis yang mengintegrasikan pengiriman tepat waktu dengan keamanan operasional berstandar enterprise.</p>
                    <div class="d-flex gap-3">
                        <a href="login.php" class="btn btn-primary btn-lg rounded-pill px-5 fw-bold shadow"><i class="fas fa-rocket me-2"></i> Mulai Bekerja</a>
                        <a href="#alur" class="btn btn-light btn-lg rounded-pill px-4 fw-bold shadow-sm border">Pelajari Sistem</a>
                    </div>
                </div>
                <div class="col-lg-6 mt-5 mt-lg-0" data-aos="fade-left" data-aos-duration="1000" data-aos-delay="200">
                    <div class="hero-image-wrapper">
                        <div class="glass-card text-start position-relative">
                            <!-- Floating badges simulation -->
                            <div class="position-absolute bg-white shadow-lg p-3 rounded-4" style="top: -20px; left: -20px; z-index: 10;">
                                <div class="d-flex align-items-center">
                                    <div class="bg-success bg-opacity-10 text-success rounded-circle p-2 me-3"><i class="fas fa-check-circle fs-4"></i></div>
                                    <div><small class="text-muted fw-bold d-block">Status</small><span class="fw-bold text-dark">Delivered</span></div>
                                </div>
                            </div>
                            
                            <img src="https://images.unsplash.com/photo-1580674285054-bed31e145f59?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80" alt="Logistics Dashboard" class="img-fluid rounded-4 shadow-sm" style="opacity: 0.9;">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- PROFIL PERUSAHAAN -->
    <section id="tentang" class="section-padding bg-white">
        <div class="container">
            <div class="text-center mb-5 pb-3" data-aos="fade-up">
                <div class="section-subtitle">Company Profile</div>
                <h2 class="section-title">Solusi Rantai Pasok Terpercaya</h2>
                <p class="text-muted mx-auto" style="max-width: 700px; font-size: 1.1rem;">PT Elektronik Global adalah perusahaan distributor terkemuka yang memastikan setiap produk elektronik sampai ke tangan klien dengan aman, akurat, dan dapat dilacak secara *real-time*.</p>
            </div>

            <div class="row g-4">
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="100">
                    <div class="feature-box text-center">
                        <div class="feature-icon bg-primary bg-opacity-10 text-primary mx-auto"><i class="fas fa-box-open"></i></div>
                        <h4 class="fw-bold mb-3">Integrasi Stok Gudang</h4>
                        <p class="text-muted mb-0">Manajemen Surat Jalan kami terintegrasi langsung dengan master stok, mencegah *over-dispatch* secara otomatis.</p>
                    </div>
                </div>
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="200">
                    <div class="feature-box text-center">
                        <div class="feature-icon bg-warning bg-opacity-10 text-warning mx-auto"><i class="fas fa-route"></i></div>
                        <h4 class="fw-bold mb-3">Sistem Rute Cerdas</h4>
                        <p class="text-muted mb-0">Penerapan *Fixed Route System* memastikan supir hanya melewati *checkpoint* yang telah divalidasi oleh perusahaan.</p>
                    </div>
                </div>
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="300">
                    <div class="feature-box text-center">
                        <div class="feature-icon bg-success bg-opacity-10 text-success mx-auto"><i class="fas fa-money-check-alt"></i></div>
                        <h4 class="fw-bold mb-3">Otomatisasi Finansial</h4>
                        <p class="text-muted mb-0">Setiap pengiriman yang selesai langsung dikonversi menjadi Invoice dengan tarif per-kilometer yang akurat.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ALUR APLIKASI -->
    <section id="alur" class="section-padding bg-dark-section">
        <div class="container">
            <div class="text-center mb-5 pb-4" data-aos="fade-up">
                <div class="section-subtitle text-warning">How It Works</div>
                <h2 class="section-title text-white">Alur Operasional Terstruktur</h2>
            </div>

            <div class="row position-relative">
                <div class="col-lg-3 col-md-6" data-aos="fade-right" data-aos-delay="100">
                    <div class="timeline-step">
                        <div class="step-number">1</div>
                        <div class="timeline-line"></div>
                        <h5 class="fw-bold text-white mb-3">Terbitkan SJ</h5>
                        <p class="text-white-50 small">Admin menerbitkan Surat Jalan dan mengalokasikan armada serta driver yang berstatus *Available*.</p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6" data-aos="fade-right" data-aos-delay="200">
                    <div class="timeline-step">
                        <div class="step-number">2</div>
                        <div class="timeline-line"></div>
                        <h5 class="fw-bold text-white mb-3">Live Tracking</h5>
                        <p class="text-white-50 small">Sistem melacak progres supir secara visual melalui integrasi Maps saat melewati *checkpoint*.</p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6" data-aos="fade-right" data-aos-delay="300">
                    <div class="timeline-step">
                        <div class="step-number">3</div>
                        <div class="timeline-line"></div>
                        <h5 class="fw-bold text-white mb-3">Barang Diterima</h5>
                        <p class="text-white-50 small">Driver menyelesaikan tugas. Status armada otomatis berubah menjadi *Returning* (Rute Pulang).</p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6" data-aos="fade-right" data-aos-delay="400">
                    <div class="timeline-step">
                        <div class="step-number">4</div>
                        <h5 class="fw-bold text-white mb-3">Invoice & Selesai</h5>
                        <p class="text-white-50 small">Sistem merilis Invoice tagihan untuk klien dan armada siap kembali ke markas untuk tugas berikutnya.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- DEWAN EKSEKUTIF -->
    <section id="eksekutif" class="section-padding bg-light">
        <div class="container">
            <div class="text-center mb-5 pb-3" data-aos="fade-up">
                <div class="section-subtitle">Leadership</div>
                <h2 class="section-title">Dewan Eksekutif</h2>
                <p class="text-muted mx-auto" style="max-width: 600px;">Para pemimpin di balik kesuksesan distribusi PT Elektronik Global.</p>
            </div>

           <div class="row justify-content-center g-4">
    <!-- Direktur Utama -->
    <div class="col-lg-4 col-md-6" data-aos="zoom-in" data-aos-delay="100">
        <div class="team-card">
            <div class="team-img-wrapper">
                <!-- FOTO CEO -->
                <img src="assets/img/step.jpeg" alt="Stephani Aprilin TN" class="img-fluid">
            </div>
            <div class="team-info">
                <h4 class="team-name">Stephani Aprilin TN</h4>
                <div class="team-role">Chief Executive Officer</div>
            </div>
        </div>
    </div>
    
    <!-- Direktur Operasional -->
    <div class="col-lg-4 col-md-6" data-aos="zoom-in" data-aos-delay="200">
        <div class="team-card">
            <div class="team-img-wrapper">
                <!-- FOTO COO -->
                <img src="assets/img/jaki.jpeg" alt="Muhammad Dzaki Kurniawan" class="img-fluid">
            </div>
            <div class="team-info">
                <h4 class="team-name">Muhammad Dzaki Kurniawan</h4>
                <div class="team-role">Chief Operations Officer</div>
            </div>
        </div>
    </div>
    
    <!-- Direktur Keuangan -->
    <div class="col-lg-4 col-md-6" data-aos="zoom-in" data-aos-delay="300">
        <div class="team-card">
            <div class="team-img-wrapper">
                <!-- FOTO CFO -->
                <img src="assets/img/nurul.jpeg" alt="Purnama Nurul Fadhilah" class="img-fluid">
            </div>
            <div class="team-info">
                <h4 class="team-name">Purnama Nurul Fadhilah</h4>
                <div class="team-role">Chief Financial Officer</div>
            </div>
        </div>
    </div>
</div>
            </div>
        </div>
    </section>

    <!-- FOOTER / KONTAK -->
    <footer id="kontak" class="footer">
        <div class="container">
            <div class="row justify-content-between g-5">
                <div class="col-lg-4">
                    <div class="d-flex align-items-center mb-4">
                        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width:45px; height:45px; font-size:20px;">
                            <i class="fas fa-truck-fast"></i>
                        </div>
                        <h4 class="text-white fw-bold mb-0">Elektronik Global</h4>
                    </div>
                    <p class="text-white-50 mb-4">Pionir sistem manajemen logistik dan dokumentasi elektronik di Indonesia, melayani pengiriman dengan presisi tinggi.</p>
                    <div class="social-links">
                        <a href="#"><i class="fab fa-linkedin-in"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                    </div>
                </div>
                
                <div class="col-lg-3">
                    <h5 class="footer-title">Tautan Cepat</h5>
                    <ul class="list-unstyled text-white-50">
                        <li class="mb-2"><a href="#tentang" class="text-white-50 text-decoration-none">Profil Perusahaan</a></li>
                        <li class="mb-2"><a href="#alur" class="text-white-50 text-decoration-none">Sistem Logistik</a></li>
                        <li class="mb-2"><a href="#eksekutif" class="text-white-50 text-decoration-none">Dewan Direksi</a></li>
                        <li class="mb-2"><a href="login.php" class="text-warning text-decoration-none fw-bold">Portal Login</a></li>
                    </ul>
                </div>
                
                <div class="col-lg-4">
                    <h5 class="footer-title">Hubungi Kami</h5>
                    <div class="contact-item">
                        <i class="fas fa-map-marker-alt contact-icon"></i>
                        <div>
                            <strong class="text-white d-block mb-1">Kantor Pusat</strong>
                            <span class="text-white-50 small">Gedung Logistics Tower Lt. 15<br>Jl. Jend. Sudirman Kav. 52-53, Jakarta 12190</span>
                        </div>
                    </div>
                    <div class="contact-item mt-3">
                        <i class="fas fa-phone-alt contact-icon"></i>
                        <div>
                            <strong class="text-white d-block mb-1">Telepon</strong>
                            <span class="text-white-50 small">(021) 1234-5678 / 1234-5679</span>
                        </div>
                    </div>
                    <div class="contact-item mt-3">
                        <i class="fas fa-envelope contact-icon"></i>
                        <div>
                            <strong class="text-white d-block mb-1">Email Resmi</strong>
                            <span class="text-white-50 small">info@elektronikglobal.co.id</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <hr class="border-white opacity-10 my-4">
            
            <div class="text-center text-white-50 small">
                &copy; <?= date('Y'); ?> PT Elektronik Global. Hak Cipta Dilindungi Undang-Undang. <br>
                Powered by DocLogistics System.
            </div>
        </div>
    </footer>

    <!-- Bootstrap & Plugins JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        // Initialize Animations
        AOS.init({
            once: true,
            offset: 50
        });

        // Navbar Background on Scroll
        window.addEventListener('scroll', function() {
            if (window.scrollY > 50) {
                document.querySelector('.navbar').style.background = 'rgba(255, 255, 255, 0.95)';
                document.querySelector('.navbar').style.boxShadow = '0 10px 30px rgba(0,0,0,0.1)';
            } else {
                document.querySelector('.navbar').style.background = 'rgba(255, 255, 255, 0.9)';
                document.querySelector('.navbar').style.boxShadow = '0 4px 30px rgba(0, 0, 0, 0.05)';
            }
        });
    </script>
</body>
</html>
