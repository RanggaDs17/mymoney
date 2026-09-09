<?php
/**
 * ===================================================
 * FILE KONEKSI DATABASE (config/database.php)
 * ===================================================
 * File ini bertugas untuk menghubungkan aplikasi PHP kita 
 * ke database MySQL 'mymoney' menggunakan PDO (PHP Data Objects).
 * PDO adalah metode standar PHP yang sangat aman dari SQL Injection.
 */

// Konfigurasi Database (Pengaturan XAMPP Default)
$host     = 'localhost';
$dbname   = 'mymoney';
$username = 'root';     // Default username XAMPP
$password = '';         // Default password XAMPP (kosong)
$charset  = 'utf8mb4';

// Data Source Name (DSN) - Alamat koneksi database
$dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";

// Pengaturan Opsi PDO
$options = [
    // Jika ada error database, lempar Exception agar mudah ditangani
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    // Format hasil query otomatis menjadi Array Asosiatif (misal: $row['name'])
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    // Matikan simulasi prepared statement untuk keamanan ekstra dari SQL Injection
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    // Membuat koneksi ke MySQL menggunakan PDO
    $pdo = new PDO($dsn, $username, $password, $options);
} catch (PDOException $e) {
    // Jika koneksi gagal, jangan tampilkan detail error mentah ke user (keamanan)
    // Tampilkan pesan ramah pengguna dan catat error asli di log server
    error_log("Database Connection Error: " . $e->getMessage());
    die("Koneksi ke database gagal. Pastikan MySQL di XAMPP sudah dinyalakan.");
}
