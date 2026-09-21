<?php
// Proteksi akses: hanya user yang sudah login yang bisa mengakses halaman ini
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/../config/database.php';

$adminUsername = $_SESSION['admin_username'] ?? 'Admin';

// Notifikasi Flash
$flashSuccess = $_SESSION['flash_success'] ?? null;
$flashError   = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

// =========================================================================
// 1. PROSES AKSI: HAPUS PESAN
// =========================================================================
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $deleteId = (int)$_GET['id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM messages WHERE id = ?");
        $stmt->execute([$deleteId]);
        $_SESSION['flash_success'] = 'Pesan berhasil dihapus dari sistem.';
    } catch (PDOException $e) {
        $_SESSION['flash_error'] = 'Gagal menghapus pesan: ' . $e->getMessage();
    }
    header('Location: messages.php');
    exit;
}

// =========================================================================
// 2. PROSES AKSI: TANDAI SEBAGAI SUDAH DIBACA / BELUM DIBACA
// =========================================================================
if (isset($_GET['action']) && $_GET['action'] === 'mark_read' && isset($_GET['id'])) {
    $msgId = (int)$_GET['id'];
    try {
        $stmt = $pdo->prepare("UPDATE messages SET is_read = 1 WHERE id = ?");
        $stmt->execute([$msgId]);
        $_SESSION['flash_success'] = 'Pesan telah ditandai sebagai sudah dibaca.';
    } catch (PDOException $e) {
        $_SESSION['flash_error'] = 'Gagal memperbarui status pesan: ' . $e->getMessage();
    }
    header('Location: messages.php');
    exit;
}

if (isset($_GET['action']) && $_GET['action'] === 'mark_unread' && isset($_GET['id'])) {
    $msgId = (int)$_GET['id'];
    try {
        $stmt = $pdo->prepare("UPDATE messages SET is_read = 0 WHERE id = ?");
        $stmt->execute([$msgId]);
        $_SESSION['flash_success'] = 'Pesan telah ditandai sebagai belum dibaca.';
    } catch (PDOException $e) {
        $_SESSION['flash_error'] = 'Gagal memperbarui status pesan: ' . $e->getMessage();
    }
    header('Location: messages.php');
    exit;
}

// =========================================================================
// 3. PROSES AKSI: TANDAI SEMUA DIBACA
// =========================================================================
if (isset($_GET['action']) && $_GET['action'] === 'mark_all_read') {
    try {
        $pdo->query("UPDATE messages SET is_read = 1 WHERE is_read = 0");
        $_SESSION['flash_success'] = 'Semua pesan telah ditandai sebagai sudah dibaca.';
    } catch (PDOException $e) {
        $_SESSION['flash_error'] = 'Gagal memperbarui status: ' . $e->getMessage();
    }
    header('Location: messages.php');
    exit;
}

// =========================================================================
// 4. DETAIL PESAN (JIKA DIBUKA)
// =========================================================================
$selectedMessage = null;
if (isset($_GET['view']) && is_numeric($_GET['view'])) {
    $viewId = (int)$_GET['view'];
    try {
        $viewStmt = $pdo->prepare("SELECT * FROM messages WHERE id = ?");
        $viewStmt->execute([$viewId]);
        $selectedMessage = $viewStmt->fetch();

        // Otomatis tandai sebagai sudah dibaca saat dilihat
        if ($selectedMessage && $selectedMessage['is_read'] == 0) {
            $updateRead = $pdo->prepare("UPDATE messages SET is_read = 1 WHERE id = ?");
            $updateRead->execute([$viewId]);
            $selectedMessage['is_read'] = 1;
        }
    } catch (PDOException $e) {
        $selectedMessage = null;
    }
}

// =========================================================================
// 5. AMBIL SEMUA PESAN DARI DATABASE
// =========================================================================
$messages = [];
$unreadCount = 0;
try {
    $msgStmt = $pdo->query("SELECT * FROM messages ORDER BY created_at DESC");
    $messages = $msgStmt->fetchAll();

    $unreadStmt = $pdo->query("SELECT COUNT(*) FROM messages WHERE is_read = 0");
    $unreadCount = (int)$unreadStmt->fetchColumn();
} catch (PDOException $e) {
    $messages = [];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Pesan Masuk - Admin Panel</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 CDN & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8fafc;
            color: #334155;
        }
        h1, h2, h3, h4, h5, h6, .brand-font {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
        .unread-row {
            background-color: rgba(37, 99, 235, 0.04) !important;
            font-weight: 500;
        }
    </style>
</head>
<body class="d-flex flex-column min-vh-100">

<!-- Top Navigation Bar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top py-3 shadow-sm">
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
                    <a class="nav-link text-light opacity-75" href="index.php">Dashboard</a>
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
                    <a class="nav-link text-light fw-semibold active text-primary" href="messages.php">
                        <i class="bi bi-envelope me-1"></i> Pesan
                        <?php if ($unreadCount > 0): ?>
                            <span class="badge bg-danger rounded-pill ms-1"><?= $unreadCount; ?></span>
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

<!-- Main Content -->
<main class="container py-4 flex-grow-1">
    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4 pb-3 border-bottom">
        <div>
            <h2 class="fw-bold mb-1">Manajemen Pesan Masuk</h2>
            <p class="text-muted small mb-0">Tinjau, tanggapi, dan kelola pesan yang dikirim oleh pengunjung melalui formulir kontak.</p>
        </div>
        <div class="d-flex gap-2">
            <?php if ($unreadCount > 0): ?>
                <a href="messages.php?action=mark_all_read" class="btn btn-outline-primary btn-sm rounded-pill px-3 d-inline-flex align-items-center gap-1" onclick="return confirm('Tandai semua pesan sebagai sudah dibaca?');">
                    <i class="bi bi-check2-all"></i> Tandai Semua Dibaca
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Alert Flash -->
    <?php if ($flashSuccess): ?>
        <div class="alert alert-success alert-dismissible fade show rounded-3 d-flex align-items-center gap-2 mb-4" role="alert">
            <i class="bi bi-check-circle-fill fs-5 text-success"></i>
            <div><?= htmlspecialchars($flashSuccess); ?></div>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if ($flashError): ?>
        <div class="alert alert-danger alert-dismissible fade show rounded-3 d-flex align-items-center gap-2 mb-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill fs-5 text-danger"></i>
            <div><?= htmlspecialchars($flashError); ?></div>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Modal Pratinjau Detail Pesan -->
    <?php if ($selectedMessage): ?>
        <div class="card border-primary border-2 shadow-sm rounded-4 mb-4">
            <div class="card-header bg-primary bg-opacity-10 py-3 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0 text-primary d-flex align-items-center gap-2">
                    <i class="bi bi-envelope-open"></i> Detail Pesan Masuk
                </h5>
                <a href="messages.php" class="btn-close" aria-label="Close"></a>
            </div>
            <div class="card-body p-4">
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="text-muted small fw-semibold">Nama Pengirim:</label>
                        <div class="fw-bold fs-6 text-dark"><?= htmlspecialchars($selectedMessage['name']); ?></div>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small fw-semibold">Email Pengirim:</label>
                        <div>
                            <a href="mailto:<?= htmlspecialchars($selectedMessage['email']); ?>?subject=Re: <?= urlencode($selectedMessage['subject'] ?? 'Pesan Portofolio'); ?>" class="text-primary fw-medium text-decoration-none">
                                <i class="bi bi-reply me-1"></i> <?= htmlspecialchars($selectedMessage['email']); ?>
                            </a>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <label class="text-muted small fw-semibold">Subjek:</label>
                        <div class="fw-semibold text-dark"><?= htmlspecialchars($selectedMessage['subject'] ?: '(Tanpa Subjek)'); ?></div>
                    </div>
                    <div class="col-md-4">
                        <label class="text-muted small fw-semibold">Waktu Diterima:</label>
                        <div class="small text-muted">
                            <i class="bi bi-clock me-1"></i> <?= date('d F Y - H:i:s', strtotime($selectedMessage['created_at'])); ?>
                        </div>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="text-muted small fw-semibold">Isi Pesan:</label>
                    <div class="p-3 bg-light rounded-3 border text-dark" style="white-space: pre-wrap; font-size: 0.95rem;">
                        <?= nl2br(htmlspecialchars($selectedMessage['message'])); ?>
                    </div>
                </div>

                <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center pt-3 border-top">
                    <div class="d-flex gap-2">
                        <a href="mailto:<?= htmlspecialchars($selectedMessage['email']); ?>?subject=Re: <?= urlencode($selectedMessage['subject'] ?? 'Pesan Portofolio'); ?>" class="btn btn-primary btn-sm rounded-pill px-3 d-flex align-items-center gap-1">
                            <i class="bi bi-reply-fill"></i> Balas Lewat Email
                        </a>
                        <a href="messages.php?action=mark_unread&id=<?= (int)$selectedMessage['id']; ?>" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                            <i class="bi bi-envelope"></i> Tandai Belum Dibaca
                        </a>
                    </div>
                    <div>
                        <a href="messages.php?action=delete&id=<?= (int)$selectedMessage['id']; ?>" class="btn btn-outline-danger btn-sm rounded-pill px-3" onclick="return confirm('Apakah Anda yakin ingin menghapus pesan ini?');">
                            <i class="bi bi-trash"></i> Hapus Pesan Ini
                        </a>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Tabel Daftar Pesan -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
            <h5 class="fw-bold mb-0 d-flex align-items-center gap-2">
                <i class="bi bi-inbox text-primary"></i> Kotak Masuk
            </h5>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill px-3 py-2">
                    Total: <?= count($messages); ?> Pesan
                </span>
                <?php if ($unreadCount > 0): ?>
                    <span class="badge bg-warning bg-opacity-25 text-dark rounded-pill px-3 py-2">
                        <?= $unreadCount; ?> Belum Dibaca
                    </span>
                <?php endif; ?>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th scope="col" class="ps-3" style="width: 50px;">Status</th>
                        <th scope="col">Pengirim</th>
                        <th scope="col">Subjek & Cuplikan</th>
                        <th scope="col">Waktu</th>
                        <th scope="col" class="text-center" style="width: 140px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($messages)): ?>
                        <?php foreach ($messages as $msg): ?>
                            <tr class="<?= $msg['is_read'] == 0 ? 'unread-row' : ''; ?>">
                                <td class="ps-3 text-center">
                                    <?php if ($msg['is_read'] == 0): ?>
                                        <span class="badge bg-primary rounded-pill" title="Belum Dibaca">
                                            <i class="bi bi-envelope-fill"></i>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted opacity-50" title="Sudah Dibaca">
                                            <i class="bi bi-envelope-open"></i>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($msg['name']); ?></div>
                                    <a href="mailto:<?= htmlspecialchars($msg['email']); ?>" class="text-muted small text-decoration-none">
                                        <?= htmlspecialchars($msg['email']); ?>
                                    </a>
                                </td>
                                <td>
                                    <a href="messages.php?view=<?= (int)$msg['id']; ?>" class="text-dark text-decoration-none fw-semibold d-block">
                                        <?= htmlspecialchars($msg['subject'] ?: '(Tanpa Subjek)'); ?>
                                    </a>
                                    <small class="text-muted text-truncate d-inline-block" style="max-width: 320px;">
                                        <?= htmlspecialchars(mb_strimwidth($msg['message'], 0, 80, '...')); ?>
                                    </small>
                                </td>
                                <td class="small text-muted whitespace-nowrap">
                                    <?= date('d M Y, H:i', strtotime($msg['created_at'])); ?>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="messages.php?view=<?= (int)$msg['id']; ?>" class="btn btn-outline-primary" title="Buka Detail Pesan">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <?php if ($msg['is_read'] == 0): ?>
                                            <a href="messages.php?action=mark_read&id=<?= (int)$msg['id']; ?>" class="btn btn-outline-success" title="Tandai Sudah Dibaca">
                                                <i class="bi bi-check2"></i>
                                            </a>
                                        <?php else: ?>
                                            <a href="messages.php?action=mark_unread&id=<?= (int)$msg['id']; ?>" class="btn btn-outline-secondary" title="Tandai Belum Dibaca">
                                                <i class="bi bi-envelope"></i>
                                            </a>
                                        <?php endif; ?>
                                        <a href="messages.php?action=delete&id=<?= (int)$msg['id']; ?>" class="btn btn-outline-danger" onclick="return confirm('Apakah Anda yakin ingin menghapus pesan ini?');" title="Hapus Pesan">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center py-5 text-muted">
                                <i class="bi bi-chat-left-dots fs-2 d-block mb-2 text-secondary opacity-50"></i>
                                Kotak masuk kosong. Belum ada pesan dari pengunjung web.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<!-- Footer Admin -->
<footer class="bg-white border-top py-3 text-center text-muted small mt-auto">
    <div class="container">
        &copy; <?= date('Y'); ?> Admin Panel - Web Portfolio.
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

