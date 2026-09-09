<?php
/**
 * ===================================================
 * SIDEBAR & NAVBAR TEMPLATE (includes/sidebar.php)
 * ===================================================
 * Menampilkan menu navigasi utama desktop & mobile.
 * Otomatis mendeteksi menu aktif berdasarkan URL halaman.
 */

// Hitung path dasar aplikasi
$script_name = $_SERVER['SCRIPT_NAME'];
$app_base = preg_replace('#/(dashboard|transactions|categories|savings|reports|profile|includes|auth)/.*$#i', '', $script_name);
$app_base = rtrim($app_base, '/');

// Deteksi halaman aktif dari URL
$current_uri = $_SERVER['REQUEST_URI'];

function isActive($keyword, $uri) {
    return strpos($uri, $keyword) !== false ? 'active' : '';
}

// Inisialisasi data user dari Session
$userName = $_SESSION['user_name'] ?? 'Pengguna';
$userEmail = $_SESSION['user_email'] ?? 'user@mymoney.com';
$initial = strtoupper(substr($userName, 0, 1));
?>

<!-- Overlay untuk tampilan mobile saat sidebar terbuka -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- Sidebar Navigation -->
<aside class="app-sidebar" id="appSidebar">
    <!-- Sidebar Header / Logo -->
    <div class="sidebar-header">
        <a href="<?= $app_base ?>/dashboard/index.php" class="sidebar-brand">
            <i class="bi bi-wallet2 text-primary fs-3"></i>
            <span>MyMoney</span>
        </a>
    </div>

    <!-- Sidebar Menu List -->
    <div class="sidebar-menu">
        <div class="nav-label">Menu Utama</div>
        
        <a href="<?= $app_base ?>/dashboard/index.php" class="nav-link <?= isActive('/dashboard/', $current_uri) ?>">
            <i class="bi bi-grid-1x2-fill"></i>
            <span>Dashboard</span>
        </a>

        <a href="<?= $app_base ?>/transactions/index.php" class="nav-link <?= isActive('/transactions/', $current_uri) ?>">
            <i class="bi bi-receipt-cutoff"></i>
            <span>Transaksi</span>
        </a>

        <a href="<?= $app_base ?>/categories/index.php" class="nav-link <?= isActive('/categories/', $current_uri) ?>">
            <i class="bi bi-tags-fill"></i>
            <span>Kategori</span>
        </a>

        <a href="<?= $app_base ?>/savings/index.php" class="nav-link <?= isActive('/savings/', $current_uri) ?>">
            <i class="bi bi-piggy-bank-fill"></i>
            <span>Target Tabungan</span>
        </a>

        <a href="<?= $app_base ?>/reports/index.php" class="nav-link <?= isActive('/reports/', $current_uri) ?>">
            <i class="bi bi-bar-chart-line-fill"></i>
            <span>Laporan Keuangan</span>
        </a>

        <div class="nav-label mt-3">Pengaturan</div>

        <a href="<?= $app_base ?>/profile/index.php" class="nav-link <?= isActive('/profile/', $current_uri) ?>">
            <i class="bi bi-person-circle"></i>
            <span>Profil Pengguna</span>
        </a>

        <a href="<?= $app_base ?>/auth/logout.php" class="nav-link text-danger mt-2" onclick="return confirmDelete(event, 'Apakah Anda yakin ingin keluar dari sistem?')">
            <i class="bi bi-box-arrow-right"></i>
            <span>Logout</span>
        </a>
    </div>

    <!-- Sidebar Footer / User Profile Summary -->
    <div class="sidebar-footer">
        <div class="user-info">
            <div class="user-avatar"><?= $initial ?></div>
            <div>
                <div class="user-name"><?= htmlspecialchars($userName) ?></div>
                <div class="user-email"><?= htmlspecialchars($userEmail) ?></div>
            </div>
        </div>
    </div>
</aside>

<!-- Main App Section Container -->
<main class="app-main">
    <!-- Navbar Header Mobile / Desktop Topbar -->
    <header class="app-navbar">
        <div class="max-width-container d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-3">
                <!-- Tombol Toggle Menu Mobile -->
                <button class="btn btn-light d-lg-none p-2 border shadow-sm rounded-3" id="sidebarToggleBtn" type="button" aria-label="Toggle Navigation">
                    <i class="bi bi-list fs-4"></i>
                </button>
                <h5 class="fw-bold mb-0 text-dark header-page-title"><?= isset($page_heading) ? htmlspecialchars($page_heading) : 'Dashboard' ?></h5>
            </div>

            <div class="d-flex align-items-center gap-3">
                <span class="badge bg-primary-subtle text-primary fw-semibold px-3 py-2 rounded-pill d-none d-sm-inline-block">
                    <i class="bi bi-calendar3 me-1"></i> <?= date('d M Y') ?>
                </span>
            </div>
        </div>
    </header>

    <!-- App Content Wrapper Starts Here -->
    <div class="app-content">
        <div class="max-width-container">
