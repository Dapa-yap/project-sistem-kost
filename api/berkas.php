<?php
/** Menampilkan foto bukti/pengaduan: hanya pengelola, atau penghuni pemilik file. GET ?f=<nama file> */
require_once __DIR__ . '/../config/config.php';

$u = refresh_session_user();
$f = basename((string) ($_GET['f'] ?? ''));
if (!$u || !preg_match('/^(bukti|aduan)-[a-f0-9]{16}\.(jpg|png)$/', $f)) { http_response_code(404); exit; }

if ($u['role'] !== 'admin') {
    $milik = db_one('SELECT 1 FROM pembayaran WHERE userid = ? AND bukti_pembayaran = ?
                     UNION SELECT 1 FROM pengaduan WHERE userid = ? AND foto = ?', [$u['id'], $f, $u['id'], $f]);
    if (!$milik) { http_response_code(403); exit; }
}
$path = ROOT_PATH . '/uploads/' . $f;
if (!is_file($path)) { http_response_code(404); exit; }
header('Content-Type: ' . (substr($f, -3) === 'png' ? 'image/png' : 'image/jpeg'));
header('Content-Length: ' . filesize($path));
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, max-age=3600');
readfile($path);
