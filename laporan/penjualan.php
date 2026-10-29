<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/path.php';
requireRole(['admin', 'pimpinan']);

include __DIR__ . '/../includes/header.php';
$base = base_path();

$start = $_GET['start'] ?? '';
$end = $_GET['end'] ?? '';

$sql = "SELECT p.*, u.nama as kasir FROM penjualan p LEFT JOIN users u ON u.id = p.user_id WHERE 1=1";
$params = [];
if ($start !== '') { $sql .= " AND p.tanggal >= :start"; $params['start'] = $start; }
if ($end !== '') { $sql .= " AND p.tanggal <= :end"; $params['end'] = $end; }
$sql .= " ORDER BY p.tanggal DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$list = $stmt->fetchAll();

$totalOmset = 0;
foreach ($list as $r) {
    $totalOmset += (float)$r['total'];
}
?>
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="mb-1">Laporan Penjualan</h4>
        <p class="text-secondary mb-0">Rekap penjualan berdasarkan periode</p>
    </div>
    <button onclick="window.print()" class="btn btn-outline-secondary"><i class="bi bi-printer me-1"></i>Cetak</button>
</div>

<div class="card mb-3">
    <div class="card-header"><i class="bi bi-funnel me-2"></i>Filter Periode</div>
    <div class="card-body">
        <form method="get" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label">Dari Tanggal</label>
                <input type="date" name="start" class="form-control" value="<?= e($start) ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">Sampai Tanggal</label>
                <input type="date" name="end" class="form-control" value="<?= e($end) ?>">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary">Tampilkan</button>
            </div>
        </form>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <div class="text-secondary">Total Transaksi</div>
                <h3 class="mb-0"><?= count($list) ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <div class="text-secondary">Total Omset</div>
                <h3 class="mb-0">Rp <?= number_format($totalOmset, 0, ',', '.') ?></h3>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header"><strong>Data Penjualan</strong></div>
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
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($list)): ?>
                        <tr><td colspan="7" class="text-center text-secondary py-3">Tidak ada data</td></tr>
                    <?php else: foreach ($list as $r): ?>
                        <tr>
                            <td><?= e($r['no_nota']) ?></td>
                            <td><?= e($r['tanggal']) ?></td>
                            <td><?= e($r['pelanggan']) ?: '-' ?></td>
                            <td><?= e($r['kasir']) ?></td>
                            <td>Rp <?= number_format((float)$r['total'], 0, ',', '.') ?></td>
                            <td>Rp <?= number_format((float)$r['bayar'], 0, ',', '.') ?></td>
                            <td>Rp <?= number_format((float)$r['kembalian'], 0, ',', '.') ?></td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
@media print {
    .sidebar, .top-header, .app-footer, .btn, form { display: none !important; }
    .main-content { margin: 0; width: 100%; }
    .card { border: none; box-shadow: none; }
}
</style>

<?php include __DIR__ . '/../includes/footer.php'; ?>
