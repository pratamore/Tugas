<?php

/**
 * Helper peran (role) terpusat.
 *
 * Semua halaman memakai aturan yang sama agar menu, aksi, dan hak akses
 * konsisten untuk admin, petugas, dan pimpinan.
 */

function current_role(): string {
    return $_SESSION['role'] ?? '';
}

function user_is(string $role): bool {
    return current_role() === $role;
}

function role_label(?string $role = null): string {
    $role = $role ?? current_role();
    $labels = [
        'admin' => 'Administrator',
        'petugas' => 'Petugas',
        'pimpinan' => 'Pimpinan',
    ];
    return $labels[$role] ?? ucfirst($role);
}

function role_description(?string $role = null): string {
    $role = $role ?? current_role();
    $descriptions = [
        'admin' => 'Kelola menu, kategori, pengguna, dan laporan operasional Jejak Rasa.',
        'petugas' => 'Layani pesanan pelanggan dan catat transaksi penjualan di Jejak Rasa.',
        'pimpinan' => 'Pantau ringkasan penjualan dan laporan cafe dalam mode hanya baca.',
    ];
    return $descriptions[$role] ?? '';
}

/**
 * Daftar hak akses yang dipakai di halaman dan menu.
 */
function role_capabilities(): array {
    return [
        'barang.manage' => ['admin'],
        'kategori.manage' => ['admin'],
        'kasir.use' => ['petugas'],
        'kasir.view' => ['petugas', 'pimpinan'],
        'laporan.view' => ['admin', 'pimpinan'],
        'pengguna.manage' => ['admin'],
        'log.view' => ['admin'],
    ];
}

function role_can(string $capability): bool {
    $capabilities = role_capabilities();
    $roles = $capabilities[$capability] ?? [];
    return in_array(current_role(), $roles, true);
}

/**
 * Menu sidebar per role. Item tanpa 'section' mengikuti section sebelumnya.
 */
function role_menu_items(): array {
    return [
        ['section' => 'Utama'],
        ['label' => 'Dashboard', 'icon' => 'speedometer2', 'path' => 'dashboard.php', 'roles' => ['admin', 'petugas', 'pimpinan']],

        ['section' => 'Menu'],
        ['label' => 'Data Menu', 'icon' => 'cup-hot', 'path' => 'barang/index.php', 'roles' => ['admin', 'pimpinan']],
        ['label' => 'Kategori Menu', 'icon' => 'tags', 'path' => 'kategori/index.php', 'roles' => ['admin']],

        ['section' => 'Transaksi'],
        ['label' => 'Kasir', 'icon' => 'cart', 'path' => 'kasir/index.php', 'roles' => ['petugas']],
        ['label' => 'Riwayat Penjualan', 'icon' => 'receipt', 'path' => 'kasir/riwayat.php', 'roles' => ['petugas', 'pimpinan']],

        ['section' => 'Laporan'],
        ['label' => 'Laporan Menu', 'icon' => 'file-earmark-text', 'path' => 'laporan/index.php', 'roles' => ['admin', 'pimpinan']],
        ['label' => 'Laporan Penjualan', 'icon' => 'graph-up-arrow', 'path' => 'laporan/penjualan.php', 'roles' => ['admin', 'pimpinan']],

        ['section' => 'Administrasi'],
        ['label' => 'Manajemen Pengguna', 'icon' => 'people', 'path' => 'pengguna/index.php', 'roles' => ['admin']],
        ['label' => 'Log Aktivitas', 'icon' => 'journal-text', 'path' => 'log/index.php', 'roles' => ['admin']],
    ];
}

/**
 * Menu yang boleh dilihat oleh role aktif, dikelompokkan per section.
 */
function role_menu_for_current_user(): array {
    $role = current_role();
    $groups = [];
    $currentSection = 'Menu';

    foreach (role_menu_items() as $item) {
        if (isset($item['section'])) {
            $currentSection = $item['section'];
            continue;
        }
        if (!in_array($role, $item['roles'], true)) {
            continue;
        }
        $groups[$currentSection][] = $item;
    }

    return array_filter($groups);
}

/**
 * Cek apakah menu sedang aktif berdasarkan URL halaman saat ini.
 */
function menu_item_is_active(string $path): bool {
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $needle = '/' . ltrim($path, '/');

    if (substr($script, -strlen($needle)) === $needle) {
        return true;
    }

    // Jika halaman ini punya item menu sendiri, jangan aktifkan item lain.
    foreach (role_menu_items() as $item) {
        if (empty($item['path'])) {
            continue;
        }
        $itemPath = '/' . ltrim($item['path'], '/');
        if (substr($script, -strlen($itemPath)) === $itemPath) {
            return false;
        }
    }

    // Subhalaman (detail/edit/tambah/struk) mengikuti menu modulnya.
    $dir = dirname($needle);
    return basename($needle) === 'index.php'
        && $dir !== '/'
        && $dir !== '\\'
        && strpos($script, $dir . '/') !== false;
}

/**
 * Aksi cepat pada dashboard sesuai role.
 */
function role_quick_actions(): array {
    $role = current_role();

    if ($role === 'admin') {
        return [
            ['label' => 'Tambah Menu', 'icon' => 'plus-circle', 'path' => 'barang/tambah.php', 'style' => 'primary'],
            ['label' => 'Data Menu', 'icon' => 'cup-hot', 'path' => 'barang/index.php', 'style' => 'outline-primary'],
            ['label' => 'Kelola Kategori', 'icon' => 'tags', 'path' => 'kategori/index.php', 'style' => 'outline-secondary'],
            ['label' => 'Kelola Pengguna', 'icon' => 'people', 'path' => 'pengguna/index.php', 'style' => 'outline-secondary'],
        ];
    }

    if ($role === 'petugas') {
        return [
            ['label' => 'Transaksi Kasir', 'icon' => 'cart', 'path' => 'kasir/index.php', 'style' => 'primary'],
            ['label' => 'Riwayat Penjualan', 'icon' => 'receipt', 'path' => 'kasir/riwayat.php', 'style' => 'outline-primary'],
        ];
    }

    return [
        ['label' => 'Laporan Menu', 'icon' => 'file-earmark-text', 'path' => 'laporan/index.php', 'style' => 'primary'],
        ['label' => 'Laporan Penjualan', 'icon' => 'graph-up-arrow', 'path' => 'laporan/penjualan.php', 'style' => 'outline-primary'],
        ['label' => 'Riwayat Penjualan', 'icon' => 'receipt', 'path' => 'kasir/riwayat.php', 'style' => 'outline-secondary'],
    ];
}
