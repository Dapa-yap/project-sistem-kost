<?php
/**
 * Menyimpan pengajuan booking calon penghuni.
 * Sementara disimpan di session. TODO (database): INSERT INTO booking (...) status 'menunggu', batas = NOW()+3 hari
 */
require_once __DIR__ . '/../config/config.php';

// Aksi demo untuk mencoba tampilan status (dihapus saat database sudah ada)
$aksi = $_GET['aksi'] ?? '';
if ($aksi && !empty($_SESSION['booking_guest'])) {
    if ($aksi === 'demo-setuju')  $_SESSION['booking_guest']['status'] = 'disetujui';
    if ($aksi === 'demo-tolak')   $_SESSION['booking_guest']['status'] = 'ditolak';
    if ($aksi === 'demo-menunggu') $_SESSION['booking_guest']['status'] = 'menunggu';
    if ($aksi === 'batal')        unset($_SESSION['booking_guest']);
    redirect('guest/status.php');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('guest/kamar.php');

$id    = $_POST['kamar'] ?? '';
$nama  = trim($_POST['nama'] ?? '');
$wa    = trim($_POST['wa'] ?? '');
$tgl   = $_POST['tgl'] ?? '';
$k     = get_kamar_by_id($id);

if (!$k || $k['status'] !== 'kosong' || $nama === '' || !preg_match('/^[0-9+\- ]{9,16}$/', $wa) || !strtotime($tgl)) {
    redirect('guest/detail.php?id=' . urlencode($id) . '&error=1');
}

$_SESSION['booking_guest'] = [
    'kamar'  => $k['id'],
    'nama'   => $nama,
    'wa'     => $wa,
    'tgl'    => $tgl,
    'status' => 'menunggu',
    'dibuat' => date('Y-m-d H:i:s'),
    'batas'  => date('Y-m-d', strtotime('+3 days')),
];
redirect('guest/status.php?baru=1');
