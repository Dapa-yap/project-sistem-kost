<?php
/**
 * KostKu - konfigurasi & helper global
 * Dipanggil di paling atas setiap halaman:
 *   require_once __DIR__ . '/../config/config.php';
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('Asia/Jakarta');

define('ROOT_PATH', dirname(__DIR__));

// BASE_URL dihitung otomatis dari posisi folder terhadap document root,
// jadi project bisa ditaruh di htdocs/kostku, atau dijalankan dengan `php -S`.
$docRoot = str_replace('\\', '/', rtrim((string) realpath($_SERVER['DOCUMENT_ROOT'] ?? ''), '/\\'));
$appRoot = str_replace('\\', '/', ROOT_PATH);
define('BASE_URL', ($docRoot !== '' && strpos($appRoot, $docRoot) === 0) ? substr($appRoot, strlen($docRoot)) : '');

define('APP_NAME', 'KostKu');

/* ---------- Helper URL & output ---------- */
function url($path = '')   { return BASE_URL . '/' . ltrim($path, '/'); }
function asset($path)      { return url('assets/' . ltrim($path, '/')); }
function e($s)             { return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8'); }
function rupiah($n)        { return 'Rp ' . number_format((float) $n, 0, ',', '.'); }
function rupiah_k($n)      { return 'Rp ' . (($n % 1000 === 0) ? ($n / 1000) : number_format($n / 1000, 1, ',', '')) . 'k'; }
function redirect($path)   { header('Location: ' . url($path)); exit; }

function tgl($date, $short = true) {
    $bulan = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    $full  = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    $t = strtotime($date);
    $m = (int) date('n', $t);
    return date('j', $t) . ' ' . ($short ? $bulan[$m] : $full[$m]) . ' ' . date('Y', $t);
}

/* ---------- Auth sederhana (sementara, sebelum database) ---------- */
function is_logged_in()  { return !empty($_SESSION['user']); }
function current_user()  { return $_SESSION['user'] ?? null; }

function require_role($role) {
    $u = current_user();
    if (!$u || $u['role'] !== $role) {
        redirect('guest/login.php?role=' . $role);
    }
}

/* ---------- Badge status (dipakai di banyak halaman) ---------- */
function badge($label, $color = 'blue') {
    return '<span class="badge badge-' . e($color) . '">' . e($label) . '</span>';
}

function status_badge($type, $status) {
    $maps = [
        'kamar' => [
            'kosong'     => ['Tersedia', 'green'],
            'terisi'     => ['Terisi', 'grey'],
            'booking'    => ['Terbooking', 'brown'],
            'diperbaiki' => ['Diperbaiki', 'orange'],
        ],
        'bayar' => [
            'belum'     => ['Belum Dibayar', 'orange'],
            'menunggu'  => ['Menunggu Verifikasi', 'blue'],
            'lunas'     => ['Lunas', 'green'],
            'terlambat' => ['Terlambat', 'red'],
            'ditolak'   => ['Ditolak', 'red-solid'],
        ],
        'aduan' => [
            'diajukan' => ['Diajukan', 'blue'],
            'diproses' => ['Diproses', 'orange'],
            'selesai'  => ['Selesai', 'green'],
        ],
        'booking' => [
            'menunggu'     => ['Menunggu', 'blue'],
            'dikonfirmasi' => ['Dikonfirmasi', 'green'],
            'kedaluwarsa'  => ['Kedaluwarsa', 'orange'],
            'ditolak'      => ['Ditolak', 'red-solid'],
        ],
        'pindah' => [
            'menunggu' => ['Menunggu', 'orange'],
            'selesai'  => ['Selesai', 'green'],
            'ditolak'  => ['Ditolak', 'red-solid'],
        ],
        'kunjungan' => [
            'menunggu'     => ['Menunggu', 'blue'],
            'dikonfirmasi' => ['Dikonfirmasi', 'green'],
            'selesai'      => ['Selesai', 'green'],
            'dibatalkan'   => ['Dibatalkan', 'red-solid'],
        ],
    ];
    $m = $maps[$type][$status] ?? [ucfirst($status), 'grey'];
    return badge($m[0], $m[1]);
}

/* ---------- Komponen yang dipakai di banyak halaman ---------- */
require_once ROOT_PATH . '/components/icons.php';
require_once ROOT_PATH . '/data/data.php';
