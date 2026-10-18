<?php
require_once __DIR__ . '/../config/config.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'ok' => false,
        'error' => 'Metode request tidak diizinkan.'
    ]);
    exit;
}

$nama    = trim($_POST['nama'] ?? '');
$wa      = trim($_POST['wa'] ?? '');
$tgl     = trim($_POST['tgl'] ?? '');
$catatan = trim($_POST['catatan'] ?? '');

if ($nama === '' || $wa === '' || $tgl === '') {
    http_response_code(422);
    echo json_encode([
        'ok' => false,
        'error' => 'Data wajib belum lengkap.'
    ]);
    exit;
}

echo json_encode([
    'ok' => true,
    'message' => 'Jadwal kunjungan berhasil dikirim.'
]);