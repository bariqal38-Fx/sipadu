<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$sourceRoot = str_replace('\\', '/', (string) realpath(__DIR__ . '/..'));
$documentRoot = isset($_SERVER['DOCUMENT_ROOT'])
    ? str_replace('\\', '/', (string) realpath($_SERVER['DOCUMENT_ROOT']))
    : '';
$basePath = '';

if ($documentRoot !== '' && strpos($sourceRoot, $documentRoot) === 0) {
    $basePath = substr($sourceRoot, strlen($documentRoot));
}

define('BASE_URL', rtrim($basePath, '/'));

function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): void
{
    header('Location: ' . BASE_URL . $path);
    exit;
}

function set_flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function get_flashes(): array
{
    $flashes = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);

    return $flashes;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void
{
    $sessionToken = $_SESSION['csrf_token'] ?? '';
    $token = $_POST['csrf_token'] ?? '';

    if (!is_string($token) || $sessionToken === '' || !hash_equals($sessionToken, $token)) {
        http_response_code(419);
        exit('Sesi kedaluwarsa. Silakan kembali dan muat ulang halaman.');
    }
}

function current_user(): ?array
{
    static $loaded = false;
    static $user = null;

    if ($loaded) {
        return $user;
    }

    $loaded = true;

    if (empty($_SESSION['user_id'])) {
        return null;
    }

    $stmt = db()->prepare('SELECT id, nama, username, role, kelas, no_hp, created_at FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$_SESSION['user_id']]);
    $found = $stmt->fetch();

    if (!$found) {
        unset($_SESSION['user_id']);

        return null;
    }

    $user = $found;

    return $user;
}

function require_login(): void
{
    if (current_user() === null) {
        set_flash('warning', 'Silakan masuk terlebih dahulu untuk mengakses halaman tersebut.');
        redirect('/login.php');
    }
}

function require_admin(): void
{
    require_login();
    $user = current_user();

    if (($user['role'] ?? '') !== 'admin') {
        set_flash('danger', 'Halaman tersebut hanya dapat diakses oleh admin.');
        redirect('/dashboard.php');
    }
}

function status_badge(string $status): string
{
    $map = [
        'baru' => ['secondary', 'Baru'],
        'diproses' => ['warning', 'Diproses'],
        'selesai' => ['success', 'Selesai'],
        'ditolak' => ['danger', 'Ditolak'],
    ];

    [$class, $label] = $map[$status] ?? ['secondary', ucfirst($status)];

    return '<span class="badge status-badge text-bg-' . $class . '">' . $label . '</span>';
}

function status_label(string $status): string
{
    $map = [
        'baru' => 'Baru',
        'diproses' => 'Diproses',
        'selesai' => 'Selesai',
        'ditolak' => 'Ditolak',
    ];

    return $map[$status] ?? ucfirst($status);
}

function tanggal_id(string $datetime): string
{
    $bulan = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
    ];

    $timestamp = strtotime($datetime);

    if ($timestamp === false) {
        return $datetime;
    }

    return date('d', $timestamp) . ' ' . $bulan[(int) date('n', $timestamp)] . ' ' . date('Y', $timestamp) . ', ' . date('H:i', $timestamp) . ' WIB';
}

function excerpt(string $text, int $limit = 120): string
{
    $text = trim((string) preg_replace('/\s+/', ' ', $text));

    if (mb_strlen($text) <= $limit) {
        return $text;
    }

    return mb_substr($text, 0, $limit) . '...';
}

function upload_lampiran(array $file): ?string
{
    if (!isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Lampiran gagal diunggah. Silakan coba lagi.');
    }

    if ($file['size'] > 2 * 1024 * 1024) {
        throw new RuntimeException('Ukuran lampiran maksimal 2 MB.');
    }

    $allowed = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'pdf' => 'application/pdf',
    ];

    $extension = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));

    if (!isset($allowed[$extension])) {
        throw new RuntimeException('Format lampiran harus JPG, PNG, atau PDF.');
    }

    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = $finfo !== false ? (string) finfo_file($finfo, $file['tmp_name']) : '';

        if ($finfo !== false) {
            finfo_close($finfo);
        }

        if ($mime !== '' && !in_array($mime, array_values($allowed), true)) {
            throw new RuntimeException('Isi file lampiran tidak sesuai dengan format yang diizinkan.');
        }
    }

    $directory = __DIR__ . '/../uploads';

    if (!is_dir($directory) && !mkdir($directory, 0777, true) && !is_dir($directory)) {
        throw new RuntimeException('Folder unggahan tidak dapat dibuat.');
    }

    $filename = bin2hex(random_bytes(16)) . '.' . $extension;

    if (!move_uploaded_file($file['tmp_name'], $directory . '/' . $filename)) {
        throw new RuntimeException('Lampiran gagal disimpan. Silakan coba lagi.');
    }

    return $filename;
}

function admin_tabs(string $active): void
{
    $tabs = [
        'dashboard' => ['admin/index.php', 'bi-speedometer2', 'Dashboard'],
        'pengaduan' => ['admin/pengaduan.php', 'bi-inbox', 'Kelola Pengaduan'],
        'kategori' => ['admin/kategori.php', 'bi-tags', 'Kategori'],
        'pengguna' => ['admin/pengguna.php', 'bi-people', 'Pengguna'],
    ];

    echo '<div class="admin-tabs mb-4">';

    foreach ($tabs as $key => $tab) {
        $class = $key === $active ? 'btn btn-primary' : 'btn btn-outline-secondary';
        echo '<a class="' . $class . '" href="' . BASE_URL . '/' . $tab[0] . '"><i class="bi ' . $tab[1] . ' me-1"></i>' . $tab[2] . '</a>';
    }

    echo '</div>';
}

function pagination_links(int $page, int $totalPages, string $baseQuery): string
{
    if ($totalPages <= 1) {
        return '';
    }

    $html = '<nav aria-label="Navigasi halaman"><ul class="pagination justify-content-center mt-4 mb-0">';

    for ($i = 1; $i <= $totalPages; $i++) {
        $active = $i === $page ? ' active' : '';
        $separator = $baseQuery === '' ? '?' : '?' . $baseQuery . '&';
        $html .= '<li class="page-item' . $active . '"><a class="page-link" href="' . e($separator . 'page=' . $i) . '">' . $i . '</a></li>';
    }

    $html .= '</ul></nav>';

    return $html;
}
