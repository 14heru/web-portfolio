<?php
// Middleware Proteksi Akses Halaman Admin
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Periksa apakah user telah terautentikasi
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    $_SESSION['login_error'] = 'Sesi Anda belum aktif atau telah berakhir. Silakan login terlebih dahulu.';
    header('Location: login.php');
    exit;
}

