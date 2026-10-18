<?php
require_once __DIR__ . '/../config/config.php';

$role = 'guest'; $pageTitle = 'Fasilitas'; $pageId = 'guest-fasilitas'; $activeMenu = 'fasilitas';

include ROOT_PATH . '/header/header.php';
?>
<main>
    <section class="page-head is-center">
        <div class="container">
            <h1>Fasilitas Bersama</h1>
            <p>Semua penghuni bebas memakai fasilitas ini untuk mendukung aktivitas harian.</p>
        </div>
    </section>

    <section class="section" style="padding-top: 8px;">
        <div class="container">
            <div class="facility-grid">
                <?php foreach (get_fasilitas_bersama() as $f): ?>
                    <div class="facility">
                        <span class="facility-icon"><?= icon($f['icon']) ?></span>
                        <h3><?= e($f['nama']) ?></h3>
                        <p><?= e($f['desc']) ?></p>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="rules-block">
                <div>
                    <h2>Aturan Singkat</h2>
                    <p>Agar semua penghuni nyaman.</p>
                </div>
                <ul class="rules-list">
                    <?php foreach (get_aturan() as $a): ?><li><?= e($a) ?></li><?php endforeach; ?>
                </ul>
            </div>

            <div class="cta-banner">
                <div>
                    <h3>Ingin melihat langsung?</h3>
                    <p>Jadwalkan kunjungan, pengelola akan menemani Anda.</p>
                </div>
                <a class="btn btn-accent" href="#modal-kunjungan" data-open="modal-kunjungan">Jadwalkan Kunjungan</a>
            </div>
        </div>
    </section>
</main>
<!-- Modal: Jadwalkan kunjungan -->
<div class="modal-overlay" id="modal-kunjungan">
    <div class="modal is-wide" role="dialog" aria-modal="true" aria-labelledby="mk-title">
        <div class="modal-head">
            <div>
                <h3 id="mk-title">Jadwalkan Kunjungan</h3>
                <p>Datang dan lihat kost langsung. Gratis, tanpa kewajiban booking.</p>
            </div>
            <button class="modal-x" type="button" data-close aria-label="Tutup"><?= icon('x', 16) ?></button>
        </div>
        <form id="kunjungan-form"
      data-kamar-url="<?= url('guest/kamar.php') ?>"
      novalidate>
            <div class="field-row">
                <div class="field"><label for="k-nama">Nama lengkap</label><input class="input" id="k-nama" name="nama" placeholder="Nama Anda" required></div>
                <div class="field"><label for="k-wa">Nomor WhatsApp</label><input class="input" id="k-wa" name="wa" inputmode="tel" placeholder="0812xxxxxxx" required data-pattern="^[0-9+\- ]{9,16}$" data-pattern-msg="Nomor tidak valid"></div>
            </div>
            <div class="field-row">
                <div class="field"><label for="k-tgl">Tanggal kunjungan</label><input class="input" type="date" id="k-tgl" name="tgl" required></div>
            </div>
            <div class="field"><label for="k-catatan">Catatan (opsional)</label><textarea class="textarea" id="k-catatan" name="catatan" placeholder="Misalnya: datang bersama orang tua"></textarea></div>
            <p class="field-hint">Kunjungan Senin–Sabtu. Pengelola membalas lewat WhatsApp untuk memastikan jadwal.</p>
            <div class="modal-foot">
                <button class="btn btn-ghost" type="button" data-close>Batal</button>
                <button class="btn btn-accent" type="submit">Kirim Jadwal</button>
            </div>
        </form>
    </div>
</div>
<?php include ROOT_PATH . '/footer/footer.php'; ?>
