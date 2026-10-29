<?php
function base_path(): string {
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    // Find project root by looking for dashboard.php or index.php in common positions
    $dir = dirname($scriptName);
    // Normalize
    $dir = str_replace('\\', '/', $dir);
    // If we're in a subfolder like /inventarisku/barang, go up until we find root or just use prefix
    // For this structure, use /inventarisku as base if in htdocs/inventarisku
    if (strpos($dir, '/inventarisku') !== false) {
        $pos = strpos($dir, '/inventarisku');
        return substr($dir, 0, $pos + strlen('/inventarisku'));
    }
    // Fallback: if in subdir go up one level from current
    if (substr_count($dir, '/') >= 2) {
        $parts = explode('/', trim($dir, '/'));
        // assume last is module folder
        array_pop($parts);
        if (empty($parts)) return '';
        return '/' . implode('/', $parts);
    }
    return $dir === '/' ? '' : $dir;
}

/**
 * Bangun URL aset statis lengkap dengan cache-busting berdasarkan mtime file.
 */
function asset_url(string $path): string {
    $path = ltrim($path, '/');
    $file = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path);
    $version = is_file($file) ? (string) filemtime($file) : '1';
    return url($path) . '?v=' . $version;
}

function url($path): string {
    $base = base_path();
    $path = ltrim($path, '/');
    if ($base === '') {
        return '/' . $path;
    }
    return $base . '/' . $path;
}
