<?php
require_once __DIR__ . '/../includes/hardening.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

apply_strict_headers();
init_ultra_session();
check_login_rate_limit();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    
    $username = trim(strip_tags($_POST['username'] ?? ''));
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Username dan password wajib diisi.';
    } elseif (strlen($username) > 50) {
        $error = 'Username tidak valid.';
    } else {
        try {
            $stmt = $pdo->prepare('SELECT id, nama, username, password_hash, role, status FROM users WHERE username = :username LIMIT 1');
            $stmt->execute(['username' => $username]);
            $user = $stmt->fetch();

            if (!$user || !password_verify($password, $user['password_hash'])) {
                record_failed_login();
                $error = 'Username atau password salah.';
            } elseif ($user['status'] !== 'aktif') {
                record_failed_login();
                $error = 'Akun tidak aktif. Hubungi administrator.';
            } else {
                clear_login_rate_limit();
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['nama'] = $user['nama'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['status'] = $user['status'];
                $_SESSION['__created'] = time();
                $_SESSION['__ua'] = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
                $_SESSION['__ip'] = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
                $_SESSION['login_time'] = time();

                if (function_exists('logActivity')) {
                    logActivity($pdo, $user['id'], 'login', 'Login berhasil');
                }

                header('Location: ../dashboard.php');
                exit;
            }
        } catch (Exception $e) {
            error_log('Login error: ' . $e->getMessage());
            $error = 'Terjadi kesalahan. Silakan coba lagi.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Jejak Rasa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css?v=<?= @filemtime(__DIR__ . '/../assets/css/style.css') ?: '1' ?>">
</head>
<body>
    <div class="login-wrapper">
        <div class="card login-card">
            <div class="card-header text-center">
                <div class="login-brand-icon mb-2"><i class="bi bi-cup-hot"></i></div>
                <h4 class="mb-1">Jejak Rasa</h4>
                <p class="text-secondary mb-0">Coffee &amp; Eatery · Sistem Kasir</p>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>
                <form method="post" action="" autocomplete="off" novalidate>
                    <?= csrf_token_field() ?>
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" name="username" autocomplete="username" required maxlength="50" pattern="[A-Za-z0-9._-]+">
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" autocomplete="current-password" required maxlength="255">
                    </div>
                    <button type="submit" class="btn btn-primary w-100 d-flex justify-content-center align-items-center">Login</button>
                </form>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
