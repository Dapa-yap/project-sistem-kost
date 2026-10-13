<?php
require_once __DIR__ . '/../config/config.php';
require_role('penghuni');

$role = 'penghuni'; $pageTitle = 'Pengaduan'; $pageId = 'penghuni-pengaduan'; $activeMenu = 'pengaduan';

$user = current_user();
$riwayat = get_pengaduan_penghuni();
usort($riwayat, fn($a, $b) => strcmp($b['tgl'], $a['tgl']));

include ROOT_PATH . '/header/header.php';
?>
<main>
    <section class="page-head">
        <div class="container">
            <h1>Pengaduan</h1>
            <p>Laporkan kerusakan atau keluhan di kamar Anda.</p>
        </div>
    </section>

    <section class="page-body" style="padding-top: 28px;">
        <div class="container aduan-grid">
            <div class="card">
                <h2 style="margin-bottom:16px">Buat Pengaduan</h2>
                <form id="aduan-form" novalidate>
                    <div class="field">
                        <label for="a-kamar">Kamar</label>
                        <input class="input" id="a-kamar" value="Kamar <?= e($user['kamar']) ?>" readonly>
                    </div>
                    <div class="field">
                        <label for="a-desk">Deskripsi masalah</label>
                        <textarea class="textarea" id="a-desk" name="deskripsi" placeholder="Jelaskan masalah yang Anda alami" required data-minlength="10"></textarea>
                    </div>
                    <div class="field">
                        <span class="field-label">Foto (opsional)</span>
                        <label class="dropzone" tabindex="0" data-max-mb="5">
                            <strong>Tarik foto ke sini atau klik untuk memilih</strong>
                            <small>JPG atau PNG, maks. 5 MB</small>
                            <input type="file" name="foto" accept="image/png,image/jpeg">
                        </label>
                    </div>
                    <button class="btn btn-accent btn-block" type="submit">Kirim Pengaduan</button>
                </form>
            </div>

            <div class="card">
                <h2 style="margin-bottom:16px">Riwayat Pengaduan</h2>
                <div id="aduan-list">
                    <?php foreach ($riwayat as $a): ?>
                        <div class="list-item">
                            <div class="row"><strong><?= e($a['judul']) ?></strong><?= status_badge('aduan', $a['status']) ?></div>
                            <small>Dilaporkan <?= e(tgl($a['tgl'])) ?> · <?= e($a['info']) ?></small>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </section>
</main>
<?php include ROOT_PATH . '/footer/footer.php'; ?>
