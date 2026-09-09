<?php
/**
 * ===================================================
 * HALAMAN REGISTRASI AKUN (auth/register.php)
 * ===================================================
 * Memungkinkan pengguna baru mendaftar akun MyMoney.
 * Dilengkapi validasi server-side strict, enkripsi password
 * dengan password_hash(), dan proteksi CSRF.
 */

session_start();
require_once __DIR__ . '/../config/database.php';

// Jika user sudah login, redirect langsung ke Dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: ../dashboard/index.php");
    exit();
}

// Buat CSRF token jika belum ada di session
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$errors = [];
$success = '';
$name = '';
$email = '';

// Hitung path dasar aplikasi untuk link CSS/Asset secara fleksibel
$script_name = $_SERVER['SCRIPT_NAME'];
$app_base = preg_replace('#/auth/.*$#i', '', $script_name);
$app_base = rtrim($app_base, '/');

// Proses form saat disubmit (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Validasi CSRF Token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $errors[] = "Token keamanan CSRF tidak valid. Silakan coba lagi.";
    } else {
        // 2. Ambil & Sanitasi Input
        $name             = trim($_POST['name'] ?? '');
        $email            = trim($_POST['email'] ?? '');
        $password         = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        // 3. Validasi Server-Side
        if (empty($name)) {
            $errors[] = "Nama lengkap wajib diisi.";
        }

        if (empty($email)) {
            $errors[] = "Alamat email wajib diisi.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Format alamat email tidak valid.";
        } else {
            // Cek apakah email sudah terdaftar di database
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
            $stmt->execute([':email' => $email]);
            if ($stmt->fetch()) {
                $errors[] = "Alamat email ini sudah terdaftar. Silakan gunakan email lain atau login.";
            }
        }

        if (empty($password)) {
            $errors[] = "Password wajib diisi.";
        } elseif (strlen($password) < 8) {
            $errors[] = "Password minimal harus 8 karakter.";
        }

        if ($password !== $confirm_password) {
            $errors[] = "Konfirmasi password tidak cocok dengan password baru.";
        }

        // 4. Jika tidak ada error, simpan ke database
        if (empty($errors)) {
            try {
                // Enkripsi password menggunakan Bcrypt (Metode bawaan PHP yang sangat aman)
                $hashed_password = password_hash($password, PASSWORD_BCRYPT);

                // Insert user baru dengan Prepared Statement
                $stmtInsert = $pdo->prepare("
                    INSERT INTO users (name, email, password) 
                    VALUES (:name, :email, :password)
                ");

                $stmtInsert->execute([
                    ':name'     => $name,
                    ':email'    => $email,
                    ':password' => $hashed_password
                ]);

                // Set pesan sukses dan kosongkan nilai form
                $success = "Pendaftaran akun berhasil! Silakan login dengan akun Anda.";
                $name = '';
                $email = '';
            } catch (PDOException $e) {
                error_log("Register Error: " . $e->getMessage());
                $errors[] = "Terjadi kesalahan sistem saat mendaftar. Silakan coba lagi nanti.";
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
    <title>Daftar Akun Baru - MyMoney</title>
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
            max-width: 450px;
            overflow: hidden;
        }
        .auth-header {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            color: #ffffff;
            padding: 2rem 1.5rem;
            text-align: center;
        }
        .auth-header .logo-icon {
            font-size: 2.5rem;
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
        <h4 class="fw-bold mb-1">Daftar Akun MyMoney</h4>
        <p class="small mb-0 text-white-50">Kelola keuangan pribadi Anda dengan lebih cerdas</p>
    </div>

    <div class="auth-body">
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger alert-dismissible fade show text-start small mb-3" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-1"></i> <strong>Gagal Mendaftar:</strong>
                <ul class="mb-0 ps-3 mt-1">
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success alert-dismissible fade show text-start small mb-3" role="alert">
                <i class="bi bi-check-circle-fill me-1"></i> <?= htmlspecialchars($success) ?>
                <div class="mt-2">
                    <a href="login.php" class="btn btn-sm btn-success fw-bold">Ke Halaman Login <i class="bi bi-arrow-right"></i></a>
                </div>
            </div>
        <?php endif; ?>

        <form action="register.php" method="POST" autocomplete="off">
            <!-- CSRF Token Hidden Field -->
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

            <div class="mb-3">
                <label for="name" class="form-label small fw-semibold">Nama Lengkap</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-person text-muted"></i></span>
                    <input type="text" class="form-control border-start-0 ps-0" id="name" name="name" 
                           placeholder="Contoh: Budi Santoso" value="<?= htmlspecialchars($name) ?>" required>
                </div>
            </div>

            <div class="mb-3">
                <label for="email" class="form-label small fw-semibold">Alamat Email</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-envelope text-muted"></i></span>
                    <input type="email" class="form-control border-start-0 ps-0" id="email" name="email" 
                           placeholder="nama@email.com" value="<?= htmlspecialchars($email) ?>" required>
                </div>
            </div>

            <div class="mb-3">
                <label for="password" class="form-label small fw-semibold">Password (Minimal 8 Karakter)</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-lock text-muted"></i></span>
                    <input type="password" class="form-control border-start-0 ps-0" id="password" name="password" 
                           placeholder="••••••••" required>
                </div>
            </div>

            <div class="mb-4">
                <label for="confirm_password" class="form-label small fw-semibold">Konfirmasi Password</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-shield-lock text-muted"></i></span>
                    <input type="password" class="form-control border-start-0 ps-0" id="confirm_password" name="confirm_password" 
                           placeholder="••••••••" required>
                </div>
            </div>

            <button type="submit" class="btn btn-primary w-100 mb-3">
                <i class="bi bi-person-plus-fill me-1"></i> Buat Akun Sekarang
            </button>
        </form>

        <div class="text-center mt-3 pt-3 border-top">
            <p class="small text-muted mb-0">Sudah punya akun? 
                <a href="login.php" class="text-primary text-decoration-none fw-bold">Login di sini</a>
            </p>
        </div>
    </div>
</div>

<!-- Bootstrap 5 JS Bundle CDN -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
