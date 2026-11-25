<?php
require_once __DIR__ . '/../config/config.php';
require_role('admin');

$role = 'admin'; $pageTitle = 'Laporan Keuangan'; $pageId = 'admin-laporan'; $activeMenu = 'laporan';
$pageData = [];

// bulan dari URL (?bulan=2026-11), harus berformat YYYY-MM, bawaannya bulan ini
$bulan = $_GET['bulan'] ?? date('Y-m');
if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $bulan)) $bulan = date('Y-m');

// pilihan 12 bulan terakhir untuk dropdown
$opsiBulan = [];
for ($i = 0; $i < 12; $i++) {
    $t = strtotime("first day of -$i month");
    $opsiBulan[date('Y-m', $t)] = periode_label(date('Y-m-01', $t));
}
if (!isset($opsiBulan[$bulan])) $opsiBulan[$bulan] = periode_label($bulan . '-01');

$L     = get_laporan_keuangan($bulan);
$maks  = max(1, max($L['tren']['nilai']));
$jt    = fn($n) => number_format($n / 1000000, 1, ',', '.') . ' jt';

include ROOT_PATH . '/header/header.php';
?>
<main>
    <section class="page-head"><div class="container">
        <h1>Laporan Keuangan</h1>
        <p>Uang masuk dan tagihan per bulan.</p>
    </div></section>

    <section class="page-body" style="padding-top:28px"><div class="container">

        <form method="get" class="laporan-tools" style="margin-bottom:20px">
    <select name="bulan" class="select" onchange="this.form.submit()">
        <?php foreach ($opsiBulan as $val => $lbl): ?>
            <option value="<?= e($val) ?>" <?= $val === $bulan ? 'selected' : '' ?>><?= e($lbl) ?></option>
        <?php endforeach; ?>
    </select>
        </form>

        <div class="stats cols-3">
            <div class="stat"><div class="stat-label">Uang Masuk (Lunas)</div><div class="stat-value is-money"><?= e(rupiah($L['diterima'])) ?></div><div class="stat-note"><?= (int) $L['hitung']['lunas'] ?> tagihan</div></div>
            <div class="stat"><div class="stat-label">Total Tagihan</div><div class="stat-value is-money"><?= e(rupiah($L['total'])) ?></div><div class="stat-note"><?= (int) $L['persen'] ?>% sudah tertagih</div></div>
            <div class="stat"><div class="stat-label">Tunggakan</div><div class="stat-value is-money"><?= e(rupiah($L['tunggakan'])) ?></div><div class="stat-note"><?= (int) $L['hitung']['terlambat'] ?> penghuni terlambat</div></div>
        </div>

        <div class="card" style="margin-top:20px">
            <div class="card-head"><h2>Uang Masuk 6 Bulan Terakhir</h2></div>
            <div class="bars">
                <?php foreach ($L['tren']['label'] as $i => $lb):
                    $v = $L['tren']['nilai'][$i];
                    $last = $i === count($L['tren']['label']) - 1; ?>
                    <div class="bar-col">
                        <span class="bar-val"><?= e($jt($v)) ?></span>
                        <div class="bar-track"><div class="bar <?= $last ? 'is-now' : '' ?>" style="height:<?= round($v / $maks * 100) ?>%"></div></div>
                        <span class="bar-lb"><?= e(strtok($lb, ' ')) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="card" style="margin-top:20px">
            <div class="card-head"><h2>Status Tagihan (<?= count($L['tagihan']) ?>)</h2></div>
            <div class="laporan-status">
                <?php foreach ($L['hitung'] as $st => $n): if (!$n) continue; ?>
                    <span><?= status_badge('bayar', $st) ?> <strong><?= (int) $n ?></strong></span>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="card" style="margin-top:20px">
            <div class="card-head"><h2>Rincian Pembayaran</h2></div>
            <div class="table-wrap"><table class="table table-stack">
                <thead><tr><th>Kamar</th><th>Penghuni</th><th>Jatuh Tempo</th><th>Jumlah</th><th>Status</th></tr></thead>
                <tbody>
                    <?php foreach ($L['tagihan'] as $p): ?>
                        <tr>
                            <td data-label="Kamar"><?= e($p['kamar']) ?></td>
                            <td data-label="Penghuni"><?= e($p['nama']) ?></td>
                            <td data-label="Jatuh Tempo" class="nowrap"><?= e(tgl($p['jatuh_tempo'])) ?></td>
                            <td data-label="Jumlah" class="nowrap"><?= e(rupiah($p['jumlah'])) ?></td>
                            <td data-label="Status">
                                <?= status_badge('bayar', $p['status']) ?>
                                <?php if ($p['hari'] > 0): ?><small><?= (int) $p['hari'] ?> hari</small><?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$L['tagihan']): ?>
                        <tr class="empty-row"><td colspan="5">Belum ada tagihan pada bulan ini.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table></div>
        </div>

    </div></section>
</main>
<?php include ROOT_PATH . '/footer/footer.php'; ?>