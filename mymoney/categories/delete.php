<?php
/**
 * PROSES HAPUS KATEGORI (categories/delete.php)
 */

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';

$userId = $_SESSION['user_id'];
$id     = (int)($_GET['id'] ?? 0);
$token  = $_GET['csrf_token'] ?? '';

if ($id <= 0 || empty($token) || $token !== $_SESSION['csrf_token']) {
    $_SESSION['flash_error'] = "Permintaan tidak valid atau token kadaluarsa.";
    header("Location: index.php");
    exit();
}

try {
    // Pastikan kategori ada di database
    $stmtCheck = $pdo->prepare("SELECT id FROM categories WHERE id = :id AND (user_id = :user_id OR is_default = 1) LIMIT 1");
    $stmtCheck->execute([':id' => $id, ':user_id' => $userId]);
    
    if (!$stmtCheck->fetch()) {
        $_SESSION['flash_error'] = "Kategori tidak ditemukan.";
        header("Location: index.php");
        exit();
    }

    $stmt = $pdo->prepare("DELETE FROM categories WHERE id = :id");
    $stmt->execute([':id' => $id]);

    $_SESSION['flash_success'] = "Kategori berhasil dihapus!";
} catch (PDOException $e) {
    error_log("Delete Category Error: " . $e->getMessage());
    $_SESSION['flash_error'] = "Gagal menghapus kategori. Kategori mungkin sedang digunakan pada transaksi.";
}

header("Location: index.php");
exit();
