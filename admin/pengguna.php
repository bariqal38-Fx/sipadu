<?php
require_once __DIR__ . '/../includes/functions.php';

require_admin();
$pageTitle = 'Kelola Pengguna';

$search = trim((string) ($_GET['q'] ?? ''));

$sql = 'SELECT u.*, (SELECT COUNT(*) FROM pengaduan p WHERE p.user_id = u.id) AS jumlah_pengaduan
        FROM users u';
$params = [];

if ($search !== '') {
    $sql .= ' WHERE u.nama LIKE ? OR u.username LIKE ? OR u.kelas LIKE ?';
    $like = '%' . $search . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

$sql .= " ORDER BY CASE WHEN u.role = 'admin' THEN 0 ELSE 1 END, u.nama ASC";

$stmt = db()->prepare($sql);
$stmt->execute($params);
$penggunaList = $stmt->fetchAll();

$totalSiswa = (int) db()->query("SELECT COUNT(*) FROM users WHERE role = 'siswa'")->fetchColumn();
$totalAdmin = (int) db()->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();

require __DIR__ . '/../includes/header.php';
?>
<main class="py-4 py-lg-5">
    <div class="container">
        <div class="page-header">
            <div>
                <h1>Kelola Pengguna</h1>
                <p>Daftar akun siswa dan administrator yang terdaftar pada SIPADU.</p>
            </div>
        </div>

        <?php admin_tabs('pengguna'); ?>

        <div class="row g-3 g-lg-4 mb-4">
            <div class="col-md-4">
                <div class="stat-card">
                    <span class="stat-icon"><i class="bi bi-people"></i></span>
                    <div>
                        <div class="stat-value"><?= count($penggunaList) ?></div>
                        <div class="stat-label">Akun Ditampilkan</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <span class="stat-icon icon-green"><i class="bi bi-mortarboard"></i></span>
                    <div>
                        <div class="stat-value"><?= $totalSiswa ?></div>
                        <div class="stat-label">Akun Siswa</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <span class="stat-icon icon-violet"><i class="bi bi-shield-lock"></i></span>
                    <div>
                        <div class="stat-value"><?= $totalAdmin ?></div>
                        <div class="stat-label">Akun Admin</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="filter-bar mb-4">
            <form method="get" action="<?= BASE_URL ?>/admin/pengguna.php" class="row g-2 align-items-end">
                <div class="col-md-9">
                    <label class="form-label small mb-1" for="q">Cari Pengguna</label>
                    <input type="text" class="form-control" id="q" name="q" value="<?= e($search) ?>" placeholder="Nama, username, atau kelas">
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-grow-1"><i class="bi bi-search me-1"></i>Cari</button>
                    <a href="<?= BASE_URL ?>/admin/pengguna.php" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>

        <?php if ($penggunaList === []): ?>
            <div class="empty-state">
                <i class="bi bi-person-x"></i>
                <h5>Pengguna tidak ditemukan</h5>
                <p>Tidak ada akun yang sesuai dengan kata kunci pencarian.</p>
            </div>
        <?php else: ?>
            <div class="table-card">
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>Nama</th>
                                <th>Username</th>
                                <th>Kelas</th>
                                <th>No. HP</th>
                                <th>Role</th>
                                <th>Pengaduan</th>
                                <th>Terdaftar</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($penggunaList as $pengguna): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="avatar"><?= e(strtoupper(mb_substr($pengguna['nama'], 0, 1))) ?></span>
                                            <span class="fw-semibold"><?= e($pengguna['nama']) ?></span>
                                        </div>
                                    </td>
                                    <td class="text-muted">@<?= e($pengguna['username']) ?></td>
                                    <td><?= e($pengguna['kelas'] ?: '-') ?></td>
                                    <td class="text-muted small"><?= e($pengguna['no_hp'] ?: '-') ?></td>
                                    <td>
                                        <?php if ($pengguna['role'] === 'admin'): ?>
                                            <span class="badge text-bg-primary">Admin</span>
                                        <?php else: ?>
                                            <span class="badge text-bg-light">Siswa</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><span class="badge-soft"><?= (int) $pengguna['jumlah_pengaduan'] ?></span></td>
                                    <td class="text-muted small"><?= e(date('d/m/Y', strtotime($pengguna['created_at']))) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <p class="text-muted small mt-3 mb-0">
                <i class="bi bi-info-circle me-1"></i>
                Data pengguna bersifat baca-saja. Akun siswa dibuat melalui halaman registrasi.
            </p>
        <?php endif; ?>
    </div>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?>
