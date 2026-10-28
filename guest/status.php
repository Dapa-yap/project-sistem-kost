<?php
require_once __DIR__ . '/../config/config.php';

$role = 'guest'; $pageTitle = 'Status Pengajuan'; $pageId = 'guest-status'; $activeMenu = 'status';

kedaluwarsakan_booking();
$b = !empty($_SESSION['booking_guest']) ? get_booking_by_id($_SESSION['booking_guest']) : null;
$k = $b ? get_kamar_by_id($b['kamar']) : null;

// Tampilan per status
$states = [
    'menunggu' => [
        'emoji' => '⏳', 'tone' => 'wait', 'judul' => 'Menunggu Persetujuan',
        'desc'  => 'Pengajuan booking Kamar %s sudah diterima sistem dan sedang menunggu konfirmasi dari pengelola kost.',
        'steps' => ['done', 'current', 'todo'],
        'info_t' => 'Belum bisa masuk ke dashboard penghuni.',
        'info_d' => 'Setelah pengelola menyetujui booking, status akan berubah menjadi disetujui dan penghuni dapat melanjutkan ke dashboard.',
    ],
    'disetujui' => [
        'emoji' => '✅', 'tone' => 'ok', 'judul' => 'Booking Disetujui',
        'desc'  => 'Selamat! Booking Kamar %s sudah disetujui pengelola. Kamar ditahan untuk Anda.',
        'steps' => ['done', 'done', 'done'],
        'info_t' => 'Akun penghuni dibuat oleh pengelola.',
        'info_d' => 'Pengelola akan membuatkan akun penghuni saat Anda masuk, lalu mengirim username dan password lewat WhatsApp. Setelah itu Anda bisa masuk ke dashboard.',
    ],
    'ditolak' => [
        'emoji' => '❌', 'tone' => 'no', 'judul' => 'Booking Ditolak',
        'desc'  => 'Maaf, pengajuan booking Kamar %s belum dapat disetujui oleh pengelola.',
        'steps' => ['done', 'done', 'fail'],
        'info_t' => 'Anda bisa memilih kamar lain.',
        'info_d' => 'Lihat daftar kamar yang masih kosong dan ajukan booking lagi, atau hubungi pengelola untuk informasi lebih lanjut.',
    ],
];
$st = $b ? ($states[$b['status']] ?? $states['menunggu']) : null;

include ROOT_PATH . '/header/header.php';
?>
<main class="status-wrap">
<?php if (!$b || !$k): ?>
    <div class="status-card is-empty">
        <div class="status-emoji tone-wait">🔍</div>
        <p class="status-eyebrow">STATUS PENGAJUAN</p>
        <h1>Belum ada pengajuan</h1>
        <p class="status-desc">Anda belum mengajukan booking kamar. Pilih kamar yang masih kosong, lalu klik “Booking Kamar”.</p>
        <a class="btn btn-accent btn-block" href="<?= url('guest/kamar.php?filter=kosong') ?>">Lihat Kamar Kosong</a>
    </div>
<?php else: ?>
    <div class="status-card">
        <?php if (isset($_GET['baru'])): ?>
            <div class="alert alert-info">Booking berhasil dikirim. Pengelola akan menghubungi Anda lewat WhatsApp <?= e($b['wa']) ?>.</div>
        <?php endif; ?>

        <div class="status-emoji tone-<?= e($st['tone']) ?>"><?= $st['emoji'] ?></div>
        <p class="status-eyebrow">STATUS PENGAJUAN</p>
        <h1><?= e($st['judul']) ?></h1>
        <p class="status-desc"><?= e(sprintf($st['desc'], $k['id'])) ?></p>

        <div class="status-facts">
            <div><small>Kamar</small><strong><?= e($k['id']) ?></strong></div>
            <div><small>Lantai</small><strong><?= (int) $k['lantai'] ?></strong></div>
            <div><small>Harga</small><strong><?= e(rupiah($k['harga'])) ?> / bulan</strong></div>
        </div>

        <ol class="stepper">
            <?php
            $labels = [
                ['Pengajuan dikirim', 'Booking berhasil diajukan.'],
                ['Menunggu persetujuan', 'Pengelola sedang memeriksa pengajuan.'],
                [$b['status'] === 'ditolak' ? 'Booking ditolak' : 'Booking disetujui', $b['status'] === 'ditolak' ? 'Pengajuan tidak dapat dilanjutkan.' : 'Dashboard penghuni akan aktif setelah disetujui.'],
            ];
            foreach ($labels as $i => [$t, $d]):
                $state = $st['steps'][$i]; ?>
                <li class="step is-<?= e($state) ?>">
                    <span class="step-dot"><?= $state === 'done' ? icon('check', 16) : ($state === 'fail' ? icon('x', 16) : ($i + 1)) ?></span>
                    <div><strong><?= e($t) ?></strong><small><?= e($d) ?></small></div>
                </li>
            <?php endforeach; ?>
        </ol>

        <div class="status-note">
            <strong><?= e($st['info_t']) ?></strong>
            <p><?= e($st['info_d']) ?></p>
        </div>

        <?php if ($b['status'] === 'disetujui'): ?>
            <a class="btn btn-accent btn-block" href="<?= url('guest/login.php?role=penghuni') ?>">Sudah punya akun? Masuk</a>
        <?php elseif ($b['status'] === 'ditolak'): ?>
            <a class="btn btn-accent btn-block" href="<?= url('guest/kamar.php?filter=kosong') ?>">Pilih Kamar Lain</a>
        <?php endif; ?>
        <a class="btn btn-outline btn-block status-back" href="<?= url('guest/index.php') ?>">Kembali ke Beranda</a>

    </div>
<?php endif; ?>
</main>
<?php include ROOT_PATH . '/footer/footer.php'; ?>
