<?php
/**
 * PROSES TAMBAH DANA TABUNGAN (savings/deposit.php)
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

    $goal_id    = (int)($_POST['goal_id'] ?? 0);
    $amount_raw = $_POST['amount'] ?? '';
    $deposit    = (float)preg_replace('/[^\d.]/', '', str_replace('.', '', $amount_raw));

    if ($goal_id <= 0 || $deposit <= 0) {
        $_SESSION['flash_error'] = "Nominal setoran tidak valid.";
        header("Location: index.php");
        exit();
    }

    try {
        // Verification ownership
        $stmtCheck = $pdo->prepare("SELECT id, name, current_amount, target_amount FROM savings_goals WHERE id = :id AND user_id = :user_id LIMIT 1");
        $stmtCheck->execute([':id' => $goal_id, ':user_id' => $userId]);
        $goal = $stmtCheck->fetch();

        if (!$goal) {
            $_SESSION['flash_error'] = "Target tabungan tidak ditemukan.";
            header("Location: index.php");
            exit();
        }

        $newAmount = (float)$goal['current_amount'] + $deposit;

        $stmtUpdate = $pdo->prepare("UPDATE savings_goals SET current_amount = :current_amount WHERE id = :id AND user_id = :user_id");
        $stmtUpdate->execute([
            ':current_amount' => $newAmount,
            ':id'             => $goal_id,
            ':user_id'        => $userId
        ]);

        $_SESSION['flash_success'] = "Berhasil menambah dana Rp " . number_format($deposit, 0, ',', '.') . " ke tabungan '" . htmlspecialchars($goal['name']) . "'";
    } catch (PDOException $e) {
        error_log("Deposit Goal Error: " . $e->getMessage());
        $_SESSION['flash_error'] = "Gagal menambah dana tabungan.";
    }

    header("Location: index.php");
    exit();
} else {
    header("Location: index.php");
    exit();
}
