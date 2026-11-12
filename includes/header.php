<?php
require_once __DIR__ . '/functions.php';

if (!isset($pageTitle)) {
    $pageTitle = 'SIPADU SMKN 5 Malang';
}

$user = current_user();
$scriptPath = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
$isHome = (bool) preg_match('#/index\.php$#', $scriptPath) && strpos($scriptPath, '/admin/') === false;
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="SIPADU - Sistem Informasi Pengaduan dan Aspirasi Warga SMKN 5 Malang.">
    <title><?= e($pageTitle) ?> | SIPADU SMKN 5 Malang</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-2" href="<?= BASE_URL ?>/index.php">
            <span class="brand-icon"><i class="bi bi-megaphone-fill"></i></span>
            <span class="brand-text">
                <strong>SIPADU</strong>
                <small>SMKN 5 Malang</small>
            </span>
        </a>
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Buka navigasi">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav mx-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link<?= $isHome ? ' active' : '' ?>" href="<?= BASE_URL ?>/index.php">Beranda</a>
                </li>
                <?php if ($user !== null): ?>
                    <li class="nav-item">
                        <a class="nav-link<?= strpos($scriptPath, '/dashboard.php') !== false ? ' active' : '' ?>" href="<?= BASE_URL ?>/dashboard.php">Dashboard</a>
                    </li>
                    <?php if ($user['role'] === 'siswa'): ?>
                        <li class="nav-item">
                            <a class="nav-link<?= strpos($scriptPath, '/pengaduan/') !== false ? ' active' : '' ?>" href="<?= BASE_URL ?>/pengaduan/index.php">Pengaduan Saya</a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link<?= strpos($scriptPath, '/admin/') !== false ? ' active' : '' ?>" href="<?= BASE_URL ?>/admin/index.php">Panel Admin</a>
                        </li>
                    <?php endif; ?>
                <?php endif; ?>
            </ul>
            <div class="d-flex align-items-center gap-2 py-2 py-lg-0">
                <?php if ($user !== null): ?>
                    <div class="dropdown">
                        <button class="btn btn-light user-chip dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <span class="avatar"><?= e(strtoupper(mb_substr($user['nama'], 0, 1))) ?></span>
                            <span class="d-none d-sm-inline"><?= e($user['nama']) ?></span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2">
                            <li>
                                <span class="dropdown-item-text small text-muted">
                                    @<?= e($user['username']) ?> &middot; <?= e(ucfirst($user['role'])) ?>
                                </span>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?= BASE_URL ?>/dashboard.php"><i class="bi bi-speedometer2 me-2"></i>Dashboard</a></li>
                            <?php if ($user['role'] === 'siswa'): ?>
                                <li><a class="dropdown-item" href="<?= BASE_URL ?>/pengaduan/index.php"><i class="bi bi-chat-left-text me-2"></i>Pengaduan Saya</a></li>
                            <?php else: ?>
                                <li><a class="dropdown-item" href="<?= BASE_URL ?>/admin/index.php"><i class="bi bi-shield-lock me-2"></i>Panel Admin</a></li>
                            <?php endif; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form method="post" action="<?= BASE_URL ?>/logout.php" class="m-0">
                                    <?= csrf_field() ?>
                                    <button class="dropdown-item text-danger" type="submit"><i class="bi bi-box-arrow-right me-2"></i>Keluar</button>
                                </form>
                            </li>
                        </ul>
                    </div>
                <?php else: ?>
                    <a href="<?= BASE_URL ?>/login.php" class="btn btn-outline-primary rounded-pill px-3">Masuk</a>
                    <a href="<?= BASE_URL ?>/register.php" class="btn btn-primary rounded-pill px-3">Daftar</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>
<?php $flashes = get_flashes(); ?>
<?php if ($flashes !== []): ?>
    <div class="container mt-3">
        <?php foreach ($flashes as $flash): ?>
            <div class="alert alert-<?= e($flash['type']) ?> alert-dismissible alert-auto fade show shadow-sm" role="alert">
                <?= e($flash['message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
