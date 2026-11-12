<?php
require_once __DIR__ . '/../includes/functions.php';

require_admin();
$pageTitle = 'Kelola Pengaduan';

$allowedStatus = ['baru', 'diproses', 'selesai', 'ditolak'];
$statusFilter = (string) ($_GET['status'] ?? '');
$kategoriFilter = (int) ($_GET['kategori_id'] ?? 0);
$search = trim((string) ($_GET['q'] ?? ''));

if (!in_array($statusFilter, $allowedStatus, true)) {
    $statusFilter = '';
}

$kategoriList = db()->query('SELECT id, nama FROM kategori ORDER BY nama')->fetchAll();

$where = [];
$params = [];

if ($statusFilter !== '') {
    $where[] = 'p.status = ?';
    $params[] = $statusFilter;
}

if ($kategoriFilter > 0) {
    $where[] = 'p.kategori_id = ?';
    $params[] = $kategoriFilter;
}

if ($search !== '') {
    $where[] = '(p.judul LIKE ? OR p.isi LIKE ? OR u.nama LIKE ?)';
    $like = '%' . $search . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

$whereSql = $where !== [] ? ' WHERE ' . implode(' AND ', $where) : '';

$stmt = db()->prepare('SELECT COUNT(*) FROM pengaduan p JOIN users u ON u.id = p.user_id' . $whereSql);
$stmt->execute($params);
$totalData = (int) $stmt->fetchColumn();

$perPage = 10;
$totalPages = max(1, (int) ceil($totalData / $perPage));
$page = max(1, (int) ($_GET['page'] ?? 1));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

$sql = 'SELECT p.*, k.nama AS kategori, u.nama AS pelapor, u.kelas
        FROM pengaduan p
        JOIN kategori k ON k.id = p.kategori_id
        JOIN users u ON u.id = p.user_id'
        . $whereSql
        . ' ORDER BY p.created_at DESC LIMIT ' . $perPage . ' OFFSET ' . $offset;

$stmt = db()->prepare($sql);
$stmt->execute($params);
$pengaduanList = $stmt->fetchAll();

$queryParams = [];

if ($statusFilter !== '') {
    $queryParams['status'] = $statusFilter;
}

if ($kategoriFilter > 0) {
    $queryParams['kategori_id'] = (string) $kategoriFilter;
}

if ($search !== '') {
    $queryParams['q'] = $search;
}

$baseQuery = http_build_query($queryParams);

require __DIR__ . '/../includes/header.php';
?>
<main class="py-4 py-lg-5">
    <div class="container">
        <div class="page-header">
            <div>
                <h1>Kelola Pengaduan</h1>
                <p>Tinjau, tanggapi, dan perbarui status seluruh pengaduan warga sekolah.</p>
            </div>
        </div>

        <?php admin_tabs('pengaduan'); ?>

        <div class="filter-bar mb-4">
            <form method="get" action="<?= BASE_URL ?>/admin/pengaduan.php" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small mb-1" for="q">Cari</label>
                    <input type="text" class="form-control" id="q" name="q" value="<?= e($search) ?>" placeholder="Judul, isi, atau pelapor">
                </div>
                <div class="col-md-3">
                    <label class="form-label small mb-1" for="status">Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="">Semua status</option>
                        <?php foreach ($allowedStatus as $statusOption): ?>
                            <option value="<?= $statusOption ?>" <?= $statusFilter === $statusOption ? 'selected' : '' ?>>
                                <?= status_label($statusOption) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small mb-1" for="kategori_id">Kategori</label>
                    <select class="form-select" id="kategori_id" name="kategori_id">
                        <option value="">Semua kategori</option>
                        <?php foreach ($kategoriList as $kategori): ?>
                            <option value="<?= (int) $kategori['id'] ?>" <?= $kategoriFilter === (int) $kategori['id'] ? 'selected' : '' ?>>
                                <?= e($kategori['nama']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-grow-1">
                        <i class="bi bi-funnel me-1"></i>Terapkan
                    </button>
                    <a href="<?= BASE_URL ?>/admin/pengaduan.php" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-3">
            <span class="text-muted small">Menampilkan <?= count($pengaduanList) ?> dari <?= $totalData ?> pengaduan</span>
            <span class="text-muted small">Halaman <?= $page ?> dari <?= $totalPages ?></span>
        </div>

        <?php if ($pengaduanList === []): ?>
            <div class="empty-state">
                <i class="bi bi-inbox"></i>
                <h5>Tidak ada pengaduan</h5>
                <p>Tidak ditemukan pengaduan yang sesuai dengan filter yang dipilih.</p>
                <a href="<?= BASE_URL ?>/admin/pengaduan.php" class="btn btn-primary">Tampilkan Semua</a>
            </div>
        <?php else: ?>
            <div class="table-card">
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>Pelapor</th>
                                <th>Judul</th>
                                <th>Kategori</th>
                                <th>Status</th>
                                <th>Tanggal</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pengaduanList as $item): ?>
                                <tr>
                                    <td>
                                        <div class="fw-semibold"><?= e((int) $item['is_anonim'] === 1 ? 'Anonim' : $item['pelapor']) ?></div>
                                        <div class="text-muted small"><?= e((int) $item['is_anonim'] === 1 ? '-' : ($item['kelas'] ?: '-')) ?></div>
                                    </td>
                                    <td style="min-width: 240px;">
                                        <a href="<?= BASE_URL ?>/pengaduan/detail.php?id=<?= (int) $item['id'] ?>" class="fw-semibold text-dark">
                                            <?= e($item['judul']) ?>
                                        </a>
                                        <div class="text-muted small"><?= e(excerpt($item['isi'], 70)) ?></div>
                                    </td>
                                    <td><span class="badge-soft"><?= e($item['kategori']) ?></span></td>
                                    <td><?= status_badge($item['status']) ?></td>
                                    <td class="text-muted small"><?= e(tanggal_id($item['created_at'])) ?></td>
                                    <td class="text-end">
                                        <a href="<?= BASE_URL ?>/pengaduan/detail.php?id=<?= (int) $item['id'] ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-eye me-1"></i>Tinjau
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?= pagination_links($page, $totalPages, $baseQuery) ?>
        <?php endif; ?>
    </div>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?>
