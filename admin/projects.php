<?php
// Proteksi akses: hanya user yang sudah login yang bisa mengakses halaman ini
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/../config/database.php';

$adminUsername = $_SESSION['admin_username'] ?? 'Admin';
$uploadDir     = __DIR__ . '/../assets/img/';

// Pastikan folder assets/img/ tersedia
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Notifikasi Flash Session
$flashSuccess = $_SESSION['flash_success'] ?? null;
$flashError   = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

// =========================================================================
// 1. PROSES DELETE (HAPUS PROYEK & BERKAS GAMBAR)
// =========================================================================
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $deleteId = (int)$_GET['id'];

    try {
        // Ambil nama file gambar terlebih dahulu
        $stmtImg = $pdo->prepare("SELECT image FROM projects WHERE id = ?");
        $stmtImg->execute([$deleteId]);
        $project = $stmtImg->fetch();

        if ($project) {
            // Hapus file gambar fisik jika ada di folder assets/img/
            if (!empty($project['image']) && file_exists($uploadDir . $project['image'])) {
                unlink($uploadDir . $project['image']);
            }

            // Hapus data dari database
            $stmtDel = $pdo->prepare("DELETE FROM projects WHERE id = ?");
            $stmtDel->execute([$deleteId]);

            $_SESSION['flash_success'] = 'Proyek dan berkas gambarnya berhasil dihapus.';
        } else {
            $_SESSION['flash_error'] = 'Data proyek tidak ditemukan.';
        }
    } catch (PDOException $e) {
        $_SESSION['flash_error'] = 'Gagal menghapus proyek: ' . $e->getMessage();
    }

    header('Location: projects.php');
    exit;
}

// =========================================================================
// 2. PROSES CREATE (TAMBAH PROYEK BARU)
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btn_create'])) {
    $title       = trim($_POST['title'] ?? '');
    $categoryId  = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
    $description = trim($_POST['description'] ?? '');
    $projectUrl  = trim($_POST['project_url'] ?? '');
    $githubUrl   = trim($_POST['github_url'] ?? '');
    $imageName   = null;

    if (empty($title)) {
        $_SESSION['flash_error'] = 'Judul proyek wajib diisi.';
    } else {
        // Handle upload gambar jika ada
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $fileTmpName  = $_FILES['image']['tmp_name'];
            $fileName     = $_FILES['image']['name'];
            $fileSize     = $_FILES['image']['size'];
            $fileExt      = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $allowedExts  = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

            if (!in_array($fileExt, $allowedExts)) {
                $_SESSION['flash_error'] = 'Format gambar tidak didukung (Hanya JPG, JPEG, PNG, GIF, WEBP).';
            } elseif ($fileSize > 2 * 1024 * 1024) { // 2MB
                $_SESSION['flash_error'] = 'Ukuran berkas gambar maksimal 2MB.';
            } else {
                // Generate nama file unik
                $newFileName = 'proj_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $fileExt;
                if (move_uploaded_file($fileTmpName, $uploadDir . $newFileName)) {
                    $imageName = $newFileName;
                } else {
                    $_SESSION['flash_error'] = 'Gagal mengunggah berkas gambar ke server.';
                }
            }
        }

        // Simpan ke database jika tidak ada error upload
        if (empty($_SESSION['flash_error'])) {
            try {
                $query = "INSERT INTO projects (category_id, title, description, image, project_url, github_url, created_at) 
                        VALUES (?, ?, ?, ?, ?, ?, NOW())";
                $stmt = $pdo->prepare($query);
                $stmt->execute([
                    $categoryId,
                    $title,
                    !empty($description) ? $description : null,
                    $imageName,
                    !empty($projectUrl) ? $projectUrl : null,
                    !empty($githubUrl) ? $githubUrl : null
                ]);

                $_SESSION['flash_success'] = 'Proyek baru berhasil ditambahkan!';
                header('Location: projects.php');
                exit;
            } catch (PDOException $e) {
                $_SESSION['flash_error'] = 'Gagal menyimpan ke database: ' . $e->getMessage();
            }
        }
    }

    header('Location: projects.php');
    exit;
}

// =========================================================================
// 3. PROSES UPDATE (SIMPAN PERUBAHAN PROYEK)
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btn_update'])) {
    $editId      = (int)$_POST['id'];
    $title       = trim($_POST['title'] ?? '');
    $categoryId  = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
    $description = trim($_POST['description'] ?? '');
    $projectUrl  = trim($_POST['project_url'] ?? '');
    $githubUrl   = trim($_POST['github_url'] ?? '');

    if (empty($title)) {
        $_SESSION['flash_error'] = 'Judul proyek wajib diisi.';
    } else {
        try {
            // Ambil data proyek saat ini
            $stmtCurrent = $pdo->prepare("SELECT image FROM projects WHERE id = ?");
            $stmtCurrent->execute([$editId]);
            $currentProject = $stmtCurrent->fetch();

            if (!$currentProject) {
                $_SESSION['flash_error'] = 'Data proyek tidak ditemukan.';
            } else {
                $imageName = $currentProject['image'];

                // Periksa apakah ada file gambar baru yang diunggah
                if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                    $fileTmpName  = $_FILES['image']['tmp_name'];
                    $fileName     = $_FILES['image']['name'];
                    $fileSize     = $_FILES['image']['size'];
                    $fileExt      = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                    $allowedExts  = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

                    if (!in_array($fileExt, $allowedExts)) {
                        $_SESSION['flash_error'] = 'Format gambar tidak didukung (Hanya JPG, JPEG, PNG, GIF, WEBP).';
                    } elseif ($fileSize > 2 * 1024 * 1024) {
                        $_SESSION['flash_error'] = 'Ukuran berkas gambar maksimal 2MB.';
                    } else {
                        $newFileName = 'proj_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $fileExt;
                        if (move_uploaded_file($fileTmpName, $uploadDir . $newFileName)) {
                            // Hapus gambar lama jika ada
                            if (!empty($currentProject['image']) && file_exists($uploadDir . $currentProject['image'])) {
                                unlink($uploadDir . $currentProject['image']);
                            }
                            $imageName = $newFileName;
                        } else {
                            $_SESSION['flash_error'] = 'Gagal mengunggah gambar baru.';
                        }
                    }
                }

                if (empty($_SESSION['flash_error'])) {
                    $updateSql = "UPDATE projects SET category_id = ?, title = ?, description = ?, image = ?, project_url = ?, github_url = ? WHERE id = ?";
                    $stmtUpdate = $pdo->prepare($updateSql);
                    $stmtUpdate->execute([
                        $categoryId,
                        $title,
                        !empty($description) ? $description : null,
                        $imageName,
                        !empty($projectUrl) ? $projectUrl : null,
                        !empty($githubUrl) ? $githubUrl : null,
                        $editId
                    ]);

                    $_SESSION['flash_success'] = 'Data proyek berhasil diperbarui!';
                    header('Location: projects.php');
                    exit;
                }
            }
        } catch (PDOException $e) {
            $_SESSION['flash_error'] = 'Gagal memperbarui data: ' . $e->getMessage();
        }
    }

    header('Location: projects.php?action=edit&id=' . $editId);
    exit;
}

// =========================================================================
// 4. PERSIAPKAN DATA UNTUK TAMPILAN (READ & EDIT)
// =========================================================================
// Cek jika sedang dalam mode Edit
$isEditMode  = false;
$editProject = null;

if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $editId = (int)$_GET['id'];
    $stmtEdit = $pdo->prepare("SELECT * FROM projects WHERE id = ?");
    $stmtEdit->execute([$editId]);
    $editProject = $stmtEdit->fetch();

    if ($editProject) {
        $isEditMode = true;
    } else {
        $_SESSION['flash_error'] = 'Data proyek yang akan diedit tidak ditemukan.';
        header('Location: projects.php');
        exit;
    }
}

// Ambil semua kategori untuk dropdown
$categories = [];
try {
    $catStmt = $pdo->query("SELECT * FROM categories ORDER BY name ASC");
    $categories = $catStmt->fetchAll();
} catch (PDOException $e) {
    $categories = [];
}

// Ambil semua data proyek (dengan relasi nama kategori)
$projects = [];
try {
    $projStmt = $pdo->query("
        SELECT p.*, c.name AS category_name 
        FROM projects p 
        LEFT JOIN categories c ON p.category_id = c.id 
        ORDER BY p.created_at DESC
    ");
    $projects = $projStmt->fetchAll();
} catch (PDOException $e) {
    $projects = [];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Proyek - Admin Panel</title>

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
        .table-thumb {
            width: 56px;
            height: 56px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
        }
        .table-thumb-placeholder {
            width: 56px;
            height: 56px;
            border-radius: 8px;
            background-color: #f1f5f9;
            color: #94a3b8;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            border: 1px solid #e2e8f0;
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
                    <a class="nav-link text-light fw-semibold active text-primary" href="projects.php">
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
            <h2 class="fw-bold mb-1">Manajemen Portofolio Proyek</h2>
            <p class="text-muted small mb-0">Tambah, perbarui, atau hapus karya proyek yang ditampilkan pada halaman utama.</p>
        </div>
        <div>
            <?php if ($isEditMode): ?>
                <a href="projects.php" class="btn btn-outline-secondary btn-sm rounded-pill px-3 d-inline-flex align-items-center gap-1">
                    <i class="bi bi-plus-circle"></i> Mode Tambah Baru
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Alert Notifikasi Flash -->
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
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm rounded-4 sticky-top" style="top: 90px;">
                <div class="card-header bg-white py-3 border-bottom">
                    <h5 class="fw-bold mb-0 d-flex align-items-center gap-2">
                        <?php if ($isEditMode): ?>
                            <i class="bi bi-pencil-square text-warning"></i> Edit Proyek
                        <?php else: ?>
                            <i class="bi bi-plus-circle text-primary"></i> Tambah Proyek Baru
                        <?php endif; ?>
                    </h5>
                </div>
                <div class="card-body p-4">
                    <form action="projects.php" method="POST" enctype="multipart/form-data">
                        <?php if ($isEditMode): ?>
                            <input type="hidden" name="id" value="<?= (int)$editProject['id']; ?>">
                        <?php endif; ?>

                        <!-- Judul Proyek -->
                        <div class="mb-3">
                            <label for="title" class="form-label small fw-semibold">Judul Proyek <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="title" name="title" placeholder="Contoh: Sistem Informasi Kasir Web" value="<?= htmlspecialchars($isEditMode ? $editProject['title'] : ''); ?>" required>
                        </div>

                        <!-- Dropdown Kategori -->
                        <div class="mb-3">
                            <label for="category_id" class="form-label small fw-semibold">Kategori Proyek</label>
                            <select class="form-select" id="category_id" name="category_id">
                                <option value="">-- Pilih Kategori --</option>
                                <?php foreach ($categories as $cat): ?>
                                    <?php 
                                        $selected = '';
                                        if ($isEditMode && $editProject['category_id'] == $cat['id']) {
                                            $selected = 'selected';
                                        }
                                    ?>
                                    <option value="<?= (int)$cat['id']; ?>" <?= $selected; ?>>
                                        <?= htmlspecialchars($cat['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (empty($categories)): ?>
                                <div class="form-text text-warning small">Belum ada kategori di database.</div>
                            <?php endif; ?>
                        </div>

                        <!-- Deskripsi -->
                        <div class="mb-3">
                            <label for="description" class="form-label small fw-semibold">Deskripsi Proyek</label>
                            <textarea class="form-control" id="description" name="description" rows="4" placeholder="Jelaskan fitur, latar belakang, atau teknologi yang digunakan..."><?= htmlspecialchars($isEditMode ? ($editProject['description'] ?? '') : ''); ?></textarea>
                        </div>

                        <!-- Unggah Gambar -->
                        <div class="mb-3">
                            <label for="image" class="form-label small fw-semibold">Gambar / Thumbnail Proyek</label>
                            <input type="file" class="form-control" id="image" name="image" accept="image/*">
                            <div class="form-text small">Maksimal 2MB. Format: JPG, PNG, GIF, WEBP.</div>

                            <!-- Preview gambar saat ini (Edit mode) -->
                            <?php if ($isEditMode && !empty($editProject['image'])): ?>
                                <div class="mt-2 p-2 bg-light rounded-3 border d-flex align-items-center gap-3">
                                    <img src="../assets/img/<?= htmlspecialchars($editProject['image']); ?>" alt="Current Image" class="table-thumb">
                                    <div class="small">
                                        <div class="fw-semibold text-truncate" style="max-width: 180px;"><?= htmlspecialchars($editProject['image']); ?></div>
                                        <span class="text-muted">Gambar saat ini. Unggah file baru untuk mengganti.</span>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- URL Demo & GitHub -->
                        <div class="row g-2 mb-4">
                            <div class="col-md-6">
                                <label for="project_url" class="form-label small fw-semibold">URL Demo (Opsional)</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text"><i class="bi bi-link-45deg"></i></span>
                                    <input type="url" class="form-control" id="project_url" name="project_url" placeholder="https://..." value="<?= htmlspecialchars($isEditMode ? ($editProject['project_url'] ?? '') : ''); ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="github_url" class="form-label small fw-semibold">URL GitHub (Opsional)</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text"><i class="bi bi-github"></i></span>
                                    <input type="url" class="form-control" id="github_url" name="github_url" placeholder="https://github.com/..." value="<?= htmlspecialchars($isEditMode ? ($editProject['github_url'] ?? '') : ''); ?>">
                                </div>
                            </div>
                        </div>

                        <!-- Tombol Submit -->
                        <div class="d-flex gap-2">
                            <?php if ($isEditMode): ?>
                                <button type="submit" name="btn_update" class="btn btn-warning rounded-pill px-4 flex-grow-1 fw-semibold d-flex align-items-center justify-content-center gap-2">
                                    <i class="bi bi-check-lg"></i> Simpan Perubahan
                                </button>
                                <a href="projects.php" class="btn btn-light rounded-pill px-3 border">
                                    Batal
                                </a>
                            <?php else: ?>
                                <button type="submit" name="btn_create" class="btn btn-primary rounded-pill px-4 w-100 fw-semibold shadow-sm d-flex align-items-center justify-content-center gap-2">
                                    <i class="bi bi-plus-lg"></i> Tambahkan Proyek
                                </button>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- ================= KOLOM KANAN: TABEL DAFTAR PROYEK ================= -->
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0 d-flex align-items-center gap-2">
                        <i class="bi bi-list-task text-primary"></i> Daftar Proyek
                    </h5>
                    <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill px-3 py-2">
                        Total: <?= count($projects); ?>
                    </span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th scope="col" class="ps-3" style="width: 70px;">Gambar</th>
                                <th scope="col">Info Proyek</th>
                                <th scope="col">Kategori</th>
                                <th scope="col" class="text-center" style="width: 120px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($projects)): ?>
                                <?php foreach ($projects as $row): ?>
                                    <tr>
                                        <td class="ps-3">
                                            <?php if (!empty($row['image']) && file_exists($uploadDir . $row['image'])): ?>
                                                <img src="../assets/img/<?= htmlspecialchars($row['image']); ?>" alt="<?= htmlspecialchars($row['title']); ?>" class="table-thumb">
                                            <?php else: ?>
                                                <div class="table-thumb-placeholder">
                                                    <i class="bi bi-image"></i>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="fw-bold text-dark"><?= htmlspecialchars($row['title']); ?></div>
                                            <div class="text-muted small text-truncate" style="max-width: 250px;">
                                                <?= htmlspecialchars($row['description'] ?: 'Tidak ada deskripsi.'); ?>
                                            </div>
                                            <div class="mt-1 d-flex gap-2">
                                                <?php if (!empty($row['project_url'])): ?>
                                                    <a href="<?= htmlspecialchars($row['project_url']); ?>" target="_blank" class="small text-decoration-none text-primary" title="Demo">
                                                        <i class="bi bi-box-arrow-up-right"></i> Demo
                                                    </a>
                                                <?php endif; ?>
                                                <?php if (!empty($row['github_url'])): ?>
                                                    <a href="<?= htmlspecialchars($row['github_url']); ?>" target="_blank" class="small text-decoration-none text-dark" title="GitHub">
                                                        <i class="bi bi-github"></i> Repo
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if (!empty($row['category_name'])): ?>
                                                <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 rounded-pill px-2 py-1">
                                                    <?= htmlspecialchars($row['category_name']); ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-light text-muted border rounded-pill px-2 py-1">Tanpa Kategori</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group btn-group-sm" role="group">
                                                <a href="projects.php?action=edit&id=<?= (int)$row['id']; ?>" class="btn btn-outline-warning" title="Edit Proyek">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <a href="projects.php?action=delete&id=<?= (int)$row['id']; ?>" class="btn btn-outline-danger" onclick="return confirm('Apakah Anda yakin ingin menghapus proyek ini beserta gambarnya?');" title="Hapus Proyek">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center py-5 text-muted">
                                        <i class="bi bi-folder-x fs-2 d-block mb-2 text-secondary opacity-50"></i>
                                        Belum ada proyek yang terdaftar. Gunakan formulir di sebelah kiri untuk menambahkan proyek pertama Anda.
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

