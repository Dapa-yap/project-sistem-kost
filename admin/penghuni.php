<?php
require_once __DIR__ . '/../config/config.php';
require_role('admin');

$role = 'admin'; $pageTitle = 'Data Penghuni'; $pageId = 'admin-penghuni'; $activeMenu = 'penghuni';
$kamar    = get_kamar();
$penghuni = get_penghuni();
$pindah   = get_pindah_kamar();
$total    = count($kamar);
$terisi   = count($penghuni);
$kosong   = count(array_filter($kamar, fn($k) => $k['status'] === 'kosong'));
$pending  = count(array_filter($pindah, fn($p) => $p['status'] === 'menunggu'));
// kamar yang bisa ditempati: kosong, atau sudah ditahan lewat booking
$kamarKosong = array_values(array_filter($kamar, fn($k) => in_array($k['status'], ['kosong', 'booking'], true)));

$pageData = ['penghuni' => $penghuni, 'pindah' => $pindah, 'kamar' => $kamar, 'totalKamar' => $total];

include ROOT_PATH . '/header/header.php';
?>
<main>
    <section class="page-head"><div class="container">
        <h1>Data Penghuni</h1><p>Kelola data penghuni dan kamar yang ditempati.</p>
    </div></section>

    <section class="page-body" style="padding-top:28px"><div class="container">
        <div class="head-actions">
            <div class="stats cols-3">
                <div class="stat"><div class="stat-label">Total Penghuni</div><div class="stat-value" id="st-total"><?= $terisi ?></div><div class="stat-note">Aktif saat ini</div></div>
                <div class="stat"><div class="stat-label">Kamar Terisi</div><div class="stat-value" id="st-terisi"><?= $terisi ?></div><div class="stat-note" id="st-terisi-note">dari <?= $total ?> kamar</div></div>
                <div class="stat"><div class="stat-label">Kamar Kosong</div><div class="stat-value" id="st-kosong"><?= $kosong ?></div><div class="stat-note">Siap dibooking</div></div>
            </div>
            <button class="btn btn-brown" type="button" data-open="modal-tambah">+ Tambah Penghuni</button>
        </div>

        <div class="card">
            <div class="card-head"><h2>Daftar Penghuni</h2><input class="input" style="max-width:260px" type="search" placeholder="Cari nama atau nomor kamar" data-search-target="#penghuni-body" aria-label="Cari penghuni"></div>
            <div class="table-wrap"><table class="table">
                <thead><tr><th>No.</th><th>Nama</th><th>No. Kamar</th><th>Lantai &amp; Tipe</th><th>Tanggal Masuk</th><th>No. HP</th><th>Aksi</th></tr></thead>
                <tbody id="penghuni-body">
                    <?php foreach ($penghuni as $p): ?>
                        <tr data-kamar="<?= e($p['kamar']) ?>" data-row="<?= e(json_encode($p, JSON_UNESCAPED_UNICODE)) ?>">
                            <td class="c-no"><?= (int) $p['no'] ?></td>
                            <td class="c-nama"><?= e($p['nama']) ?></td>
                            <td class="c-kamar"><?= e($p['kamar']) ?></td>
                            <td class="c-tipe nowrap">Lantai <?= (int) $p['lantai'] ?> · <?= e($p['tipe']) ?></td>
                            <td class="nowrap"><?= e(tgl($p['masuk'])) ?></td>
                            <td class="nowrap"><?= e($p['hp']) ?></td>
                            <td><div class="actions"><button class="btn btn-outline btn-sm" type="button" data-act="detail">Detail</button><button class="btn btn-outline btn-sm" type="button" data-act="keluar">Keluarkan</button></div></td>
                        </tr>
                    <?php endforeach; ?>
                    <tr data-empty class="empty-row <?= $penghuni ? 'hidden' : '' ?>"><td colspan="7">Tidak ada penghuni yang cocok.</td></tr>
                </tbody>
            </table></div>
        </div>

        <div class="card" style="margin-top:24px">
            <div class="card-head"><h2>Pengajuan Pindah Kamar</h2><span class="badge badge-orange" id="pindah-count"><?= $pending ?> menunggu</span></div>
            <div class="table-wrap"><table class="table">
                <thead><tr><th>Penghuni</th><th>Kamar Asal</th><th>Kamar Tujuan</th><th>Tanggal Pindah</th><th>Status</th><th>Aksi</th></tr></thead>
                <tbody id="pindah-body">
                    <?php foreach ($pindah as $p): ?>
                        <tr data-pindah="<?= (int) $p['id'] ?>" data-status="<?= e($p['status']) ?>">
                            <td><?= e($p['nama']) ?></td><td><?= e($p['asal']) ?></td><td>ke <?= e($p['tujuan']) ?></td>
                            <td class="nowrap"><?= e(tgl($p['tanggal'])) ?></td>
                            <td class="col-status"><?= status_badge('pindah', $p['status']) ?></td>
                            <td class="col-aksi"><?php if ($p['status'] === 'menunggu'): ?>
                                <div class="actions"><button class="btn btn-accent btn-sm" type="button" data-act="proses">Proses</button><button class="btn btn-outline btn-sm" type="button" data-act="tolak">Tolak</button></div>
                            <?php else: ?>-<?php endif; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table></div>
        </div>
    </div></section>
</main>

<!-- Modal: tambah penghuni -->
<div class="modal-overlay" id="modal-tambah">
    <div class="modal is-wide" role="dialog" aria-modal="true" aria-labelledby="mt-title">
        <div class="modal-head">
            <div><h3 id="mt-title">Tambah penghuni</h3><p>Penghuni tidak bisa mendaftar sendiri. Buat akunnya di sini lalu berikan username dan password ke penghuni.</p></div>
            <button class="modal-x" type="button" data-close aria-label="Tutup"><?= icon('x', 16) ?></button>
        </div>
        <form id="tambah-form" novalidate>
            <div class="field-row">
                <div class="field"><label for="t-nama">Nama lengkap</label><input class="input" id="t-nama" name="nama" placeholder="Nama penghuni" required></div>
                <div class="field"><label for="t-hp">Nomor HP</label><input class="input" id="t-hp" name="hp" inputmode="tel" placeholder="0812xxxxxxx" required data-pattern="^[0-9+\- ]{9,16}$" data-pattern-msg="Nomor tidak valid"></div>
            </div>
            <div class="field-row">
                <div class="field"><label for="t-user">Username</label><input class="input" id="t-user" name="username" placeholder="Untuk login" required data-minlength="4"></div>
                <div class="field"><label for="t-pass">Password awal</label><input class="input" id="t-pass" type="password" name="password" placeholder="Minimal 8 karakter" required data-minlength="8"></div>
            </div>
            <div class="field-row">
                <div class="field"><label for="t-kamar">Kamar</label>
                    <select class="select" id="t-kamar" name="kamar" required>
                        <option value="">Pilih kamar kosong</option>
                        <?php foreach ($kamarKosong as $k): ?>
                            <option value="<?= e($k['id']) ?>"><?= e($k['id']) ?> · <?= e($k['tipe']) ?><?= $k['status'] === 'booking' ? ' (sudah dibooking)' : '' ?></option>
                        <?php endforeach; ?>
                    </select></div>
                <div class="field"><label for="t-tgl">Tanggal masuk</label><input class="input" type="date" id="t-tgl" name="tgl" required></div>
            </div>
            <div class="modal-foot">
                <button class="btn btn-ghost" type="button" data-close>Batal</button>
                <button class="btn btn-accent" type="submit">Simpan Penghuni</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: detail penghuni -->
<div class="modal-overlay" id="modal-detail">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="md2-title">
        <div class="modal-head">
            <div><h3 id="md2-title">-</h3><p>Penghuni aktif</p></div>
            <button class="modal-x" type="button" data-close aria-label="Tutup"><?= icon('x', 16) ?></button>
        </div>
        <div class="modal-info" id="md2-info"></div>
        <div class="modal-foot"><button class="btn btn-primary" type="button" data-close>Tutup</button></div>
    </div>
</div>

<!-- Modal: keluarkan penghuni -->
<div class="modal-overlay" id="modal-keluar">
    <div class="modal is-wide" role="dialog" aria-modal="true" aria-labelledby="mk-title">
        <div class="modal-head">
            <div><h3 id="mk-title">Keluarkan penghuni</h3><p id="mk-sub">-</p></div>
            <button class="modal-x" type="button" data-close aria-label="Tutup"><?= icon('x', 16) ?></button>
        </div>
        <form id="keluar-form" novalidate>
            <div class="modal-info">
                <div><span>Tagihan</span><strong id="mk-tagihan">-</strong></div>
                <div><span>Deposit</span><strong id="mk-deposit">-</strong></div>
            </div>
            <div class="field-row">
                <div class="field"><label for="k-tgl">Tanggal keluar</label><input class="input" type="date" id="k-tgl" name="tgl" required></div>
                <div class="field"><label for="k-kondisi">Kondisi kamar</label>
                    <select class="select" id="k-kondisi" name="kondisi"><option value="baik">Baik</option><option value="perbaikan">Perlu perbaikan</option></select></div>
            </div>
            <div class="alert alert-warn" id="mk-warn">-</div>
            <div class="modal-foot">
                <button class="btn btn-ghost" type="button" data-close>Batal</button>
                <button class="btn btn-accent" type="submit">Selesaikan Keluar</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: tolak pindah kamar -->
<div class="modal-overlay" id="modal-tolak">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="mtl-title">
        <div class="modal-head">
            <div><h3 id="mtl-title">Tolak pengajuan pindah</h3><p>Penghuni akan diberi tahu beserta alasannya.</p></div>
            <button class="modal-x" type="button" data-close aria-label="Tutup"><?= icon('x', 16) ?></button>
        </div>
        <form id="tolak-form" novalidate>
            <div class="field"><label for="tl-alasan">Alasan penolakan</label><textarea class="textarea" id="tl-alasan" name="alasan" placeholder="Contoh: kamar tujuan sudah dibooking" required></textarea></div>
            <div class="modal-foot">
                <button class="btn btn-ghost" type="button" data-close>Batal</button>
                <button class="btn btn-danger" type="submit">Tolak Pindah</button>
            </div>
        </form>
    </div>
</div>
<?php include ROOT_PATH . '/footer/footer.php'; ?>
