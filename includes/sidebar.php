<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/path.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/roles.php';

$base = base_path();
$role = current_role();
$menuGroups = role_menu_for_current_user();
?>
<nav class="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-brand-icon">
            <i class="bi bi-cup-hot"></i>
        </div>
        <div class="sidebar-brand-text">
            <h4 class="mb-0">Jejak Rasa</h4>
            <small>Coffee &amp; Eatery</small>
        </div>
    </div>

    <div class="sidebar-user">
        <div class="sidebar-user-avatar">
            <i class="bi bi-person-circle"></i>
        </div>
        <div class="sidebar-user-info">
            <div class="sidebar-user-name"><?= e($_SESSION['nama'] ?? '-') ?></div>
            <span class="sidebar-user-role"><?= e(role_label($role)) ?></span>
        </div>
    </div>

    <ul class="sidebar-menu">
        <?php foreach ($menuGroups as $section => $items): ?>
            <li class="menu-section"><?= e($section) ?></li>
            <?php foreach ($items as $item): ?>
                <?php $isActive = menu_item_is_active($item['path']); ?>
                <li class="menu-item">
                    <a href="<?= e($base . '/' . $item['path']) ?>" class="<?= $isActive ? 'active' : '' ?>"<?= $isActive ? ' aria-current="page"' : '' ?>>
                        <i class="bi bi-<?= e($item['icon']) ?>"></i>
                        <span><?= e($item['label']) ?></span>
                    </a>
                </li>
            <?php endforeach; ?>
        <?php endforeach; ?>
    </ul>
</nav>
