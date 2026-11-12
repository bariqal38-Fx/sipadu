<?php
require_once __DIR__ . '/../includes/functions.php';

require_admin();
$pageTitle = 'Kelola Kategori';

$editId = (int) ($_GET['edit'] ?? 0);
$editKategori = ['id' => 0, 'nama' => '', 'deskripsi' => ''];
$errors = [];

if ($editId > 0) {
    $stmt = db()->prepare('SELECT id, nama, deskripsi FROM kategori WHERE id = ? LIMIT 1');
    $stmt->execute([$editId]);
    $found = $stmt->fetch();

    if ($found !== false) {
        $editKategori = $found;
    } else {
        $editId = 0;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'simpan') {
        $id = (int) ($_POST['id'] ?? 0);
        $nama = trim((string) ($_POST['nama'] ?? ''));
        $deskripsi = trim((string) ($_POST['deskripsi'] ?? ''));

        if (mb_strlen($nama) < 3 || mb_strlen($nama) > 80) {
            $errors[] = 'Nama kategori harus terdiri dari 3 sampai 80 karakter.';
        }

        if (mb_strlen($deskripsi) > 500) {
            $errors[] = 'Deskripsi maksimal 500 karakter.';
        }

        if ($errors === []) {
            $stmt = db()->prepare('SELECT COUNT(*) FROM kategori WHERE nama = ? AND id <> ?');
            $stmt->execute([$nama, $id]);

            if ((int) $stmt->fetchColumn() > 0) {
                $errors[] = 'Nama kategori sudah digunakan.';
            }
        }

        if ($errors === []) {
            if ($id > 0) {
                $stmt = db()->prepare('UPDATE kategori SET nama = ?, deskripsi = ? WHERE id = ?');
                $stmt->execute([$nama, $deskripsi !== '' ? $deskripsi : null, $id]);
                set_flash('success', 'Kategori berhasil diperbarui.');
            } else {
                $stmt = db()->prepare('INSERT INTO kategori (nama, deskripsi) VALUES (?, ?)');
                $stmt->execute([$nama, $deskripsi !== '' ? $deskripsi : null]);
                set_flash('success', 'Kategori baru berhasil ditambahkan.');
            }

            redirect('/admin/kategori.php');
        }

        $editId = $id;
        $editKategori = ['id' => $id, 'nama' => $nama, 'deskripsi' => $deskripsi];
    }

    if ($action === 'hapus') {
        $id = (int) ($_POST['id'] ?? 0);

        $stmt = db()->prepare('SELECT COUNT(*) FROM pengaduan WHERE kategori_id = ?');
        $stmt->execute([$id]);

        if ((int) $stmt->fetchColumn() > 0) {
            set_flash('danger', 'Kategori tidak dapat dihapus karena masih digunakan oleh pengaduan.');
        } else {
            $stmt = db()->prepare('DELETE FROM kategori WHERE id = ?');
            $stmt->execute([$id]);
            set_flash('success', 'Kategori berhasil dihapus.');
        }

        redirect('/admin/kategori.php');
    }
}

$kategoriList = db()->query(
    'SELECT k.*, (SELECT COUNT(*) FROM pengaduan p WHERE p.kategori_id = k.id) AS jumlah
     FROM kategori k
     ORDER BY k.nama ASC'
)->fetchAll();

require __DIR__ . '/../includes/header.php';
?>
<main class="py-4 py-lg-5">
    <div class="container">
        <div class="page-header">
            <div>
                <h1>Kelola Kategori</h1>
                <p>Atur kategori pengaduan yang tersedia untuk warga sekolah.</p>
            </div>
        </div>

        <?php admin_tabs('kategori'); ?>

        <?php if ($errors !== []): ?>
            <div class="alert alert-danger">
                <ul class="mb-0 ps-3">
                    <?php foreach ($errors as $error): ?>
                        <li><?= e($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <div class="row g-4">
            <div class="col-lg-7">
                <div class="table-card">
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                                <tr>
                                    <th>Kategori</th>
                                    <th>Pengaduan</th>
                                    <th class="text-end">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($kategoriList as $kategori): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-semibold"><?= e($kategori['nama']) ?></div>
                                            <div class="text-muted small"><?= e($kategori['deskripsi'] ?: 'Tanpa deskripsi.') ?></div>
                                        </td>
                                        <td><span class="badge text-bg-light"><?= (int) $kategori['jumlah'] ?></span></td>
                                        <td class="text-end">
                                            <div class="d-flex justify-content-end gap-2">
                                                <a href="<?= BASE_URL ?>/admin/kategori.php?edit=<?= (int) $kategori['id'] ?>" class="btn btn-sm btn-outline-primary">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <form method="post" action="<?= BASE_URL ?>/admin/kategori.php" class="m-0" data-confirm="Hapus kategori ini?">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="action" value="hapus">
                                                    <input type="hidden" name="id" value="<?= (int) $kategori['id'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="detail-card">
                    <h6 class="fw-bold mb-3">
                        <i class="bi <?= $editId > 0 ? 'bi-pencil-square' : 'bi-plus-circle' ?> me-2"></i>
                        <?= $editId > 0 ? 'Edit Kategori' : 'Tambah Kategori' ?>
                    </h6>
                    <form method="post" action="<?= BASE_URL ?>/admin/kategori.php">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="simpan">
                        <input type="hidden" name="id" value="<?= (int) $editKategori['id'] ?>">
                        <div class="mb-3">
                            <label class="form-label" for="nama">Nama Kategori</label>
                            <input type="text" class="form-control" id="nama" name="nama" value="<?= e($editKategori['nama']) ?>" maxlength="80" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="deskripsi">Deskripsi <span class="text-muted fw-normal">(opsional)</span></label>
                            <textarea class="form-control" id="deskripsi" name="deskripsi" rows="4" maxlength="500"><?= e($editKategori['deskripsi']) ?></textarea>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary flex-grow-1">
                                <i class="bi bi-save me-2"></i><?= $editId > 0 ? 'Simpan Perubahan' : 'Tambah Kategori' ?>
                            </button>
                            <?php if ($editId > 0): ?>
                                <a href="<?= BASE_URL ?>/admin/kategori.php" class="btn btn-outline-secondary">Batal</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?>
