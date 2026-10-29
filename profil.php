<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/path.php';

$base = base_path();
$error = '';
$success = '';

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
$stmt->execute(['id' => $_SESSION['user_id']]);
$user = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ubah_password'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Token CSRF tidak valid.';
    } else {
        $current = $_POST['current_password'] ?? '';
        $new = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        if ($current === '' || $new === '' || $confirm === '') {
            $error = 'Semua kolom password wajib diisi.';
        } elseif (!$user || !password_verify($current, $user['password_hash'])) {
            $error = 'Password saat ini tidak sesuai.';
        } elseif (strlen($new) < 8) {
            $error = 'Password baru minimal 8 karakter.';
        } elseif ($new !== $confirm) {
            $error = 'Konfirmasi password baru tidak sama.';
        } elseif ($new === $current) {
            $error = 'Password baru harus berbeda dari password saat ini.';
        } else {
            try {
                $hash = password_hash($new, PASSWORD_DEFAULT);
                $upd = $pdo->prepare("UPDATE users SET password_hash = :hash WHERE id = :id");
                $upd->execute(['hash' => $hash, 'id' => $_SESSION['user_id']]);
                logActivity($pdo, $_SESSION['user_id'], 'ubah_password', 'Mengubah password akun sendiri');
                $success = 'Password berhasil diperbarui.';
                $stmt->execute(['id' => $_SESSION['user_id']]);
                $user = $stmt->fetch();
            } catch (Exception $e) {
                $error = 'Terjadi kesalahan saat memperbarui password.';
            }
        }
    }
}

include __DIR__ . '/includes/header.php';
?>
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="mb-1">Profil Pengguna</h4>
        <p class="text-secondary mb-0">Informasi akun dan keamanan Anda</p>
    </div>
    <a href="<?= e($base) ?>/dashboard.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Kembali</a>
</div>

<?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="card h-100" style="max-width: 650px;">
            <div class="card-header"><i class="bi bi-person-circle me-2"></i>Detail Profil</div>
            <div class="card-body">
                <div class="row mb-2">
                    <div class="col-sm-4 text-secondary">Nama Lengkap</div>
                    <div class="col-sm-8 fw-semibold"><?= e($user['nama'] ?? '-') ?></div>
                </div>
                <div class="row mb-2">
                    <div class="col-sm-4 text-secondary">Username</div>
                    <div class="col-sm-8"><?= e($user['username'] ?? '-') ?></div>
                </div>
                <div class="row mb-2">
                    <div class="col-sm-4 text-secondary">Role</div>
                    <div class="col-sm-8"><?= e($user ? role_label($user['role']) : '-') ?></div>
                </div>
                <div class="row mb-2">
                    <div class="col-sm-4 text-secondary">Status</div>
                    <div class="col-sm-8 text-capitalize"><?= e($user['status'] ?? '-') ?></div>
                </div>
                <div class="row">
                    <div class="col-sm-4 text-secondary">Terdaftar Sejak</div>
                    <div class="col-sm-8"><?= e($user['created_at'] ?? '-') ?></div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card h-100" style="max-width: 650px;">
            <div class="card-header"><i class="bi bi-key me-2"></i>Ubah Password</div>
            <div class="card-body">
                <form method="post">
                    <?= csrfField() ?>
                    <input type="hidden" name="ubah_password" value="1">
                    <div class="mb-3">
                        <label class="form-label">Password Saat Ini <span class="text-danger">*</span></label>
                        <input type="password" name="current_password" class="form-control" autocomplete="current-password" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password Baru <span class="text-danger">*</span></label>
                        <input type="password" name="new_password" class="form-control" autocomplete="new-password" minlength="8" required>
                        <small class="text-secondary">Minimal 8 karakter.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Konfirmasi Password Baru <span class="text-danger">*</span></label>
                        <input type="password" name="confirm_password" class="form-control" autocomplete="new-password" minlength="8" required>
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-shield-check me-1"></i>Perbarui Password</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
