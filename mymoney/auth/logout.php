<?php
/**
 * ===================================================
 * PROCESS LOGOUT (auth/logout.php)
 * ===================================================
 * Menghapus seluruh session aktif dan cookies session,
 * kemudian mengarahkan pengguna kembali ke halaman Login.
 */

session_start();

// Kosongkan semua variabel session
$_SESSION = array();

// Hapus cookie session dari browser pengguna
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(), 
        '', 
        time() - 42000,
        $params["path"], 
        $params["domain"],
        $params["secure"], 
        $params["httponly"]
    );
}

// Hancurkan session di server
session_destroy();

// Redirect ke halaman login
header("Location: login.php");
exit();
