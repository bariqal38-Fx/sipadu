<?php
require_once __DIR__ . '/includes/functions.php';

require_login();
$user = current_user();

if ($user['role'] === 'admin') {
    redirect('/admin/index.php');
}

$pageTitle = 'Dashboard';

$stmt = db()->prepare(
    'SELECT
        COUNT(*) AS total,
        SUM(status = "baru") AS baru,
        SUM(status = "diproses") AS diproses,
        SUM(status = "selesai") AS selesai
     FROM pengaduan WHERE user_id = ?'
);
$stmt->execute([$user['id']]);
$stats = $stmt->fetch();

$stmt = db()->prepare(
    'SELECT p.*, k.nama AS kategori
     FROM pengaduan p
     JOIN kategori k ON k.id = p.kategori_id
     WHERE p.user_id = ?
     ORDER BY p.created_at DESC
     LIMIT 5'
);
$stmt->execute([$user['id']]);
$pengaduanTerbaru = $stmt->fetchAll();

require __DIR__ . '/includes/header.php';
?>
<main class="py-4 py-lg-5">
    <div class="container">
        <div class="welcome-banner mb-4">
            <div class="row align-items-center g-3">
                <div class="col-lg-8">
                    <h1>Halo, <?= e($user['nama']) ?>!</h1>
                    <p>
                        <?= $user['kelas'] !== null && $user['kelas'] !== '' ? e($user['kelas']) . ' &middot; ' : '' ?>
                        Pantau pengaduan Anda dan sampaikan aspirasi terbaru untuk sekolah.
                    </p>
                </div>
                <div class="col-lg-4 text-lg-end">
                    <a href="<?= BASE_URL ?>/pengaduan/tambah.php" class="btn btn-hero">
                        <i class="bi bi-plus-circle me-2"></i>Pengaduan Baru
                    </a>
                </div>
            </div>
        </div>

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
                    <span class="stat-icon icon-amber"><i class="bi bi-hourglass-split"></i></span>
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

        <div class="page-header">
            <div>
                <h1>Pengaduan Terbaru</h1>
                <p>Lima pengaduan terakhir yang Anda kirimkan.</p>
            </div>
            <a href="<?= BASE_URL ?>/pengaduan/index.php" class="btn btn-outline-primary">
                Lihat Semua <i class="bi bi-arrow-right ms-1"></i>
            </a>
        </div>

        <?php if ($pengaduanTerbaru === []): ?>
            <div class="empty-state">
                <i class="bi bi-inbox"></i>
                <h5>Belum ada pengaduan</h5>
                <p>Anda belum pernah mengirim pengaduan. Mulai sampaikan aspirasi Anda sekarang.</p>
                <a href="<?= BASE_URL ?>/pengaduan/tambah.php" class="btn btn-primary">
                    <i class="bi bi-send me-2"></i>Kirim Pengaduan Pertama
                </a>
            </div>
        <?php else: ?>
            <div class="table-card">
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>Judul</th>
                                <th>Kategori</th>
                                <th>Status</th>
                                <th>Tanggal</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pengaduanTerbaru as $item): ?>
                                <tr>
                                    <td>
                                        <div class="fw-semibold"><?= e($item['judul']) ?></div>
                                        <div class="text-muted small"><?= e(excerpt($item['isi'], 70)) ?></div>
                                    </td>
                                    <td><span class="badge-soft"><?= e($item['kategori']) ?></span></td>
                                    <td><?= status_badge($item['status']) ?></td>
                                    <td class="text-muted small"><?= e(tanggal_id($item['created_at'])) ?></td>
                                    <td class="text-end">
                                        <a href="<?= BASE_URL ?>/pengaduan/detail.php?id=<?= (int) $item['id'] ?>" class="btn btn-sm btn-outline-primary">Detail</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
