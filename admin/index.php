<?php
require_once __DIR__ . '/../includes/functions.php';

require_admin();
$user = current_user();
$pageTitle = 'Dashboard Admin';

$stats = db()->query(
    "SELECT
        COUNT(*) AS total,
        SUM(CASE WHEN status = 'baru' THEN 1 ELSE 0 END) AS baru,
        SUM(CASE WHEN status = 'diproses' THEN 1 ELSE 0 END) AS diproses,
        SUM(CASE WHEN status = 'selesai' THEN 1 ELSE 0 END) AS selesai,
        SUM(CASE WHEN status = 'ditolak' THEN 1 ELSE 0 END) AS ditolak
     FROM pengaduan"
)->fetch();

$totalPengguna = (int) db()->query('SELECT COUNT(*) FROM users')->fetchColumn();
$totalKategori = (int) db()->query('SELECT COUNT(*) FROM kategori')->fetchColumn();

$perKategori = db()->query(
    'SELECT k.nama, COUNT(p.id) AS jumlah
     FROM kategori k
     LEFT JOIN pengaduan p ON p.kategori_id = k.id
     GROUP BY k.id, k.nama
     ORDER BY jumlah DESC, k.nama ASC'
)->fetchAll();

$maxKategori = 1;

foreach ($perKategori as $row) {
    if ((int) $row['jumlah'] > $maxKategori) {
        $maxKategori = (int) $row['jumlah'];
    }
}

$pengaduanTerbaru = db()->query(
    'SELECT p.*, k.nama AS kategori, u.nama AS pelapor
     FROM pengaduan p
     JOIN kategori k ON k.id = p.kategori_id
     JOIN users u ON u.id = p.user_id
     ORDER BY p.created_at DESC
     LIMIT 5'
)->fetchAll();

require __DIR__ . '/../includes/header.php';
?>
<main class="py-4 py-lg-5">
    <div class="container">
        <div class="welcome-banner mb-4">
            <div class="row align-items-center g-3">
                <div class="col-lg-8">
                    <span class="hero-badge mb-3"><i class="bi bi-shield-check"></i> Panel Administrator</span>
                    <h1>Selamat datang, <?= e($user['nama']) ?>!</h1>
                    <p>Kelola pengaduan warga sekolah, tanggapi laporan, dan pantau statistik layanan SIPADU.</p>
                </div>
                <div class="col-lg-4 text-lg-end">
                    <a href="<?= BASE_URL ?>/admin/pengaduan.php" class="btn btn-hero">
                        <i class="bi bi-inbox me-2"></i>Kelola Pengaduan
                    </a>
                </div>
            </div>
        </div>

        <?php admin_tabs('dashboard'); ?>

        <div class="row g-3 g-lg-4 mb-4">
            <div class="col-6 col-lg-3">
                <div class="stat-card">
                    <span class="stat-icon"><i class="bi bi-chat-left-text"></i></span>
                    <div>
                        <div class="stat-value"><?= (int) $stats['total'] ?></div>
                        <div class="stat-label">Total Pengaduan</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="stat-card">
                    <span class="stat-icon icon-amber"><i class="bi bi-inbox"></i></span>
                    <div>
                        <div class="stat-value"><?= (int) $stats['baru'] ?></div>
                        <div class="stat-label">Menunggu</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="stat-card">
                    <span class="stat-icon icon-violet"><i class="bi bi-arrow-repeat"></i></span>
                    <div>
                        <div class="stat-value"><?= (int) $stats['diproses'] ?></div>
                        <div class="stat-label">Diproses</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="stat-card">
                    <span class="stat-icon icon-green"><i class="bi bi-check2-circle"></i></span>
                    <div>
                        <div class="stat-value"><?= (int) $stats['selesai'] ?></div>
                        <div class="stat-label">Selesai</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-lg-5">
                <div class="detail-card h-100">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h6 class="fw-bold mb-0"><i class="bi bi-bar-chart me-2"></i>Pengaduan per Kategori</h6>
                        <span class="badge text-bg-light"><?= $totalKategori ?> kategori</span>
                    </div>
                    <?php if ($perKategori === []): ?>
                        <p class="text-muted small mb-0">Belum ada kategori.</p>
                    <?php else: ?>
                        <?php foreach ($perKategori as $row): ?>
                            <?php $lebar = ((int) $row['jumlah'] / $maxKategori) * 100; ?>
                            <div class="chart-row">
                                <div class="chart-label">
                                    <span><?= e($row['nama']) ?></span>
                                    <strong><?= (int) $row['jumlah'] ?></strong>
                                </div>
                                <div class="progress">
                                    <div class="progress-bar" style="width: <?= round($lebar, 1) ?>%"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-lg-7">
                <div class="detail-card h-100">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="fw-bold mb-0"><i class="bi bi-clock-history me-2"></i>Pengaduan Terbaru</h6>
                        <a href="<?= BASE_URL ?>/admin/pengaduan.php" class="small fw-semibold">Lihat semua</a>
                    </div>
                    <?php if ($pengaduanTerbaru === []): ?>
                        <p class="text-muted small mb-0">Belum ada pengaduan masuk.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Judul</th>
                                        <th>Pelapor</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pengaduanTerbaru as $item): ?>
                                        <tr>
                                            <td>
                                                <a href="<?= BASE_URL ?>/pengaduan/detail.php?id=<?= (int) $item['id'] ?>" class="fw-semibold text-dark d-block">
                                                    <?= e(excerpt($item['judul'], 45)) ?>
                                                </a>
                                                <span class="text-muted small"><?= e($item['kategori']) ?> &middot; <?= e(tanggal_id($item['created_at'])) ?></span>
                                            </td>
                                            <td class="small"><?= e((int) $item['is_anonim'] === 1 ? 'Anonim' : $item['pelapor']) ?></td>
                                            <td><?= status_badge($item['status']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="row g-3 g-lg-4">
            <div class="col-md-4">
                <div class="stat-card">
                    <span class="stat-icon icon-rose"><i class="bi bi-x-circle"></i></span>
                    <div>
                        <div class="stat-value"><?= (int) $stats['ditolak'] ?></div>
                        <div class="stat-label">Pengaduan Ditolak</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <span class="stat-icon icon-violet"><i class="bi bi-people"></i></span>
                    <div>
                        <div class="stat-value"><?= $totalPengguna ?></div>
                        <div class="stat-label">Total Pengguna</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <span class="stat-icon icon-green"><i class="bi bi-tags"></i></span>
                    <div>
                        <div class="stat-value"><?= $totalKategori ?></div>
                        <div class="stat-label">Kategori Layanan</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?>
