<?php
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Beranda';

$totalPengaduan = (int) db()->query('SELECT COUNT(*) FROM pengaduan')->fetchColumn();
$totalSelesai = (int) db()->query("SELECT COUNT(*) FROM pengaduan WHERE status = 'selesai'")->fetchColumn();
$totalKategori = (int) db()->query('SELECT COUNT(*) FROM kategori')->fetchColumn();
$totalWarga = (int) db()->query("SELECT COUNT(*) FROM users WHERE role = 'siswa'")->fetchColumn();

$kategoriList = db()->query(
    'SELECT k.id, k.nama, k.deskripsi, (SELECT COUNT(*) FROM pengaduan p WHERE p.kategori_id = k.id) AS jumlah
     FROM kategori k
     ORDER BY k.id'
)->fetchAll();

$icons = ['bi-building-gear', 'bi-trash3', 'bi-shield-check', 'bi-mortarboard', 'bi-cup-hot', 'bi-three-dots'];
$colors = ['', 'cat-green', 'cat-amber', 'cat-rose', 'cat-violet', 'cat-cyan'];

require __DIR__ . '/includes/header.php';
?>
<main>
    <section class="hero">
        <div class="container">
            <span class="hero-badge"><i class="bi bi-broadcast"></i> Kanal Resmi Aspirasi Warga Sekolah</span>
            <h1>Suara Warga<br><span class="highlight">SMKN 5 Malang</span></h1>
            <p class="lead">
                Sampaikan pengaduan, keluhan, dan aspirasi Anda secara mudah, aman, dan transparan.
                Pantau tindak lanjutnya secara langsung sampai permasalahan selesai.
            </p>
            <div class="hero-actions">
                <a href="<?= BASE_URL ?>/pengaduan/tambah.php" class="btn btn-hero">
                    <i class="bi bi-send me-2"></i>Kirim Pengaduan
                </a>
                <a href="#alur" class="btn btn-hero-outline">
                    <i class="bi bi-diagram-3 me-2"></i>Lihat Alur
                </a>
            </div>
            <div class="hero-stats">
                <div class="hero-stat">
                    <strong><?= number_format($totalPengaduan, 0, ',', '.') ?></strong>
                    <span>Pengaduan Masuk</span>
                </div>
                <div class="hero-stat">
                    <strong><?= number_format($totalSelesai, 0, ',', '.') ?></strong>
                    <span>Pengaduan Selesai</span>
                </div>
                <div class="hero-stat">
                    <strong><?= number_format($totalKategori, 0, ',', '.') ?></strong>
                    <span>Kategori Layanan</span>
                </div>
            </div>
        </div>
    </section>

    <section class="section" id="kategori">
        <div class="container">
            <div class="text-center mb-5">
                <span class="eyebrow">Kategori Pengaduan</span>
                <h2 class="section-title">Sampaikan Sesuai Kebutuhan</h2>
                <p class="section-subtitle">
                    Pilih kategori yang paling sesuai agar pengaduan dapat diteruskan
                    kepada pihak yang tepat dengan cepat.
                </p>
            </div>
            <div class="row g-4">
                <?php foreach ($kategoriList as $index => $kategori): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="category-card card-soft">
                            <span class="category-icon <?= e($colors[$index % count($colors)]) ?>">
                                <i class="bi <?= e($icons[$index % count($icons)]) ?>"></i>
                            </span>
                            <h5><?= e($kategori['nama']) ?></h5>
                            <p><?= e($kategori['deskripsi'] ?: 'Kategori pengaduan warga sekolah.') ?></p>
                            <span class="badge-soft mt-3 d-inline-block"><?= (int) $kategori['jumlah'] ?> pengaduan</span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="section-tight" id="alur">
        <div class="container">
            <div class="text-center mb-5">
                <span class="eyebrow">Cara Kerja</span>
                <h2 class="section-title">Empat Langkah Mudah</h2>
                <p class="section-subtitle">Dari laporan masuk hingga selesai ditindaklanjuti.</p>
            </div>
            <div class="row g-4">
                <div class="col-md-6 col-lg-3">
                    <div class="step-card">
                        <span class="step-number">1</span>
                        <h5>Daftar dan Masuk</h5>
                        <p>Buat akun menggunakan NIS atau username, lalu masuk ke sistem SIPADU.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="step-card">
                        <span class="step-number">2</span>
                        <h5>Tulis Pengaduan</h5>
                        <p>Isi judul, kategori, lokasi, dan detail permasalahan. Lampiran foto atau PDF didukung.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="step-card">
                        <span class="step-number">3</span>
                        <h5>Admin Menindaklanjuti</h5>
                        <p>Admin sekolah memverifikasi, memberi tanggapan, dan memperbarui status penanganan.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="step-card">
                        <span class="step-number">4</span>
                        <h5>Pantau dan Nilai</h5>
                        <p>Pantau status secara real-time dan berikan umpan balik setelah pengaduan selesai.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <div class="row g-4 align-items-center">
                <div class="col-lg-5">
                    <span class="eyebrow">Mengapa SIPADU</span>
                    <h2 class="section-title">Transparan, Aman, dan Mudah Diakses</h2>
                    <p class="text-muted mb-4">
                        SIPADU dirancang khusus untuk warga SMKN 5 Malang agar setiap masukan
                        tercatat rapi, terdokumentasi, dan ditindaklanjuti secara terukur.
                    </p>
                    <a href="<?= BASE_URL ?>/register.php" class="btn btn-primary px-4">
                        <i class="bi bi-person-plus me-2"></i>Buat Akun Sekarang
                    </a>
                </div>
                <div class="col-lg-7">
                    <div class="row g-4">
                        <div class="col-sm-6">
                            <div class="feature-card">
                                <i class="bi bi-incognito"></i>
                                <h5 class="mt-3 mb-2">Opsi Anonim</h5>
                                <p class="text-muted small mb-0">Sembunyikan identitas pelapor saat pengaduan ditampilkan.</p>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="feature-card">
                                <i class="bi bi-paperclip"></i>
                                <h5 class="mt-3 mb-2">Bukti Lampiran</h5>
                                <p class="text-muted small mb-0">Unggah foto atau dokumen PDF sebagai bukti pendukung laporan.</p>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="feature-card">
                                <i class="bi bi-clock-history"></i>
                                <h5 class="mt-3 mb-2">Riwayat Lengkap</h5>
                                <p class="text-muted small mb-0">Semua laporan dan tanggapan tersimpan dalam satu riwayat.</p>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="feature-card">
                                <i class="bi bi-phone"></i>
                                <h5 class="mt-3 mb-2">Responsif</h5>
                                <p class="text-muted small mb-0">Nyaman digunakan melalui komputer, tablet, maupun ponsel.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="section-tight">
        <div class="container">
            <div class="cta-banner">
                <div>
                    <h3>Punya keluhan atau ide untuk sekolah?</h3>
                    <p>Jangan diamkan. Sampaikan melalui SIPADU dan pantau tindak lanjutnya.</p>
                </div>
                <a href="<?= BASE_URL ?>/pengaduan/tambah.php" class="btn btn-hero btn-lg">
                    <i class="bi bi-megaphone me-2"></i>Mulai Melapor
                </a>
            </div>
        </div>
    </section>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
