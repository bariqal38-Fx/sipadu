<?php
require_once __DIR__ . '/../includes/functions.php';

require_login();
$user = current_user();
$pageTitle = 'Detail Pengaduan';

$id = (int) ($_GET['id'] ?? 0);

$stmt = db()->prepare(
    'SELECT p.*, k.nama AS kategori, u.nama AS pelapor, u.kelas, u.username
     FROM pengaduan p
     JOIN kategori k ON k.id = p.kategori_id
     JOIN users u ON u.id = p.user_id
     WHERE p.id = ?
     LIMIT 1'
);
$stmt->execute([$id]);
$pengaduan = $stmt->fetch();

if ($pengaduan === false) {
    http_response_code(404);
    $pageTitle = 'Pengaduan Tidak Ditemukan';
    require __DIR__ . '/../includes/header.php';
    ?>
    <main class="py-5">
        <div class="container">
            <div class="empty-state">
                <i class="bi bi-search"></i>
                <h5>Pengaduan tidak ditemukan</h5>
                <p>Data pengaduan yang Anda cari tidak tersedia atau telah dihapus.</p>
                <a href="<?= BASE_URL ?>/pengaduan/index.php" class="btn btn-primary">Kembali ke Daftar</a>
            </div>
        </div>
    </main>
    <?php
    require __DIR__ . '/../includes/footer.php';
    exit;
}

$isAdmin = $user['role'] === 'admin';
$isOwner = (int) $pengaduan['user_id'] === (int) $user['id'];

if (!$isAdmin && !$isOwner) {
    http_response_code(403);
    $pageTitle = 'Akses Ditolak';
    require __DIR__ . '/../includes/header.php';
    ?>
    <main class="py-5">
        <div class="container">
            <div class="empty-state">
                <i class="bi bi-shield-x"></i>
                <h5>Akses ditolak</h5>
                <p>Anda tidak memiliki izin untuk melihat pengaduan milik pengguna lain.</p>
                <a href="<?= BASE_URL ?>/dashboard.php" class="btn btn-primary">Kembali ke Dashboard</a>
            </div>
        </div>
    </main>
    <?php
    require __DIR__ . '/../includes/footer.php';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'ubah_status' && $isAdmin) {
        $status = (string) ($_POST['status'] ?? '');
        $allowedStatus = ['baru', 'diproses', 'selesai', 'ditolak'];

        if (!in_array($status, $allowedStatus, true)) {
            set_flash('danger', 'Status yang dipilih tidak valid.');
        } else {
            $stmt = db()->prepare('UPDATE pengaduan SET status = ? WHERE id = ?');
            $stmt->execute([$status, $id]);
            set_flash('success', 'Status pengaduan berhasil diperbarui menjadi ' . status_label($status) . '.');
        }

        redirect('/pengaduan/detail.php?id=' . $id);
    }

    if ($action === 'tanggapi' && $isAdmin) {
        $isi = trim((string) ($_POST['isi'] ?? ''));

        if (mb_strlen($isi) < 5) {
            set_flash('danger', 'Tanggapan minimal 5 karakter.');
        } elseif (mb_strlen($isi) > 2000) {
            set_flash('danger', 'Tanggapan maksimal 2000 karakter.');
        } else {
            $stmt = db()->prepare('INSERT INTO tanggapan (pengaduan_id, user_id, isi) VALUES (?, ?, ?)');
            $stmt->execute([$id, $user['id'], $isi]);

            if ($pengaduan['status'] === 'baru') {
                $stmt = db()->prepare("UPDATE pengaduan SET status = 'diproses' WHERE id = ?");
                $stmt->execute([$id]);
            }

            set_flash('success', 'Tanggapan berhasil dikirim.');
        }

        redirect('/pengaduan/detail.php?id=' . $id);
    }

    if ($action === 'umpan_balik' && $isOwner) {
        if ($pengaduan['status'] !== 'selesai') {
            set_flash('danger', 'Umpan balik hanya dapat diberikan setelah pengaduan selesai.');
            redirect('/pengaduan/detail.php?id=' . $id);
        }

        $rating = (int) ($_POST['rating'] ?? 0);
        $komentar = trim((string) ($_POST['komentar'] ?? ''));

        if ($rating < 1 || $rating > 5) {
            set_flash('danger', 'Silakan pilih rating 1 sampai 5 bintang.');
        } elseif (mb_strlen($komentar) > 500) {
            set_flash('danger', 'Komentar maksimal 500 karakter.');
        } else {
            $stmt = db()->prepare('SELECT COUNT(*) FROM umpan_balik WHERE pengaduan_id = ?');
            $stmt->execute([$id]);

            if ((int) $stmt->fetchColumn() > 0) {
                set_flash('warning', 'Umpan balik untuk pengaduan ini sudah pernah dikirim.');
            } else {
                $stmt = db()->prepare('INSERT INTO umpan_balik (pengaduan_id, rating, komentar) VALUES (?, ?, ?)');
                $stmt->execute([$id, $rating, $komentar !== '' ? $komentar : null]);
                set_flash('success', 'Terima kasih, umpan balik Anda telah tersimpan.');
            }
        }

        redirect('/pengaduan/detail.php?id=' . $id);
    }
}

$stmt = db()->prepare(
    'SELECT t.*, u.nama, u.role
     FROM tanggapan t
     JOIN users u ON u.id = t.user_id
     WHERE t.pengaduan_id = ?
     ORDER BY t.created_at ASC'
);
$stmt->execute([$id]);
$tanggapanList = $stmt->fetchAll();

$stmt = db()->prepare('SELECT * FROM umpan_balik WHERE pengaduan_id = ? LIMIT 1');
$stmt->execute([$id]);
$umpanBalik = $stmt->fetch();

$isAnonim = (int) $pengaduan['is_anonim'] === 1;
$pelaporTampil = $isAnonim && $isAdmin ? 'Anonim' : $pengaduan['pelapor'];
$kelasTampil = $isAnonim && $isAdmin ? '-' : ($pengaduan['kelas'] ?: '-');
$lampiran = $pengaduan['lampiran'];
$lampiranExt = $lampiran !== null ? strtolower(pathinfo($lampiran, PATHINFO_EXTENSION)) : '';
$lampiranIsImage = in_array($lampiranExt, ['jpg', 'jpeg', 'png'], true);

require __DIR__ . '/../includes/header.php';
?>
<main class="py-4 py-lg-5">
    <div class="container">
        <nav aria-label="breadcrumb" class="mb-3">
            <ol class="breadcrumb small mb-0">
                <li class="breadcrumb-item">
                    <a href="<?= BASE_URL ?>/<?= $isAdmin ? 'admin/pengaduan.php' : 'pengaduan/index.php' ?>">
                        <?= $isAdmin ? 'Kelola Pengaduan' : 'Pengaduan Saya' ?>
                    </a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">Detail</li>
            </ol>
        </nav>

        <div class="row g-4">
            <div class="col-lg-8">
                <div class="detail-card mb-4">
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <span class="badge-soft"><?= e($pengaduan['kategori']) ?></span>
                        <?= status_badge($pengaduan['status']) ?>
                        <?php if ($isAnonim): ?>
                            <span class="badge text-bg-light"><i class="bi bi-incognito me-1"></i>Anonim</span>
                        <?php endif; ?>
                    </div>
                    <h1 class="detail-title"><?= e($pengaduan['judul']) ?></h1>
                    <div class="detail-meta">
                        <span><i class="bi bi-person"></i><?= e($pelaporTampil) ?></span>
                        <span><i class="bi bi-calendar3"></i><?= e(tanggal_id($pengaduan['created_at'])) ?></span>
                        <?php if ($pengaduan['lokasi']): ?>
                            <span><i class="bi bi-geo-alt"></i><?= e($pengaduan['lokasi']) ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="detail-body"><?= e($pengaduan['isi']) ?></div>

                    <?php if ($lampiran !== null): ?>
                        <hr class="my-4">
                        <h6 class="fw-bold mb-3"><i class="bi bi-paperclip me-2"></i>Lampiran</h6>
                        <?php if ($lampiranIsImage): ?>
                            <a href="<?= BASE_URL ?>/uploads/<?= e($lampiran) ?>" target="_blank" rel="noopener">
                                <img src="<?= BASE_URL ?>/uploads/<?= e($lampiran) ?>" alt="Lampiran pengaduan" class="img-fluid rounded border" style="max-height: 340px;">
                            </a>
                        <?php else: ?>
                            <a href="<?= BASE_URL ?>/uploads/<?= e($lampiran) ?>" target="_blank" rel="noopener" class="btn btn-outline-primary">
                                <i class="bi bi-file-earmark-pdf me-2"></i>Buka Lampiran PDF
                            </a>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>

                <div class="detail-card">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="fw-bold mb-0"><i class="bi bi-chat-square-text me-2"></i>Riwayat Tanggapan</h5>
                        <span class="badge text-bg-light"><?= count($tanggapanList) ?> tanggapan</span>
                    </div>

                    <?php if ($tanggapanList === []): ?>
                        <div class="text-center text-muted py-4">
                            <i class="bi bi-hourglass-split fs-3 d-block mb-2"></i>
                            <p class="mb-0">Belum ada tanggapan dari admin. Pengaduan Anda sedang menunggu ditinjau.</p>
                        </div>
                    <?php else: ?>
                        <ul class="timeline">
                            <?php foreach ($tanggapanList as $tanggapan): ?>
                                <li class="timeline-item <?= $tanggapan['role'] === 'admin' ? 'is-admin' : '' ?>">
                                    <div class="timeline-head">
                                        <span class="timeline-name"><?= e($tanggapan['nama']) ?></span>
                                        <span class="badge text-bg-light"><?= $tanggapan['role'] === 'admin' ? 'Admin' : 'Siswa' ?></span>
                                        <span class="timeline-time"><?= e(tanggal_id($tanggapan['created_at'])) ?></span>
                                    </div>
                                    <div class="timeline-body"><?= e($tanggapan['isi']) ?></div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="detail-card mb-4">
                    <h6 class="fw-bold mb-3"><i class="bi bi-info-circle me-2"></i>Informasi Pengaduan</h6>
                    <ul class="info-list">
                        <li><span class="label">Status</span><span class="value"><?= status_badge($pengaduan['status']) ?></span></li>
                        <li><span class="label">Kategori</span><span class="value"><?= e($pengaduan['kategori']) ?></span></li>
                        <li><span class="label">Pelapor</span><span class="value"><?= e($pelaporTampil) ?></span></li>
                        <li><span class="label">Kelas</span><span class="value"><?= e($kelasTampil) ?></span></li>
                        <li><span class="label">Dikirim</span><span class="value"><?= e(tanggal_id($pengaduan['created_at'])) ?></span></li>
                        <li><span class="label">Diperbarui</span><span class="value"><?= e(tanggal_id($pengaduan['updated_at'])) ?></span></li>
                    </ul>
                </div>

                <?php if ($isAdmin): ?>
                    <div class="detail-card mb-4">
                        <h6 class="fw-bold mb-3"><i class="bi bi-sliders me-2"></i>Ubah Status</h6>
                        <form method="post" action="<?= BASE_URL ?>/pengaduan/detail.php?id=<?= $id ?>">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="ubah_status">
                            <select class="form-select mb-3" name="status" required>
                                <?php foreach (['baru', 'diproses', 'selesai', 'ditolak'] as $statusOption): ?>
                                    <option value="<?= $statusOption ?>" <?= $pengaduan['status'] === $statusOption ? 'selected' : '' ?>>
                                        <?= status_label($statusOption) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-check2 me-2"></i>Simpan Status
                            </button>
                        </form>
                    </div>

                    <div class="detail-card">
                        <h6 class="fw-bold mb-3"><i class="bi bi-reply me-2"></i>Beri Tanggapan</h6>
                        <form method="post" action="<?= BASE_URL ?>/pengaduan/detail.php?id=<?= $id ?>">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="tanggapi">
                            <textarea class="form-control mb-3" name="isi" rows="4" maxlength="2000" placeholder="Tulis tanggapan atau informasi tindak lanjut..." required></textarea>
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-send me-2"></i>Kirim Tanggapan
                            </button>
                            <div class="form-text text-center mt-2">Status otomatis menjadi Diproses bila masih Baru.</div>
                        </form>
                    </div>
                <?php elseif ($umpanBalik !== false): ?>
                    <div class="detail-card">
                        <h6 class="fw-bold mb-3"><i class="bi bi-star me-2 text-warning"></i>Umpan Balik Anda</h6>
                        <div class="rating-display mb-2">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i class="bi <?= $i <= (int) $umpanBalik['rating'] ? 'bi-star-fill' : 'bi-star' ?>"></i>
                            <?php endfor; ?>
                        </div>
                        <p class="small text-muted mb-0">
                            <?= $umpanBalik['komentar'] !== null && $umpanBalik['komentar'] !== '' ? e($umpanBalik['komentar']) : 'Tanpa komentar.' ?>
                        </p>
                    </div>
                <?php elseif ($isOwner && $pengaduan['status'] === 'selesai'): ?>
                    <div class="detail-card">
                        <h6 class="fw-bold mb-3"><i class="bi bi-star me-2 text-warning"></i>Beri Umpan Balik</h6>
                        <p class="small text-muted">Bagaimana kualitas penanganan pengaduan ini?</p>
                        <form method="post" action="<?= BASE_URL ?>/pengaduan/detail.php?id=<?= $id ?>">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="umpan_balik">
                            <div class="rating-input mb-3">
                                <?php for ($i = 5; $i >= 1; $i--): ?>
                                    <input type="radio" id="star<?= $i ?>" name="rating" value="<?= $i ?>" <?= $i === 5 ? 'required' : '' ?>>
                                    <label for="star<?= $i ?>" title="<?= $i ?> bintang"><i class="bi bi-star-fill"></i></label>
                                <?php endfor; ?>
                            </div>
                            <textarea class="form-control mb-3" name="komentar" rows="3" maxlength="500" placeholder="Komentar (opsional)"></textarea>
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-send me-2"></i>Kirim Umpan Balik
                            </button>
                        </form>
                    </div>
                <?php else: ?>
                    <div class="detail-card">
                        <h6 class="fw-bold mb-3"><i class="bi bi-info-circle me-2"></i>Catatan</h6>
                        <p class="small text-muted mb-0">
                            Umpan balik dapat Anda berikan setelah pengaduan berstatus <strong>Selesai</strong>.
                            Admin akan memperbarui status setelah permasalahan ditindaklanjuti.
                        </p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?>
