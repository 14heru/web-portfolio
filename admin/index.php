<?php
// Proteksi akses: hanya user yang sudah login yang bisa mengakses halaman ini
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/../config/database.php';

$adminUsername = $_SESSION['admin_username'] ?? 'Admin';

// Ambil data statistik dari database
$totalProjects   = 0;
$totalCategories = 0;
$totalMessages   = 0;
$unreadMessages  = 0;
$recentMessages  = [];

try {
    $totalProjects   = $pdo->query("SELECT COUNT(*) FROM projects")->fetchColumn();
    $totalCategories = $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
    $totalMessages   = $pdo->query("SELECT COUNT(*) FROM messages")->fetchColumn();
    $unreadMessages  = $pdo->query("SELECT COUNT(*) FROM messages WHERE is_read = 0")->fetchColumn();

    $recentStmt = $pdo->query("SELECT * FROM messages ORDER BY created_at DESC LIMIT 5");
    $recentMessages = $recentStmt->fetchAll();
} catch (PDOException $e) {
    // Tangani jika tabel belum siap
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Web Portfolio</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 CDN & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/theme-glass.css">

    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #0a0d14;
            color: #cbd5e1;
        }
        h1, h2, h3, h4, h5, h6, .brand-font {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
        .stat-card {
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 1rem;
            background: rgba(255, 255, 255, 0.03);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
        }
        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }
    </style>
</head>
<body class="admin-body d-flex flex-column min-vh-100">
<div class="bg-aurora" aria-hidden="true"></div>

<!-- Top Navigation Bar -->
<nav class="navbar navbar-expand-lg navbar-dark admin-navbar sticky-top py-3 shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="index.php">
            <span class="bg-primary text-white rounded-3 p-1 px-2 fs-6">
                <i class="bi bi-shield-lock"></i>
            </span>
            <span class="brand-font">Admin<span class="text-primary">Panel</span></span>
        </a>

        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#adminNavbar" aria-controls="adminNavbar" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="adminNavbar">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0 ms-lg-3">
                <li class="nav-item">
                    <a class="nav-link text-light fw-semibold active text-primary" href="index.php">Dashboard</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-light opacity-75" href="projects.php">
                        <i class="bi bi-briefcase me-1"></i> Proyek
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-light opacity-75" href="categories.php">
                        <i class="bi bi-tags me-1"></i> Kategori
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-light opacity-75" href="messages.php">
                        <i class="bi bi-envelope me-1"></i> Pesan
                        <?php if ($unreadMessages > 0): ?>
                            <span class="badge bg-danger rounded-pill ms-1"><?= (int)$unreadMessages; ?></span>
                        <?php endif; ?>
                    </a>
                </li>
            </ul>

            <ul class="navbar-nav ms-auto align-items-lg-center gap-2">
                <li class="nav-item">
                    <a class="nav-link text-light opacity-75" href="../index.php" target="_blank">
                        <i class="bi bi-box-arrow-up-right me-1"></i> Lihat Situs
                    </a>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle text-light d-flex align-items-center gap-2" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-person-circle fs-5 text-primary"></i>
                        <span><?= htmlspecialchars($adminUsername); ?></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 rounded-3">
                        <li><h6 class="dropdown-header">Akun: <?= htmlspecialchars($adminUsername); ?></h6></li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item text-danger d-flex align-items-center gap-2" href="logout.php">
                                <i class="bi bi-box-arrow-right"></i> Keluar (Logout)
                            </a>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- Main Admin Content -->
<main class="container admin-main flex-grow-1">
    <!-- Header Greeting -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4 pb-3 border-bottom">
        <div>
            <h2 class="fw-bold mb-1">Selamat Datang, <?= htmlspecialchars($adminUsername); ?>! 👋</h2>
            <p class="text-muted small mb-0">Kelola konten portofolio, kategori, dan tanggapi pesan masuk pengunjung.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="projects.php" class="btn btn-primary btn-sm rounded-pill px-3 d-inline-flex align-items-center gap-1 shadow-sm">
                <i class="bi bi-briefcase"></i> Kelola Proyek
            </a>
            <a href="logout.php" class="btn btn-outline-danger btn-sm rounded-pill px-3 d-inline-flex align-items-center gap-1">
                <i class="bi bi-box-arrow-right"></i> Logout
            </a>
        </div>
    </div>

    <!-- Stat Cards Grid -->
    <div class="row g-4 mb-4">
        <div class="col-sm-6 col-lg-3">
            <a href="projects.php" class="text-decoration-none">
                <div class="stat-card p-4 h-100">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                            <i class="bi bi-briefcase"></i>
                        </div>
                        <span class="badge bg-primary bg-opacity-10 text-primary fw-medium">Portofolio</span>
                    </div>
                    <h3 class="fw-bold mb-1 text-dark"><?= (int)$totalProjects; ?></h3>
                    <span class="text-muted small">Total Proyek &rarr;</span>
                </div>
            </a>
        </div>

        <div class="col-sm-6 col-lg-3">
            <a href="categories.php" class="text-decoration-none">
                <div class="stat-card p-4 h-100">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="stat-icon bg-info bg-opacity-10 text-info">
                            <i class="bi bi-tags"></i>
                        </div>
                        <span class="badge bg-info bg-opacity-10 text-info fw-medium">Kategori</span>
                    </div>
                    <h3 class="fw-bold mb-1 text-dark"><?= (int)$totalCategories; ?></h3>
                    <span class="text-muted small">Kategori Proyek &rarr;</span>
                </div>
            </a>
        </div>

        <div class="col-sm-6 col-lg-3">
            <a href="messages.php" class="text-decoration-none">
                <div class="stat-card p-4 h-100">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="stat-icon bg-success bg-opacity-10 text-success">
                            <i class="bi bi-envelope"></i>
                        </div>
                        <span class="badge bg-success bg-opacity-10 text-success fw-medium">Inbox</span>
                    </div>
                    <h3 class="fw-bold mb-1 text-dark"><?= (int)$totalMessages; ?></h3>
                    <span class="text-muted small">Total Pesan &rarr;</span>
                </div>
            </a>
        </div>

        <div class="col-sm-6 col-lg-3">
            <a href="messages.php" class="text-decoration-none">
                <div class="stat-card p-4 h-100">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="stat-icon bg-warning bg-opacity-10 text-warning">
                            <i class="bi bi-chat-dots"></i>
                        </div>
                        <span class="badge bg-warning bg-opacity-10 text-warning fw-medium">Unread</span>
                    </div>
                    <h3 class="fw-bold mb-1 text-dark"><?= (int)$unreadMessages; ?></h3>
                    <span class="text-muted small">Pesan Belum Dibaca &rarr;</span>
                </div>
            </a>
        </div>
    </div>

    <!-- Recent Messages Table -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
            <h5 class="fw-bold mb-0 d-flex align-items-center gap-2">
                <i class="bi bi-inbox text-primary"></i> Pesan Masuk Terbaru
            </h5>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-light text-dark border"><?= count($recentMessages); ?> Terakhir</span>
                <a href="messages.php" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                    Buka Kotak Masuk &rarr;
                </a>
            </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th scope="col" class="ps-4">Pengirim</th>
                        <th scope="col">Subjek</th>
                        <th scope="col">Pesan</th>
                        <th scope="col">Tanggal</th>
                        <th scope="col" class="text-center">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($recentMessages)): ?>
                        <?php foreach ($recentMessages as $msg): ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-semibold text-dark"><?= htmlspecialchars($msg['name']); ?></div>
                                    <small class="text-muted"><?= htmlspecialchars($msg['email']); ?></small>
                                </td>
                                <td>
                                    <span class="fw-medium text-dark"><?= htmlspecialchars($msg['subject'] ?: '(Tanpa Subjek)'); ?></span>
                                </td>
                                <td class="text-truncate" style="max-width: 250px;">
                                    <?= htmlspecialchars($msg['message']); ?>
                                </td>
                                <td class="small text-muted">
                                    <?= date('d M Y, H:i', strtotime($msg['created_at'])); ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($msg['is_read'] == 0): ?>
                                        <span class="badge bg-warning text-dark rounded-pill px-2">Baru</span>
                                    <?php else: ?>
                                        <span class="badge bg-light text-muted border rounded-pill px-2">Dibaca</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">
                                <i class="bi bi-chat-left-text fs-3 d-block mb-2 text-secondary opacity-50"></i>
                                Belum ada pesan masuk dari pengunjung.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<!-- Footer Admin -->
<footer class="admin-footer mt-auto">
    <div class="container">
        &copy; <?= date('Y'); ?> Admin Panel - Web Portfolio.
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
