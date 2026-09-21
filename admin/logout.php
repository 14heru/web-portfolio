<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Hapus semua variabel sesi
$_SESSION = [];

// Hapus cookie sesi jika ada
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

// Hancurkan sesi
session_destroy();

// Mulai sesi baru untuk pesan informasi logout
session_start();
$_SESSION['login_error'] = 'Anda telah berhasil keluar dari sistem.';

header('Location: login.php');
exit;

