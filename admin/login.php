<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Jika sudah login, langsung alihkan ke dashboard admin
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: index.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';

$errorMessage = $_SESSION['login_error'] ?? null;
unset($_SESSION['login_error']);

// Handle Pemrosesan Login (Metode POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        $errorMessage = 'Mohon isi username dan kata sandi Anda.';
    } else {
        try {
            // Cari user berdasarkan username atau email
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username OR email = :email LIMIT 1");
            $stmt->execute([
                ':username' => $username,
                ':email'    => $username,
            ]);
            $user = $stmt->fetch();

            // Verifikasi kecocokan kata sandi dengan hash di database
            if ($user && password_verify($password, $user['password'])) {
                // Regenerasi ID sesi untuk mencegah session fixation attacks
                session_regenerate_id(true);

                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_user_id']   = $user['id'];
                $_SESSION['admin_username']  = $user['username'];
                $_SESSION['admin_email']     = $user['email'];

                header('Location: index.php');
                exit;
            } else {
                $errorMessage = 'Username atau kata sandi yang Anda masukkan salah.';
            }
        } catch (PDOException $e) {
            $errorMessage = 'Terjadi kesalahan sistem saat menghubungi database: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin - Web Portfolio</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 CDN & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #334155 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
        }
        h1, h2, h3, h4, h5, .brand-font {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
        .login-card {
            max-width: 420px;
            width: 100%;
            background: #ffffff;
            border-radius: 1.25rem;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.25);
            overflow: hidden;
        }
        .form-control:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 0.25rem rgba(37, 99, 235, 0.15);
        }
    </style>
</head>
<body>

<div class="login-card p-4 p-md-5">
    <div class="text-center mb-4">
        <div class="d-inline-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary rounded-circle mb-3" style="width: 64px; height: 64px;">
            <i class="bi bi-shield-lock-fill fs-2"></i>
        </div>
        <h4 class="fw-bold mb-1 brand-font">Admin Panel</h4>
        <p class="text-muted small">Masuk untuk mengelola web portofolio</p>
    </div>

    <!-- Alert Notifikasi Error -->
    <?php if (!empty($errorMessage)): ?>
        <div class="alert alert-danger alert-dismissible fade show rounded-3 small d-flex align-items-center gap-2 mb-4" role="alert">
            <i class="bi bi-exclamation-octagon-fill fs-5 text-danger"></i>
            <div><?= htmlspecialchars($errorMessage); ?></div>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <form action="login.php" method="POST">
        <div class="mb-3">
            <label for="username" class="form-label small fw-semibold text-secondary">Username atau Email</label>
            <div class="input-group">
                <span class="input-group-text bg-light border-end-0 text-muted">
                    <i class="bi bi-person"></i>
                </span>
                <input type="text" class="form-control bg-light border-start-0 ps-0" id="username" name="username" placeholder="Masukkan username" required autofocus value="<?= htmlspecialchars($_POST['username'] ?? ''); ?>">
            </div>
        </div>

        <div class="mb-4">
            <label for="password" class="form-label small fw-semibold text-secondary">Kata Sandi</label>
            <div class="input-group">
                <span class="input-group-text bg-light border-end-0 text-muted">
                    <i class="bi bi-key"></i>
                </span>
                <input type="password" class="form-control bg-light border-start-0 ps-0" id="password" name="password" placeholder="Masukkan kata sandi" required>
            </div>
        </div>

        <button type="submit" name="login" class="btn btn-primary w-100 py-2 rounded-pill fw-semibold shadow-sm mb-3 d-flex align-items-center justify-content-center gap-2">
            <i class="bi bi-box-arrow-in-right"></i> Masuk ke Dashboard
        </button>

        <div class="text-center">
            <a href="../index.php" class="text-decoration-none small text-muted d-inline-flex align-items-center gap-1">
                <i class="bi bi-arrow-left"></i> Kembali ke Beranda
            </a>
        </div>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

