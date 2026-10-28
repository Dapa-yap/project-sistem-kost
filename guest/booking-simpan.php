<?php
/** Menyimpan pengajuan booking calon penghuni ke database, lalu menampilkan halaman status. */
require_once __DIR__ . '/../config/config.php';

// "Hapus pengajuan" = hanya melupakan pengajuan di perangkat ini (data di database tetap ada)
if (($_GET['aksi'] ?? '') === 'batal') {
    unset($_SESSION['booking_guest']);
    redirect('guest/status.php');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('guest/kamar.php');

$id = $_POST['kamar'] ?? '';
try {
    $bookingId = buat_booking($id, $_POST['nama'] ?? '', $_POST['wa'] ?? '', $_POST['tgl'] ?? '');
} catch (AksiError $e) {
    redirect('guest/detail.php?id=' . urlencode($id) . '&error=1');
}

$_SESSION['booking_guest'] = $bookingId;
redirect('guest/status.php?baru=1');
