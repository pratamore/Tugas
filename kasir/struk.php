<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/path.php';
requireRole(['petugas', 'pimpinan']);

include __DIR__ . '/../includes/header.php';
$base = base_path();

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT p.*, u.nama as kasir FROM penjualan p LEFT JOIN users u ON u.id = p.user_id WHERE p.id = :id");
$stmt->execute(['id' => $id]);
$jual = $stmt->fetch();

if (!$jual) {
    render_not_found(
        'Transaksi tidak ditemukan',
        'Struk yang Anda cari tidak tersedia atau sudah dihapus.',
        $base . '/kasir/riwayat.php',
        'Kembali ke Riwayat Penjualan'
    );
    include __DIR__ . '/../includes/footer.php';
    exit;
}

$detail = $pdo->prepare("SELECT d.*, b.nama_barang FROM penjualan_detail d LEFT JOIN barang b ON b.id = d.barang_id WHERE d.penjualan_id = :id");
$detail->execute(['id' => $id]);
$items = $detail->fetchAll();
?>
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="mb-1">Struk Pembayaran</h4>
        <p class="text-secondary mb-0">Transaksi berhasil</p>
    </div>
    <div>
        <button onclick="window.print()" class="btn btn-outline-secondary"><i class="bi bi-printer me-1"></i>Cetak Struk</button>
        <?php if (role_can('kasir.use')): ?>
            <a href="<?= e($base) ?>/kasir/index.php" class="btn btn-primary"><i class="bi bi-cart me-1"></i>Kasir Baru</a>
        <?php else: ?>
            <a href="<?= e($base) ?>/kasir/riwayat.php" class="btn btn-outline-primary"><i class="bi bi-arrow-left me-1"></i>Kembali ke Riwayat</a>
        <?php endif; ?>
    </div>
</div>

<div class="card" style="max-width: 400px;">
    <div class="card-body">
        <div class="text-center mb-3">
            <h5 class="mb-1">JEJAK RASA</h5>
            <small class="text-secondary">Coffee &amp; Eatery</small>
        </div>
        <div class="mb-2">
            <div class="d-flex justify-content-between"><span>No. Nota</span><span class="fw-semibold"><?= e($jual['no_nota']) ?></span></div>
            <div class="d-flex justify-content-between"><span>Tanggal</span><span><?= e($jual['tanggal']) ?></span></div>
            <div class="d-flex justify-content-between"><span>Kasir</span><span><?= e($jual['kasir']) ?></span></div>
            <?php if ($jual['pelanggan']): ?>
            <div class="d-flex justify-content-between"><span>Pelanggan</span><span><?= e($jual['pelanggan']) ?></span></div>
            <?php endif; ?>
        </div>
        <hr>
        <?php foreach ($items as $it): ?>
        <div class="mb-2">
            <div><?= e($it['nama_barang']) ?></div>
            <div class="d-flex justify-content-between text-secondary small">
                <span><?= e($it['jumlah']) ?> x Rp <?= number_format((float)$it['harga_satuan'], 0, ',', '.') ?></span>
                <span>Rp <?= number_format((float)$it['subtotal'], 0, ',', '.') ?></span>
            </div>
        </div>
        <?php endforeach; ?>
        <hr>
        <div class="d-flex justify-content-between"><span>Total</span><span class="fw-semibold">Rp <?= number_format((float)$jual['total'], 0, ',', '.') ?></span></div>
        <div class="d-flex justify-content-between"><span>Bayar</span><span>Rp <?= number_format((float)$jual['bayar'], 0, ',', '.') ?></span></div>
        <div class="d-flex justify-content-between"><span>Kembalian</span><span>Rp <?= number_format((float)$jual['kembalian'], 0, ',', '.') ?></span></div>
        <hr>
        <div class="text-center text-secondary small">Terima kasih &amp; sampai jumpa lagi</div>
    </div>
</div>

<style>
@media print {
    .sidebar, .top-header, .app-footer, .btn { display: none !important; }
    .main-content { margin: 0; width: 100%; }
    .card { border: none; box-shadow: none; }
}
</style>

<?php include __DIR__ . '/../includes/footer.php'; ?>
