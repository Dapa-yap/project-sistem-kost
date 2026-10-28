<?php
/** Ganti password dari halaman login (butuh username + kata sandi lama). Balasan JSON. */
require_once __DIR__ . '/../config/config.php';
header('Content-Type: application/json; charset=utf-8');

function out($ok, $pesan, $code = 200) { http_response_code($code); echo json_encode(['ok' => $ok, 'error' => $ok ? null : $pesan]); exit; }

if ($_SERVER['REQUEST_METHOD'] !== 'POST') out(false, 'Metode tidak diizinkan.', 405);
if (!throttle_ok('reset', 5, 10)) out(false, 'Terlalu banyak percobaan. Coba lagi beberapa menit lagi.', 429);

try {
    ganti_password(trim($_POST['username'] ?? ''), $_POST['lama'] ?? '', $_POST['baru'] ?? '');
    unset($_SESSION['throttle']['reset']);
    out(true, '');
} catch (AksiError $e) {
    throttle_hit('reset');
    out(false, $e->getMessage(), 422);
} catch (Throwable $e) {
    error_log('[KostKu] reset password: ' . $e->getMessage());
    out(false, 'Terjadi kesalahan pada server.', 500);
}
