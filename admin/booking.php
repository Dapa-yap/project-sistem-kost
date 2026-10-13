<?php
require_once __DIR__ . '/../config/config.php';
require_role('admin');

$role = 'admin'; $pageTitle = 'Booking Kamar'; $pageId = 'admin-booking'; $activeMenu = 'booking';
$booking = get_booking();
$cnt = fn($s) => count(array_filter($booking, fn($b) => $b['status'] === $s));
$urgent = count(array_filter($booking, fn($b) => $b['status'] === 'menunggu' && $b['urgent']));
$pageData = ['booking' => $booking];

include ROOT_PATH . '/header/header.php';
?>
<main>
    <section class="page-head"><div class="container">
        <h1>Booking Kamar</h1><p>Konfirmasi atau tolak booking dari calon penghuni sebelum batas waktunya habis.</p>
    </div></section>

    <section class="page-body" style="padding-top:28px"><div class="container">
        <div class="stats cols-4">
            <div class="stat"><div class="stat-label">Menunggu Konfirmasi</div><div class="stat-value" id="st-menunggu"><?= $cnt('menunggu') ?></div><div class="stat-note">Perlu ditinjau</div></div>
            <div class="stat"><div class="stat-label">Dikonfirmasi</div><div class="stat-value" id="st-dikonfirmasi"><?= $cnt('dikonfirmasi') ?></div><div class="stat-note">Siap menghuni</div></div>
            <div class="stat"><div class="stat-label">Batas Waktu 24 Jam</div><div class="stat-value" id="st-urgent"><?= $urgent ?></div><div class="stat-note">Segera diputuskan</div></div>
            <div class="stat"><div class="stat-label">Kedaluwarsa</div><div class="stat-value" id="st-kedaluwarsa"><?= $cnt('kedaluwarsa') ?></div><div class="stat-note">Kamar dilepas kembali</div></div>
        </div>

        <div class="split">
            <div class="card">
                <div class="card-head">
                    <h2>Daftar Booking</h2>
                    <div class="chips" data-filter-target="#booking-body" data-filter-attr="status">
                        <button class="chip active" data-filter="all">Semua</button>
                        <button class="chip" data-filter="menunggu">Menunggu</button>
                        <button class="chip" data-filter="dikonfirmasi">Dikonfirmasi</button>
                        <button class="chip" data-filter="kedaluwarsa">Kedaluwarsa</button>
                    </div>
                </div>
                <div class="table-wrap"><table class="table">
                    <thead><tr><th>Calon Penghuni</th><th>Kamar</th><th>Tgl. Booking</th><th>Batas Waktu</th><th>Status</th></tr></thead>
                    <tbody id="booking-body">
                        <?php foreach ($booking as $b): ?>
                            <tr class="is-clickable" data-booking="<?= (int) $b['id'] ?>" data-status="<?= e($b['status']) ?>">
                                <td><?= e($b['nama']) ?></td><td><?= e($b['kamar']) ?></td>
                                <td class="nowrap"><?= e(tgl($b['tgl'])) ?></td><td class="nowrap"><?= e(tgl($b['batas'])) ?></td>
                                <td class="col-status"><?= status_badge('booking', $b['status']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <tr data-empty class="empty-row hidden"><td colspan="5">Tidak ada booking pada kategori ini.</td></tr>
                    </tbody>
                </table></div>
            </div>

            <aside class="card panel-sticky">
                <h2 class="side-title">Detail Booking</h2>
                <div id="booking-panel"><p class="side-empty">Pilih salah satu booking untuk melihat detailnya.</p></div>
            </aside>
        </div>
    </div></section>
</main>
<?php include ROOT_PATH . '/footer/footer.php'; ?>
