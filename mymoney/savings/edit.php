<?php
/**
 * PROSES EDIT TARGET TABUNGAN (savings/edit.php)
 */

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';

$userId = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['flash_error'] = "Token CSRF tidak valid.";
        header("Location: index.php");
        exit();
    }

    $id             = (int)($_POST['id'] ?? 0);
    $name           = trim($_POST['name'] ?? '');
    $target_raw     = $_POST['target_amount'] ?? '';
    $current_raw    = $_POST['current_amount'] ?? '';
    $deadline       = $_POST['deadline'] ?? '';

    $target_amount  = (float)preg_replace('/[^\d.]/', '', str_replace('.', '', $target_raw));
    $current_amount = (float)preg_replace('/[^\d.]/', '', str_replace('.', '', $current_raw));

    if ($id <= 0 || empty($name) || $target_amount <= 0 || empty($deadline)) {
        $_SESSION['flash_error'] = "Data edit target tabungan tidak lengkap.";
        header("Location: index.php");
        exit();
    }

    try {
        $stmtUpdate = $pdo->prepare("
            UPDATE savings_goals 
            SET name = :name, target_amount = :target_amount, current_amount = :current_amount, deadline = :deadline 
            WHERE id = :id AND user_id = :user_id
        ");
        $stmtUpdate->execute([
            ':name'           => $name,
            ':target_amount'  => $target_amount,
            ':current_amount' => $current_amount,
            ':deadline'       => $deadline,
            ':id'             => $id,
            ':user_id'        => $userId
        ]);

        $_SESSION['flash_success'] = "Target tabungan berhasil diperbarui!";
    } catch (PDOException $e) {
        error_log("Edit Goal Error: " . $e->getMessage());
        $_SESSION['flash_error'] = "Gagal memperbarui target tabungan.";
    }

    header("Location: index.php");
    exit();
} else {
    header("Location: index.php");
    exit();
}
