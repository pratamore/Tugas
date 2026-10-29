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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Token CSRF tidak valid.';
    } else {
        $nama = trim($_POST['nama'] ?? '');
        $deskripsi = trim($_POST['deskripsi'] ?? '');
        if ($nama === '') {
            $error = 'Nama kategori wajib diisi.';
        } else {
            try {
                $check = $pdo->prepare("SELECT id FROM kategori WHERE nama = :nama");
                $check->execute(['nama' => $nama]);
                if ($check->fetch()) {
                    $error = 'Nama kategori sudah ada.';
                } else {
                    $pdo->beginTransaction();
                    $stmt = $pdo->prepare("INSERT INTO kategori (nama, deskripsi) VALUES (:nama, :deskripsi)");
                    $stmt->execute(['nama' => $nama, 'deskripsi' => $deskripsi === '' ? null : $deskripsi]);
                    logActivity($pdo, $_SESSION['user_id'], 'tambah_kategori', 'Menambah kategori: ' . $nama);
                    $pdo->commit();
                    $success = 'Kategori berhasil ditambahkan.';
                }
            } catch (Exception $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                $error = 'Terjadi kesalahan.';
            }
        }
    }
}

$list = $pdo->query("SELECT * FROM kategori ORDER BY nama")->fetchAll();
?>
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="mb-1">Manajemen Kategori Menu</h4>
        <p class="text-secondary mb-0">Kelola kategori menu kopi, makanan, dan camilan</p>
    </div>
</div>

<?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><i class="bi bi-plus-circle me-2"></i>Tambah Kategori</div>
            <div class="card-body">
                <form method="post">
                    <?= csrfField() ?>
                    <input type="hidden" name="tambah" value="1">
                    <div class="mb-3">
                        <label class="form-label">Nama Kategori <span class="text-danger">*</span></label>
                        <input type="text" name="nama" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Deskripsi</label>
                        <textarea name="deskripsi" class="form-control" rows="3" placeholder="Deskripsi kategori..."></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>Simpan</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <span><i class="bi bi-tags me-2"></i>Daftar Kategori</span>
                <span class="text-secondary small"><?= count($list) ?> data</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Nama Kategori</th>
                                <th>Deskripsi</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($list)): ?>
                                <tr><td colspan="3" class="text-center text-secondary py-3">Belum ada kategori</td></tr>
                            <?php else: foreach ($list as $k): ?>
                                <tr>
                                    <td class="fw-semibold"><?= e($k['nama']) ?></td>
                                    <td><?= $k['deskripsi'] ? e($k['deskripsi']) : '-' ?></td>
                                    <td class="text-center"><a href="<?= e($base) ?>/kategori/edit.php?id=<?= e($k['id']) ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a></td>
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
