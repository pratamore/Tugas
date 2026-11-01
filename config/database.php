<?php
require_once __DIR__ . '/../includes/hardening.php';
require_once __DIR__ . '/../includes/security.php';

apply_strict_headers();

$host = 'localhost';
$dbname = 'inventarisku';
$username = 'root';
$password = '';

$dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
    PDO::ATTR_PERSISTENT => false,
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
];

try {
    $pdo = new PDO($dsn, $username, $password, $options);
    // Set timeout
    $pdo->exec("SET SESSION wait_timeout=28800");
} catch (PDOException $e) {
    http_response_code(500);
    error_log('Database connection error: ' . $e->getMessage());
    die('Koneksi database gagal. Silakan hubungi administrator.');
}
