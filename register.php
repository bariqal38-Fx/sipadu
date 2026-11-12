<?php
require_once __DIR__ . '/includes/functions.php';

if (current_user() !== null) {
    redirect(current_user()['role'] === 'admin' ? '/admin/index.php' : '/dashboard.php');
}

$pageTitle = 'Daftar Akun';
$errors = [];
$old = [
    'nama' => '',
    'username' => '',
    'kelas' => '',
    'no_hp' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    foreach ($old as $key => $value) {
        $old[$key] = trim((string) ($_POST[$key] ?? ''));
    }

    $password = (string) ($_POST['password'] ?? '');
    $passwordConfirm = (string) ($_POST['password_confirm'] ?? '');

    if (mb_strlen($old['nama']) < 3 || mb_strlen($old['nama']) > 100) {
        $errors[] = 'Nama lengkap harus terdiri dari 3 sampai 100 karakter.';
    }

    if (!preg_match('/^[A-Za-z0-9._-]{3,50}$/', $old['username'])) {
        $errors[] = 'Username hanya boleh berisi huruf, angka, titik, garis bawah, atau tanda hubung (3-50 karakter).';
    }

    if ($old['kelas'] === '' || mb_strlen($old['kelas']) > 20) {
        $errors[] = 'Kelas wajib diisi dengan maksimal 20 karakter.';
    }

    if ($old['no_hp'] !== '' && !preg_match('/^[0-9+\-\s]{8,20}$/', $old['no_hp'])) {
        $errors[] = 'Nomor HP tidak valid. Gunakan angka 8 sampai 20 digit.';
    }

    if (strlen($password) < 6) {
        $errors[] = 'Password minimal 6 karakter.';
    }

    if ($password !== $passwordConfirm) {
        $errors[] = 'Konfirmasi password tidak sama.';
    }

    if ($errors === []) {
        $stmt = db()->prepare('SELECT COUNT(*) FROM users WHERE username = ?');
        $stmt->execute([$old['username']]);

        if ((int) $stmt->fetchColumn() > 0) {
            $errors[] = 'Username sudah digunakan. Silakan pilih username lain.';
        }
    }

    if ($errors === []) {
        $stmt = db()->prepare(
            'INSERT INTO users (nama, username, password, role, kelas, no_hp) VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $old['nama'],
            $old['username'],
            password_hash($password, PASSWORD_DEFAULT),
            'siswa',
            $old['kelas'],
            $old['no_hp'] !== '' ? $old['no_hp'] : null,
        ]);

        set_flash('success', 'Akun berhasil dibuat. Silakan masuk menggunakan username dan password Anda.');
        redirect('/login.php');
    }
}

require __DIR__ . '/includes/header.php';
?>
<main class="auth-shell">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-xl-10">
                <div class="auth-card">
                    <div class="row g-0">
                        <div class="col-lg-5 d-none d-lg-block">
                            <div class="auth-aside">
                                <span class="brand-icon brand-icon-light"><i class="bi bi-megaphone-fill"></i></span>
                                <h2>Bergabung,<br>Bersama Membangun Sekolah.</h2>
                                <p>
                                    Daftarkan diri Anda untuk menyampaikan pengaduan dan aspirasi
                                    bagi kemajuan SMKN 5 Malang.
                                </p>
                                <div class="auth-benefit">
                                    <i class="bi bi-check-circle"></i>
                                    <span>Gratis dan hanya membutuhkan data dasar.</span>
                                </div>
                                <div class="auth-benefit">
                                    <i class="bi bi-check-circle"></i>
                                    <span>Setiap laporan tercatat dengan rapi.</span>
                                </div>
                                <div class="auth-benefit">
                                    <i class="bi bi-check-circle"></i>
                                    <span>Identitas pelapor dapat disembunyikan.</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-7">
                            <div class="auth-form">
                                <h1>Daftar Akun Siswa</h1>
                                <p class="subtitle">Lengkapi data berikut untuk membuat akun SIPADU.</p>

                                <?php if ($errors !== []): ?>
                                    <div class="alert alert-danger">
                                        <ul class="mb-0 ps-3">
                                            <?php foreach ($errors as $error): ?>
                                                <li><?= e($error) ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                <?php endif; ?>

                                <form method="post" action="<?= BASE_URL ?>/register.php">
                                    <?= csrf_field() ?>
                                    <div class="row g-3">
                                        <div class="col-12">
                                            <label class="form-label" for="nama">Nama Lengkap</label>
                                            <input type="text" class="form-control" id="nama" name="nama" value="<?= e($old['nama']) ?>" placeholder="Contoh: Alif Pratama" maxlength="100" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label" for="username">Username / NIS</label>
                                            <input type="text" class="form-control" id="username" name="username" value="<?= e($old['username']) ?>" placeholder="Contoh: 12345" maxlength="50" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label" for="kelas">Kelas</label>
                                            <input type="text" class="form-control" id="kelas" name="kelas" value="<?= e($old['kelas']) ?>" placeholder="Contoh: XI RPL 1" maxlength="20" required>
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label" for="no_hp">Nomor HP <span class="text-muted fw-normal">(opsional)</span></label>
                                            <input type="text" class="form-control" id="no_hp" name="no_hp" value="<?= e($old['no_hp']) ?>" placeholder="Contoh: 081234567890" maxlength="20">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label" for="password">Password</label>
                                            <input type="password" class="form-control" id="password" name="password" placeholder="Minimal 6 karakter" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label" for="password_confirm">Konfirmasi Password</label>
                                            <input type="password" class="form-control" id="password_confirm" name="password_confirm" placeholder="Ulangi password" required>
                                        </div>
                                    </div>
                                    <button type="submit" class="btn btn-primary w-100 py-2 mt-4">
                                        <i class="bi bi-person-plus me-2"></i>Daftar
                                    </button>
                                </form>

                                <p class="text-center text-muted small mt-4 mb-0">
                                    Sudah memiliki akun?
                                    <a href="<?= BASE_URL ?>/login.php" class="fw-semibold">Masuk di sini</a>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
