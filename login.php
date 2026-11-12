<?php
require_once __DIR__ . '/includes/functions.php';

if (current_user() !== null) {
    redirect(current_user()['role'] === 'admin' ? '/admin/index.php' : '/dashboard.php');
}

$pageTitle = 'Masuk';
$errors = [];
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if ($username === '') {
        $errors[] = 'Username wajib diisi.';
    }

    if ($password === '') {
        $errors[] = 'Password wajib diisi.';
    }

    if ($errors === []) {
        $stmt = db()->prepare('SELECT id, nama, username, password, role FROM users WHERE username = ? LIMIT 1');
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user === false || !password_verify($password, $user['password'])) {
            $errors[] = 'Username atau password salah. Silakan coba kembali.';
        } else {
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int) $user['id'];
            set_flash('success', 'Selamat datang, ' . $user['nama'] . '.');

            if ($user['role'] === 'admin') {
                redirect('/admin/index.php');
            }

            redirect('/dashboard.php');
        }
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
                                <h2>Suara Anda,<br>Perubahan Nyata.</h2>
                                <p>
                                    Masuk ke SIPADU untuk menyampaikan pengaduan dan aspirasi
                                    bagi kemajuan SMKN 5 Malang.
                                </p>
                                <div class="auth-benefit">
                                    <i class="bi bi-shield-check"></i>
                                    <span>Data pelapor dijaga dan dapat dikirim secara anonim.</span>
                                </div>
                                <div class="auth-benefit">
                                    <i class="bi bi-activity"></i>
                                    <span>Pantau status penanganan secara transparan.</span>
                                </div>
                                <div class="auth-benefit">
                                    <i class="bi bi-chat-square-heart"></i>
                                    <span>Berikan penilaian setelah pengaduan selesai.</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-7">
                            <div class="auth-form">
                                <h1>Masuk ke Akun</h1>
                                <p class="subtitle">Gunakan username dan password yang telah terdaftar.</p>

                                <?php if ($errors !== []): ?>
                                    <div class="alert alert-danger">
                                        <ul class="mb-0 ps-3">
                                            <?php foreach ($errors as $error): ?>
                                                <li><?= e($error) ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                <?php endif; ?>

                                <form method="post" action="<?= BASE_URL ?>/login.php" class="needs-validation">
                                    <?= csrf_field() ?>
                                    <div class="mb-3">
                                        <label class="form-label" for="username">Username / NIS</label>
                                        <input type="text" class="form-control" id="username" name="username" value="<?= e($username) ?>" placeholder="Contoh: 12345" required autofocus>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label" for="password">Password</label>
                                        <input type="password" class="form-control" id="password" name="password" placeholder="Masukkan password" required>
                                    </div>
                                    <button type="submit" class="btn btn-primary w-100 py-2 mt-2">
                                        <i class="bi bi-box-arrow-in-right me-2"></i>Masuk
                                    </button>
                                </form>

                                <p class="text-center text-muted small mt-4 mb-3">
                                    Belum memiliki akun?
                                    <a href="<?= BASE_URL ?>/register.php" class="fw-semibold">Daftar di sini</a>
                                </p>

                                <div class="demo-box">
                                    <strong>Akun demo:</strong>
                                    admin <code>admin</code> / <code>admin123</code>
                                    &middot; siswa <code>12345</code> / <code>siswa123</code>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
