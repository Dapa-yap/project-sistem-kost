<?php
require_once __DIR__ . '/../config/config.php';
require_role('admin');

$role = 'admin'; $pageTitle = 'Data Kamar'; $pageId = 'admin-kamar'; $activeMenu = 'kamar';
$kamar = get_kamar();
$first = reset($kamar);
$pageData = ['kamar' => $kamar];
$fasilitasMaster = ['AC', 'Kipas angin', 'Wi-Fi', 'Kasur & bantal', 'Lemari pakaian', 'Meja & kursi belajar', 'Jendela berventilasi'];

include ROOT_PATH . '/header/header.php';
?>
<main>
    <section class="page-head"><div class="container">
        <h1>Data Kamar</h1><p>Kelola denah, status, dan harga kamar. Lantai 1 untuk parkir, kamar ada di lantai 2 dan 3.</p>
    </div></section>

    <section class="page-body" style="padding-top:28px"><div class="container">
        <div class="chip-row">
            <div class="chips" data-filter-target="#floor-wrap" data-filter-attr="lantai">
                <button class="chip active" data-filter="all">Semua</button>
                <button class="chip" data-filter="2">Lantai 2</button>
                <button class="chip" data-filter="3">Lantai 3</button>
            </div>
            <button class="chip" type="button" data-open="modal-denah">Lihat Denah</button>
        </div>

        <div class="split">
            <div class="card">
                <h2>Denah Kamar</h2>
                <div class="legend"><span><i class="t"></i>Terisi</span><span><i></i>Kosong</span><span><i class="b"></i>Booking</span><span><i class="p"></i>Diperbaiki</span></div>
                <div id="floor-wrap">
                    <?php foreach ([2 => 'A', 3 => 'B'] as $lt => $blok): ?>
                        <div class="floor-block" data-lantai="<?= $lt ?>">
                            <h4>Lantai <?= $lt ?></h4>
                            <div class="room-tiles">
                                <?php foreach ($kamar as $k): if ($k['lantai'] !== $lt) continue; ?>
                                    <button type="button" class="denah-room st-<?= e($k['status']) ?>" data-room="<?= e($k['id']) ?>">
                                        <strong><?= e($k['id']) ?></strong><span><?= e($k['tipe']) ?></span>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <aside class="card panel-sticky" id="room-panel">
                <div class="card-head" style="margin-bottom:10px"><h2 id="kp-id" style="font-size:26px;color:var(--navy)">-</h2><span id="kp-badge"></span></div>
                <div class="kv">
                    <div class="kv-item"><small>Tipe</small><strong id="kp-tipe">-</strong></div>
                    <div class="kv-item"><small>Lantai</small><strong id="kp-lantai">-</strong></div>
                    <div class="kv-item"><small>Ukuran</small><strong id="kp-ukuran">-</strong></div>
                    <div class="kv-item"><small>Harga</small><strong id="kp-harga">-</strong></div>
                    <div class="kv-item"><small>Penghuni</small><strong id="kp-penghuni">-</strong></div>
                    <div class="kv-item"><small>Fasilitas</small><strong id="kp-fasilitas">-</strong></div>
                </div>
                <div class="side-actions"><button class="btn btn-outline" type="button" id="kp-ubah">Ubah Data</button></div>
            </aside>
        </div>
    </div></section>
</main>

<!-- Modal: denah lantai -->
<div class="modal-overlay denah-modal" id="modal-denah">
    <div class="modal is-wide" role="dialog" aria-modal="true" aria-labelledby="md-title">
        <div class="modal-head">
            <div><h3 id="md-title">Denah kamar</h3><p>Klik kamar untuk melihat detailnya.</p></div>
            <button class="modal-x" type="button" data-close aria-label="Tutup"><?= icon('x', 16) ?></button>
        </div>
        <?php foreach ([2, 3] as $lantai): ?>
            <h4>Denah Lantai <?= $lantai ?></h4>
            <?php $denahClickable = true; $denahLabel = 'status'; $highlight = ''; include ROOT_PATH . '/components/denah.php'; ?>
        <?php endforeach; ?>
        <div class="modal-foot"><button class="btn btn-primary" type="button" data-close>Tutup</button></div>
    </div>
</div>

<!-- Modal: ubah data kamar -->
<div class="modal-overlay" id="modal-ubah">
    <div class="modal is-wide" role="dialog" aria-modal="true" aria-labelledby="mu-title">
        <div class="modal-head">
            <div><h3 id="mu-title">Ubah data kamar</h3></div>
            <button class="modal-x" type="button" data-close aria-label="Tutup"><?= icon('x', 16) ?></button>
        </div>
        <form id="ubah-form" novalidate>
            <div class="field-row">
                <div class="field"><label for="u-nomor">Nomor kamar</label><input class="input" id="u-nomor" name="nomor" readonly></div>
                <div class="field"><label for="u-lantai">Lantai</label><input class="input" id="u-lantai" name="lantai" readonly></div>
            </div>
            <div class="field-row">
                <div class="field"><label for="u-tipe">Tipe</label>
                    <select class="select" id="u-tipe" name="tipe"><option>Standar</option><option>Premium</option></select></div>
                <div class="field"><label for="u-ukuran">Ukuran</label><input class="input" id="u-ukuran" name="ukuran" placeholder="3 × 3 m" required></div>
            </div>
            <div class="field"><label for="u-status">Status</label>
                <select class="select" id="u-status" name="status">
                    <option value="kosong">Kosong</option><option value="terisi">Terisi</option>
                    <option value="booking">Booking</option><option value="diperbaiki">Diperbaiki</option>
                </select></div>
            <div class="field"><label for="u-harga">Harga per bulan (Rp)</label><input class="input" id="u-harga" name="harga" inputmode="numeric" placeholder="600000" required data-pattern="^[0-9]{5,8}$" data-pattern-msg="Isi angka saja, contoh 600000"></div>
            <div class="field">
                <span class="field-label">Fasilitas</span>
                <div id="u-fasilitas">
                    <?php foreach ($fasilitasMaster as $f): ?>
                        <label class="pill-check"><input type="checkbox" name="fasilitas" value="<?= e($f) ?>"><span><?= e($f) ?></span></label>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="modal-foot">
                <button class="btn btn-ghost" type="button" data-close>Batal</button>
                <button class="btn btn-accent" type="submit">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>
<?php include ROOT_PATH . '/footer/footer.php'; ?>
