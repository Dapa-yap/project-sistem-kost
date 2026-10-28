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
        </div>
    </section>
</main>
<?php include ROOT_PATH . '/footer/footer.php'; ?>
