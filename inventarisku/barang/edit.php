<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/path.php';
requireRole(['admin']);

include __DIR__ . '/../includes/header.php';
$base = base_path();

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM barang WHERE id = :id");
$stmt->execute(['id' => $id]);
$barang = $stmt->fetch();

if (!$barang) {
    render_not_found(
        'Menu tidak ditemukan',
        'Data menu yang ingin diubah tidak tersedia atau sudah dihapus.',
        $base . '/barang/index.php',
        'Kembali ke Data Menu'
    );
    include __DIR__ . '/../includes/footer.php';
    exit;
}

$cats = $pdo->query("SELECT * FROM kategori ORDER BY nama")->fetchAll();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Token CSRF tidak valid.';
    } else {
        $nama_barang = trim($_POST['nama_barang'] ?? '');
        $kategori_id = $_POST['kategori_id'] ?? '';
        $harga_jual = $_POST['harga_jual'] ?? 0;
        $lokasi = trim($_POST['lokasi'] ?? '');
        $keterangan = trim($_POST['keterangan'] ?? '');
        $status = $_POST['status'] ?? 'aktif';

        if ($nama_barang === '' || $kategori_id === '') {
            $error = 'Nama menu dan kategori wajib diisi.';
        } elseif (!is_numeric($harga_jual) || (float)$harga_jual < 0) {
            $error = 'Harga jual harus berupa angka non-negatif.';
        } elseif (!in_array($status, ['aktif', 'nonaktif'], true)) {
            $error = 'Status tidak valid.';
        } else {
            try {
                $check = $pdo->prepare("SELECT id FROM barang WHERE nama_barang = :nama_barang AND id != :id");
                $check->execute(['nama_barang' => $nama_barang, 'id' => $id]);
                if ($check->fetch()) {
                    $error = 'Nama menu sudah digunakan.';
                } else {
                    $pdo->beginTransaction();
                    $stmtU = $pdo->prepare("UPDATE barang SET nama_barang=:nama_barang, kategori_id=:kategori_id, harga_jual=:harga_jual, lokasi=:lokasi, keterangan=:keterangan, status=:status WHERE id=:id");
                    $stmtU->execute([
                        'nama_barang' => $nama_barang,
                        'kategori_id' => $kategori_id,
                        'harga_jual' => (float)$harga_jual,
                        'lokasi' => $lokasi === '' ? null : $lokasi,
                        'keterangan' => $keterangan === '' ? null : $keterangan,
                        'status' => $status,
                        'id' => $id
                    ]);
                    logActivity($pdo, $_SESSION['user_id'], 'ubah_barang', 'Mengubah menu: ' . $nama_barang);
                    $pdo->commit();
                    $success = 'Menu berhasil diperbarui.';
                    $stmt->execute(['id' => $id]);
                    $barang = $stmt->fetch();
                }
            } catch (Exception $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $error = 'Terjadi kesalahan saat memperbarui menu.';
            }
        }
    }
}
?>
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="mb-1">Edit Menu</h4>
        <p class="text-secondary mb-0">Ubah data menu Jejak Rasa</p>
    </div>
    <a href="<?= e($base) ?>/barang/index.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Kembali</a>
</div>

<div class="card">
    <div class="card-header"><i class="bi bi-pencil-square me-2"></i>Form Edit Menu</div>
    <div class="card-body">
        <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>
        <form method="post">
            <?= csrfField() ?>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Nama Menu <span class="text-danger">*</span></label>
                    <input type="text" name="nama_barang" class="form-control" value="<?= e($barang['nama_barang']) ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Kategori <span class="text-danger">*</span></label>
                    <select name="kategori_id" class="form-select" required>
                        <?php foreach ($cats as $c): ?>
                            <option value="<?= e($c['id']) ?>" <?= ($barang['kategori_id'] == $c['id']) ? 'selected' : '' ?>><?= e($c['nama']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Harga Jual (Rp) <span class="text-danger">*</span></label>
                    <input type="number" name="harga_jual" class="form-control" min="0" step="500" value="<?= e($barang['harga_jual'] ?? 0) ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Area / Display</label>
                    <input type="text" name="lokasi" class="form-control" value="<?= e($barang['lokasi']) ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="aktif" <?= ($barang['status'] === 'aktif') ? 'selected' : '' ?>>Aktif</option>
                        <option value="nonaktif" <?= ($barang['status'] === 'nonaktif') ? 'selected' : '' ?>>Nonaktif</option>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Keterangan</label>
                    <textarea name="keterangan" class="form-control" rows="3"><?= e($barang['keterangan']) ?></textarea>
                </div>
            </div>
            <div class="mt-3">
                <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>Simpan Perubahan</button>
                <a href="<?= e($base) ?>/barang/index.php" class="btn btn-outline-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
