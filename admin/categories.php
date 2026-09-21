<?php
// Proteksi akses: hanya user yang sudah login yang bisa mengakses halaman ini
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/../config/database.php';

$adminUsername = $_SESSION['admin_username'] ?? 'Admin';

// Notifikasi Flash
$flashSuccess = $_SESSION['flash_success'] ?? null;
$flashError   = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

// Helper function untuk generate slug
function generateSlug($text) {
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    $text = strtolower($text);
    return empty($text) ? 'n-a' : $text;
}

// =========================================================================
// 1. PROSES DELETE (HAPUS KATEGORI)
// =========================================================================
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $deleteId = (int)$_GET['id'];

    try {
        $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
        $stmt->execute([$deleteId]);

        $_SESSION['flash_success'] = 'Kategori berhasil dihapus. Proyek yang terhubung kini berstatus tanpa kategori.';
    } catch (PDOException $e) {
        $_SESSION['flash_error'] = 'Gagal menghapus kategori: ' . $e->getMessage();
    }

    header('Location: categories.php');
    exit;
}

// =========================================================================
// 2. PROSES CREATE (TAMBAH KATEGORI BARU)
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btn_create'])) {
    $name = trim($_POST['name'] ?? '');
    $slug = trim($_POST['slug'] ?? '');

    if (empty($name)) {
        $_SESSION['flash_error'] = 'Nama kategori wajib diisi.';
    } else {
        if (empty($slug)) {
            $slug = generateSlug($name);
        } else {
            $slug = generateSlug($slug);
        }

        try {
            // Periksa apakah slug sudah terdaftar
            $check = $pdo->prepare("SELECT id FROM categories WHERE slug = ?");
            $check->execute([$slug]);
            if ($check->fetch()) {
                $_SESSION['flash_error'] = 'Slug kategori sudah digunakan, silakan gunakan slug lain.';
            } else {
                $insertStmt = $pdo->prepare("INSERT INTO categories (name, slug) VALUES (?, ?)");
                $insertStmt->execute([$name, $slug]);

                $_SESSION['flash_success'] = 'Kategori baru berhasil ditambahkan!';
                header('Location: categories.php');
                exit;
            }
        } catch (PDOException $e) {
            $_SESSION['flash_error'] = 'Gagal menyimpan kategori: ' . $e->getMessage();
        }
    }

    header('Location: categories.php');
    exit;
}

// =========================================================================
// 3. PROSES UPDATE (SIMPAN PERUBAHAN KATEGORI)
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btn_update'])) {
    $editId = (int)$_POST['id'];
    $name   = trim($_POST['name'] ?? '');
    $slug   = trim($_POST['slug'] ?? '');

    if (empty($name)) {
        $_SESSION['flash_error'] = 'Nama kategori tidak boleh kosong.';
    } else {
        if (empty($slug)) {
            $slug = generateSlug($name);
        } else {
            $slug = generateSlug($slug);
        }

        try {
            // Periksa keunikan slug kecuali untuk id yang sedang diedit
            $check = $pdo->prepare("SELECT id FROM categories WHERE slug = ? AND id != ?");
            $check->execute([$slug, $editId]);
            if ($check->fetch()) {
                $_SESSION['flash_error'] = 'Slug kategori sudah digunakan oleh kategori lain.';
            } else {
                $updateStmt = $pdo->prepare("UPDATE categories SET name = ?, slug = ? WHERE id = ?");
                $updateStmt->execute([$name, $slug, $editId]);

                $_SESSION['flash_success'] = 'Kategori berhasil diperbarui!';
                header('Location: categories.php');
                exit;
            }
        } catch (PDOException $e) {
            $_SESSION['flash_error'] = 'Gagal memperbarui kategori: ' . $e->getMessage();
        }
    }

    header('Location: categories.php?action=edit&id=' . $editId);
    exit;
}

// =========================================================================
// 4. PERSIAPKAN DATA (READ & EDIT)
// =========================================================================
$isEditMode   = false;
$editCategory = null;

if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $editId = (int)$_GET['id'];
    $stmtEdit = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
    $stmtEdit->execute([$editId]);
    $editCategory = $stmtEdit->fetch();

    if ($editCategory) {
        $isEditMode = true;
    } else {
        $_SESSION['flash_error'] = 'Kategori tidak ditemukan.';
        header('Location: categories.php');
        exit;
    }
}

// Ambil semua kategori beserta jumlah proyek yang terhubung
$categories = [];
try {
    $query = "
        SELECT c.*, COUNT(p.id) AS total_projects 
        FROM categories c 
        LEFT JOIN projects p ON c.id = p.category_id 
        GROUP BY c.id 
        ORDER BY c.name ASC
    ";
    $catStmt = $pdo->query($query);
    $categories = $catStmt->fetchAll();
} catch (PDOException $e) {
    $categories = [];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Kategori - Admin Panel</title>

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
                    <a class="nav-link text-light fw-semibold active text-primary" href="categories.php">
                        <i class="bi bi-tags me-1"></i> Kategori
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-light opacity-75" href="messages.php">
                        <i class="bi bi-envelope me-1"></i> Pesan
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
            <h2 class="fw-bold mb-1">Manajemen Kategori Proyek</h2>
            <p class="text-muted small mb-0">Kelola kelompok kategori untuk mengklasifikasikan portofolio proyek.</p>
        </div>
        <div>
            <?php if ($isEditMode): ?>
                <a href="categories.php" class="btn btn-outline-secondary btn-sm rounded-pill px-3 d-inline-flex align-items-center gap-1">
                    <i class="bi bi-plus-circle"></i> Mode Tambah Baru
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

    <div class="row g-4">
        <!-- ================= KOLOM KIRI: FORM TAMBAH / EDIT ================= -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 sticky-top" style="top: 90px;">
                <div class="card-header bg-white py-3 border-bottom">
                    <h5 class="fw-bold mb-0 d-flex align-items-center gap-2">
                        <?php if ($isEditMode): ?>
                            <i class="bi bi-pencil-square text-warning"></i> Edit Kategori
                        <?php else: ?>
                            <i class="bi bi-plus-circle text-primary"></i> Tambah Kategori
                        <?php endif; ?>
                    </h5>
                </div>
                <div class="card-body p-4">
                    <form action="categories.php" method="POST">
                        <?php if ($isEditMode): ?>
                            <input type="hidden" name="id" value="<?= (int)$editCategory['id']; ?>">
                        <?php endif; ?>

                        <!-- Nama Kategori -->
                        <div class="mb-3">
                            <label for="name" class="form-label small fw-semibold">Nama Kategori <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" name="name" placeholder="Contoh: Mobile Apps" value="<?= htmlspecialchars($isEditMode ? $editCategory['name'] : ''); ?>" required>
                        </div>

                        <!-- Slug -->
                        <div class="mb-4">
                            <label for="slug" class="form-label small fw-semibold">Slug URL (Opsional)</label>
                            <input type="text" class="form-control" id="slug" name="slug" placeholder="Contoh: mobile-apps" value="<?= htmlspecialchars($isEditMode ? $editCategory['slug'] : ''); ?>">
                            <div class="form-text small">Bila dikosongkan, slug akan digenerate otomatis dari nama kategori.</div>
                        </div>

                        <!-- Tombol Submit -->
                        <div class="d-flex gap-2">
                            <?php if ($isEditMode): ?>
                                <button type="submit" name="btn_update" class="btn btn-warning rounded-pill px-4 flex-grow-1 fw-semibold d-flex align-items-center justify-content-center gap-2">
                                    <i class="bi bi-check-lg"></i> Simpan
                                </button>
                                <a href="categories.php" class="btn btn-light rounded-pill px-3 border">
                                    Batal
                                </a>
                            <?php else: ?>
                                <button type="submit" name="btn_create" class="btn btn-primary rounded-pill px-4 w-100 fw-semibold shadow-sm d-flex align-items-center justify-content-center gap-2">
                                    <i class="bi bi-plus-lg"></i> Tambahkan
                                </button>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- ================= KOLOM KANAN: DAFTAR KATEGORI ================= -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0 d-flex align-items-center gap-2">
                        <i class="bi bi-tags text-primary"></i> Daftar Kategori
                    </h5>
                    <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill px-3 py-2">
                        Total: <?= count($categories); ?>
                    </span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th scope="col" class="ps-4">Nama Kategori</th>
                                <th scope="col">Slug URL</th>
                                <th scope="col" class="text-center">Jumlah Proyek</th>
                                <th scope="col" class="text-center" style="width: 120px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($categories)): ?>
                                <?php foreach ($categories as $cat): ?>
                                    <tr>
                                        <td class="ps-4">
                                            <div class="fw-bold text-dark d-flex align-items-center gap-2">
                                                <i class="bi bi-tag-fill text-primary"></i>
                                                <?= htmlspecialchars($cat['name']); ?>
                                            </div>
                                        </td>
                                        <td>
                                            <code><?= htmlspecialchars($cat['slug']); ?></code>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-light text-dark border rounded-pill px-3 py-1">
                                                <?= (int)$cat['total_projects']; ?> Proyek
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group btn-group-sm" role="group">
                                                <a href="categories.php?action=edit&id=<?= (int)$cat['id']; ?>" class="btn btn-outline-warning" title="Edit Kategori">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <a href="categories.php?action=delete&id=<?= (int)$cat['id']; ?>" class="btn btn-outline-danger" onclick="return confirm('Apakah Anda yakin ingin menghapus kategori ini?');" title="Hapus Kategori">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center py-5 text-muted">
                                        <i class="bi bi-tags fs-2 d-block mb-2 text-secondary opacity-50"></i>
                                        Belum ada kategori yang dibuat. Gunakan formulir di sebelah kiri untuk menambahkan kategori pertama Anda.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
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

