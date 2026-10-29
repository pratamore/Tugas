<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/path.php';
requireRole(['petugas', 'pimpinan']);

include __DIR__ . '/../includes/header.php';
$base = base_path();

$q = trim($_GET['q'] ?? '');
$start = $_GET['start'] ?? '';
$end = $_GET['end'] ?? '';

$sql = "SELECT p.*, u.nama as kasir FROM penjualan p LEFT JOIN users u ON u.id = p.user_id WHERE 1=1";
$params = [];
if ($q !== '') {
    $sql .= " AND (p.no_nota LIKE :q_nota OR p.pelanggan LIKE :q_pelanggan)";
    $params['q_nota'] = '%' . $q . '%';
    $params['q_pelanggan'] = '%' . $q . '%';
}
if ($start !== '') {
    $sql .= " AND p.tanggal >= :start";
    $params['start'] = $start;
}
if ($end !== '') {
    $sql .= " AND p.tanggal <= :end";
    $params['end'] = $end;
}
$sql .= " ORDER BY p.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$list = $stmt->fetchAll();
?>
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="mb-1">Riwayat Penjualan</h4>
        <p class="text-secondary mb-0">Daftar transaksi penjualan</p>
    </div>
    <?php if (role_can('kasir.use')): ?>
        <a href="<?= e($base) ?>/kasir/index.php" class="btn btn-primary"><i class="bi bi-cart me-1"></i>Kasir</a>
    <?php endif; ?>
</div>

<div class="card mb-3">
    <div class="card-header"><i class="bi bi-funnel me-2"></i>Filter</div>
    <div class="card-body">
        <form method="get" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label">Cari</label>
                <input type="text" name="q" class="form-control" placeholder="No. Nota / Pelanggan" value="<?= e($q) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Dari Tanggal</label>
                <input type="date" name="start" class="form-control" value="<?= e($start) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Sampai Tanggal</label>
                <input type="date" name="end" class="form-control" value="<?= e($end) ?>">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100"><i class="bi bi-search me-1"></i>Terapkan</button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between">
        <span><i class="bi bi-receipt me-2"></i>Daftar Penjualan</span>
        <span class="text-secondary small"><?= count($list) ?> data</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>No. Nota</th>
                        <th>Tanggal</th>
                        <th>Pelanggan</th>
                        <th>Kasir</th>
                        <th>Total</th>
                        <th>Bayar</th>
                        <th>Kembalian</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($list)): ?>
                        <tr><td colspan="8" class="text-center text-secondary py-3">Belum ada penjualan</td></tr>
                    <?php else: foreach ($list as $r): ?>
                        <tr>
                            <td class="fw-semibold"><?= e($r['no_nota']) ?></td>
                            <td><?= e($r['tanggal']) ?></td>
                            <td><?= e($r['pelanggan']) ?: '-' ?></td>
                            <td><?= e($r['kasir']) ?></td>
                            <td>Rp <?= number_format((float)$r['total'], 0, ',', '.') ?></td>
                            <td>Rp <?= number_format((float)$r['bayar'], 0, ',', '.') ?></td>
                            <td>Rp <?= number_format((float)$r['kembalian'], 0, ',', '.') ?></td>
                            <td class="text-center">
                                <a href="<?= e($base) ?>/kasir/struk.php?id=<?= e($r['id']) ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-printer"></i> Lihat Struk</a>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
