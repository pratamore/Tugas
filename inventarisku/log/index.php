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

// Hapus log tunggal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hapus_log'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Token CSRF tidak valid.';
    } else {
        $lid = (int)($_POST['log_id'] ?? 0);
        if ($lid <= 0) {
            $error = 'ID log tidak valid.';
        } else {
            try {
                $pdo->beginTransaction();
                $del = $pdo->prepare("DELETE FROM log_aktivitas WHERE id = :id");
                $del->execute(['id' => $lid]);
                logActivity($pdo, $_SESSION['user_id'], 'hapus_log_aktivitas', 'Menghapus log aktivitas ID ' . $lid);
                $pdo->commit();
                $success = 'Log aktivitas berhasil dihapus.';
            } catch (Exception $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                $error = 'Terjadi kesalahan saat menghapus log aktivitas.';
            }
        }
    }
}

// Hapus semua log
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hapus_semua'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Token CSRF tidak valid.';
    } else {
        try {
            $pdo->beginTransaction();
            $delAll = $pdo->query("DELETE FROM log_aktivitas");
            logActivity($pdo, $_SESSION['user_id'], 'hapus_semua_log', 'Menghapus seluruh log aktivitas');
            $pdo->commit();
            $success = 'Seluruh log aktivitas berhasil dihapus.';
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $error = 'Terjadi kesalahan saat menghapus seluruh log aktivitas.';
        }
    }
}

$sql = "SELECT l.*, u.nama as user_nama, u.username 
        FROM log_aktivitas l 
        LEFT JOIN users u ON u.id = l.user_id 
        ORDER BY l.created_at DESC 
        LIMIT 200";
$list = $pdo->query($sql)->fetchAll();
?>
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="mb-1">Log Aktivitas</h4>
        <p class="text-secondary mb-0">Riwayat tindakan penting pengguna (200 terbaru)</p>
    </div>
    <?php if (!empty($list)): ?>
        <form method="post" onsubmit="return confirm('Yakin ingin menghapus SELURUH log aktivitas? Tindakan ini tidak dapat dibatalkan.');">
            <?= csrfField() ?>
            <input type="hidden" name="hapus_semua" value="1">
            <button type="submit" class="btn btn-outline-danger"><i class="bi bi-trash me-1"></i>Hapus Semua Log</button>
        </form>
    <?php endif; ?>
</div>

<?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>

<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between">
        <span><i class="bi bi-journal-text me-2"></i>Daftar Log</span>
        <span class="text-secondary small"><?= count($list) ?> data</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Waktu</th>
                        <th>Pengguna</th>
                        <th>Aktivitas</th>
                        <th>Detail</th>
                        <th>IP Address</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($list)): ?>
                        <tr><td colspan="6" class="text-center text-secondary py-4"><i class="bi bi-inbox fs-3 d-block mb-2"></i>Belum ada log aktivitas</td></tr>
                    <?php else: foreach ($list as $l): ?>
                        <tr>
                            <td><?= e($l['created_at']) ?></td>
                            <td><?= e($l['user_nama']) ?> <small class="text-secondary">(<?= e($l['username']) ?>)</small></td>
                            <td><?= e($l['aktivitas']) ?></td>
                            <td><?= e($l['detail']) ?: '-' ?></td>
                            <td><?= e($l['alamat_ip']) ?: '-' ?></td>
                            <td class="text-center">
                                <form method="post" class="d-inline" onsubmit="return confirm('Yakin ingin menghapus log ini?');">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="hapus_log" value="1">
                                    <input type="hidden" name="log_id" value="<?= e($l['id']) ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus Log"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
