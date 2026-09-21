<?php
// ==========================================================================
// config/database.example.php - TEMPLATE KONFIGURASI DATABASE
// ==========================================================================
// CARA PENGGUNAAN:
//   1. Salin berkas ini menjadi config/database.php
//      (di Windows: copy config\database.example.php config\database.php)
//      (di Linux/Mac: cp config/database.example.php config/database.php)
//   2. Sesuaikan nilai $host, $user, $password, dan $database
//      dengan konfigurasi MySQL di server/lokal Anda.
//   3. Berkas config/database.php sudah dikecualikan via .gitignore
//      sehingga tidak akan terekspos ke GitHub.
// ==========================================================================

// Konfigurasi Database
$host     = 'localhost';            // Host server database
$user     = 'GANTI_DB_USER';       // Username database (lokal XAMPP biasanya: root)
$password = 'GANTI_DB_PASSWORD';   // Password database (lokal XAMPP biasanya: kosong)
$database = 'GANTI_NAMA_DATABASE'; // Nama database (contoh: data_portfolio)

try {
    $dsn = "mysql:host={$host};dbname={$database};charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
} catch (PDOException $e) {
    // Tampilkan error saat development — nonaktifkan di production
    die("Koneksi database gagal: " . $e->getMessage());
}

