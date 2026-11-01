<?php

function e($value): string {
    if ($value === null) {
        return '';
    }
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirect($path) {
    // Ubah path relatif menjadi absolut agar tidak bergantung pada folder halaman asal.
    if ($path !== ''
        && $path[0] !== '/'
        && !preg_match('#^https?://#i', $path)
        && function_exists('base_path')) {
        $path = base_path() . '/' . ltrim($path, '/');
    }
    header('Location: ' . $path);
    exit;
}

/**
 * Tampilkan halaman "data tidak ditemukan" yang konsisten dengan tema aplikasi.
 */
function render_not_found(string $title, string $message, string $backUrl, string $backLabel = 'Kembali'): void {
    echo '<div class="card">'
        . '<div class="card-body text-center py-5">'
        . '<div class="text-warning mb-3" style="font-size:2.5rem;"><i class="bi bi-exclamation-triangle"></i></div>'
        . '<h5 class="mb-2">' . e($title) . '</h5>'
        . '<p class="text-secondary mb-4">' . e($message) . '</p>'
        . '<a href="' . e($backUrl) . '" class="btn btn-primary"><i class="bi bi-arrow-left me-1"></i>' . e($backLabel) . '</a>'
        . '</div></div>';
}

function isLoggedIn(): bool {
    return isset($_SESSION['user_id']) && isset($_SESSION['role']) && isset($_SESSION['status']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        redirect('auth/login.php');
    }
    if (isset($_SESSION['status']) && $_SESSION['status'] !== 'aktif') {
        session_unset();
        session_destroy();
        redirect('auth/login.php');
    }
}

function requireRole(array $roles) {
    requireLogin();
    if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $roles, true)) {
        http_response_code(403);
        $role = $_SESSION['role'] ?? '';
        $roleLabel = function_exists('role_label') ? role_label($role) : ucfirst($role);
        $baseUrl = function_exists('base_path') ? base_path() : '';
        $dashboardUrl = $baseUrl . '/dashboard.php';
        $styleUrl = function_exists('asset_url')
            ? asset_url('assets/css/style.css')
            : $baseUrl . '/assets/css/style.css';

        echo '<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Akses Ditolak - Jejak Rasa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="' . htmlspecialchars($styleUrl, ENT_QUOTES, 'UTF-8') . '" rel="stylesheet">
</head>
<body>
    <div class="login-wrapper">
        <div class="card login-card">
            <div class="card-body p-4 p-md-5 text-center">
                <div class="mb-3 text-warning" style="font-size: 3rem;"><i class="bi bi-shield-lock"></i></div>
                <h4 class="mb-2">Akses Ditolak</h4>
                <p class="text-secondary mb-4">Halaman ini tidak tersedia untuk role <strong>' . htmlspecialchars($roleLabel, ENT_QUOTES, 'UTF-8') . '</strong>. Silakan kembali ke dashboard sesuai hak akses Anda.</p>
                <a href="' . htmlspecialchars($dashboardUrl, ENT_QUOTES, 'UTF-8') . '" class="btn btn-primary w-100 justify-content-center"><i class="bi bi-arrow-left me-1"></i>Kembali ke Dashboard</a>
            </div>
        </div>
    </div>
</body>
</html>';
        exit;
    }
}

function generateCsrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrfToken($token): bool {
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

function csrfField(): string {
    $token = generateCsrfToken();
    return '<input type="hidden" name="csrf_token" value="' . e($token) . '">';
}

function logActivity($pdo, $userId, $aktivitas, $detail = null) {
    try {
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        // Limit IP length to fit column
        if ($ip !== null && strlen($ip) > 45) {
            $ip = substr($ip, 0, 45);
        }
        $stmt = $pdo->prepare('INSERT INTO log_aktivitas (user_id, aktivitas, detail, alamat_ip) VALUES (:user_id, :aktivitas, :detail, :alamat_ip)');
        $stmt->execute([
            'user_id' => $userId,
            'aktivitas' => $aktivitas,
            'detail' => $detail,
            'alamat_ip' => $ip
        ]);
    } catch (Exception $e) {
        // Do not interrupt flow if logging fails
    }
}

function getBaseUrl() {
    // Simple base detection for relative paths in this structure
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    $dir = dirname($scriptName);
    if ($dir === '/' || $dir === '\\') {
        return '';
    }
    return rtrim($dir, '/\\');
}
