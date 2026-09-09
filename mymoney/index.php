<?php
/**
 * ===================================================
 * FILE INDEX UTAMA (index.php)
 * ===================================================
 * Berfungsi sebagai pendorong (router/redirector) utama.
 * Mengarahkan user yang sudah login ke Dashboard,
 * dan user yang belum login ke Halaman Login.
 */

session_start();

if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
    // User sudah login, alihkan ke Dashboard
    header("Location: dashboard/index.php");
    exit();
} else {
    // User belum login, alihkan ke Halaman Login
    header("Location: auth/login.php");
    exit();
}
