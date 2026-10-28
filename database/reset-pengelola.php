<?php
/**
 * Mengganti password akun pengelola (satu-satunya) lewat terminal.
 *   php database/reset-pengelola.php "<password baru>"
 * Tidak membuat akun baru, hanya mengubah password akun pengelola yang sudah ada.
 */
if (PHP_SAPI !== 'cli') { http_response_code(403); exit("Hanya lewat terminal.\n"); }

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../data/data.php';

$baru = $argv[1] ?? '';
if ($baru === '') { echo 'Password baru (min. 8 karakter): '; $baru = trim((string) fgets(STDIN)); }
if (strlen($baru) < 8) exit("Password minimal 8 karakter.\n");

$n = db_exec("UPDATE \"user\" SET password = ? WHERE role::text IN ('admin','pengelola')", [password_hash($baru, PASSWORD_DEFAULT)]);
echo $n ? "Password pengelola diganti.\n" : "Belum ada akun pengelola. Jalankan: php database/setup.php\n";
