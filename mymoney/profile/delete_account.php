<?php
/**
 * ===================================================
 * PROSES HAPUS AKUN PERMANEN (profile/delete_account.php)
 * ===================================================
 * Menghapus akun pengguna secara permanen dari database.
 * Semua data terkait (Transaksi, Kategori, Tabungan) akan 
 * terhapus secara otomatis melalui Foreign Key CASCADE.
 */

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';

$userId = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Validasi CSRF Token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['flash_error'] = "Token CSRF tidak valid. Silakan coba lagi.";
        header("Location: index.php");
        exit();
    }

    $password = $_POST['password'] ?? '';

    if (empty($password)) {
        $_SESSION['flash_error'] = "Password konfirmasi wajib diisi untuk menghapus akun.";
        header("Location: index.php");
        exit();
    }

    try {
        // 2. Verifikasi Password Pengguna
        $stmtUser = $pdo->prepare("SELECT password FROM users WHERE id = :id LIMIT 1");
        $stmtUser->execute([':id' => $userId]);
        $user = $stmtUser->fetch();

        if (!$user || !password_verify($password, $user['password'])) {
            $_SESSION['flash_error'] = "Password yang Anda masukkan salah. Akun gagal dihapus.";
            header("Location: index.php");
            exit();
        }

        // 3. Hapus Pengguna dari Database (Foreign Key CASCADE akan menghapus data terkait)
        $stmtDelete = $pdo->prepare("DELETE FROM users WHERE id = :id");
        $stmtDelete->execute([':id' => $userId]);

        // 4. Bersihkan Session & Cookie
        $_SESSION = array();

        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(), 
                '', 
                time() - 42000,
                $params["path"], 
                $params["domain"],
                $params["secure"], 
                $params["httponly"]
            );
        }

        session_destroy();

        // 5. Redirect ke Login dengan status terhapus
        session_start();
        $_SESSION['flash_success'] = "Akun Anda telah berhasil dihapus secara permanen dari sistem.";
        header("Location: ../auth/login.php");
        exit();

    } catch (PDOException $e) {
        error_log("Delete Account Error: " . $e->getMessage());
        $_SESSION['flash_error'] = "Terjadi kesalahan sistem saat menghapus akun.";
        header("Location: index.php");
        exit();
    }
} else {
    header("Location: index.php");
    exit();
}
