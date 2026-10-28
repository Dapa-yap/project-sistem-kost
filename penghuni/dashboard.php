<?php
require_once __DIR__ . '/../config/config.php';
require_role('penghuni');

$role = 'penghuni'; $pageTitle = 'Dashboard'; $pageId = 'penghuni-dashboard'; $activeMenu = 'dashboard';

$user    = current_user();
$kamar   = get_kamar_by_id($user['kamar']);
$penghuniRow = null;
foreach (get_penghuni() as $p) { if ($p['kamar'] === $user['kamar']) { $penghuniRow = $p; } }
$tagihan = get_tagihan_penghuni();
// tagihan tertua yang belum beres (daftar diurutkan terbaru -> terlama)
$next = null;
foreach ($tagihan as $t) { if (in_array($t['status'], ['belum', 'terlambat', 'ditolak'], true)) { $next = $t; } }
// tagihan siklus berjalan = yang tanggalnya paling baru tetapi tidak di masa depan
$tagihanBulanIni = null;
foreach ($tagihan as $t) { if ($t['tgl_tagihan'] <= date('Y-m-d')) { $tagihanBulanIni = $t; break; } }
$pindahAktif = get_pindah_penghuni();
$aduanAktif = array_values(array_filter(get_pengaduan_penghuni(), fn($a) => $a['status'] !== 'selesai'));
$namaDepan = explode(' ', $user['nama'])[0];

$tujuan = array_values(array_filter(get_kamar(), fn($k) => $k['status'] === 'kosong'));
$pageData = ['kamarAsal' => $kamar['id']];

include ROOT_PATH . '/header/header.php';
?>
<main>
    <section class="page-head">
        <div class="container">
            <h1>Halo, <?= e($namaDepan) ?></h1>
            <p>Berikut ringkasan kamar dan tagihan Anda.</p>
        </div>
    </section>

    <section class="page-body" style="padding-top: 28px;">
        <div class="container">
            <div class="dash-top">
                <div class="stat">
                    <div class="stat-label">Kamar Saya</div>
                    <div class="stat-value"><?= e($kamar['id']) ?></div>
                    <div class="stat-note"><?= e($kamar['tipe']) ?> · <?= e($kamar['ukuran']) ?> · <?= e(rupiah($kamar['harga'])) ?>/bulan</div>
                </div>
                <div class="stat">
                    <div class="stat-label">Tagihan Berikutnya</div>
                    <?php if ($next): ?>
                        <div class="stat-value is-money"><?= e(rupiah($next['jumlah'])) ?></div>
                        <div class="stat-note">Jatuh tempo <?= e(tgl($next['jatuh_tempo'])) ?></div>
                        <div class="stat-action"><a class="btn btn-accent btn-sm" href="<?= url('penghuni/pembayaran.php?bayar=' . urlencode($next['periode'])) ?>">Bayar Sekarang</a></div>
                    <?php else: ?>
                        <div class="stat-value is-money">Lunas</div>
                        <div class="stat-note">Tidak ada tagihan yang menunggu</div>
                    <?php endif; ?>
                </div>
                <div class="stat">
                    <div class="stat-label">Pengaduan Aktif</div>
                    <div class="stat-value"><?= count($aduanAktif) ?></div>
                    <div class="stat-note"><?= $aduanAktif ? e($aduanAktif[0]['judul']) . ' · ' . e($aduanAktif[0]['status'] === 'diproses' ? 'sedang diproses' : 'menunggu ditinjau') : 'Tidak ada pengaduan aktif' ?></div>
                </div>
            </div>

            <div class="dash-grid">
                <div class="card">
                    <div class="card-head"><h2>Kamar Saya</h2><span class="status-inline">Penghuni aktif</span></div>
                    <div class="kv-rows">
                        <div><span>Nomor kamar</span><strong><?= e($kamar['id']) ?></strong></div>
                        <div><span>Tipe dan ukuran</span><strong><?= e($kamar['tipe']) ?> · <?= e($kamar['ukuran']) ?></strong></div>
                        <div><span>Harga sewa</span><strong><?= e(rupiah($kamar['harga'])) ?> / bulan</strong></div>
                        <div><span>Tanggal masuk</span><strong><?= e(tgl($penghuniRow['masuk'] ?? date('Y-m-d'), false)) ?></strong></div>
                        <div><span>Tagihan bulan ini</span>
                            <strong><?= $tagihanBulanIni && $tagihanBulanIni['status'] === 'lunas' ? '<span class="status-inline">Lunas</span>' : '<span class="status-inline is-warn">' . e($tagihanBulanIni ? strip_tags(status_badge('bayar', $tagihanBulanIni['status'])) : '-') . '</span>' ?></strong>
                        </div>
                    </div>
                </div>

                <div class="card move-card">
                    <h2>Pindah Kamar</h2>
                    <p>Ingin pindah ke kamar lain? Ajukan ke pengelola. Kamar tujuan harus kosong dan tagihan bulan ini lunas.</p>
                    <?php $ps = $pindahAktif ? norm_status($pindahAktif['status']) : null; ?>
                    <?php if ($ps === 'menunggu'): ?>
                        <div class="alert alert-info">Pengajuan pindah ke kamar <?= e($pindahAktif['tujuan']) ?> (<?= e(tgl($pindahAktif['tanggal'])) ?>) sedang ditinjau pengelola.</div>
                    <?php else: ?>
                        <?php if ($ps === 'ditolak'): ?><div class="alert alert-warn">Pengajuan pindah ke kamar <?= e($pindahAktif['tujuan']) ?> ditolak<?= $pindahAktif['alasan_tolak'] ? ': ' . e($pindahAktif['alasan_tolak']) : '' ?>.</div><?php endif; ?>
                        <button class="btn btn-accent btn-block" type="button" data-open="modal-pindah">Ajukan Pindah Kamar</button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>
</main>

<!-- Modal: ajukan pindah kamar -->
<div class="modal-overlay" id="modal-pindah">
    <div class="modal is-wide" role="dialog" aria-modal="true" aria-labelledby="mp-title">
        <div class="modal-head">
            <div>
                <h3 id="mp-title">Ajukan pindah kamar</h3>
                <p>Anda saat ini di kamar <?= e($kamar['id']) ?>. Pengelola akan meninjau pengajuan Anda.</p>
            </div>
            <button class="modal-x" type="button" data-close aria-label="Tutup"><?= icon('x', 16) ?></button>
        </div>
        <form id="pindah-form" novalidate>
            <div class="field-row">
                <div class="field">
                    <label for="p-tujuan">Kamar tujuan</label>
                    <select class="select" id="p-tujuan" name="tujuan" required>
                        <option value="">Pilih kamar kosong</option>
                        <?php foreach ($tujuan as $t): ?>
                            <option value="<?= e($t['id']) ?>"><?= e($t['id']) ?> · <?= e($t['tipe']) ?> · <?= e(rupiah($t['harga'])) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field">
                    <label for="p-tgl">Rencana tanggal pindah</label>
                    <input class="input" type="date" id="p-tgl" name="tgl" required>
                </div>
            </div>
            <div class="field">
                <label for="p-alasan">Alasan pindah</label>
                <textarea class="textarea" id="p-alasan" name="alasan" placeholder="Contoh: ingin kamar lebih besar" required></textarea>
            </div>
            <div class="alert alert-warn">Tagihan bulan berjalan harus lunas. Selisih harga berlaku mulai bulan setelah pindah.</div>
            <div class="modal-foot">
                <button class="btn btn-ghost" type="button" data-close>Batal</button>
                <button class="btn btn-accent" type="submit">Kirim Pengajuan</button>
            </div>
        </form>
    </div>
</div>

<?php include ROOT_PATH . '/footer/footer.php'; ?>
