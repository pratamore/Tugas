<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/path.php';
requireRole(['admin']);

include __DIR__ . '/../includes/header.php';
$base = base_path();

$error = '';
$success = '';

// Tambah pengguna
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Token CSRF tidak valid.';
    } else {
        $nama = trim($_POST['nama'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? 'petugas';
        $status = $_POST['status'] ?? 'aktif';

        if ($nama === '' || $username === '' || $password === '') {
            $error = 'Nama, username, dan password wajib diisi.';
        } elseif (!in_array($role, ['admin', 'petugas', 'pimpinan'], true)) {
            $error = 'Role tidak valid.';
        } elseif (!in_array($status, ['aktif', 'nonaktif'], true)) {
            $error = 'Status tidak valid.';
        } else {
            try {
                $check = $pdo->prepare("SELECT id FROM users WHERE username = :username");
                $check->execute(['username' => $username]);
                if ($check->fetch()) {
                    $error = 'Username sudah digunakan.';
                } else {
                    $pdo->beginTransaction();
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("INSERT INTO users (nama, username, password_hash, role, status) VALUES (:nama, :username, :password_hash, :role, :status)");
                    $stmt->execute([
                        'nama' => $nama,
                        'username' => $username,
                        'password_hash' => $hash,
                        'role' => $role,
                        'status' => $status
                    ]);
                    logActivity($pdo, $_SESSION['user_id'], 'tambah_pengguna', 'Menambah pengguna: ' . $username);
                    $pdo->commit();
                    $success = 'Pengguna berhasil ditambahkan.';
                }
            } catch (Exception $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                $error = 'Terjadi kesalahan.';
            }
        }
    }
}

// Toggle status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_status'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Token CSRF tidak valid.';
    } else {
        $uid = (int)($_POST['user_id'] ?? 0);
        $newStatus = $_POST['new_status'] ?? 'nonaktif';
        if ($uid <= 0) {
            $error = 'ID pengguna tidak valid.';
        } elseif ($uid == $_SESSION['user_id']) {
            $error = 'Tidak dapat mengubah status akun sendiri.';
        } elseif (!in_array($newStatus, ['aktif', 'nonaktif'], true)) {
            $error = 'Status tidak valid.';
        } else {
            try {
                $pdo->beginTransaction();
                $up = $pdo->prepare("UPDATE users SET status = :status WHERE id = :id");
                $up->execute(['status' => $newStatus, 'id' => $uid]);
                logActivity($pdo, $_SESSION['user_id'], 'ubah_status_pengguna', 'Mengubah status pengguna ID ' . $uid . ' menjadi ' . $newStatus);
                $pdo->commit();
                $success = 'Status pengguna berhasil diperbarui.';
            } catch (Exception $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                $error = 'Terjadi kesalahan.';
            }
        }
    }
}

// Hapus pengguna
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hapus_pengguna'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Token CSRF tidak valid.';
    } else {
        $uid = (int)($_POST['user_id'] ?? 0);
        if ($uid <= 0) {
            $error = 'ID pengguna tidak valid.';
        } elseif ($uid == $_SESSION['user_id']) {
            $error = 'Tidak dapat menghapus akun sendiri.';
        } else {
            try {
                // Cek apakah user ada
                $cek = $pdo->prepare("SELECT username, nama FROM users WHERE id = :id");
                $cek->execute(['id' => $uid]);
                $userHapus = $cek->fetch();
                
                if (!$userHapus) {
                    $error = 'Pengguna tidak ditemukan.';
                } else {
                    $pdo->beginTransaction();
                    // Set user_id menjadi NULL di log_aktivitas dan penjualan agar riwayat tetap ada
                    $updLog = $pdo->prepare("UPDATE log_aktivitas SET user_id = NULL WHERE user_id = :id");
                    $updLog->execute(['id' => $uid]);

                    $updJual = $pdo->prepare("UPDATE penjualan SET user_id = NULL WHERE user_id = :id");
                    $updJual->execute(['id' => $uid]);
                    
                    // Hapus pengguna
                    $del = $pdo->prepare("DELETE FROM users WHERE id = :id");
                    $del->execute(['id' => $uid]);
                    
                    logActivity($pdo, $_SESSION['user_id'], 'hapus_pengguna', 'Menghapus pengguna: ' . $userHapus['username'] . ' (' . $userHapus['nama'] . ')');
                    $pdo->commit();
                    $success = 'Pengguna berhasil dihapus.';
                }
            } catch (Exception $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                $error = 'Terjadi kesalahan saat menghapus pengguna.';
            }
        }
    }
}

$users = $pdo->query("SELECT * FROM users ORDER BY created_at DESC")->fetchAll();
?>
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="mb-1">Manajemen Pengguna</h4>
        <p class="text-secondary mb-0">Kelola akun pengguna sistem</p>
    </div>
</div>

<?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><i class="bi bi-person-plus me-2"></i>Tambah Pengguna</div>
            <div class="card-body">
                <form method="post">
                    <?= csrfField() ?>
                    <input type="hidden" name="tambah" value="1">
                    <div class="mb-2">
                        <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                        <input type="text" name="nama" class="form-control" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Username <span class="text-danger">*</span></label>
                        <input type="text" name="username" class="form-control" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Password <span class="text-danger">*</span></label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Role <span class="text-danger">*</span></label>
                        <select name="role" class="form-select">
                            <option value="petugas">Petugas</option>
                            <option value="pimpinan">Pimpinan</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="aktif">Aktif</option>
                            <option value="nonaktif">Nonaktif</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>Simpan</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <span><i class="bi bi-people me-2"></i>Daftar Pengguna</span>
                <span class="text-secondary small"><?= count($users) ?> data</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Nama</th>
                                <th>Username</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($users)): ?>
                                <tr><td colspan="5" class="text-center text-secondary py-3">Belum ada pengguna</td></tr>
                            <?php else: foreach ($users as $u): ?>
                                <tr>
                                    <td class="fw-semibold"><?= e($u['nama']) ?></td>
                                    <td><?= e($u['username']) ?></td>
                                    <td class="text-capitalize"><?= e($u['role']) ?></td>
                                    <td>
                                        <?php if ($u['status'] === 'aktif'): ?>
                                            <span class="badge bg-success-subtle text-success-emphasis">Aktif</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary-subtle text-secondary-emphasis">Nonaktif</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($u['id'] != $_SESSION['user_id']): ?>
                                            <form method="post" class="d-inline">
                                                <?= csrfField() ?>
                                                <input type="hidden" name="toggle_status" value="1">
                                                <input type="hidden" name="user_id" value="<?= e($u['id']) ?>">
                                                <input type="hidden" name="new_status" value="<?= ($u['status'] === 'aktif') ? 'nonaktif' : 'aktif' ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-secondary" title="<?= ($u['status'] === 'aktif') ? 'Nonaktifkan' : 'Aktifkan' ?>">
                                                    <i class="bi <?= ($u['status'] === 'aktif') ? 'bi-slash-circle' : 'bi-check-circle' ?>"></i>
                                                </button>
                                            </form>
                                            <form method="post" class="d-inline" onsubmit="return confirm('Yakin ingin menghapus pengguna <?= e($u['nama']) ?>? Tindakan ini tidak dapat dibatalkan.');">
                                                <?= csrfField() ?>
                                                <input type="hidden" name="hapus_pengguna" value="1">
                                                <input type="hidden" name="user_id" value="<?= e($u['id']) ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus Pengguna">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <span class="text-secondary small">Akun Anda</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
