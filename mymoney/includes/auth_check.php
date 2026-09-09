<?php
/**
 * ===================================================
 * FILE PROTEKSI SESSION (includes/auth_check.php)
 * ===================================================
 * File ini wajib di-include di bagian paling atas setiap 
 * halaman terproteksi (Dashboard, Transaksi, Laporan, dll).
 * Jika user belum login, otomatis di-redirect ke halaman Login.
 */

// Jalankan session jika belum dimulai
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cek apakah session user_id sudah ada (tanda bahwa pengguna sudah berhasil login)
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    // Hitung path aplikasi secara dinamis agar kompatibel di folder XAMPP manapun
    $script_name = $_SERVER['SCRIPT_NAME'];
    $app_base = preg_replace('#/(dashboard|transactions|categories|savings|reports|profile|includes|auth)/.*$#i', '', $script_name);
    $login_url = rtrim($app_base, '/') . '/auth/login.php';

    // Redirect user ke halaman login
    header("Location: " . $login_url);
    exit();
}
