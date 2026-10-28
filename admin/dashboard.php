<?php
require_once __DIR__ . '/../config/config.php';
require_role('admin');

$role = 'admin'; $pageTitle = 'Dashboard Pengelola'; $pageId = 'admin-dashboard'; $activeMenu = 'dashboard';

$kamar   = get_kamar();
$total   = count($kamar);
$terisi  = count(array_filter($kamar, fn($k) => $k['status'] === 'terisi'));
$kosong  = count(array_filter($kamar, fn($k) => $k['status'] === 'kosong'));
$hunian  = $total ? round($terisi / $total * 100) : 0;

$pay     = get_pembayaran_admin();
$menunggu = array_values(array_filter($pay, fn($p) => $p['status'] === 'menunggu'));
$booking = array_values(array_filter(get_booking(), fn($b) => $b['status'] === 'menunggu'));
$aduan   = get_pengaduan_admin();
$aduanBaru = count(array_filter($aduan, fn($a) => $a['status'] === 'diajukan'));
$aduanTampil = array_slice(array_values(array_filter($aduan, fn($a) => $a['status'] !== 'selesai')), 0, 3);

$pageData = ['pembayaran' => $pay, 'aduan' => $aduan];

include ROOT_PATH . '/header/header.php';
?>
<main>
    <section class="page-head"><div class="container">
        <h1>Dashboard Pengelola</h1><p>Ringkasan operasional kost hari ini.</p>
    </div></section>

    <section class="page-body" style="padding-top:28px"><div class="container">
        <div class="stats cols-4">
            <div class="stat"><div class="stat-label">Total Kamar</div><div class="stat-value"><?= $total ?></div><div class="stat-note"><?= $terisi ?> terisi · <?= $kosong ?> kosong</div></div>
            <div class="stat"><div class="stat-label">Tingkat Hunian</div><div class="stat-value"><?= $hunian ?>%</div><div class="stat-note"><?= $terisi ?> dari <?= $total ?> kamar terisi</div></div>
            <div class="stat"><div class="stat-label">Menunggu Verifikasi</div><div class="stat-value" id="stat-menunggu"><?= count($menunggu) ?></div><div class="stat-note">Bukti pembayaran</div></div>
            <div class="stat"><div class="stat-label">Pengaduan Baru</div><div class="stat-value" id="stat-aduan"><?= $aduanBaru ?></div><div class="stat-note">Perlu ditindaklanjuti</div></div>
        </div>

        <div class="split split-wide">
            <div class="card">
                <div class="card-head"><h2>Verifikasi Pembayaran</h2><a class="link" href="<?= url('admin/pembayaran.php') ?>">Lihat semua</a></div>
                <div class="table-wrap"><table class="table">
                    <thead><tr><th>Kamar</th><th>Penghuni</th><th>Periode</th><th>Jumlah</th><th>Aksi</th></tr></thead>
                    <tbody id="verif-body">
                        <?php foreach ($menunggu as $i => $p): ?>
                            <tr data-pay="<?= (int) $p['id'] ?>">
                                <td><?= e($p['kamar']) ?></td><td><?= e($p['nama']) ?></td><td><?= e($p['periode']) ?></td><td class="nowrap"><?= e(rupiah($p['jumlah'])) ?></td>
                                <td><button class="btn <?= $i === 0 ? 'btn-accent' : 'btn-outline' ?> btn-sm" type="button" data-act="verifikasi">Verifikasi</button></td>
                            </tr>
                        <?php endforeach; ?>
                        <tr data-empty class="empty-row <?= $menunggu ? 'hidden' : '' ?>"><td colspan="5">Tidak ada bukti yang menunggu verifikasi.</td></tr>
                    </tbody>
                </table></div>
            </div>

            <div class="card">
                <div class="card-head"><h2>Booking Masuk</h2><a class="link" href="<?= url('admin/booking.php') ?>">Lihat semua</a></div>
                <div class="mini-list">
                    <?php foreach ($booking as $b): ?>
                        <a class="mini-item" href="<?= url('admin/booking.php') ?>">
                            <div class="row"><strong><?= e($b['nama']) ?></strong><?= $b['urgent'] ? badge('Segera', 'orange') : badge('Baru', 'peach') ?></div>
                            <small>Kamar <?= e($b['kamar']) ?> · batas <?= e(tgl($b['batas'])) ?></small>
                        </a>
                    <?php endforeach; ?>
                    <?php if (!$booking): ?><p class="side-empty">Belum ada booking baru.</p><?php endif; ?>
                </div>
            </div>
        </div>

        <div class="card" style="margin-top:20px">
            <div class="card-head"><h2>Pengaduan Terbaru</h2><a class="link" href="<?= url('admin/pengaduan.php') ?>">Lihat semua</a></div>
            <div class="table-wrap"><table class="table">
                <thead><tr><th>Kamar</th><th>Deskripsi</th><th>Tgl. Pengaduan</th><th>Status</th><th>Aksi</th></tr></thead>
                <tbody>
                    <?php foreach ($aduanTampil as $a): ?>
                        <tr data-aduan="<?= (int) $a['id'] ?>" data-status="<?= e($a['status']) ?>">
                            <td><?= e($a['kamar']) ?></td><td><?= e($a['deskripsi']) ?></td><td class="nowrap"><?= e(tgl($a['tgl'])) ?></td>
                            <td class="col-status"><?= status_badge('aduan', $a['status']) ?></td>
                            <td><button class="btn btn-outline btn-sm" type="button" data-act="aduan">Tindak Lanjut</button></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table></div>
        </div>
    </div></section>
</main>
<?php include ROOT_PATH . '/components/modal-verifikasi.php'; include ROOT_PATH . '/components/modal-aduan.php'; ?>
<?php include ROOT_PATH . '/footer/footer.php'; ?>
