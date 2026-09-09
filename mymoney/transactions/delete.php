<?php
/**
 * HAPUS TRANSAKSI (transactions/delete.php)
 */

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';

$userId = $_SESSION['user_id'];
$id     = (int)($_GET['id'] ?? 0);
$token  = $_GET['csrf_token'] ?? '';

if ($id <= 0 || empty($token) || $token !== $_SESSION['csrf_token']) {
    $_SESSION['flash_error'] = "Permintaan hapus tidak valid atau CSRF token kadaluarsa.";
    header("Location: index.php");
    exit();
}

try {
    $stmt = $pdo->prepare("DELETE FROM transactions WHERE id = :id AND user_id = :user_id");
    $stmt->execute([':id' => $id, ':user_id' => $userId]);

    if ($stmt->rowCount() > 0) {
        $_SESSION['flash_success'] = "Transaksi berhasil dihapus!";
    } else {
        $_SESSION['flash_error'] = "Transaksi tidak ditemukan atau Anda tidak memiliki akses.";
    }
} catch (PDOException $e) {
    error_log("Delete Transaction Error: " . $e->getMessage());
    $_SESSION['flash_error'] = "Gagal menghapus transaksi.";
}

header("Location: index.php");
exit();
