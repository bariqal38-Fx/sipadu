<?php
require_once __DIR__ . '/../includes/functions.php';

require_login();
$user = current_user();
$pageTitle = 'Kirim Pengaduan';

$kategoriList = db()->query('SELECT id, nama FROM kategori ORDER BY nama')->fetchAll();
$errors = [];
$old = [
    'judul' => '',
    'kategori_id' => '',
    'lokasi' => '',
    'isi' => '',
    'is_anonim' => 0,
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $old['judul'] = trim((string) ($_POST['judul'] ?? ''));
    $old['kategori_id'] = (string) ($_POST['kategori_id'] ?? '');
    $old['lokasi'] = trim((string) ($_POST['lokasi'] ?? ''));
    $old['isi'] = trim((string) ($_POST['isi'] ?? ''));
    $old['is_anonim'] = isset($_POST['is_anonim']) ? 1 : 0;

    if (mb_strlen($old['judul']) < 5 || mb_strlen($old['judul']) > 150) {
        $errors[] = 'Judul pengaduan harus terdiri dari 5 sampai 150 karakter.';
    }

    if (!ctype_digit($old['kategori_id'])) {
        $errors[] = 'Kategori pengaduan wajib dipilih.';
    } else {
        $stmt = db()->prepare('SELECT COUNT(*) FROM kategori WHERE id = ?');
        $stmt->execute([(int) $old['kategori_id']]);

        if ((int) $stmt->fetchColumn() === 0) {
            $errors[] = 'Kategori pengaduan yang dipilih tidak ditemukan.';
        }
    }

    if (mb_strlen($old['lokasi']) > 150) {
        $errors[] = 'Lokasi maksimal 150 karakter.';
    }

    if (mb_strlen($old['isi']) < 20) {
        $errors[] = 'Isi pengaduan minimal 20 karakter agar dapat ditindaklanjuti dengan baik.';
    }

    $lampiran = null;

    if ($errors === []) {
        try {
            $lampiran = upload_lampiran($_FILES['lampiran'] ?? []);
        } catch (RuntimeException $exception) {
            $errors[] = $exception->getMessage();
        }
    }

    if ($errors === []) {
        $stmt = db()->prepare(
            'INSERT INTO pengaduan (user_id, kategori_id, judul, isi, lokasi, lampiran, status, is_anonim)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $user['id'],
            (int) $old['kategori_id'],
            $old['judul'],
            $old['isi'],
            $old['lokasi'] !== '' ? $old['lokasi'] : null,
            $lampiran,
            'baru',
            $old['is_anonim'],
        ]);

        $pengaduanId = (int) db()->lastInsertId();
        set_flash('success', 'Pengaduan berhasil dikirim. Pantau status penanganannya pada halaman detail.');
        redirect('/pengaduan/detail.php?id=' . $pengaduanId);
    }
}

require __DIR__ . '/../includes/header.php';
?>
<main class="py-4 py-lg-5">
    <div class="container">
        <nav aria-label="breadcrumb" class="mb-3">
            <ol class="breadcrumb small mb-0">
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/pengaduan/index.php">Pengaduan Saya</a></li>
                <li class="breadcrumb-item active" aria-current="page">Kirim Pengaduan</li>
            </ol>
        </nav>

        <div class="page-header">
            <div>
                <h1>Kirim Pengaduan Baru</h1>
                <p>Sampaikan permasalahan secara jelas agar dapat ditindaklanjuti dengan cepat.</p>
            </div>
        </div>

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
            <div class="col-lg-8">
                <div class="detail-card">
                    <form method="post" action="<?= BASE_URL ?>/pengaduan/tambah.php" enctype="multipart/form-data">
                        <?= csrf_field() ?>
                        <div class="mb-3">
                            <label class="form-label" for="judul">Judul Pengaduan</label>
                            <input type="text" class="form-control" id="judul" name="judul" value="<?= e($old['judul']) ?>" placeholder="Contoh: AC di Lab Komputer 2 tidak menyala" maxlength="150" required>
                            <div class="form-text">Tuliskan inti permasalahan dalam satu kalimat singkat.</div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="kategori_id">Kategori</label>
                                <select class="form-select" id="kategori_id" name="kategori_id" required>
                                    <option value="">Pilih kategori</option>
                                    <?php foreach ($kategoriList as $kategori): ?>
                                        <option value="<?= (int) $kategori['id'] ?>" <?= (string) $kategori['id'] === $old['kategori_id'] ? 'selected' : '' ?>>
                                            <?= e($kategori['nama']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="lokasi">Lokasi Kejadian <span class="text-muted fw-normal">(opsional)</span></label>
                                <input type="text" class="form-control" id="lokasi" name="lokasi" value="<?= e($old['lokasi']) ?>" placeholder="Contoh: Lab Komputer 2" maxlength="150">
                            </div>
                        </div>

                        <div class="mt-3">
                            <label class="form-label" for="isi">Isi Pengaduan</label>
                            <textarea class="form-control" id="isi" name="isi" rows="7" placeholder="Jelaskan kronologi, dampak, dan harapan Anda terkait permasalahan ini..." required><?= e($old['isi']) ?></textarea>
                            <div class="form-text">Minimal 20 karakter. Sertakan informasi yang akurat dan tidak menyinggung pihak lain.</div>
                        </div>

                        <div class="mt-3">
                            <label class="form-label" for="lampiran">Lampiran Bukti <span class="text-muted fw-normal">(opsional)</span></label>
                            <input type="file" class="form-control" id="lampiran" name="lampiran" accept=".jpg,.jpeg,.png,.pdf" data-file-label="#fileName">
                            <div class="form-text">Format JPG, PNG, atau PDF. Maksimal 2 MB. <span id="fileName" class="text-muted">Belum ada file dipilih</span></div>
                        </div>

                        <div class="form-check mt-3">
                            <input class="form-check-input" type="checkbox" value="1" id="is_anonim" name="is_anonim" <?= $old['is_anonim'] === 1 ? 'checked' : '' ?>>
                            <label class="form-check-label" for="is_anonim">
                                Kirim sebagai anonim
                                <span class="d-block text-muted small">Nama Anda tidak akan ditampilkan kepada admin pada pengaduan ini.</span>
                            </label>
                        </div>

                        <div class="d-flex flex-wrap gap-2 mt-4">
                            <button type="submit" class="btn btn-primary px-4">
                                <i class="bi bi-send me-2"></i>Kirim Pengaduan
                            </button>
                            <a href="<?= BASE_URL ?>/pengaduan/index.php" class="btn btn-outline-secondary px-4">Batal</a>
                        </div>
                    </form>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="detail-card mb-4">
                    <h6 class="fw-bold mb-3"><i class="bi bi-lightbulb me-2 text-warning"></i>Tips Menulis</h6>
                    <ul class="small text-muted ps-3 mb-0">
                        <li class="mb-2">Gunakan judul yang spesifik dan mudah dipahami.</li>
                        <li class="mb-2">Sertakan lokasi serta waktu kejadian bila ada.</li>
                        <li class="mb-2">Jelaskan dampak permasalahan terhadap warga sekolah.</li>
                        <li class="mb-0">Lampirkan foto atau dokumen pendukung agar laporan lebih kuat.</li>
                    </ul>
                </div>
                <div class="detail-card">
                    <h6 class="fw-bold mb-3"><i class="bi bi-incognito me-2 text-primary"></i>Privasi Pelapor</h6>
                    <p class="small text-muted mb-2">
                        Identitas Anda hanya digunakan untuk keperluan tindak lanjut. Dengan mengaktifkan opsi anonim,
                        nama Anda tidak ditampilkan pada daftar pengaduan.
                    </p>
                    <p class="small text-muted mb-0">
                        Status pengaduan akan diperbarui oleh admin: <strong>Baru</strong> &rarr; <strong>Diproses</strong> &rarr; <strong>Selesai</strong>.
                    </p>
                </div>
            </div>
        </div>
    </div>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?>
