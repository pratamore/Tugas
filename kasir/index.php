<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/path.php';
requireRole(['petugas']);

$base = base_path();

if (!isset($_SESSION['keranjang'])) {
    $_SESSION['keranjang'] = [];
}

$error = '';
$success = '';

// Tambah ke keranjang
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_keranjang'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Token CSRF tidak valid.';
    } else {
        $barang_id = (int)($_POST['barang_id'] ?? 0);
        $jumlah = (int)($_POST['jumlah'] ?? 0);
        if ($barang_id <= 0 || $jumlah <= 0) {
            $error = 'Pilih menu dan jumlah yang valid.';
        } else {
            $stmt = $pdo->prepare("SELECT id, nama_barang, harga_jual FROM barang WHERE id = :id AND status='aktif'");
            $stmt->execute(['id' => $barang_id]);
            $b = $stmt->fetch();
            if (!$b) {
                $error = 'Menu tidak ditemukan atau tidak aktif.';
            } else {
                $key = $b['id'];
                if (isset($_SESSION['keranjang'][$key])) {
                    $_SESSION['keranjang'][$key]['qty'] += $jumlah;
                    $_SESSION['keranjang'][$key]['subtotal'] = $_SESSION['keranjang'][$key]['qty'] * $_SESSION['keranjang'][$key]['harga'];
                    $success = 'Keranjang diperbarui.';
                } else {
                    $_SESSION['keranjang'][$key] = [
                        'id' => $b['id'],
                        'nama' => $b['nama_barang'],
                        'harga' => (float)$b['harga_jual'],
                        'qty' => $jumlah,
                        'subtotal' => (float)$b['harga_jual'] * $jumlah
                    ];
                    $success = 'Menu ditambahkan ke keranjang.';
                }
            }
        }
    }
}

// Hapus item
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hapus_item'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Token CSRF tidak valid.';
    } else {
        $key = (int)($_POST['key'] ?? 0);
        unset($_SESSION['keranjang'][$key]);
        $success = 'Item dihapus dari keranjang.';
    }
}

// Kosongkan keranjang
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['kosongkan'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Token CSRF tidak valid.';
    } else {
        $_SESSION['keranjang'] = [];
        $success = 'Keranjang dikosongkan.';
    }
}

// Proses pembayaran
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['proses_bayar'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Token CSRF tidak valid.';
    } else {
        if (empty($_SESSION['keranjang'])) {
            $error = 'Keranjang kosong.';
        } else {
            $pelanggan = trim($_POST['pelanggan'] ?? '');
            $bayar = (float)($_POST['bayar'] ?? 0);
            $total = 0;
            foreach ($_SESSION['keranjang'] as $item) {
                $total += (float)$item['subtotal'];
            }
            if ($bayar < $total) {
                $error = 'Jumlah bayar kurang dari total.';
            } else {
                try {
                    $pdo->beginTransaction();
                    $noNota = 'TRJ-' . date('YmdHis') . '-' . rand(100, 999);
                    $kembalian = $bayar - $total;
                    $stmtJual = $pdo->prepare("INSERT INTO penjualan (no_nota, user_id, pelanggan, total, bayar, kembalian, tanggal) VALUES (:no_nota, :user_id, :pelanggan, :total, :bayar, :kembalian, :tanggal)");
                    $stmtJual->execute([
                        'no_nota' => $noNota,
                        'user_id' => $_SESSION['user_id'],
                        'pelanggan' => $pelanggan === '' ? null : $pelanggan,
                        'total' => $total,
                        'bayar' => $bayar,
                        'kembalian' => $kembalian,
                        'tanggal' => date('Y-m-d')
                    ]);
                    $penjualanId = $pdo->lastInsertId();

                    foreach ($_SESSION['keranjang'] as $item) {
                        $stmtDetail = $pdo->prepare("INSERT INTO penjualan_detail (penjualan_id, barang_id, jumlah, harga_satuan, subtotal) VALUES (:penjualan_id, :barang_id, :jumlah, :harga_satuan, :subtotal)");
                        $stmtDetail->execute([
                            'penjualan_id' => $penjualanId,
                            'barang_id' => $item['id'],
                            'jumlah' => $item['qty'],
                            'harga_satuan' => $item['harga'],
                            'subtotal' => $item['subtotal']
                        ]);
                    }

                    logActivity($pdo, $_SESSION['user_id'], 'penjualan', 'Transaksi penjualan ' . $noNota . ' Total Rp ' . number_format($total, 0, ',', '.'));
                    $pdo->commit();
                    $_SESSION['keranjang'] = [];
                    header('Location: ' . $base . '/kasir/struk.php?id=' . $penjualanId);
                    exit;
                } catch (Exception $e) {
                    if ($pdo->inTransaction()) $pdo->rollBack();
                    $error = 'Terjadi kesalahan saat memproses penjualan.';
                }
            }
        }
    }
}

$barangList = $pdo->query("SELECT b.id, b.nama_barang, b.harga_jual, k.nama AS kategori 
                           FROM barang b 
                           LEFT JOIN kategori k ON k.id = b.kategori_id 
                           WHERE b.status='aktif' 
                           ORDER BY k.nama, b.nama_barang")->fetchAll();

$totalKeranjang = 0;
foreach ($_SESSION['keranjang'] as $item) {
    $totalKeranjang += (float)$item['subtotal'];
}

include __DIR__ . '/../includes/header.php';
?>
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="mb-1">Kasir</h4>
        <p class="text-secondary mb-0">Catat pesanan pelanggan Jejak Rasa</p>
    </div>
    <a href="<?= e($base) ?>/kasir/riwayat.php" class="btn btn-outline-secondary"><i class="bi bi-receipt me-1"></i>Riwayat Penjualan</a>
</div>

<?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>

<div class="row g-3">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <span><i class="bi bi-cup-hot me-2"></i>Pilih Menu</span>
                <span class="text-secondary small d-none d-sm-inline">Klik menu untuk menambah ke keranjang</span>
            </div>
            <div class="card-body">
                <form method="post" id="formTambahMenu">
                    <?= csrfField() ?>
                    <input type="hidden" name="tambah_keranjang" value="1">
                    <input type="hidden" name="barang_id" id="menuTerpilih" value="">
                    <div class="row g-2 align-items-end mb-3">
                        <div class="col-md-8">
                            <label class="form-label" for="cariMenu">Cari Menu</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-search"></i></span>
                                <input type="search" id="cariMenu" class="form-control" placeholder="Ketik nama menu, contoh: latte" autocomplete="off">
                                <button type="button" class="btn btn-outline-secondary" id="tombolCari">Cari</button>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="jumlahMenu">Jumlah</label>
                            <input type="number" name="jumlah" id="jumlahMenu" class="form-control" min="1" value="1" required>
                        </div>
                    </div>

                    <div class="menu-grid" id="daftarMenu">
                        <?php foreach ($barangList as $br): ?>
                            <button type="button" class="menu-pilih" data-id="<?= e($br['id']) ?>" data-nama="<?= e($br['nama_barang']) ?>">
                                <span class="menu-pilih-nama"><?= e($br['nama_barang']) ?></span>
                                <span class="menu-pilih-kategori"><?= e($br['kategori']) ?: 'Tanpa kategori' ?></span>
                                <span class="menu-pilih-harga">Rp <?= number_format((float)$br['harga_jual'], 0, ',', '.') ?></span>
                            </button>
                        <?php endforeach; ?>
                    </div>

                    <div class="alert alert-secondary mt-3 mb-0 d-none" id="menuTidakDitemukan">
                        <i class="bi bi-search me-1"></i>Menu tidak ditemukan. Coba kata kunci lain.
                    </div>

                    <p class="text-secondary small mb-0 mt-3">
                        <i class="bi bi-info-circle me-1"></i>Atur jumlah lalu klik kartu menu. Menu yang sama otomatis digabung di keranjang.
                    </p>
                </form>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header d-flex align-items-center justify-content-between">
                <span><i class="bi bi-cart3 me-2"></i>Keranjang Pesanan</span>
                <?php if (!empty($_SESSION['keranjang'])): ?>
                    <form method="post" class="d-inline">
                        <?= csrfField() ?>
                        <input type="hidden" name="kosongkan" value="1">
                        <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash me-1"></i>Kosongkan</button>
                    </form>
                <?php endif; ?>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Menu</th>
                                <th>Harga</th>
                                <th>Qty</th>
                                <th>Subtotal</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($_SESSION['keranjang'])): ?>
                                <tr><td colspan="5" class="text-center text-secondary py-3">Keranjang masih kosong</td></tr>
                            <?php else: foreach ($_SESSION['keranjang'] as $key => $item): ?>
                                <tr>
                                    <td class="fw-semibold"><?= e($item['nama']) ?></td>
                                    <td>Rp <?= number_format((float)$item['harga'], 0, ',', '.') ?></td>
                                    <td><?= e($item['qty']) ?></td>
                                    <td>Rp <?= number_format((float)$item['subtotal'], 0, ',', '.') ?></td>
                                    <td class="text-center">
                                        <form method="post" class="d-inline">
                                            <?= csrfField() ?>
                                            <input type="hidden" name="hapus_item" value="1">
                                            <input type="hidden" name="key" value="<?= e($key) ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-x-lg"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header"><i class="bi bi-cash-coin me-2"></i>Pembayaran</div>
            <div class="card-body">
                <form method="post">
                    <?= csrfField() ?>
                    <input type="hidden" name="proses_bayar" value="1">
                    <div class="mb-3">
                        <label class="form-label">Nama Pelanggan (Opsional)</label>
                        <input type="text" name="pelanggan" class="form-control" placeholder="Nama pelanggan">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Total Belanja</label>
                        <input type="text" class="form-control" value="Rp <?= number_format($totalKeranjang, 0, ',', '.') ?>" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Uang Bayar <span class="text-danger">*</span></label>
                        <input type="number" name="bayar" class="form-control" min="<?= $totalKeranjang ?>" step="1000" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100" <?= empty($_SESSION['keranjang']) ? 'disabled' : '' ?>><i class="bi bi-check-circle me-1"></i>Proses Pembayaran</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    const form = document.getElementById('formTambahMenu');
    if (!form) return;

    const input = document.getElementById('cariMenu');
    const tombol = document.getElementById('tombolCari');
    const daftar = document.getElementById('daftarMenu');
    const kartu = Array.from(daftar.querySelectorAll('.menu-pilih'));
    const kosong = document.getElementById('menuTidakDitemukan');
    const terpilih = document.getElementById('menuTerpilih');
    const jumlah = document.getElementById('jumlahMenu');

    function saringMenu() {
        const kata = input.value.trim().toLowerCase();
        let tampil = 0;

        kartu.forEach(function (k) {
            const cocok = kata === '' || k.dataset.nama.toLowerCase().includes(kata);
            k.classList.toggle('d-none', !cocok);
            if (cocok) tampil++;
        });

        kosong.classList.toggle('d-none', tampil > 0);
        return tampil;
    }

    function kartuTampil() {
        return kartu.filter(function (k) { return !k.classList.contains('d-none'); });
    }

    function tambahMenu(kartuMenu) {
        if (!jumlah.checkValidity()) {
            jumlah.reportValidity();
            return;
        }
        terpilih.value = kartuMenu.dataset.id;
        form.submit();
    }

    input.addEventListener('input', saringMenu);

    input.addEventListener('keydown', function (e) {
        if (e.key !== 'Enter') return;
        e.preventDefault();
        const hasil = kartuTampil();
        if (hasil.length === 1) {
            tambahMenu(hasil[0]);
        }
    });

    tombol.addEventListener('click', function () {
        saringMenu();
        const hasil = kartuTampil();
        if (hasil.length === 1) {
            tambahMenu(hasil[0]);
        } else {
            input.focus();
        }
    });

    kartu.forEach(function (kartuMenu) {
        kartuMenu.addEventListener('click', function () {
            tambahMenu(kartuMenu);
        });
    });
})();
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
