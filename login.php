<?php
session_start();
require 'koneksi.php';

if (isset($_POST['login'])) {
    // Sanitasi input agar lebih aman
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);

    $query = "SELECT * FROM login WHERE email='$email' AND password='$password'";
    $cek = mysqli_query($conn, $query);

    if (mysqli_num_rows($cek) > 0) {
        $data = mysqli_fetch_assoc($cek);
        
        // 1. Bersihkan dan seragamkan role (Hapus spasi gaib & jadikan Huruf Besar)
        $role_bersih = strtoupper(trim($data['role']));
        
        // 2. Simpan ke session pakai role yang sudah dibersihkan
        $_SESSION['log'] = 'True';
        $_SESSION['role'] = $role_bersih;
        $_SESSION['email'] = $data['email'];
        $_SESSION['nama_lengkap'] = $data['nama_lengkap']; 
        
        // 3. PEMISAHAN REDIRECT BERDASARKAN ROLE
        if ($role_bersih == 'DRIVER') {
            // Jika yang login adalah driver, lempar ke dashboard_driver.php
            header('location:dashboard_driver.php');
        } else {
            // Jika selain driver (Manager/Staf/Admin), lempar ke index.php
            header('location:index.php');
        }
        
        exit(); // WAJIB ada exit() setelah header location agar script berhenti

    } else {
        $error = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | DocLogistics System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .login-container {
            position: relative;
            z-index: 1;
        }

        /* Glassmorphism Effect */
        .card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border: none;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.2);
            padding: 20px;
        }

        .brand-logo {
            width: 80px;
            height: 80px;
            background: #1e3c72;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: -60px auto 20px;
            color: white;
            font-size: 30px;
            box-shadow: 0 10px 20px rgba(30, 60, 114, 0.3);
        }

        .btn-primary {
            background: #1e3c72;
            border: none;
            padding: 12px;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-primary:hover {
            background: #162d55;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        .form-control {
            border-radius: 10px;
            padding: 12px 15px;
            border: 1px solid #dee2e6;
        }

        .form-control:focus {
            box-shadow: 0 0 0 3px rgba(30, 60, 114, 0.1);
            border-color: #1e3c72;
        }

        /* Background Decorations */
        .shape {
            position: absolute;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            z-index: 0;
        }
    </style>
</head>
<body>

<div class="shape" style="width: 300px; height: 300px; top: -100px; left: -100px;"></div>
<div class="shape" style="width: 200px; height: 200px; bottom: -50px; right: -50px;"></div>

<div class="container login-container">
    <div class="row justify-content-center">
        <div class="col-md-5 col-lg-4">
            <div class="card shadow">
                <div class="brand-logo">
                    <i class="fas fa-file-invoice"></i>
                </div>
                <h3 class="text-center fw-bold mb-1">PT Elektronik Global (SPJ)</h3>
                <p class="text-center text-muted mb-4 small">Document Logistics Management System</p>

                <?php if(isset($error)): ?>
                    <div class="alert alert-danger py-2 small text-center" role="alert">
                        <i class="fas fa-exclamation-circle me-1"></i> Email atau Password salah!
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Email Address</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="fas fa-envelope text-muted"></i></span>
                            <input type="email" name="email" class="form-control border-start-0" placeholder="name@company.com" required>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label small fw-semibold">Password</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="fas fa-lock text-muted"></i></span>
                            <input type="password" name="password" class="form-control border-start-0" placeholder="••••••••" required>
                        </div>
                    </div>

                    <button type="submit" name="login" class="btn btn-primary w-100 mb-3">
                        Log In <i class="fas fa-arrow-right ms-2"></i>
                    </button>
                    
                    <div class="text-center">
                        <a href="#" class="text-decoration-none small text-muted">Forgot Password?</a>
                    </div>
                </form>
            </div>
            <p class="text-center text-white-50 mt-4 small">&copy; 2026 PT Elektronik Global (SPJ). All Rights Reserved.</p>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>