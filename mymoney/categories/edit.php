<?php
/**
 * PROSES EDIT KATEGORI (categories/edit.php)
 */

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';

$userId = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['flash_error'] = "Token keamanan tidak valid.";
        header("Location: index.php");
        exit();
    }

    $id   = (int)($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $type = $_POST['type'] ?? 'expense';

    if ($id <= 0 || empty($name)) {
        $_SESSION['flash_error'] = "Data kategori tidak valid.";
        header("Location: index.php");
        exit();
    }

    if (!in_array($type, ['income', 'expense'])) {
        $type = 'expense';
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

        $stmt = $pdo->prepare("UPDATE categories SET name = :name, type = :type WHERE id = :id");
        $stmt->execute([
            ':name' => $name,
            ':type' => $type,
            ':id'   => $id
        ]);

        $_SESSION['flash_success'] = "Kategori berhasil diperbarui!";
    } catch (PDOException $e) {
        error_log("Edit Category Error: " . $e->getMessage());
        $_SESSION['flash_error'] = "Gagal memperbarui kategori.";
    }

    header("Location: index.php");
    exit();
} else {
    header("Location: index.php");
    exit();
}
