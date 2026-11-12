<?php
declare(strict_types=1);

define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_NAME', 'sipadu_smk5');
define('DB_USER', 'root');
define('DB_PASS', '');

function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4';

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo '<div style="font-family:system-ui,sans-serif;max-width:680px;margin:80px auto;padding:24px;border:1px solid #fecaca;background:#fef2f2;border-radius:12px;color:#0f172a">';
        echo '<h2 style="margin-top:0">Koneksi database gagal</h2>';
        echo '<p>Pastikan MySQL pada XAMPP sudah berjalan dan database <code>' . DB_NAME . '</code> sudah dibuat.</p>';
        echo '<p>Impor file <code>04_Database/database.sql</code> melalui phpMyAdmin, lalu muat ulang halaman ini.</p>';
        echo '<p style="color:#64748b;font-size:14px">Detail: ' . htmlspecialchars($e->getMessage()) . '</p>';
        echo '</div>';
        exit;
    }

    return $pdo;
}
