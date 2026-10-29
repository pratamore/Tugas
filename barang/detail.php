<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/path.php';
requireRole(['admin', 'pimpinan']);

include __DIR__ . '/../includes/header.php';
$base = base_path();

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT b.*, k.nama as kategori_nama FROM barang b LEFT JOIN kategori k ON k.id = b.kategori_id WHERE b.id = :id");
$stmt->execute(['id' => $id]);
$barang = $stmt->fetch();

if (!$barang) {
    render_not_found(
        'Menu tidak ditemukan',
        'Data menu yang Anda cari tidak tersedia atau sudah dihapus.',
        $base . '/barang/index.php',
        'Kembali ke Data Menu'
    );
    include __DIR__ . '/../includes/footer.php';
    exit;
}

// Ringkasan penjualan menu ini
$ringkasan = ['total_terjual' => 0, 'total_omset' => 0, 'total_transaksi' => 0];
try {
    $stmtR = $pdo->prepare("SELECT COALESCE(SUM(d.jumlah),0) as total_terjual, COALESCE(SUM(d.subtotal),0) as total_omset, COUNT(DISTINCT d.penjualan_id) as total_transaksi FROM penjualan_detail d WHERE d.barang_id = :id");
    $stmtR->execute(['id' => $id]);
    $ringkasan = $stmtR->fetch() ?: $ringkasan;
} catch (Throwable $e) {
    // Tabel penjualan belum tersedia
}

$riwayat = [];
try {
    $stmtH = $pdo->prepare("SELECT p.no_nota, p.tanggal, d.jumlah, d.harga_satuan, d.subtotal 
                            FROM penjualan_detail d 
                            JOIN penjualan p ON p.id = d.penjualan_id 
                            WHERE d.barang_id = :id 
                            ORDER BY p.created_at DESC 
                            LIMIT 10");
    $stmtH->execute(['id' => $id]);
    $riwayat = $stmtH->fetchAll();
} catch (Throwable $e) {
    $riwayat = [];
}
?>
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="mb-1">Detail Menu</h4>
        <p class="text-secondary mb-0">Informasi menu & ringkasan penjualan</p>
    </div>
    <a href="<?= e($base) ?>/barang/index.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Kembali</a>
</div>

<div class="row g-3">
    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-header"><i class="bi bi-cup-hot me-2"></i>Informasi Menu</div>
            <div class="card-body">
                <div class="row mb-2">
                    <div class="col-5 text-secondary">Nama Menu</div>
                    <div class="col-7 fw-semibold"><?= e($barang['nama_barang']) ?></div>
                </div>
                <div class="row mb-2">
                    <div class="col-5 text-secondary">Kategori</div>
                    <div class="col-7"><?= e($barang['kategori_nama']) ?></div>
                </div>
                <div class="row mb-2">
                    <div class="col-5 text-secondary">Harga Jual</div>
                    <div class="col-7 fw-semibold">Rp <?= number_format((float)($barang['harga_jual'] ?? 0), 0, ',', '.') ?></div>
                </div>
                <div class="row mb-2">
                    <div class="col-5 text-secondary">Area / Display</div>
                    <div class="col-7"><?= e($barang['lokasi']) ?: '-' ?></div>
                </div>
                <div class="row mb-2">
                    <div class="col-5 text-secondary">Status</div>
                    <div class="col-7 text-capitalize"><?= e($barang['status']) ?></div>
                </div>
                <div class="row">
                    <div class="col-5 text-secondary">Keterangan</div>
                    <div class="col-7"><?= $barang['keterangan'] ? nl2br(e($barang['keterangan'])) : '-' ?></div>
                </div>
                <?php if (role_can('barang.manage')): ?>
                    <div class="mt-3">
                        <a href="<?= e($base) ?>/barang/edit.php?id=<?= e($barang['id']) ?>" class="btn btn-primary btn-sm"><i class="bi bi-pencil me-1"></i>Edit Menu</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="row g-3 mb-3">
            <div class="col-6">
                <div class="card h-100">
                    <div class="card-body">
                        <p class="text-secondary mb-1">Total Terjual</p>
                        <h3 class="mb-0"><?= e($ringkasan['total_terjual']) ?> <small class="fs-6 text-secondary">porsi</small></h3>
                    </div>
                </div>
            </div>
            <div class="col-6">
                <div class="card h-100">
                    <div class="card-body">
                        <p class="text-secondary mb-1">Total Omset</p>
                        <h3 class="mb-0">Rp <?= number_format((float)$ringkasan['total_omset'], 0, ',', '.') ?></h3>
                    </div>
                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-header"><i class="bi bi-receipt me-2"></i>Riwayat Penjualan (10 Terakhir)</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>No. Nota</th>
                                <th>Tanggal</th>
                                <th>Qty</th>
                                <th>Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($riwayat)): ?>
                                <tr><td colspan="4" class="text-center text-secondary py-3">Menu ini belum pernah terjual</td></tr>
                            <?php else: foreach ($riwayat as $r): ?>
                                <tr>
                                    <td><?= e($r['no_nota']) ?></td>
                                    <td><?= e($r['tanggal']) ?></td>
                                    <td><?= e($r['jumlah']) ?></td>
                                    <td>Rp <?= number_format((float)$r['subtotal'], 0, ',', '.') ?></td>
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
