<?php
/**
 * PROSES TAMBAH TARGET TABUNGAN (savings/add.php)
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

    $name           = trim($_POST['name'] ?? '');
    $target_raw     = $_POST['target_amount'] ?? '';
    $current_raw    = $_POST['current_amount'] ?? '0';
    $deadline       = $_POST['deadline'] ?? '';

    $target_amount  = (float)preg_replace('/[^\d.]/', '', str_replace('.', '', $target_raw));
    $current_amount = (float)preg_replace('/[^\d.]/', '', str_replace('.', '', $current_raw));

    if (empty($name)) {
        $_SESSION['flash_error'] = "Nama target wajib diisi.";
        header("Location: index.php");
        exit();
    }

    if ($target_amount <= 0) {
        $_SESSION['flash_error'] = "Target nominal harus lebih dari Rp 0.";
        header("Location: index.php");
        exit();
    }

    if (empty($deadline)) {
        $_SESSION['flash_error'] = "Tenggat waktu wajib diisi.";
        header("Location: index.php");
        exit();
    }

    try {
        $stmt = $pdo->prepare("
            INSERT INTO savings_goals (user_id, name, target_amount, current_amount, deadline) 
            VALUES (:user_id, :name, :target_amount, :current_amount, :deadline)
        ");
        $stmt->execute([
            ':user_id'        => $userId,
            ':name'           => $name,
            ':target_amount'  => $target_amount,
            ':current_amount' => $current_amount,
            ':deadline'       => $deadline
        ]);

        $_SESSION['flash_success'] = "Target tabungan '$name' berhasil dibuat!";
    } catch (PDOException $e) {
        error_log("Add Goal Error: " . $e->getMessage());
        $_SESSION['flash_error'] = "Gagal membuat target tabungan.";
    }

    header("Location: index.php");
    exit();
} else {
    header("Location: index.php");
    exit();
}
