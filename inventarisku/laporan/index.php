<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/path.php';
requireRole(['admin', 'pimpinan']);

include __DIR__ . '/../includes/header.php';
$base = base_path();

$jenisLaporan = $_GET['jenis'] ?? 'semua';
$jenisValid = ['semua', 'kategori'];
if (!in_array($jenisLaporan, $jenisValid, true)) {
    $jenisLaporan = 'semua';
}
$kategoriId = $_GET['kategori'] ?? '';

$data = [];
$judul = 'Laporan Menu';

if ($jenisLaporan === 'semua') {
    $judul = 'Laporan Seluruh Menu';
    $sql = "SELECT b.nama_barang, k.nama as kategori, b.harga_jual, b.lokasi, b.status 
            FROM barang b LEFT JOIN kategori k ON k.id = b.kategori_id WHERE 1=1";
    $params = [];
    if ($kategoriId !== '') { $sql .= " AND b.kategori_id = :kategori_id"; $params['kategori_id'] = $kategoriId; }
    $sql .= " ORDER BY k.nama, b.nama_barang";
    $stmt = $pdo->prepare($sql); $stmt->execute($params); $data = $stmt->fetchAll();
} else {
    $judul = 'Laporan Menu Berdasarkan Kategori';
    $sql = "SELECT k.nama as kategori, 
                   COUNT(b.id) as jumlah_menu,
                   COALESCE(MIN(b.harga_jual),0) as harga_min,
                   COALESCE(MAX(b.harga_jual),0) as harga_max,
                   COALESCE(AVG(b.harga_jual),0) as harga_rata
            FROM kategori k 
            LEFT JOIN barang b ON b.kategori_id = k.id AND b.status='aktif' 
            GROUP BY k.id, k.nama 
            ORDER BY k.nama";
    $data = $pdo->query($sql)->fetchAll();
}

$cats = $pdo->query("SELECT * FROM kategori ORDER BY nama")->fetchAll();
?>
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="mb-1">Laporan Menu</h4>
        <p class="text-secondary mb-0">Rekap daftar menu Jejak Rasa</p>
    </div>
    <button onclick="window.print()" class="btn btn-outline-secondary"><i class="bi bi-printer me-1"></i>Cetak</button>
</div>

<div class="card mb-3">
    <div class="card-header"><i class="bi bi-funnel me-2"></i>Pengaturan Laporan</div>
    <div class="card-body">
        <form method="get" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label">Jenis Laporan</label>
                <select name="jenis" class="form-select" onchange="this.form.submit()">
                    <option value="semua" <?= ($jenisLaporan === 'semua') ? 'selected' : '' ?>>Seluruh Menu</option>
                    <option value="kategori" <?= ($jenisLaporan === 'kategori') ? 'selected' : '' ?>>Menu Berdasarkan Kategori</option>
                </select>
            </div>
            <?php if ($jenisLaporan === 'semua'): ?>
                <div class="col-md-4">
                    <label class="form-label">Kategori</label>
                    <select name="kategori" class="form-select">
                        <option value="">Semua</option>
                        <?php foreach ($cats as $c): ?>
                            <option value="<?= e($c['id']) ?>" <?= ($kategoriId == $c['id']) ? 'selected' : '' ?>><?= e($c['nama']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-file-earmark-text me-1"></i>Tampilkan</button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between">
        <strong><?= e($judul) ?></strong>
        <span class="text-secondary small"><?= count($data) ?> baris</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <?php if ($jenisLaporan === 'kategori'): ?>
                <table class="table table-hover mb-0">
                    <thead><tr><th>Kategori</th><th>Jumlah Menu</th><th>Harga Terendah</th><th>Harga Tertinggi</th><th>Harga Rata-rata</th></tr></thead>
                    <tbody>
                        <?php if (empty($data)): ?><tr><td colspan="5" class="text-center text-secondary py-3">Tidak ada data</td></tr>
                        <?php else: foreach ($data as $d): ?>
                            <tr>
                                <td class="fw-semibold"><?= e($d['kategori']) ?></td>
                                <td><?= e($d['jumlah_menu']) ?></td>
                                <td>Rp <?= number_format((float)$d['harga_min'], 0, ',', '.') ?></td>
                                <td>Rp <?= number_format((float)$d['harga_max'], 0, ',', '.') ?></td>
                                <td>Rp <?= number_format((float)$d['harga_rata'], 0, ',', '.') ?></td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <table class="table table-hover mb-0">
                    <thead><tr><th>Nama Menu</th><th>Kategori</th><th>Harga Jual</th><th>Area</th><th>Status</th></tr></thead>
                    <tbody>
                        <?php if (empty($data)): ?><tr><td colspan="5" class="text-center text-secondary py-3">Tidak ada data</td></tr>
                        <?php else: foreach ($data as $d): ?>
                            <tr>
                                <td class="fw-semibold"><?= e($d['nama_barang']) ?></td>
                                <td><?= e($d['kategori']) ?></td>
                                <td>Rp <?= number_format((float)$d['harga_jual'], 0, ',', '.') ?></td>
                                <td><?= e($d['lokasi']) ?: '-' ?></td>
                                <td class="text-capitalize"><?= e($d['status']) ?></td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
@media print {
    .sidebar, .top-header, .app-footer, .btn, form, .card-header .btn { display: none !important; }
    .main-content { margin: 0; width: 100%; }
    .card { border: none; box-shadow: none; }
    body { background: #fff; }
}
</style>

<?php include __DIR__ . '/../includes/footer.php'; ?>
