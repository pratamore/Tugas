<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/path.php';
require_once __DIR__ . '/roles.php';
$base = base_path();

$namaHeader = $_SESSION['nama'] ?? '-';
$roleHeader = role_label($_SESSION['role'] ?? '');
$subHeader = '@' . ($_SESSION['username'] ?? '-');
if (strcasecmp(trim($namaHeader), trim($roleHeader)) !== 0) {
    $subHeader .= ' · ' . $roleHeader;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jejak Rasa - Sistem Kasir Cafe</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= e(asset_url('assets/css/style.css')) ?>">
</head>
<body>
    <div class="app-wrapper">
        <?php include __DIR__ . '/sidebar.php'; ?>
        <div class="main-content">
            <header class="top-header d-flex align-items-center justify-content-between px-4">
                <div class="header-title">
                    <h5 class="mb-0"><i class="bi bi-cup-hot me-2"></i>Jejak Rasa</h5>
                </div>
                <div class="header-user d-flex align-items-center">
                    <div class="user-info text-end me-3">
                        <div class="user-name"><?= e($namaHeader) ?></div>
                        <div class="user-role"><?= e($subHeader) ?></div>
                    </div>
                    <div class="dropdown">
                        <button class="btn btn-link dropdown-toggle text-decoration-none" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person-circle fs-4"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="<?= e($base) ?>/profil.php"><i class="bi bi-person me-2"></i>Profil</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?= e($base) ?>/auth/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                        </ul>
                    </div>
                </div>
            </header>
            <main class="content-area p-4">
