<?php
/** Menyimpan jadwal kunjungan / survey dari pengunjung. Balasan JSON. */
require_once __DIR__ . '/../config/config.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Metode request tidak diizinkan.']);
    exit;
}

if (!throttle_ok('jadwal', 10, 10)) {
    http_response_code(429);
    echo json_encode(['ok' => false, 'error' => 'Terlalu banyak pengiriman. Coba lagi beberapa menit lagi.']);
    exit;
}

try {
    buat_kunjungan(
        ($_POST['jenis'] ?? '') === 'survey' ? 'survey' : 'kunjungan',
        $_POST['nama'] ?? '', $_POST['wa'] ?? '', $_POST['tgl'] ?? '',
        $_POST['jam'] ?? '', $_POST['kamar'] ?? '', $_POST['catatan'] ?? ''
    );
    throttle_hit('jadwal');
    echo json_encode(['ok' => true, 'message' => 'Jadwal berhasil dikirim.']);
} catch (AksiError $e) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
} catch (Throwable $e) {
    error_log('[KostKu] kunjungan: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Terjadi kesalahan pada server.']);
}
