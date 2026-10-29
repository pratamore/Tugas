<?php
if (defined('INVENTARISKU_HARDENING')) {
    return;
}
define('INVENTARISKU_HARDENING', true);

// Hilangkan info PHP
if (function_exists('header_remove')) {
    @header_remove('X-Powered-By');
    @header_remove('Server');
}

// Security Headers Lebih Ketat
function apply_strict_headers() {
    if (headers_sent()) {
        return;
    }

    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: no-referrer');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=(), usb=(), payment=(), accelerometer=()');
    header('Cross-Origin-Opener-Policy: same-origin');
    header('Cross-Origin-Embedder-Policy: require-corp');
    header('Cross-Origin-Resource-Policy: same-origin');

    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        header('Strict-Transport-Security: max-age=63072000; includeSubDomains; preload');
    }

    // CSP yang lebih ketat tapi tetap kompatibel dengan Bootstrap & Chart.js
    $csp  = "default-src 'self'; ";
    $csp .= "script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; ";
    $csp .= "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; ";
    $csp .= "img-src 'self' data: blob: https://cdn.jsdelivr.net; ";
    $csp .= "font-src 'self' https://cdn.jsdelivr.net data:; ";
    $csp .= "connect-src 'self'; ";
    $csp .= "frame-ancestors 'none'; ";
    $csp .= "base-uri 'self'; ";
    $csp .= "form-action 'self'; ";
    $csp .= "object-src 'none'; ";
    $csp .= "upgrade-insecure-requests";
    header("Content-Security-Policy: " . $csp);
}

// Session Hardening
function init_ultra_session() {
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    @ini_set('session.use_strict_mode', '1');
    @ini_set('session.use_only_cookies', '1');
    @ini_set('session.cookie_httponly', '1');
    @ini_set('session.use_trans_sid', '0');
    @ini_set('session.gc_probability', '1');
    @ini_set('session.gc_divisor', '100');
    @ini_set('session.gc_maxlifetime', '1800');
    @ini_set('session.cookie_lifetime', '0');

    $isHttps = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Strict'
    ]);

    session_start();

    if (!isset($_SESSION['__created'])) {
        $_SESSION['__created'] = time();
        $_SESSION['__ua'] = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        $_SESSION['__ip'] = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }

    if (time() - $_SESSION['__created'] > 900) {
        session_regenerate_id(true);
        $_SESSION['__created'] = time();
    }
}

// Validasi Session Anti-Hijack
function validate_session_integrity() {
    $currentUa = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    $currentIp = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

    if (isset($_SESSION['__ua']) && $_SESSION['__ua'] !== $currentUa) {
        force_logout();
    }

    if (isset($_SESSION['__ip']) && $_SESSION['__ip'] !== $currentIp) {
        // Bisa diperketat, untuk jaringan dinamis cukup comment jika sering berubah
        // force_logout();
    }

    if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time'] > 1800)) {
        force_logout();
    }

    $_SESSION['login_time'] = time();
}

function force_logout() {
    $_SESSION = array();
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();

    // Tentukan base URL aplikasi dari lokasi folder yang memiliki auth/login.php
    // agar tidak bergantung pada nama folder tertentu (mis. /inventarisku).
    $docRoot = str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'] ?? '') ?: '');
    $scriptFile = str_replace('\\', '/', realpath($_SERVER['SCRIPT_FILENAME'] ?? '') ?: '');
    $base = '';

    if ($docRoot !== '' && $scriptFile !== '') {
        $appRoot = dirname($scriptFile);
        while (strlen($appRoot) >= strlen($docRoot) && $appRoot !== dirname($appRoot)) {
            if (is_file($appRoot . '/auth/login.php') && is_dir($appRoot . '/includes')) {
                break;
            }
            $appRoot = dirname($appRoot);
        }
        if (strpos($appRoot, $docRoot) === 0) {
            $base = rtrim(substr($appRoot, strlen($docRoot)), '/');
        }
    }

    header('Location: ' . $base . '/auth/login.php');
    exit;
}

// Validasi ID Integer
function validate_id($id, $min = 1) {
    $id = filter_var($id, FILTER_VALIDATE_INT);
    if ($id === false || $id < $min) {
        return false;
    }
    return $id;
}

// Sanitasi String
function clean_string($str) {
    $str = trim($str);
    $str = strip_tags($str);
    return $str;
}

// Jalankan Otomatis
apply_strict_headers();
init_ultra_session();
