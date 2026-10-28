<?php
require_once __DIR__ . '/../config/config.php';
require_role('penghuni');

$role = 'penghuni'; $pageTitle = 'Pembayaran'; $pageId = 'penghuni-pembayaran'; $activeMenu = 'pembayaran';

$tagihan = get_tagihan_penghuni();
$aktif = null;
foreach ($tagihan as $t) { if (in_array($t['status'], ['belum', 'terlambat', 'ditolak'], true)) { $aktif = $t; } }   // tertua yang belum beres
$lunas = array_filter($tagihan, fn($t) => $t['status'] === 'lunas');
$totalLunas = array_sum(array_column($lunas, 'jumlah'));
$sisaHari = $aktif ? max(0, (int) ceil((strtotime($aktif['jatuh_tempo']) - strtotime(date('Y-m-d'))) / 86400)) : null;

$pageData = ['bukaBayar' => $_GET['bayar'] ?? null];

include ROOT_PATH . '/header/header.php';
?>
<main>
    <section class="page-head">
        <div class="container">
            <h1>Pembayaran</h1>
            <p>Pantau tagihan dan unggah bukti pembayaran.</p>
        </div>
    </section>

    <section class="page-body" style="padding-top: 28px;">
        <div class="container">
            <div class="stats cols-3">
                <div class="stat">
                    <div class="stat-label">Tagihan Aktif</div>
                    <div class="stat-value is-money" id="stat-aktif"><?= $aktif ? e(rupiah($aktif['jumlah'])) : 'Rp 0' ?></div>
                    <div class="stat-note" id="stat-aktif-note"><?= $aktif ? 'Periode ' . e($aktif['periode']) : 'Tidak ada tagihan aktif' ?></div>
                </div>
                <div class="stat">
                    <div class="stat-label">Dibayar Tahun Ini</div>
                    <div class="stat-value is-money" id="stat-lunas"><?= e(rupiah($totalLunas)) ?></div>
                    <div class="stat-note" id="stat-lunas-note"><?= count($lunas) ?> pembayaran lunas</div>
                </div>
                <div class="stat">
                    <div class="stat-label">Jatuh Tempo Berikutnya</div>
                    <div class="stat-value" id="stat-tempo"><?= $aktif ? e(date('j', strtotime($aktif['jatuh_tempo'])) . ' ' . explode(' ', tgl($aktif['jatuh_tempo']))[1]) : '-' ?></div>
                    <div class="stat-note" id="stat-tempo-note"><?= $aktif ? 'Sisa ' . $sisaHari . ' hari' : 'Semua tagihan tertangani' ?></div>
                </div>
            </div>

            <div class="chips pay-toolbar" data-filter-target="#pay-body" data-filter-attr="status">
                <button class="chip active" data-filter="all">Semua</button>
                <button class="chip" data-filter="belum">Belum Dibayar</button>
                <button class="chip" data-filter="menunggu">Menunggu Verifikasi</button>
                <button class="chip" data-filter="lunas">Lunas</button>
            </div>

            <div class="table-wrap">
                <table class="table pay-table">
                    <thead>
                        <tr><th>Periode</th><th>Tgl. Tagihan</th><th>Jatuh Tempo</th><th>Jumlah</th><th>Status</th><th>Bukti</th><th>Aksi</th></tr>
                    </thead>
                    <tbody id="pay-body">
                        <?php foreach ($tagihan as $t): ?>
                            <tr data-status="<?= e($t['status']) ?>" data-periode="<?= e($t['periode']) ?>"
                                data-row="<?= e(json_encode($t, JSON_UNESCAPED_UNICODE)) ?>">
                                <td class="nowrap"><?= e($t['periode']) ?></td>
                                <td class="nowrap"><?= e(tgl($t['tgl_tagihan'])) ?></td>
                                <td class="nowrap"><?= e(tgl($t['jatuh_tempo'])) ?></td>
                                <td class="nowrap"><?= e(rupiah($t['jumlah'])) ?></td>
                                <td class="col-status"><?= status_badge('bayar', $t['status']) ?></td>
                                <td class="bukti"><?= $t['bukti'] ? '<a class="file" target="_blank" rel="noopener" href="' . e(url('api/berkas.php?f=' . urlencode($t['bukti']))) . '">Lihat foto</a>' : '—' ?></td>
                                <td class="col-aksi"><div class="actions">
                                    <?php if (in_array($t['status'], ['belum', 'terlambat', 'ditolak'], true)): ?>
                                        <button class="btn btn-outline btn-sm" type="button" data-act="bukti">Kirim Bukti</button>
                                        <button class="btn btn-accent btn-sm" type="button" data-act="bayar">Bayar</button>
                                    <?php else: ?>
                                        <button class="btn btn-outline btn-sm" type="button" data-act="detail">Detail</button>
                                    <?php endif; ?>
                                </div></td>
                            </tr>
                        <?php endforeach; ?>
                        <tr data-empty class="empty-row hidden"><td colspan="7">Belum ada tagihan pada kategori ini.</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</main>

<!-- Modal: transfer ke rekening -->
<div class="modal-overlay" id="modal-rekening">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="mrk-title">
        <div class="modal-head">
            <div>
                <h3 id="mrk-title">Bayar tagihan</h3>
                <p id="mrk-sub">Transfer sesuai jumlah, lalu unggah bukti transfer.</p>
            </div>
            <button class="modal-x" type="button" data-close aria-label="Tutup"><?= icon('x', 16) ?></button>
        </div>
        <div class="field-label" style="margin-bottom:8px">Transfer ke rekening <?= e(BANK_NAMA) ?></div>
        <div class="va-number"><strong id="mrk-number"><?= e(BANK_REKENING) ?></strong><button type="button" id="mrk-copy">Salin</button></div>
        <div class="modal-info">
            <div><span>Atas nama</span><strong><?= e(BANK_ATAS_NAMA) ?></strong></div>
            <div><span>Jumlah transfer</span><strong id="mrk-total">-</strong></div>
            <div><span>Bayar sebelum</span><strong id="mrk-tempo">-</strong></div>
        </div>
        <div class="modal-foot">
            <button class="btn btn-outline" type="button" data-close>Tutup</button>
            <button class="btn btn-accent" type="button" id="mrk-upload">Unggah Bukti</button>
        </div>
    </div>
</div>

<!-- Modal: unggah bukti -->
<div class="modal-overlay" id="modal-bukti">
    <div class="modal is-wide" role="dialog" aria-modal="true" aria-labelledby="mbk-title">
        <div class="modal-head">
            <div>
                <h3 id="mbk-title">Bayar tagihan</h3>
                <p>Transfer, lalu unggah bukti pembayaran.</p>
            </div>
            <button class="modal-x" type="button" data-close aria-label="Tutup"><?= icon('x', 16) ?></button>
        </div>
        <form id="bukti-form" novalidate>
            <div class="modal-info">
                <div><span>Jumlah</span><strong id="mbk-jumlah">-</strong></div>
                <div><span>Jatuh tempo</span><strong id="mbk-tempo">-</strong></div>
                <div><span>Transfer ke</span><strong><?= e(BANK_NAMA . ' ' . BANK_REKENING . ' · ' . BANK_ATAS_NAMA) ?></strong></div>
            </div>
            <div class="field">
                <label class="dropzone" tabindex="0" id="mbk-drop" data-max-mb="5">
                    <strong>Tarik foto bukti ke sini atau klik untuk memilih</strong>
                    <small>JPG atau PNG, maks. 5 MB</small>
                    <input type="file" name="bukti" accept="image/png,image/jpeg">
                </label>
            </div>
            <div class="modal-foot">
                <button class="btn btn-ghost" type="button" data-close>Batal</button>
                <button class="btn btn-accent" type="submit">Kirim Bukti</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: detail tagihan -->
<div class="modal-overlay" id="modal-detail">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="mdt-title">
        <div class="modal-head">
            <div><h3 id="mdt-title">Detail tagihan</h3><p id="mdt-sub">-</p></div>
            <button class="modal-x" type="button" data-close aria-label="Tutup"><?= icon('x', 16) ?></button>
        </div>
        <div class="modal-info" id="mdt-info"></div>
        <div class="modal-foot"><button class="btn btn-primary" type="button" data-close>Tutup</button></div>
    </div>
</div>

<?php include ROOT_PATH . '/footer/footer.php'; ?>
