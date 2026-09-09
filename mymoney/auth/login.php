<?php
/**
 * ===================================================
 * HALAMAN LOGIN AKUN (auth/login.php)
 * ===================================================
 * Mengautentikasi email & password pengguna.
 * Dilengkapi session_regenerate_id(), proteksi brute-force sederhana,
 * verifikasi password_verify(), dan CSRF token.
 */

session_start();
require_once __DIR__ . '/../config/database.php';

// Jika user sudah login, langsung alihkan ke Dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: ../dashboard/index.php");
    exit();
}

// Inisialisasi CSRF Token jika belum ada
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Inisialisasi penghitung percobaan login (Brute-Force Protection)
if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
    $_SESSION['last_attempt_time'] = time();
}

$errors = [];
$email = '';

// Proses form saat disubmit (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Cek Proteksi Brute-Force (Maksimal 5x gagal dalam 5 menit)
    $time_since_last = time() - $_SESSION['last_attempt_time'];
    if ($_SESSION['login_attempts'] >= 5 && $time_since_last < 300) {
        $remaining_seconds = 300 - $time_since_last;
        $remaining_minutes = ceil($remaining_seconds / 60);
        $errors[] = "Terlalu banyak percobaan login gagal. Harap tunggu $remaining_minutes menit sebelum mencoba kembali.";
    } else {
        // Reset hitungan jika jeda waktu sudah lebih dari 5 menit
        if ($time_since_last >= 300) {
            $_SESSION['login_attempts'] = 0;
        }

        // 2. Validasi CSRF Token
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            $errors[] = "Token keamanan CSRF tidak valid. Silakan coba lagi.";
        } else {
            // 3. Ambil & Sanitasi Input
            $email    = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';

            if (empty($email) || empty($password)) {
                $errors[] = "Email dan password wajib diisi.";
            } else {
                try {
                    // Cari data user berdasarkan email menggunakan Prepared Statement
                    $stmt = $pdo->prepare("SELECT id, name, email, password FROM users WHERE email = :email LIMIT 1");
                    $stmt->execute([':email' => $email]);
                    $user = $stmt->fetch();

                    // Verifikasi ketersediaan user & kebenaran password menggunakan password_verify()
                    if ($user && password_verify($password, $user['password'])) {
                        // Login Berhasil!
                        // Regenerasi Session ID untuk mencegah serangan Session Fixation
                        session_regenerate_id(true);

                        // Simpan data login ke Session
                        $_SESSION['user_id']   = $user['id'];
                        $_SESSION['user_name'] = $user['name'];
                        $_SESSION['user_email'] = $user['email'];

                        // Reset hitungan percobaan login
                        unset($_SESSION['login_attempts']);
                        unset($_SESSION['last_attempt_time']);

                        // Redirect ke Dashboard
                        header("Location: ../dashboard/index.php");
                        exit();
                    } else {
                        // Login Gagal! Tambah hitungan percobaan
                        $_SESSION['login_attempts'] += 1;
                        $_SESSION['last_attempt_time'] = time();
                        
                        $errors[] = "Kombinasi email atau password salah. Harap periksa kembali.";
                    }
                } catch (PDOException $e) {
                    error_log("Login Error: " . $e->getMessage());
                    $errors[] = "Terjadi kesalahan sistem. Silakan coba lagi nanti.";
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - MyMoney</title>
    <!-- Bootstrap 5 CSS CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
        }
        .auth-card {
            background: #ffffff;
            border-radius: 1.25rem;
            box-shadow: 0 20px 40px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 420px;
            overflow: hidden;
        }
        .auth-header {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            color: #ffffff;
            padding: 2.25rem 1.5rem;
            text-align: center;
        }
        .auth-header .logo-icon {
            font-size: 2.75rem;
            margin-bottom: 0.5rem;
        }
        .auth-body {
            padding: 2rem 1.75rem;
        }
        .form-control:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 0.25rem rgba(37, 99, 235, 0.25);
        }
        .btn-primary {
            background: #2563eb;
            border: none;
            padding: 0.75rem;
            font-weight: 600;
            border-radius: 0.5rem;
        }
        .btn-primary:hover {
            background: #1d4ed8;
        }
    </style>
</head>
<body>

<div class="auth-card">
    <div class="auth-header">
        <div class="logo-icon"><i class="bi bi-wallet2"></i></div>
        <h4 class="fw-bold mb-1">Selamat Datang!</h4>
        <p class="small mb-0 text-white-50">Masuk untuk mengelola keuangan MyMoney Anda</p>
    </div>

    <div class="auth-body">
        <?php if (!empty($_SESSION['flash_success'])): ?>
            <div class="alert alert-success alert-dismissible fade show text-start small mb-3" role="alert">
                <i class="bi bi-check-circle-fill me-1"></i> <?= htmlspecialchars($_SESSION['flash_success']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['flash_success']); ?>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger alert-dismissible fade show text-start small mb-3" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-1"></i> <strong>Gagal Login:</strong>
                <ul class="mb-0 ps-3 mt-1">
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <form action="login.php" method="POST" autocomplete="off">
            <!-- CSRF Token Hidden Field -->
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

            <div class="mb-3">
                <label for="email" class="form-label small fw-semibold">Alamat Email</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-envelope text-muted"></i></span>
                    <input type="email" class="form-control border-start-0 ps-0" id="email" name="email" 
                           placeholder="nama@email.com" value="<?= htmlspecialchars($email) ?>" required autofocus>
                </div>
            </div>

            <div class="mb-4">
                <label for="password" class="form-label small fw-semibold">Password</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-lock text-muted"></i></span>
                    <input type="password" class="form-control border-start-0 ps-0" id="password" name="password" 
                           placeholder="••••••••" required>
                </div>
            </div>

            <button type="submit" class="btn btn-primary w-100 mb-3">
                <i class="bi bi-box-arrow-in-right me-1"></i> Masuk Akun
            </button>
        </form>

        <div class="text-center mt-3 pt-3 border-top">
            <p class="small text-muted mb-0">Belum punya akun? 
                <a href="register.php" class="text-primary text-decoration-none fw-bold">Daftar Sekarang</a>
            </p>
        </div>
    </div>
</div>

<!-- Bootstrap 5 JS Bundle CDN -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
