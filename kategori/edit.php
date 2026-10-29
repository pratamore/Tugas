<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/path.php';
requireRole(['admin']);

include __DIR__ . '/../includes/header.php';
$base = base_path();

$id = $_GET['id'] ?? 0;
$stmt = $pdo->prepare("SELECT * FROM kategori WHERE id = :id");
$stmt->execute(['id' => $id]);
$kat = $stmt->fetch();
if (!$kat) {
    render_not_found(
        'Kategori tidak ditemukan',
        'Data kategori yang ingin diubah tidak tersedia atau sudah dihapus.',
        $base . '/kategori/index.php',
        'Kembali ke Kategori'
    );
    include __DIR__ . '/../includes/footer.php';
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Token CSRF tidak valid.';
    } else {
        $nama = trim($_POST['nama'] ?? '');
        $deskripsi = trim($_POST['deskripsi'] ?? '');
        if ($nama === '') {
            $error = 'Nama kategori wajib diisi.';
        } else {
            try {
                $check = $pdo->prepare("SELECT id FROM kategori WHERE nama = :nama AND id != :id");
                $check->execute(['nama' => $nama, 'id' => $id]);
                if ($check->fetch()) {
                    $error = 'Nama kategori sudah ada.';
                } else {
                    $pdo->beginTransaction();
                    $up = $pdo->prepare("UPDATE kategori SET nama=:nama, deskripsi=:deskripsi WHERE id=:id");
                    $up->execute(['nama' => $nama, 'deskripsi' => $deskripsi === '' ? null : $deskripsi, 'id' => $id]);
                    logActivity($pdo, $_SESSION['user_id'], 'ubah_kategori', 'Mengubah kategori: ' . $nama);
                    $pdo->commit();
                    $success = 'Kategori berhasil diperbarui.';
                    $stmt->execute(['id' => $id]);
                    $kat = $stmt->fetch();
                }
            } catch (Exception $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                $error = 'Terjadi kesalahan.';
            }
        }
    }
}
?>
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="mb-1">Edit Kategori</h4>
        <p class="text-secondary mb-0">Ubah data kategori</p>
    </div>
    <a href="<?= e($base) ?>/kategori/index.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Kembali</a>
</div>

<div class="card" style="max-width: 600px;">
    <div class="card-header"><i class="bi bi-pencil-square me-2"></i>Form Edit Kategori</div>
    <div class="card-body">
        <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>
        <form method="post">
            <?= csrfField() ?>
            <div class="mb-3">
                <label class="form-label">Nama Kategori <span class="text-danger">*</span></label>
                <input type="text" name="nama" class="form-control" value="<?= e($kat['nama']) ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Deskripsi</label>
                <textarea name="deskripsi" class="form-control" rows="3"><?= e($kat['deskripsi']) ?></textarea>
            </div>
            <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>Simpan Perubahan</button>
            <a href="<?= e($base) ?>/kategori/index.php" class="btn btn-outline-secondary">Batal</a>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
