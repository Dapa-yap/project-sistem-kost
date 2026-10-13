<?php
require_once __DIR__ . '/../config/config.php';

$id = $_GET['id'] ?? '';
$k  = get_kamar_by_id($id);
if (!$k) { redirect('guest/kamar.php'); }
$kamarUtama = $k;   // dipulihkan setelah loop kamar lain

$role = 'guest'; $pageTitle = 'Kamar ' . $k['id']; $pageId = 'guest-detail'; $activeMenu = 'kamar';
$bisaBooking = $k['status'] === 'kosong';
$pageData = ['kamar' => $k['id'], 'lantai' => $k['lantai'], 'bisaBooking' => $bisaBooking];

// Fasilitas kamar: daftar master + penanda ada/tidak
$punya = array_merge($k['fasilitas'], ['Stop kontak']);
$master = [
    ['Kasur & bantal', 'Kasur & bantal'],
    ['Lemari pakaian', 'Lemari pakaian'],
    ['Meja & kursi belajar', 'Meja & kursi belajar'],
    ['Stop kontak', 'Stop kontak'],
    ['Jendela berventilasi', 'Jendela berventilasi'],
    ['AC', 'AC'],
    ['Kipas angin', 'Kipas angin'],
];
$bersama = ['Parkir Luas', 'Dapur Umum', 'Keamanan CCTV', 'Layanan Bersih', 'Wi-Fi Cepat', 'Area Laundry', 'Ruang Belajar', 'Air Minum Isi Ulang'];

// Kamar lain yang masih kosong
$lain = array_values(array_filter(get_kamar(), fn($x) => $x['status'] === 'kosong' && $x['id'] !== $k['id']));
$lain = array_slice($lain, 0, 3);

$bookingSaya = $_SESSION['booking_guest'] ?? null;
$punyaPengajuan = $bookingSaya && $bookingSaya['status'] === 'menunggu';

include ROOT_PATH . '/header/header.php';
?>
<main>
    <div class="container detail-head">
        <?php if (isset($_GET['error'])): ?><div class="alert alert-error" role="alert">Booking gagal dikirim. Pastikan data terisi benar dan kamar masih tersedia.</div><?php endif; ?>
        <div class="detail-title">
            <h1>Kamar <?= e($k['id']) ?></h1>
            <?= status_badge('kamar', $k['status']) ?>
            <?php if ($k['tipe'] === 'Premium'): ?><span class="badge badge-peach">Premium</span><?php endif; ?>
        </div>
        <p class="detail-sub">Lantai <?= (int) $k['lantai'] ?> · <?= e($k['ukuran']) ?> · <?= e(kamar_ringkas($k)) ?></p>
        <a class="back-link" href="<?= url('guest/kamar.php') ?>"><?= icon('arrow-left', 14) ?> Kembali ke daftar kamar</a>
    </div>

    <div class="container detail-layout">
        <div>
            <!-- Galeri -->
            <div class="gallery">
                <div class="gallery-tags">
                    <?php foreach (['Tempat tidur', 'Lemari pakaian', 'Meja & kursi belajar', 'Kamar mandi umum'] as $i => $t): ?>
                        <span><b><?= $i + 1 ?></b><?= e($t) ?></span>
                    <?php endforeach; ?>
                </div>
                <div class="gallery-photos">
                    <?php for ($i = 1; $i <= 3; $i++): ?>
                        <div class="gallery-photo">Foto kamar <?= $i ?></div>
                    <?php endfor; ?>
                </div>
            </div>

            <!-- Spesifikasi -->
            <div class="spec-grid">
                <div class="spec"><?= icon('ruler') ?><small>Ukuran</small><strong><?= e($k['ukuran']) ?></strong></div>
                <div class="spec"><?= icon('layers') ?><small>Lantai</small><strong>Lantai <?= (int) $k['lantai'] ?></strong></div>
                <div class="spec"><?= icon('star') ?><small>Tipe</small><strong><?= e($k['tipe']) ?></strong></div>
                <div class="spec"><?= icon('user') ?><small>Kapasitas</small><strong><?= e($k['kapasitas']) ?></strong></div>
            </div>

            <section class="detail-section">
                <h2>Fasilitas kamar</h2>
                <ul class="check-list">
                    <?php foreach ($master as [$key, $label]):
                        $ada = in_array($key, $punya, true); ?>
                        <li class="<?= $ada ? '' : 'is-off' ?>">
                            <?= icon($ada ? 'check' : 'minus', 16) ?>
                            <span><?= e($label) ?><?= $ada ? '' : ' (tidak ada)' ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </section>

            <section class="detail-section">
                <h2>Fasilitas bersama</h2>
                <ul class="check-list">
                    <?php foreach ($bersama as $b): ?><li><?= icon('check', 16) ?><span><?= e($b) ?></span></li><?php endforeach; ?>
                </ul>
            </section>

            <section class="detail-section">
                <h2>Biaya dan ketentuan</h2>
                <div class="cost-table">
                    <div><span>Harga sewa</span><strong><?= e(rupiah($k['harga'])) ?> / bulan</strong></div>
                    <div><span>Listrik dan air</span><strong>Termasuk</strong></div>
                    <div><span>Sewa minimal</span><strong>1 bulan</strong></div>
                    <div><span>Kamar mandi</span><strong>KM umum (di lantai ini)</strong></div>
                </div>
            </section>

            <section class="detail-section">
                <h2>Aturan singkat</h2>
                <ul class="check-list">
                    <?php foreach (['Tamu hingga pukul 21.00', 'Tenang setelah pukul 22.00', 'Dilarang merokok di dalam', 'Lapor kerusakan ke pengelola'] as $a): ?>
                        <li><?= icon('check', 16) ?><span><?= e($a) ?></span></li>
                    <?php endforeach; ?>
                </ul>
            </section>
        </div>

        <!-- Kartu booking -->
        <aside class="card book-card">
            <div class="book-price"><?= e(rupiah($k['harga'])) ?> <small>/bulan</small></div>
            <div class="status-line"><?= status_badge('kamar', $k['status']) ?></div>

            <?php if ($punyaPengajuan): ?>
                <a class="btn btn-accent" href="<?= url('guest/status.php') ?>">Lihat Status Booking</a>
            <?php else: ?>
                <button class="btn btn-accent" type="button" data-open="modal-booking" <?= $bisaBooking ? '' : 'disabled' ?>>Booking Kamar</button>
            <?php endif; ?>
            <button class="btn btn-outline" type="button" data-open="modal-survey" <?= $bisaBooking ? '' : 'disabled' ?>>Jadwalkan Survei</button>
            <p class="book-note"><?= $punyaPengajuan ? 'Anda masih punya pengajuan booking (Kamar ' . e($bookingSaya['kamar']) . ').' : ($bisaBooking ? 'Booking gratis. Kamar ditahan 3 hari.' : 'Kamar ini sedang tidak tersedia.') ?></p>

            <div class="book-points">
                <div><?= icon('clock', 16) ?><span>Dikonfirmasi pengelola dalam 1 hari kerja.</span></div>
                <div><?= icon('shield', 16) ?><span>Deposit 1 bulan, dikembalikan saat Anda keluar.</span></div>
            </div>
        </aside>
    </div>

    <!-- Denah -->
    <div class="container">
        <section class="denah-block">
            <h2>Denah Lantai <?= (int) $k['lantai'] ?></h2>
            <?php $lantai = $k['lantai']; $highlight = $k['id']; $denahClickable = false; include ROOT_PATH . '/components/denah.php'; ?>
            <div class="denah-legend">
                <span><i class="t"></i>Terisi</span><span><i></i>Kosong</span><span><i class="b"></i>Booking</span><span><i class="p"></i>Diperbaiki</span><span><i class="m"></i>Kamar ini</span>
            </div>
        </section>
    </div>

    <?php if ($lain): ?>
    <section class="related" style="margin-top: 40px;">
        <div class="container">
            <h2>Kamar lain yang masih kosong</h2>
            <div class="room-grid">
                <?php foreach ($lain as $k2) { $k = $k2; include ROOT_PATH . '/components/room-card.php'; } ?>
            </div>
        </div>
    </section>
    <?php endif; ?>
</main>

<?php $k = $kamarUtama; /* kembalikan ke kamar utama */ ?>

<!-- Modal: Booking -->
<div class="modal-overlay" id="modal-booking">
    <div class="modal is-wide" role="dialog" aria-modal="true" aria-labelledby="mb-title">
        <div class="modal-head">
            <div>
                <h3 id="mb-title">Booking Kamar <?= e($k['id']) ?></h3>
                <p>Kamar ditahan 3 hari. Pengelola akan menghubungi Anda lewat WhatsApp.</p>
            </div>
            <button class="modal-x" type="button" data-close aria-label="Tutup"><?= icon('x', 16) ?></button>
        </div>
        <form id="booking-form" method="post" action="<?= url('guest/booking-simpan.php') ?>" novalidate>
            <input type="hidden" name="kamar" value="<?= e($k['id']) ?>">
            <div class="modal-info">
                <div><span>Harga sewa</span><strong><?= e(rupiah($k['harga'])) ?> / bulan</strong></div>
                <div><span>Deposit</span><strong><?= e(rupiah($k['harga'])) ?></strong></div>
            </div>
            <div class="field-row">
                <div class="field"><label for="b-nama">Nama lengkap</label><input class="input" id="b-nama" name="nama" placeholder="Nama Anda" required></div>
                <div class="field"><label for="b-wa">Nomor WhatsApp</label><input class="input" id="b-wa" name="wa" inputmode="tel" placeholder="0812xxxxxxx" required data-pattern="^[0-9+\- ]{9,16}$" data-pattern-msg="Nomor tidak valid"></div>
            </div>
            <div class="field"><label for="b-tgl">Rencana tanggal masuk</label><input class="input" type="date" id="b-tgl" name="tgl" required></div>
            <div class="modal-foot">
                <button class="btn btn-ghost" type="button" data-close>Batal</button>
                <button class="btn btn-accent" type="submit">Kirim Booking</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Jadwalkan survei -->
<div class="modal-overlay" id="modal-survey">
    <div class="modal is-wide" role="dialog" aria-modal="true" aria-labelledby="ms-title">
        <div class="modal-head">
            <div>
                <h3 id="ms-title">Jadwalkan Survei Kamar <?= e($k['id']) ?></h3>
                <p>Lihat kamar langsung sebelum memutuskan. Tanpa biaya dan tanpa kewajiban booking.</p>
            </div>
            <button class="modal-x" type="button" data-close aria-label="Tutup"><?= icon('x', 16) ?></button>
        </div>
        <form id="survey-form" novalidate>
            <div class="field-row">
                <div class="field"><label for="s-nama">Nama lengkap</label><input class="input" id="s-nama" name="nama" placeholder="Nama Anda" required></div>
                <div class="field"><label for="s-wa">Nomor WhatsApp</label><input class="input" id="s-wa" name="wa" inputmode="tel" placeholder="0812xxxxxxx" required data-pattern="^[0-9+\- ]{9,16}$" data-pattern-msg="Nomor tidak valid"></div>
            </div>
            <div class="field"><label for="s-tgl">Tanggal kunjungan</label><input class="input" type="date" id="s-tgl" name="tgl" required></div>
            <div class="field">
                <span class="field-label">Jam kunjungan</span>
                <label class="radio-card"><input type="radio" name="jam" value="09.00 – 11.00" checked required> 09.00 – 11.00</label>
                <label class="radio-card"><input type="radio" name="jam" value="13.00 – 15.00"> 13.00 – 15.00</label>
                <label class="radio-card"><input type="radio" name="jam" value="16.00 – 17.30"> 16.00 – 17.30</label>
            </div>
            <p class="field-hint">Pengelola membalas lewat WhatsApp untuk memastikan jadwal.</p>
            <div class="modal-foot">
                <button class="btn btn-ghost" type="button" data-close>Batal</button>
                <button class="btn btn-accent" type="submit">Kirim Jadwal</button>
            </div>
        </form>
    </div>
</div>

<?php include ROOT_PATH . '/footer/footer.php'; ?>
