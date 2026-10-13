<?php
require_once __DIR__ . '/../config/config.php';

$role = 'guest'; $pageTitle = 'Kontak'; $pageId = 'guest-kontak'; $activeMenu = 'kontak';

include ROOT_PATH . '/header/header.php';
?>
<main>
    <section class="page-head is-center">
        <div class="container">
            <h1>Hubungi Kami</h1>
            <p>Hubungi kami untuk menjadwalkan kunjungan atau bertanya.</p>
        </div>
    </section>

    <section class="container contact-grid">
        <div class="card">
            <h2>Informasi Kontak</h2>
            <div class="contact-list">
                <div class="contact-item"><span class="contact-icon"><?= icon('pin') ?></span><div><strong>Alamat</strong><span>Jalan Jalan dengan sejarah kodoku</span></div></div>
                <div class="contact-item"><span class="contact-icon"><?= icon('phone') ?></span><div><strong>Telepon / WhatsApp</strong><span>0812-3456-7890</span></div></div>
                <div class="contact-item"><span class="contact-icon"><?= icon('mail') ?></span><div><strong>Email</strong><span>admin@kostku.com</span></div></div>
                <div class="contact-item"><span class="contact-icon"><?= icon('clock') ?></span><div><strong>Jam kunjungan</strong><span>Senin–Sabtu, 09.00–17.00</span></div></div>
            </div>
        </div>
        <div class="map-box">
            <!-- Ganti dengan <iframe> Google Maps -->
            Peta lokasi KostKu
        </div>
    </section>
</main>
<?php include ROOT_PATH . '/footer/footer.php'; ?>
