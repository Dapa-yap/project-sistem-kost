<?php
require_once __DIR__ . '/../config/config.php';
require_role('admin');

$role = 'admin'; $pageTitle = 'Pengaduan'; $pageId = 'admin-pengaduan'; $activeMenu = 'pengaduan';
$aduan = get_pengaduan_admin();
$cnt = fn($s) => count(array_filter($aduan, fn($a) => $a['status'] === $s));
$pageData = ['aduan' => $aduan];

include ROOT_PATH . '/header/header.php';
?>
<main>
    <section class="page-head"><div class="container">
        <h1>Pengaduan</h1><p>Tinjau keluhan penghuni dan perbarui status perbaikannya.</p>
    </div></section>

    <section class="page-body" style="padding-top:28px"><div class="container">
        <div class="stats cols-3">
            <div class="stat"><div class="stat-label">Diajukan</div><div class="stat-value" id="st-diajukan"><?= $cnt('diajukan') ?></div><div class="stat-note">Menunggu ditinjau</div></div>
            <div class="stat"><div class="stat-label">Diproses</div><div class="stat-value" id="st-diproses"><?= $cnt('diproses') ?></div><div class="stat-note">Sedang diperbaiki</div></div>
            <div class="stat"><div class="stat-label">Selesai</div><div class="stat-value" id="st-selesai"><?= $cnt('selesai') ?></div><div class="stat-note">Dalam daftar ini</div></div>
        </div>

        <div class="card">
            <div class="card-head">
                <h2>Daftar Pengaduan</h2>
                <div class="chips" data-filter-target="#aduan-body" data-filter-attr="status">
                    <button class="chip active" data-filter="all">Semua</button>
                    <button class="chip" data-filter="diajukan">Diajukan</button>
                    <button class="chip" data-filter="diproses">Diproses</button>
                    <button class="chip" data-filter="selesai">Selesai</button>
                </div>
            </div>
            <div class="table-wrap"><table class="table">
                <thead><tr><th>Kamar</th><th>Deskripsi</th><th>Foto</th><th>Tgl. Diajukan</th><th>Status</th><th>Aksi</th></tr></thead>
                <tbody id="aduan-body">
                    <?php foreach ($aduan as $a): ?>
                        <tr data-aduan="<?= (int) $a['id'] ?>" data-status="<?= e($a['status']) ?>">
                            <td><?= e($a['kamar']) ?></td><td><?= e($a['deskripsi']) ?></td>
                            <td><?= $a['foto'] ? badge('1 foto', 'blue') : badge('Tanpa foto', 'grey') ?></td>
                            <td class="nowrap"><?= e(tgl($a['tgl'])) ?></td>
                            <td class="col-status"><?= status_badge('aduan', $a['status']) ?></td>
                            <td><button class="btn <?= $a['status'] === 'diajukan' ? 'btn-brown' : 'btn-outline' ?> btn-sm" type="button" data-act="aduan">Ubah Status</button></td>
                        </tr>
                    <?php endforeach; ?>
                    <tr data-empty class="empty-row hidden"><td colspan="6">Tidak ada pengaduan pada kategori ini.</td></tr>
                </tbody>
            </table></div>
        </div>
    </div></section>
</main>
<?php include ROOT_PATH . '/components/modal-aduan.php'; ?>
<?php include ROOT_PATH . '/footer/footer.php'; ?>
