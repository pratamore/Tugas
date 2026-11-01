<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$role = current_role();
$isPetugas = user_is('petugas');
$isPimpinan = user_is('pimpinan');

// Statistik katalog menu (semua role)
$totalMenu = (int)($pdo->query("SELECT COUNT(*) as c FROM barang WHERE status='aktif'")->fetch()['c'] ?? 0);
$totalKategori = (int)($pdo->query("SELECT COUNT(*) as c FROM kategori")->fetch()['c'] ?? 0);

// Ringkasan penjualan hari ini
$penjualanHariIni = 0;
$omsetHariIni = 0;
$itemTerjualHariIni = 0;
$totalPenjualan = 0;
$totalOmset = 0;
$recentSales = [];
$menuPerKategori = [];
$chartData = [];

try {
    $stmtJual = $pdo->prepare("SELECT COUNT(*) as jumlah, COALESCE(SUM(total),0) as omset FROM penjualan WHERE tanggal = :tanggal");
    $stmtJual->execute(['tanggal' => date('Y-m-d')]);
    $ringkasanJual = $stmtJual->fetch();
    $penjualanHariIni = (int)($ringkasanJual['jumlah'] ?? 0);
    $omsetHariIni = (float)($ringkasanJual['omset'] ?? 0);

    $stmtItem = $pdo->prepare("SELECT COALESCE(SUM(d.jumlah),0) as item FROM penjualan_detail d JOIN penjualan p ON p.id = d.penjualan_id WHERE p.tanggal = :tanggal");
    $stmtItem->execute(['tanggal' => date('Y-m-d')]);
    $itemTerjualHariIni = (int)($stmtItem->fetch()['item'] ?? 0);

    $ringkasanAll = $pdo->query("SELECT COUNT(*) as jumlah, COALESCE(SUM(total),0) as omset FROM penjualan")->fetch();
    $totalPenjualan = (int)($ringkasanAll['jumlah'] ?? 0);
    $totalOmset = (float)($ringkasanAll['omset'] ?? 0);

    $recentSales = $pdo->query("SELECT p.no_nota, p.tanggal, p.pelanggan, p.total, u.nama 
                                FROM penjualan p 
                                LEFT JOIN users u ON u.id = p.user_id 
                                ORDER BY p.created_at DESC 
                                LIMIT 10")->fetchAll();
} catch (Throwable $e) {
    // Tabel penjualan belum tersedia; ringkasan tetap 0.
}

if (!$isPetugas) {
    // Jumlah menu per kategori
    $menuPerKategori = $pdo->query("SELECT k.nama as kategori, COUNT(b.id) as jumlah_menu 
                                    FROM kategori k 
                                    LEFT JOIN barang b ON b.kategori_id = k.id AND b.status='aktif' 
                                    GROUP BY k.id, k.nama 
                                    ORDER BY k.nama")->fetchAll();

    // Omset bulanan tahun berjalan
    $bulanMap = [1=>'Jan',2=>'Feb',3=>'Mar',4=>'Apr',5=>'Mei',6=>'Jun',7=>'Jul',8=>'Ags',9=>'Sep',10=>'Okt',11=>'Nov',12=>'Des'];
    for ($i = 1; $i <= 12; $i++) {
        $chartData[$i] = ['label' => $bulanMap[$i], 'total' => 0];
    }
    try {
        $stmtChart = $pdo->prepare("SELECT MONTH(tanggal) as bulan, COALESCE(SUM(total),0) as omset 
                                    FROM penjualan 
                                    WHERE YEAR(tanggal) = :tahun 
                                    GROUP BY MONTH(tanggal) 
                                    ORDER BY bulan");
        $stmtChart->execute(['tahun' => date('Y')]);
        foreach ($stmtChart->fetchAll() as $r) {
            $b = (int)$r['bulan'];
            if (isset($chartData[$b])) {
                $chartData[$b]['total'] = (float)$r['omset'];
            }
        }
    } catch (Throwable $e) {
        // Grafik tetap nol bila data belum tersedia.
    }
}

// Kartu statistik sesuai role
if ($isPetugas) {
    $statCards = [
        ['label' => 'Penjualan Hari Ini', 'value' => $penjualanHariIni, 'icon' => 'receipt', 'color' => 'success'],
        ['label' => 'Omset Hari Ini', 'value' => 'Rp ' . number_format($omsetHariIni, 0, ',', '.'), 'icon' => 'cash-coin', 'color' => 'primary'],
        ['label' => 'Item Terjual Hari Ini', 'value' => $itemTerjualHariIni, 'icon' => 'bag-check', 'color' => 'warning'],
        ['label' => 'Menu Aktif', 'value' => $totalMenu, 'icon' => 'cup-hot', 'color' => 'primary'],
    ];
} else {
    $statCards = [
        ['label' => 'Total Menu', 'value' => $totalMenu, 'icon' => 'cup-hot', 'color' => 'primary'],
        ['label' => 'Total Kategori', 'value' => $totalKategori, 'icon' => 'tags', 'color' => 'primary'],
        ['label' => 'Penjualan Hari Ini', 'value' => $penjualanHariIni, 'icon' => 'receipt', 'color' => 'success'],
        ['label' => 'Omset Hari Ini', 'value' => 'Rp ' . number_format($omsetHariIni, 0, ',', '.'), 'icon' => 'cash-coin', 'color' => 'warning'],
    ];
}

include __DIR__ . '/includes/header.php';
?>
<div class="card dashboard-hero mb-4">
    <div class="card-body d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
        <div>
            <span class="role-pill"><i class="bi bi-shield-check"></i><?= e(role_label($role)) ?></span>
            <h4 class="mb-1">Selamat datang, <?= e($_SESSION['nama'] ?? 'Pengguna') ?></h4>
            <p><?= e(role_description($role)) ?></p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <?php foreach (role_quick_actions() as $action): ?>
                <a href="<?= e(base_path() . '/' . $action['path']) ?>" class="btn <?= $action['style'] === 'primary' ? 'btn-light' : 'btn-outline-light' ?>">
                    <i class="bi bi-<?= e($action['icon']) ?>"></i><?= e($action['label']) ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php if ($isPimpinan): ?>
    <div class="alert alert-info d-flex align-items-center gap-2 mb-4">
        <i class="bi bi-eye"></i>
        <span>Anda masuk sebagai <strong>Pimpinan</strong>. Halaman ini bersifat hanya baca; perubahan data dilakukan oleh admin atau petugas.</span>
    </div>
<?php elseif ($isPetugas): ?>
    <div class="alert alert-success d-flex align-items-center gap-2 mb-4">
        <i class="bi bi-lightning-charge"></i>
        <span>Fokus operasional hari ini: <strong><?= e($penjualanHariIni) ?> transaksi penjualan</strong> dengan total <strong>Rp <?= number_format($omsetHariIni, 0, ',', '.') ?></strong>.</span>
    </div>
<?php endif; ?>

<div class="row g-3 mb-4">
    <?php foreach ($statCards as $card): ?>
        <div class="col-md-6 col-lg-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <p class="text-secondary mb-1"><?= e($card['label']) ?></p>
                            <h3 class="mb-0"><?= e($card['value']) ?></h3>
                        </div>
                        <span class="stat-icon text-<?= e($card['color']) ?>">
                            <i class="bi bi-<?= e($card['icon']) ?>"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php if (!$isPetugas): ?>
<div class="row g-3 mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <span><i class="bi bi-graph-up-arrow me-2"></i>Omset Penjualan per Bulan (Tahun <?= date('Y') ?>)</span>
                <span class="text-secondary small">Total omset: Rp <?= number_format($totalOmset, 0, ',', '.') ?> dari <?= e($totalPenjualan) ?> transaksi</span>
            </div>
            <div class="card-body">
                <canvas id="chartOmset" height="90"></canvas>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if ($isPetugas): ?>
<div class="row g-3">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <span>Penjualan Terbaru</span>
                <a href="<?= e(base_path()) ?>/kasir/riwayat.php" class="btn btn-sm btn-outline-secondary">Lihat Semua</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>No. Nota</th>
                                <th>Tanggal</th>
                                <th>Pelanggan</th>
                                <th>Total</th>
                                <th>Kasir</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recentSales)): ?>
                                <tr>
                                    <td colspan="5" class="text-center text-secondary py-3">Belum ada penjualan</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($recentSales as $rs): ?>
                                    <tr>
                                        <td><?= e($rs['no_nota']) ?></td>
                                        <td><?= e($rs['tanggal']) ?></td>
                                        <td><?= e($rs['pelanggan']) ?: '-' ?></td>
                                        <td>Rp <?= number_format((float)$rs['total'], 0, ',', '.') ?></td>
                                        <td><?= e($rs['nama']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php else: ?>
<div class="row g-3">
    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-header d-flex align-items-center justify-content-between">
                <span>Menu per Kategori</span>
                <a href="<?= e(base_path()) ?>/barang/index.php" class="btn btn-sm btn-outline-secondary">Lihat Semua</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Kategori</th>
                                <th>Jumlah Menu</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($menuPerKategori)): ?>
                                <tr>
                                    <td colspan="2" class="text-center text-secondary py-3">Belum ada kategori</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($menuPerKategori as $mk): ?>
                                    <tr>
                                        <td><?= e($mk['kategori']) ?></td>
                                        <td><?= e($mk['jumlah_menu']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-header d-flex align-items-center justify-content-between">
                <span>Penjualan Terbaru</span>
                <a href="<?= e(base_path()) ?>/kasir/riwayat.php" class="btn btn-sm btn-outline-secondary">Lihat Semua</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>No. Nota</th>
                                <th>Tanggal</th>
                                <th>Pelanggan</th>
                                <th>Total</th>
                                <th>Kasir</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recentSales)): ?>
                                <tr>
                                    <td colspan="5" class="text-center text-secondary py-3">Belum ada penjualan</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($recentSales as $rs): ?>
                                    <tr>
                                        <td><?= e($rs['no_nota']) ?></td>
                                        <td><?= e($rs['tanggal']) ?></td>
                                        <td><?= e($rs['pelanggan']) ?: '-' ?></td>
                                        <td>Rp <?= number_format((float)$rs['total'], 0, ',', '.') ?></td>
                                        <td><?= e($rs['nama']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if (!$isPetugas): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const ctx = document.getElementById('chartOmset');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: [<?php foreach ($chartData as $cd): echo '"' . e($cd['label']) . '",'; endforeach; ?>],
        datasets: [{
            label: 'Omset (Rp)',
            data: [<?php foreach ($chartData as $cd): echo (float)$cd['total'] . ','; endforeach; ?>],
            backgroundColor: 'rgba(192, 122, 75, 0.6)',
            borderColor: '#C07A4B',
            borderWidth: 1,
            borderRadius: 6
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return 'Rp ' + Number(value).toLocaleString('id-ID');
                    }
                }
            }
        },
        plugins: {
            legend: { display: false },
            title: { display: false }
        }
    }
});
</script>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
