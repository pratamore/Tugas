<?php
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

init_secure_session();

if (isset($_SESSION['user_id']) && function_exists('logActivity')) {
    try {
        logActivity($pdo, $_SESSION['user_id'], 'logout', 'Logout berhasil');
    } catch (Exception $e) {
        // Abaikan
    }
}

// Hancurkan session dengan aman
$_SESSION = array();

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy();
header('Location: login.php');
exit;
