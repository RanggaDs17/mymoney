<?php
/**
 * PROSES TAMBAH KATEGORI (categories/add.php)
 */

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';

$userId = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Check
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['flash_error'] = "Token keamanan tidak valid.";
        header("Location: index.php");
        exit();
    }

    $name = trim($_POST['name'] ?? '');
    $type = $_POST['type'] ?? 'expense';

    if (empty($name)) {
        $_SESSION['flash_error'] = "Nama kategori tidak boleh kosong.";
        header("Location: index.php");
        exit();
    }

    if (!in_array($type, ['income', 'expense'])) {
        $type = 'expense';
    }

    try {
        $stmt = $pdo->prepare("
            INSERT INTO categories (user_id, name, type, is_default) 
            VALUES (:user_id, :name, :type, 0)
        ");
        $stmt->execute([
            ':user_id' => $userId,
            ':name'    => $name,
            ':type'    => $type
        ]);

        $_SESSION['flash_success'] = "Kategori '$name' berhasil ditambahkan!";
    } catch (PDOException $e) {
        error_log("Add Category Error: " . $e->getMessage());
        $_SESSION['flash_error'] = "Gagal menyimpan kategori baru.";
    }

    header("Location: index.php");
    exit();
} else {
    header("Location: index.php");
    exit();
}
