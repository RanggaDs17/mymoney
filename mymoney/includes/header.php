<?php
/**
 * ===================================================
 * HEADER TEMPLATE (includes/header.php)
 * ===================================================
 * Menyediakan tag <head>, CDN Bootstrap 5, Bootstrap Icons,
 * Google Fonts, dan stylesheet custom aplikasi MyMoney.
 */

// Jalankan session jika belum berjalan
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Hitung path dasar aplikasi untuk penyertaan asset secara universal
$script_name = $_SERVER['SCRIPT_NAME'];
$app_base = preg_replace('#/(dashboard|transactions|categories|savings|reports|profile|includes|auth)/.*$#i', '', $script_name);
$app_base = rtrim($app_base, '/');

// Judul halaman dinamis (jika tidak diset, gunakan default)
$page_title = isset($page_title) ? $page_title . ' - MyMoney' : 'MyMoney - Pencatat Keuangan Pribadi';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?></title>

    <!-- Bootstrap 5 CSS CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    
    <!-- Google Fonts (Plus Jakarta Sans) -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Custom Stylesheet -->
    <link href="<?= $app_base ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>

<div class="app-wrapper">
