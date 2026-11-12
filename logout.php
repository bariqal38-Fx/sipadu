<?php
require_once __DIR__ . '/includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/');
}

verify_csrf();

$_SESSION = [];
session_regenerate_id(true);
set_flash('success', 'Anda telah keluar dari SIPADU.');

redirect('/login.php');
