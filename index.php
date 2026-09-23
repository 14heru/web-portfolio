<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pageTitle = "Heru Perdana Saputra - Portofolio Web Developer";

// Muat koneksi database
require_once __DIR__ . '/config/database.php';

// Inisialisasi variabel notifikasi & data form lama
$alertMsg   = $_SESSION['alert_msg'] ?? null;
$alertType  = $_SESSION['alert_type'] ?? null;
$oldData    = $_SESSION['form_data'] ?? [];
unset($_SESSION['alert_msg'], $_SESSION['alert_type'], $_SESSION['form_data']);

// Handle Pemrosesan Formulir Kontak (Metode POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $name    = trim($_POST['name'] ?? '');
    $email   = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    // Validasi Input
    if (empty($name) || empty($email) || empty($message)) {
        $_SESSION['alert_type'] = 'danger';
        $_SESSION['alert_msg']  = 'Mohon lengkapi seluruh kolom yang wajib diisi (Nama, Email, dan Pesan).';
        $_SESSION['form_data']  = compact('name', 'email', 'subject', 'message');
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['alert_type'] = 'warning';
        $_SESSION['alert_msg']  = 'Format alamat email yang Anda masukkan tidak valid.';
        $_SESSION['form_data']  = compact('name', 'email', 'subject', 'message');
    } else {
        try {
            // 1. Simpan data ke tabel messages secara aman menggunakan PDO prepared statement
            $query = "INSERT INTO `messages` (`name`, `email`, `subject`, `message`, `is_read`, `created_at`) 
                      VALUES (:name, :email, :subject, :message, 0, NOW())";
            $stmt = $pdo->prepare($query);
            $stmt->execute([
                ':name'    => $name,
                ':email'   => $email,
                ':subject' => !empty($subject) ? $subject : null,
                ':message' => $message,
            ]);

            // 2. Kirim salinan notifikasi email via PHPMailer (SMTP Gmail)
            require_once __DIR__ . '/config/mail.php';
            sendContactNotification($name, $email, $subject, $message);

            $_SESSION['alert_type'] = 'success';
            $_SESSION['alert_msg']  = 'Terima kasih! Pesan Anda telah berhasil dikirim dan akan segera kami tanggapi.';
        } catch (PDOException $e) {
            $_SESSION['alert_type'] = 'danger';
            $_SESSION['alert_msg']  = 'Terjadi kesalahan sistem saat menyimpan pesan: ' . $e->getMessage();
            $_SESSION['form_data']  = compact('name', 'email', 'subject', 'message');
        }
    }

    // Redirect kembali ke section kontak (Post/Redirect/Get pattern)
    header("Location: index.php#contact");
    exit;
}

// Ambil data kategori & proyek dari database
try {
    $catStmt = $pdo->query("SELECT * FROM categories ORDER BY name ASC");
    $categories = $catStmt->fetchAll();

    $projStmt = $pdo->query("
        SELECT p.*, c.name AS category_name, c.slug AS category_slug 
        FROM projects p 
        LEFT JOIN categories c ON p.category_id = c.id 
        ORDER BY p.created_at DESC
    ");
    $projects = $projStmt->fetchAll();
} catch (PDOException $e) {
    // Jika tabel belum diimpor atau query gagal, tangani tanpa memutus halaman
    $categories = [];
    $projects   = [];
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<!-- ================= HERO SECTION ================= -->
<section id="hero" class="hero-section text-center text-lg-start">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-7">
                <span class="pill-badge mb-4 text-white">
                    <i class="bi bi-stars"></i> Terbuka untuk Peluang Proyek Baru
                </span>
                <p class="hero-eyebrow mb-3">Fullstack Developer · Indonesia</p>
                <h1 class="hero-title mb-3">
                    <span class="text-white">Hi, I'm</span> <span class="text-gradient">Heru Perdana Saputra</span>.
                </h1>
                <p class="hero-subtitle mb-4">
                    Saya seorang pengembang web profesional yang merancang dan membangun aplikasi web yang cepat, responsif, serta mudah digunakan dengan teknologi terkini seperti PHP, MySQL, dan Bootstrap.
                </p>
                <div class="d-flex flex-wrap gap-3 justify-content-center justify-content-lg-start hero-actions">
                    <a href="#contact" class="btn btn-primary btn-lg px-4 rounded-pill d-inline-flex align-items-center gap-2">
                        <i class="bi bi-send"></i> Contact Me
                    </a>
                    <a href="https://github.com/14heru" target="_blank" rel="noopener noreferrer" class="btn btn-glass btn-lg px-4 rounded-pill d-inline-flex align-items-center gap-2" title="Profil GitHub Heru">
                        <i class="bi bi-github"></i> GitHub
                    </a>
                </div>
            </div>

            <div class="col-lg-5 text-center">
                <div class="profile-frame">
                    <div class="profile-lanyard" aria-hidden="true"></div>
                    <div class="profile-card">
                        <div class="profile-photo-wrapper">
                            <img src="assets/img/profile.jpg" alt="Foto profil Heru Perdana Saputra" onerror="this.style.display='none'; this.nextElementSibling.hidden=false;">
                            <div class="profile-initials" hidden>HPS<small>Profile photo</small></div>
                        </div>
                        <div class="profile-meta">
                            <div class="profile-name">Heru Perdana Saputra</div>
                            <div class="profile-role">Fullstack Developer</div>
                            <div class="profile-status"><span class="dot"></span> Available for collaboration</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ================= ABOUT SECTION ================= -->
<section id="about" class="section section-divider border-bottom">
    <div class="container py-4">
        <div class="row align-items-center g-5">
            <div class="col-lg-6">
                <div class="glass-card glass-card-pad">
                    <div class="row g-3">
                        <div class="col-6"><div class="stat-card"><div class="stat-value">1</div><p class="stat-label">Tahun Pengalaman</p></div></div>
                        <div class="col-6"><div class="stat-card"><div class="stat-value">3</div><p class="stat-label">Proyek Selesai</p></div></div>
                        <div class="col-6"><div class="stat-card"><div class="stat-value">-</div><p class="stat-label">Klien Puas</p></div></div>
                        <div class="col-6"><div class="stat-card"><div class="stat-value">100%</div><p class="stat-label">Dedikasi Kualitas</p></div></div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <h6 class="text-primary fw-bold text-uppercase mb-2">Tentang Saya</h6>
                <h2 class="fw-bold mb-3">Mendedikasikan Diri untuk Menghasilkan Kode Bersih dan Pengalaman Pengguna Terbaik</h2>
                <p class="text-muted leading-relaxed">
                    Saya <strong>Heru Perdana Saputra</strong>, seorang pengembang web yang berdedikasi dalam pembuatan website modern, efisien, dan ramah pengguna. Dengan fondasi logika pemrograman yang kuat dan pemahaman desain antarmuka, saya siap membantu merealisasikan kebutuhan digital Anda.
                </p>
                <div class="row g-3 mt-2">
                    <div class="col-sm-6">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-check-circle-fill text-success"></i>
                            <span class="fw-medium">Responsif di Semua Layar</span>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-check-circle-fill text-success"></i>
                            <span class="fw-medium">Optimasi Kecepatan & SEO</span>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-check-circle-fill text-success"></i>
                            <span class="fw-medium">Struktur Kode Rapi & Aman</span>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-check-circle-fill text-success"></i>
                            <span class="fw-medium">Dukungan Berkelanjutan</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ================= SKILLS SECTION ================= -->
<section id="skills" class="section section-divider">
    <div class="container py-4">
        <div class="text-center max-w-600 mx-auto mb-5">
            <h6 class="text-primary fw-bold text-uppercase mb-2">Keahlian</h6>
            <h2 class="fw-bold">Keahlian & Kemampuan Teknis</h2>
            <p class="text-muted">Teknologi dan perangkat kerja yang rutin saya gunakan dalam proses pengembangan.</p>
        </div>

        <div class="row g-4">
            <!-- Skill 1: Frontend -->
            <div class="col-md-4">
                <div class="skill-card glass-card-hover h-100">
                    <div class="skill-icon bg-primary bg-opacity-10 text-primary">
                        <i class="bi bi-layout-text-window-reverse"></i>
                    </div>
                    <h5 class="fw-bold mb-2">Frontend Development</h5>
                    <p class="text-muted small mb-3">Membuat tampilan interaktif, dinamis, dan responsif untuk kenyamanan pengguna.</p>
                    <div class="d-flex flex-wrap gap-1">
                        <span class="badge bg-light text-secondary border">HTML5</span>
                        <span class="badge bg-light text-secondary border">CSS3</span>
                        <span class="badge bg-light text-secondary border">JavaScript</span>
                        <span class="badge bg-light text-secondary border">Bootstrap 5</span>
                    </div>
                </div>
            </div>

            <!-- Skill 2: Backend -->
            <div class="col-md-4">
                <div class="skill-card glass-card-hover h-100">
                    <div class="skill-icon bg-success bg-opacity-10 text-success">
                        <i class="bi bi-server"></i>
                    </div>
                    <h5 class="fw-bold mb-2">Backend Development</h5>
                    <p class="text-muted small mb-3">Membangun arsitektur server, logika bisnis yang andal, dan integrasi API yang aman.</p>
                    <div class="d-flex flex-wrap gap-1">
                        <span class="badge bg-light text-secondary border">PHP (Native / OOP)</span>
                        <span class="badge bg-light text-secondary border">MySQL / MariaDB</span>
                        <span class="badge bg-light text-secondary border">REST API</span>
                        <span class="badge bg-light text-secondary border">PDO Security</span>
                    </div>
                </div>
            </div>

            <!-- Skill 3: Tools & Best Practices -->
            <div class="col-md-4">
                <div class="skill-card glass-card-hover h-100">
                    <div class="skill-icon bg-warning bg-opacity-10 text-warning">
                        <i class="bi bi-tools"></i>
                    </div>
                    <h5 class="fw-bold mb-2">Tools & Praktik Terbaik</h5>
                    <p class="text-muted small mb-3">Pemanfaatan alat modern untuk efisiensi kolaborasi serta version control.</p>
                    <div class="d-flex flex-wrap gap-1">
                        <span class="badge bg-light text-secondary border">Git / GitHub</span>
                        <span class="badge bg-light text-secondary border">XAMPP / Apache</span>
                        <span class="badge bg-light text-secondary border">VS Code</span>
                        <span class="badge bg-light text-secondary border">Postman</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ================= PROJECTS SECTION ================= -->
<section id="projects" class="section section-divider border-top">
    <div class="container py-4">
        <div class="text-center max-w-600 mx-auto mb-4">
            <h6 class="text-primary fw-bold text-uppercase mb-2">Portofolio</h6>
            <h2 class="fw-bold">Karya & Proyek Pilihan</h2>
            <p class="text-muted">Beberapa proyek yang telah selesai dikembangkan dengan berbagai studi kasus.</p>
        </div>

        <!-- Filter Buttons (Interaktif via JavaScript) -->
        <?php if (!empty($categories)): ?>
            <div class="d-flex flex-wrap justify-content-center gap-2 mb-5">
                <button class="btn btn-sm btn-outline-primary btn-filter active rounded-pill px-3" data-filter="all">Semua</button>
                <?php foreach ($categories as $cat): ?>
                    <button class="btn btn-sm btn-outline-primary btn-filter rounded-pill px-3" data-filter="<?= htmlspecialchars($cat['slug']); ?>">
                        <?= htmlspecialchars($cat['name']); ?>
                    </button>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Project Grid -->
        <div class="row g-4 project-container">
            <?php if (!empty($projects)): ?>
                <?php foreach ($projects as $project): ?>
                    <div class="col-lg-4 col-md-6 project-item" data-category="<?= htmlspecialchars($project['category_slug'] ?? 'uncategorized'); ?>">
                        <div class="portfolio-card glass-card-hover h-100 d-flex flex-column">
                            <div class="card-img-wrapper">
                                <?php if (!empty($project['image']) && file_exists(__DIR__ . '/assets/img/' . $project['image'])): ?>
                                    <img src="assets/img/<?= htmlspecialchars($project['image']); ?>" alt="<?= htmlspecialchars($project['title']); ?>" loading="lazy">
                                <?php else: ?>
                                    <div class="card-placeholder-icon">
                                        <i class="bi bi-window-fullscreen"></i>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="card-body p-4 d-flex flex-column flex-grow-1">
                                <div class="mb-2">
                                    <span class="badge bg-primary bg-opacity-10 text-primary fw-semibold px-2 py-1">
                                        <?= htmlspecialchars($project['category_name'] ?? 'Umum'); ?>
                                    </span>
                                </div>
                                <h5 class="card-title fw-bold text-dark mb-2">
                                    <?= htmlspecialchars($project['title']); ?>
                                </h5>
                                <p class="card-text text-muted small flex-grow-1">
                                    <?= nl2br(htmlspecialchars($project['description'] ?? 'Belum ada deskripsi untuk proyek ini.')); ?>
                                </p>
                                <div class="d-flex gap-2 pt-3 border-top mt-auto">
                                    <?php if (!empty($project['project_url'])): ?>
                                        <a href="<?= htmlspecialchars($project['project_url']); ?>" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-primary rounded-pill px-3 flex-fill text-center">
                                            <i class="bi bi-box-arrow-up-right me-1"></i> Demo
                                        </a>
                                    <?php endif; ?>
                                    <?php if (!empty($project['github_url'])): ?>
                                        <a href="<?= htmlspecialchars($project['github_url']); ?>" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline-dark rounded-pill px-3 flex-fill text-center">
                                            <i class="bi bi-github me-1"></i> GitHub
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <!-- Fallback jika belum ada data proyek di database -->
                <div class="col-12 text-center py-5">
                    <div class="p-5 bg-light rounded-4 border d-inline-block max-w-500">
                        <i class="bi bi-folder2-open display-4 text-muted mb-3 d-block"></i>
                        <h5 class="fw-bold">Belum Ada Proyek Ditambahkan</h5>
                        <p class="text-muted small mb-3">
                            Data proyek akan otomatis tampil di sini setelah Anda menambahkannya melalui database atau halaman Admin.
                        </p>
                        <a href="admin/index.php" class="btn btn-sm btn-primary rounded-pill px-3">
                            <i class="bi bi-plus-circle me-1"></i> Kelola via Admin Panel
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- ================= CONTACT SECTION ================= -->
<section id="contact" class="section section-divider">
    <div class="container py-4">
        <div class="row g-5">
            <!-- Informasi Kontak -->
            <div class="col-lg-5">
                <h6 class="text-primary fw-bold text-uppercase mb-2">Hubungi Saya</h6>
                <h2 class="fw-bold mb-3">Mari Bekerja Sama untuk Proyek Anda</h2>
                <p class="text-muted mb-4">
                    Punya ide menarik atau membutuhkan bantuan dalam pembuatan aplikasi web? Tinggalkan pesan, saya akan segera merespons.
                </p>

                <div class="d-flex flex-column gap-3">
                    <div class="glass-card glass-card-hover glass-card-pad d-flex align-items-start gap-3">
                        <div class="bg-primary bg-opacity-10 text-primary p-2 rounded-3 fs-5">
                            <i class="bi bi-envelope"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold mb-1">Email</h6>
                            <a href="mailto:heruperdanaaa@gmail.com" class="text-muted small text-decoration-none">heruperdanaaa@gmail.com</a>
                        </div>
                    </div>

                    <div class="glass-card glass-card-hover glass-card-pad d-flex align-items-start gap-3">
                        <div class="bg-info bg-opacity-10 text-info p-2 rounded-3 fs-5">
                            <i class="bi bi-geo-alt"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold mb-1">Lokasi</h6>
                            <p class="text-muted small mb-0">Indonesia (Remote Friendly)</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Formulir Kontak -->
            <div class="col-lg-7">
                <div class="glass-card glass-card-pad p-md-5">
                    <h4 class="fw-bold mb-3">Kirim Pesan</h4>

                    <!-- Notifikasi Pesan -->
                    <?php if ($alertMsg): ?>
                        <div class="alert alert-<?= htmlspecialchars($alertType); ?> alert-dismissible fade show rounded-3 d-flex align-items-center gap-2 mb-4" role="alert">
                            <?php if ($alertType === 'success'): ?>
                                <i class="bi bi-check-circle-fill fs-5 text-success"></i>
                            <?php elseif ($alertType === 'warning'): ?>
                                <i class="bi bi-exclamation-triangle-fill fs-5 text-warning"></i>
                            <?php else: ?>
                                <i class="bi bi-x-circle-fill fs-5 text-danger"></i>
                            <?php endif; ?>
                            <div><?= htmlspecialchars($alertMsg); ?></div>
                            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <form action="index.php" method="POST">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="name" class="form-label small fw-semibold">Nama Lengkap <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="name" name="name" placeholder="Nama Anda" value="<?= htmlspecialchars($oldData['name'] ?? ''); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="email" class="form-label small fw-semibold">Alamat Email <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" id="email" name="email" placeholder="nama@email.com" value="<?= htmlspecialchars($oldData['email'] ?? ''); ?>" required>
                            </div>
                            <div class="col-12">
                                <label for="subject" class="form-label small fw-semibold">Subjek</label>
                                <input type="text" class="form-control" id="subject" name="subject" placeholder="Misal: Penawaran Pembuatan Website Portofolio" value="<?= htmlspecialchars($oldData['subject'] ?? ''); ?>">
                            </div>
                            <div class="col-12">
                                <label for="message" class="form-label small fw-semibold">Pesan <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="message" name="message" rows="5" placeholder="Tuliskan pesan atau detail proyek Anda di sini..." required><?= htmlspecialchars($oldData['message'] ?? ''); ?></textarea>
                            </div>
                            <div class="col-12">
                                <button type="submit" name="send_message" class="btn btn-primary btn-lg rounded-pill px-4 w-100 shadow-sm d-flex align-items-center justify-content-center gap-2">
                                    <i class="bi bi-send"></i> Kirim Pesan Sekarang
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
