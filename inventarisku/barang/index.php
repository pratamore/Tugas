<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/path.php';
requireRole(['admin', 'pimpinan']);

include __DIR__ . '/../includes/header.php';
$base = base_path();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hapus_barang'])) {
    if (!role_can('barang.manage')) {
        $error = 'Hanya admin yang dapat menghapus menu.';
    } elseif (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Token CSRF tidak valid.';
    } else {
        $bid = (int)($_POST['barang_id'] ?? 0);
        if ($bid <= 0) {
            $error = 'ID menu tidak valid.';
        } else {
            try {
                $cek = $pdo->prepare("SELECT nama_barang FROM barang WHERE id = :id");
                $cek->execute(['id' => $bid]);
                $menuHapus = $cek->fetch();
                if (!$menuHapus) {
                    $error = 'Menu tidak ditemukan.';
                } else {
                    $cekJual = $pdo->prepare("SELECT COUNT(*) as c FROM penjualan_detail WHERE barang_id = :id");
                    $cekJual->execute(['id' => $bid]);
                    $hasJual = (int)$cekJual->fetch()['c'] > 0;
                    if ($hasJual) {
                        $error = 'Menu tidak dapat dihapus karena sudah pernah terjual. Silakan nonaktifkan menu saja.';
                    } else {
                        $pdo->beginTransaction();
                        $del = $pdo->prepare("DELETE FROM barang WHERE id = :id");
                        $del->execute(['id' => $bid]);
                        logActivity($pdo, $_SESSION['user_id'], 'hapus_barang', 'Menghapus menu: ' . $menuHapus['nama_barang']);
                        $pdo->commit();
                        $success = 'Menu berhasil dihapus.';
                    }
                }
            } catch (Exception $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                $error = 'Terjadi kesalahan saat menghapus menu.';
            }
        }
    }
}

$search = trim($_GET['q'] ?? '');
$kategoriId = $_GET['kategori'] ?? '';
$status = $_GET['status'] ?? '';

$sql = "SELECT b.*, k.nama as kategori_nama 
        FROM barang b 
        LEFT JOIN kategori k ON k.id = b.kategori_id 
        WHERE 1=1";
$params = [];

if ($search !== '') {
    $sql .= " AND b.nama_barang LIKE :search_nama";
    $params['search_nama'] = '%' . $search . '%';
}
if ($kategoriId !== '') {
    $sql .= " AND b.kategori_id = :kategori_id";
    $params['kategori_id'] = $kategoriId;
}
if ($status !== '') {
    $sql .= " AND b.status = :status";
    $params['status'] = $status;
}

$sql .= " ORDER BY b.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$barangList = $stmt->fetchAll();

$cats = $pdo->query("SELECT * FROM kategori ORDER BY nama")->fetchAll();
?>
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="mb-1">Data Menu</h4>
        <p class="text-secondary mb-0">Kelola daftar menu kopi, makanan, dan camilan</p>
    </div>
    <?php if (role_can('barang.manage')): ?>
        <a href="<?= e($base) ?>/barang/tambah.php" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Tambah Menu</a>
    <?php endif; ?>
</div>

<?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>

<div class="card mb-3">
    <div class="card-header"><i class="bi bi-funnel me-2"></i>Filter Pencarian</div>
    <div class="card-body">
        <form method="get" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label">Cari Menu</label>
                <input type="text" name="q" class="form-control" placeholder="Nama menu" value="<?= e($search) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Kategori</label>
                <select name="kategori" class="form-select">
                    <option value="">Semua Kategori</option>
                    <?php foreach ($cats as $c): ?>
                        <option value="<?= e($c['id']) ?>" <?= ($kategoriId == $c['id']) ? 'selected' : '' ?>><?= e($c['nama']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="">Semua Status</option>
                    <option value="aktif" <?= ($status === 'aktif') ? 'selected' : '' ?>>Aktif</option>
                    <option value="nonaktif" <?= ($status === 'nonaktif') ? 'selected' : '' ?>>Nonaktif</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100"><i class="bi bi-search me-1"></i>Terapkan</button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between">
        <span><i class="bi bi-list-ul me-2"></i>Daftar Menu</span>
        <span class="text-secondary small"><?= count($barangList) ?> menu</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Nama Menu</th>
                        <th>Kategori</th>
                        <th>Harga Jual</th>
                        <th>Area</th>
                        <th>Status</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($barangList)): ?>
                        <tr>
                            <td colspan="6" class="text-center text-secondary py-4">
                                <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                                Tidak ada menu ditemukan
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($barangList as $b): ?>
                            <tr>
                                <td>
                                    <div class="fw-semibold"><?= e($b['nama_barang']) ?></div>
                                    <?php if ($b['keterangan']): ?>
                                        <small class="text-secondary"><?= e($b['keterangan']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><span class="badge bg-secondary-subtle text-secondary-emphasis"><?= e($b['kategori_nama']) ?></span></td>
                                <td>Rp <?= number_format((float)$b['harga_jual'], 0, ',', '.') ?></td>
                                <td><?= e($b['lokasi']) ?: '-' ?></td>
                                <td>
                                    <?php if ($b['status'] === 'aktif'): ?>
                                        <span class="badge bg-success-subtle text-success-emphasis">Aktif</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary-subtle text-secondary-emphasis">Nonaktif</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <a href="<?= e($base) ?>/barang/detail.php?id=<?= e($b['id']) ?>" class="btn btn-sm btn-outline-primary" title="Detail"><i class="bi bi-eye"></i></a>
                                    <?php if (role_can('barang.manage')): ?>
                                        <a href="<?= e($base) ?>/barang/edit.php?id=<?= e($b['id']) ?>" class="btn btn-sm btn-outline-secondary" title="Edit"><i class="bi bi-pencil"></i></a>
                                        <form method="post" class="d-inline" onsubmit="return confirm('Yakin ingin menghapus menu <?= e($b['nama_barang']) ?>? Menu hanya bisa dihapus jika belum pernah terjual.');">
                                            <?= csrfField() ?>
                                            <input type="hidden" name="hapus_barang" value="1">
                                            <input type="hidden" name="barang_id" value="<?= e($b['id']) ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus Menu"><i class="bi bi-trash"></i></button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
