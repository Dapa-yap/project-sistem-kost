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
                <div class="contact-item"><span class="contact-icon"><?= icon('pin') ?></span><div><strong>Alamat</strong><span>Jl. Kembang Turi Gg. IIIA, Jatimulyo, Kec. Lowokwaru, Kota Malang, Jawa Timur 65141</span></div></div>
                <div class="contact-item"><span class="contact-icon"><?= icon('phone') ?></span><div><strong>Telepon / WhatsApp</strong><span>0821-4033-9455</span></div></div>
                <div class="contact-item"><span class="contact-icon"><?= icon('mail') ?></span><div><strong>Email</strong><span>admin@kostku.com</span></div></div>
                <div class="contact-item"><span class="contact-icon"><?= icon('clock') ?></span><div><strong>Jam kunjungan</strong><span>Senin–Sabtu, 09.00–17.00</span></div></div>
            </div>
        </div>
       <div class="map-box">
    <iframe
        src="https://maps.google.com/maps?q=-7.9425844,112.6150476&z=17&output=embed"
        title="Lokasi KostKu"
        loading="lazy"
        referrerpolicy="no-referrer-when-downgrade"
        allowfullscreen></iframe>
</div>
    </section>
</main>
<?php include ROOT_PATH . '/footer/footer.php'; ?>
