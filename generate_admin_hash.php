<?php
// Jalankan file ini via CLI: php generate_admin_hash.php
// Ganti password di bawah sesuai keinginan
$password = 'InventarisKu@2026';
echo password_hash($password, PASSWORD_DEFAULT);
echo PHP_EOL;
