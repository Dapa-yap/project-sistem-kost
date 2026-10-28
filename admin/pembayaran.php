<?php
require_once __DIR__ . '/../config/config.php';
require_role('admin');

$role = 'admin'; $pageTitle = 'Pembayaran'; $pageId = 'admin-pembayaran'; $activeMenu = 'pembayaran';
$pay = get_pembayaran_admin();
$pageData = ['pembayaran' => $pay];

include ROOT_PATH . '/header/header.php';
?>
<main>
    <section class="page-head"><div class="container">
        <h1>Pembayaran</h1><p>Verifikasi bukti transfer penghuni.</p>
    </div></section>

    <section class="page-body" style="padding-top:28px"><div class="container">
        <div class="split">
            <div>
                <div class="chips chip-row" data-filter-target="#pay-body" data-filter-attr="status">
                    <button class="chip active" data-filter="all">Semua</button>
                    <button class="chip" data-filter="menunggu">Menunggu</button>
                    <button class="chip" data-filter="belum">Belum Dibayar</button>
                    <button class="chip" data-filter="lunas">Lunas</button>
                    <button class="chip" data-filter="terlambat">Terlambat</button>
                </div>
                <div class="card">
                    <div class="card-head"><h2>Daftar Tagihan</h2></div>
                    <div class="table-wrap"><table class="table">
                        <thead><tr><th>Penghuni</th><th>Kamar</th><th>Periode</th><th>Jumlah</th><th>Jatuh Tempo</th><th>Status</th></tr></thead>
                        <tbody id="pay-body">
                            <?php foreach ($pay as $p): ?>
                                <tr class="is-clickable" data-pay="<?= (int) $p['id'] ?>" data-status="<?= e($p['status']) ?>">
                                    <td><?= e($p['nama']) ?></td><td><?= e($p['kamar']) ?></td><td class="nowrap"><?= e($p['periode']) ?></td>
                                    <td class="nowrap"><?= e(rupiah($p['jumlah'])) ?></td><td class="nowrap"><?= e(tgl($p['jatuh_tempo'])) ?></td>
                                    <td class="col-status"><?= status_badge('bayar', $p['status']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <tr data-empty class="empty-row hidden"><td colspan="6">Tidak ada tagihan pada kategori ini.</td></tr>
                        </tbody>
                    </table></div>
                </div>
            </div>

            <aside class="card panel-sticky">
                <h2 class="side-title">Verifikasi Bukti</h2>
                <div id="pay-panel"><p class="side-empty">Pilih salah satu tagihan untuk melihat bukti transfer.</p></div>
            </aside>
        </div>
    </div></section>
</main>
<?php include ROOT_PATH . '/components/modal-verifikasi.php'; ?>
<?php include ROOT_PATH . '/footer/footer.php'; ?>
