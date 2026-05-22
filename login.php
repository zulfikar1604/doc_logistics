<?php
session_start();
require 'koneksi.php';

if (isset($_POST['login'])) {
    // Sanitasi input agar lebih aman
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password_input = $_POST['password'];

    // Ambil user berdasarkan email saja (password dicek terpisah)
    $query = "SELECT * FROM login WHERE email='$email'";
    $cek = mysqli_query($conn, $query);

    if (mysqli_num_rows($cek) > 0) {
        $data = mysqli_fetch_assoc($cek);
        
        // Cek password: support hash (password_verify) DAN plain text (backward compat)
        $password_valid = false;
        if (password_verify($password_input, $data['password'])) {
            // Password sudah di-hash → cocok
            $password_valid = true;
        } elseif ($password_input === $data['password']) {
            // Password masih plain text → cocok, lalu auto-upgrade ke hash
            $hashed = password_hash($password_input, PASSWORD_DEFAULT);
            mysqli_query($conn, "UPDATE login SET password = '$hashed' WHERE id = '{$data['id']}'");
            $password_valid = true;
        }
        
        if ($password_valid) {
            // 1. Bersihkan dan seragamkan role
            $role_bersih = strtoupper(trim($data['role']));
            
            // 2. Simpan ke session
            $_SESSION['log'] = 'True';
            $_SESSION['id_login'] = $data['id'];
            $_SESSION['role'] = $role_bersih;
            $_SESSION['email'] = $data['email'];
            $_SESSION['nama_lengkap'] = $data['nama_lengkap']; 
            
            // 3. Redirect berdasarkan role
            if ($role_bersih == 'DRIVER') {
                header('location:dashboard_driver.php');
            } else {
                header('location:dashboard_utama.php');
            }
            exit();
        } else {
            $error = true;
        }
    } else {
        $error = true;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal Login | PT Elektronik Global</title>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e3a8a 100%);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            position: relative;
        }

        .login-container {
            position: relative;
            z-index: 10;
        }

        /* Glassmorphism Effect */
        .card-glass {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,0.5);
            border-radius: 24px;
            box-shadow: 0 25px 50px rgba(0,0,0,0.15);
            padding: 30px;
        }

        .brand-logo {
            width: 85px;
            height: 85px;
            background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: -70px auto 20px;
            color: white;
            font-size: 35px;
            box-shadow: 0 15px 30px rgba(30, 58, 138, 0.4);
            transform: rotate(-5deg);
        }

        .btn-primary {
            background: #1e3a8a;
            border: none;
            padding: 14px;
            border-radius: 12px;
            font-weight: 700;
            letter-spacing: 0.5px;
            transition: all 0.3s;
        }

        .btn-primary:hover {
            background: #0f172a;
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(15, 23, 42, 0.3);
        }

        .form-control {
            border-radius: 12px;
            padding: 14px 15px;
            border: 1px solid #e2e8f0;
            background: #f8fafc;
            font-weight: 500;
        }

        .form-control:focus {
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.15);
            border-color: #3b82f6;
            background: white;
        }
        
        .input-group-text {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
        }

        .btn-back {
            position: absolute;
            top: 30px;
            left: 30px;
            color: rgba(255,255,255,0.7);
            text-decoration: none;
            font-weight: 600;
            transition: 0.3s;
            z-index: 20;
            display: flex;
            align-items: center;
        }
        .btn-back:hover { color: white; transform: translateX(-5px); }

        /* Background Decorations */
        .shape {
            position: absolute;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 70%);
            border-radius: 50%;
            z-index: 0;
            pointer-events: none;
        }
    </style>
</head>
<body>

<a href="index.php" class="btn-back"><i class="fas fa-arrow-left me-2"></i> Kembali ke Beranda</a>

<div class="shape" style="width: 800px; height: 800px; top: -300px; left: -200px;"></div>
<div class="shape" style="width: 600px; height: 600px; bottom: -200px; right: -100px; background: radial-gradient(circle, rgba(59,130,246,0.15) 0%, transparent 70%);"></div>

<div class="container login-container">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5 col-xl-4">
            <div class="card-glass">
                <div class="brand-logo">
                    <i class="fas fa-truck-fast" style="transform: rotate(5deg);"></i>
                </div>
                <h3 class="text-center fw-bold mb-1" style="color: #0f172a;">Portal Internal</h3>
                <p class="text-center text-muted mb-4 small fw-medium">PT Elektronik Global Logistics System</p>

                <?php if(isset($error)): ?>
                    <div class="alert alert-danger py-2 small fw-bold text-center border-0" style="border-radius: 10px; background: #ffe4e6; color: #e11d48;" role="alert">
                        <i class="fas fa-exclamation-circle me-1"></i> Kredensial tidak valid!
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary">Alamat Email</label>
                        <div class="input-group">
                            <span class="input-group-text border-end-0"><i class="fas fa-envelope text-primary opacity-50"></i></span>
                            <input type="email" name="email" class="form-control border-start-0 ps-0" placeholder="nama@elektronikglobal.com" required>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label small fw-bold text-secondary">Kata Sandi</label>
                        <div class="input-group">
                            <span class="input-group-text border-end-0"><i class="fas fa-lock text-primary opacity-50"></i></span>
                            <input type="password" name="password" class="form-control border-start-0 ps-0" placeholder="••••••••" required>
                        </div>
                    </div>

                    <button type="submit" name="login" class="btn btn-primary w-100 mb-4 shadow-sm">
                        Masuk Sistem <i class="fas fa-sign-in-alt ms-2"></i>
                    </button>
                </form>
            </div>
            
            <div class="text-center mt-4">
                <p class="text-white-50 small mb-0">&copy; <?= date('Y') ?> PT Elektronik Global.</p>
                <p class="text-white-50 small opacity-50" style="font-size: 0.7rem;">Sistem hanya dapat diakses oleh personil yang berwenang.</p>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>