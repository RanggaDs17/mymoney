<?php
/**
 * HAPUS TARGET TABUNGAN (savings/delete.php)
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
    $stmt = $pdo->prepare("DELETE FROM savings_goals WHERE id = :id AND user_id = :user_id");
    $stmt->execute([':id' => $id, ':user_id' => $userId]);

    if ($stmt->rowCount() > 0) {
        $_SESSION['flash_success'] = "Target tabungan berhasil dihapus!";
    } else {
        $_SESSION['flash_error'] = "Target tabungan tidak ditemukan.";
    }
} catch (PDOException $e) {
    error_log("Delete Goal Error: " . $e->getMessage());
    $_SESSION['flash_error'] = "Gagal menghapus target tabungan.";
}

header("Location: index.php");
exit();
