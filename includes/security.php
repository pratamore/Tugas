<?php
if (defined('INVENTARISKU_SECURE')) {
    return;
}
define('INVENTARISKU_SECURE', true);

// Terapkan security header
function apply_security_headers() {
    if (headers_sent()) {
        return;
    }

    // Mencegah MIME sniffing
    header('X-Content-Type-Options: nosniff');
    // Mencegah clickjacking
    header('X-Frame-Options: SAMEORIGIN');
    // Proteksi XSS legacy
    header('X-XSS-Protection: 1; mode=block');
    // Referrer Policy
    header('Referrer-Policy: strict-origin-when-cross-origin');
    // Permissions Policy
    header('Permissions-Policy: camera=(), microphone=(), geolocation=(), usb=()');
    // Content Security Policy (menyesuaikan dengan Bootstrap/Chart.js CDN)
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; img-src 'self' data: https://cdn.jsdelivr.net; font-src 'self' https://cdn.jsdelivr.net data:; connect-src 'self'; frame-ancestors 'self'; base-uri 'self'; form-action 'self'");
    // HSTS hanya aktif jika menggunakan HTTPS
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
    }
}

// Inisialisasi session aman
function init_secure_session() {
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Lax');
    ini_set('session.gc_maxlifetime', '3600');
    ini_set('session.cookie_lifetime', '0');

    $isHttps = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);

    session_start();

    // Regenerasi ID session secara berkala untuk mencegah fixation
    if (!isset($_SESSION['CREATED'])) {
        $_SESSION['CREATED'] = time();
    } elseif (time() - $_SESSION['CREATED'] > 1800) {
        session_regenerate_id(true);
        $_SESSION['CREATED'] = time();
    }
}

// CSRF
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        try {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        } catch (Exception $e) {
            $_SESSION['csrf_token'] = bin2hex(openssl_random_pseudo_bytes(32));
        }
    }
    return $_SESSION['csrf_token'];
}

function csrf_token_field() {
    $token = generate_csrf_token();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
}

function verify_csrf() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
        if (empty($_SESSION['csrf_token']) || empty($token) || !hash_equals($_SESSION['csrf_token'], $token)) {
            http_response_code(419);
            die('Permintaan tidak valid atau token CSRF kedaluwarsa. Silakan muat ulang halaman.');
        }
    }
}

// Proteksi metode HTTP
function enforce_post_only() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        die('Metode tidak diizinkan.');
    }
    verify_csrf();
}

// Rate limit login
function check_login_rate_limit() {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $key = 'login_attempts_' . md5($ip);
    $blockKey = 'login_block_' . md5($ip);

    if (isset($_SESSION[$blockKey]) && $_SESSION[$blockKey] > time()) {
        $remaining = ceil(($_SESSION[$blockKey] - time()) / 60);
        http_response_code(429);
        die('Terlalu banyak percobaan login. Coba lagi dalam ' . $remaining . ' menit.');
    }

    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = ['count' => 0, 'time' => time()];
    }

    if (time() - $_SESSION[$key]['time'] > 900) {
        $_SESSION[$key] = ['count' => 0, 'time' => time()];
    }

    if ($_SESSION[$key]['count'] >= 5) {
        $_SESSION[$blockKey] = time() + 900; // blokir 15 menit
        $_SESSION[$key]['count'] = 0;
        http_response_code(429);
        die('Terlalu banyak percobaan login gagal. Akses diblokir selama 15 menit.');
    }
}

function record_failed_login() {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $key = 'login_attempts_' . md5($ip);
    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = ['count' => 0, 'time' => time()];
    }
    $_SESSION[$key]['count']++;
}

function clear_login_rate_limit() {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    unset($_SESSION['login_attempts_' . md5($ip)]);
    unset($_SESSION['login_block_' . md5($ip)]);
}

// Terapkan otomatis
apply_security_headers();
init_secure_session();
