<?php
require_once __DIR__ . '/../includes/functions.php';

require_login();
$user = current_user();
$pageTitle = 'Pengaduan Saya';

$allowedStatus = ['baru', 'diproses', 'selesai', 'ditolak'];
$statusFilter = (string) ($_GET['status'] ?? '');

if (!in_array($statusFilter, $allowedStatus, true)) {
    $statusFilter = '';
}

$stmt = db()->prepare('SELECT status, COUNT(*) AS jumlah FROM pengaduan WHERE user_id = ? GROUP BY status');
$stmt->execute([$user['id']]);
$counts = ['baru' => 0, 'diproses' => 0, 'selesai' => 0, 'ditolak' => 0];
$total = 0;

foreach ($stmt->fetchAll() as $row) {
    $counts[$row['status']] = (int) $row['jumlah'];
    $total += (int) $row['jumlah'];
}

$sql = 'SELECT p.*, k.nama AS kategori,
            (SELECT COUNT(*) FROM tanggapan t WHERE t.pengaduan_id = p.id) AS jumlah_tanggapan
        FROM pengaduan p
        JOIN kategori k ON k.id = p.kategori_id
        WHERE p.user_id = ?';
$params = [$user['id']];

if ($statusFilter !== '') {
    $sql .= ' AND p.status = ?';
    $params[] = $statusFilter;
}

$sql .= ' ORDER BY p.created_at DESC';

$stmt = db()->prepare($sql);
$stmt->execute($params);
$pengaduanList = $stmt->fetchAll();

$filters = [
    '' => ['Semua', $total],
    'baru' => ['Baru', $counts['baru']],
    'diproses' => ['Diproses', $counts['diproses']],
    'selesai' => ['Selesai', $counts['selesai']],
    'ditolak' => ['Ditolak', $counts['ditolak']],
];

require __DIR__ . '/../includes/header.php';
?>
<main class="py-4 py-lg-5">
    <div class="container">
        <div class="page-header">
            <div>
                <h1>Pengaduan Saya</h1>
                <p>Seluruh pengaduan yang pernah Anda kirimkan beserta status penanganannya.</p>
            </div>
            <a href="<?= BASE_URL ?>/pengaduan/tambah.php" class="btn btn-primary">
                <i class="bi bi-plus-circle me-2"></i>Pengaduan Baru
            </a>
        </div>

        <div class="d-flex flex-wrap gap-2 mb-4">
            <?php foreach ($filters as $key => $filter): ?>
                <a href="<?= BASE_URL ?>/pengaduan/index.php<?= $key !== '' ? '?status=' . e($key) : '' ?>"
                   class="btn btn-sm <?= $statusFilter === $key ? 'btn-primary' : 'btn-outline-secondary' ?> rounded-pill">
                    <?= e($filter[0]) ?>
                    <span class="badge text-bg-light ms-1"><?= (int) $filter[1] ?></span>
                </a>
            <?php endforeach; ?>
        </div>

        <?php if ($pengaduanList === []): ?>
            <div class="empty-state">
                <i class="bi bi-inbox"></i>
                <h5>Belum ada pengaduan<?= $statusFilter !== '' ? ' dengan status ini' : '' ?></h5>
                <p>Mulai sampaikan pengaduan atau aspirasi Anda melalui SIPADU.</p>
                <a href="<?= BASE_URL ?>/pengaduan/tambah.php" class="btn btn-primary">
                    <i class="bi bi-send me-2"></i>Kirim Pengaduan
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
                                <th>Tanggapan</th>
                                <th>Tanggal</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pengaduanList as $item): ?>
                                <tr>
                                    <td style="min-width: 220px;">
                                        <div class="fw-semibold">
                                            <?= e($item['judul']) ?>
                                            <?php if ((int) $item['is_anonim'] === 1): ?>
                                                <span class="badge text-bg-light ms-1" title="Dikirim sebagai anonim"><i class="bi bi-incognito"></i></span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="text-muted small"><?= e(excerpt($item['isi'], 80)) ?></div>
                                    </td>
                                    <td><span class="badge-soft"><?= e($item['kategori']) ?></span></td>
                                    <td><?= status_badge($item['status']) ?></td>
                                    <td class="text-muted small">
                                        <i class="bi bi-chat-dots me-1"></i><?= (int) $item['jumlah_tanggapan'] ?>
                                    </td>
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
<?php require __DIR__ . '/../includes/footer.php'; ?>
