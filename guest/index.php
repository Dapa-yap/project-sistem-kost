<?php
require_once __DIR__ . '/../config/config.php';

$role = 'guest'; $pageTitle = 'Beranda'; $pageId = 'guest-beranda'; $activeMenu = 'beranda';

$semua   = get_kamar();
$tersedia = array_filter($semua, fn($k) => $k['status'] === 'kosong');
// 3 kamar pilihan: ambil yang tersedia dulu
$pilihan = array_slice(array_values($tersedia), 0, 3);

include ROOT_PATH . '/header/header.php';
?>
<main>
    <!-- Hero -->
    <section class="hero">
        <div class="container hero-grid">
            <div>
                <span class="hero-tag">Pilihan Mahasiswa Perantau</span>
                <h1>Ruang Nyaman Untuk <em>Cerita Barumu.</em></h1>
                <p class="lead">Tempat tinggal idaman dengan fasilitas lengkap, keamanan terjamin, dan lokasi strategis. Temukan kamar kosong dan jadwalkan kunjungan hari ini.</p>
                <div class="hero-cta">
                    <a class="btn btn-accent" href="<?= url('guest/kamar.php?filter=kosong') ?>">Cari Kamar Kosong</a>
                    <a class="btn btn-ghost" href="<?= url('guest/fasilitas.php') ?>">Lihat Fasilitas</a>
                </div>
            </div>
            <div class="hero-visual">
                <div class="hero-visual-inner">
                    <!-- Ganti dengan foto kost: <img src="<?= asset('img/hero.jpg') ?>" alt="Kost"> -->
                    Foto kost
                </div>
                <div class="hero-status">
                    <span class="dot"></span>
                    <div><small>Status Saat Ini</small><strong><?= count($tersedia) ?> Kamar Tersedia</strong></div>
                </div>
            </div>
        </div>
    </section>

    <!-- Pilihan kamar -->
    <section class="section section-tint">
        <div class="container">
            <div class="section-title">
                <h2>Pilihan Kamar KostKu</h2>
                <p>Temukan kamar yang sesuai dengan kebutuhan dan gaya hidupmu. Semua kamar dirancang untuk produktivitas dan kenyamanan maksimal.</p>
            </div>
            <div class="room-grid">
                <?php foreach ($pilihan as $k) { include ROOT_PATH . '/components/room-card.php'; } ?>
            </div>
            <div class="section-more">
                <a class="btn btn-outline" href="<?= url('guest/kamar.php') ?>">Lihat Semua Kamar →</a>
            </div>
        </div>
    </section>

    <!-- Fasilitas bersama -->
    <section class="section">
        <div class="container">
            <div class="section-title is-left">
                <h2>Fasilitas Bersama</h2>
                <p>Kenyamanan ekstra di luar kamar. Nikmati fasilitas bersama yang menunjang aktivitas keseharian Anda.</p>
            </div>
            <div class="facility-grid is-compact">
                <?php foreach (array_slice(get_fasilitas_bersama(), 0, 4) as $f): ?>
                    <div class="facility">
                        <span class="facility-icon"><?= icon($f['icon']) ?></span>
                        <h3><?= e($f['nama']) ?></h3>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
</main>
<?php include ROOT_PATH . '/footer/footer.php'; ?>
