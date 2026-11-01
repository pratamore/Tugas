<?php
require_once __DIR__ . '/hardening.php';
require_once __DIR__ . '/security.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/roles.php';

apply_strict_headers();
init_ultra_session();

// Validasi session
if (!isset($_SESSION['user_id'], $_SESSION['role'], $_SESSION['status'])) {
    force_logout();
}

// Cek status akun
if ($_SESSION['status'] !== 'aktif') {
    force_logout();
}

// Validasi integritas session
validate_session_integrity();

// Cek role minimal
$allowedRoles = ['admin', 'petugas', 'pimpinan'];
if (!in_array($_SESSION['role'], $allowedRoles, true)) {
    force_logout();
}
