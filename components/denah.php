<?php
/**
 * components/denah.php -> denah satu lantai, mengikuti denah asli.
 * Lantai 2 = A-01..A-11, Lantai 3 = B-01..B-07 (lantai 1 = parkir).
 * Variabel: $lantai (2|3), $highlight (id kamar yg ditandai),
 *           $denahClickable (bool), $denahLabel ('status'|'tipe')
 */
$lantai = (int) $lantai;
$all = get_kamar();
$highlight = $highlight ?? '';
$denahClickable = $denahClickable ?? false;
$denahLabel = $denahLabel ?? 'status';
$tag = $denahClickable ? 'button' : 'div';

$layouts = [
    2 => [
        // 6 kolom: 3 kamar | jalan vertikal | sisi kiri kolom kanan | sisi kanan kolom kanan
        'cols'  => 'minmax(0,1fr) minmax(0,1fr) minmax(0,1fr) minmax(0,.43fr) minmax(0,.2fr) minmax(0,.8fr)',
        // 8 baris: tinggi disusun supaya wastafel kecil, KM 2 buah, dan A-05/A-04 lebih turun dari A-06
        'rows'  => ['20fr', '20fr', '40fr', '34fr', '48fr', '32fr', '50fr', '80fr'],
        'areas' => [
            'a09 a10 a11 .  .   km1',
            'a09 a10 a11 .  .   km1',
            'a09 a10 a11 .  .   km2',
            'j1  j1  j1  j1 a05 a05',
            'a08 a07 a06 jv a05 a05',
            'a08 a07 a06 jv a04 a04',
            'tg  j2  j2  jv a04 a04',
            'tg  a01 a02 jv a03 a03',
        ],
    ],
    3 => [
        'rows'  => ['76px', '40px', '76px', '76px', '76px'],
        'areas' => ['b07 sn sn ws', 'b07 sn sn km', 'b06 sn sn b05', 'b06 j1 j1 b04', 'b01 b02 b03 b03'],
    ],
];
// Wastafel lantai 2: di baris pertama, di sebelah kiri KM (menyambung ke A-11)
$layouts[2]['areas'][0] = 'a09 a10 a11 ws ws km1';

$layout = $layouts[$lantai] ?? $layouts[2];
$special = [
    'ws'  => ['Wastafel', 'is-service'], 'km'  => ['KM Umum', 'is-service'],
    'km1' => ['KM Umum', 'is-service'],  'km2' => ['KM Umum', 'is-service'],
    'j1'  => ['Jalan', 'is-corridor'],   'j2'  => ['Jalan', 'is-corridor'],
    'jv'  => ['', 'is-corridor'],
    'tg'  => ['Tangga', 'is-stairs'],    'sn'  => ['Ruang Santai', 'is-lounge'],
];
$names = [];
foreach ($layout['areas'] as $dnRow) {
    foreach (preg_split('/\s+/', trim($dnRow)) as $dnCell) {
        if ($dnCell !== '.') { $names[$dnCell] = true; }
    }
}
$areaCss = implode(' ', array_map(fn($r) => '"' . preg_replace('/\s+/', ' ', trim($r)) . '"', $layout['areas']));
$colsCss = isset($layout['cols']) ? ' grid-template-columns: ' . $layout['cols'] . ';' : '';
?>
<div class="denah" data-lantai="<?= $lantai ?>"
     style="grid-template-areas: <?= e($areaCss) ?>; grid-template-rows: <?= e(implode(' ', $layout['rows'])) ?>;<?= e($colsCss) ?>">
    <?php foreach (array_keys($names) as $dnCell):
        if (isset($special[$dnCell])): [$dnLabel, $dnCls] = $special[$dnCell]; ?>
            <div class="denah-cell <?= e($dnCls) ?>" style="grid-area: <?= e($dnCell) ?>"><?= e($dnLabel) ?></div>
        <?php else:
            $dnId = strtoupper($dnCell[0]) . '-' . substr($dnCell, 1);
            if (!isset($all[$dnId])) continue;
            $dnRoom = $all[$dnId];
            $dnLbl = $denahLabel === 'tipe' ? $dnRoom['tipe'] : ['kosong' => 'Kosong', 'terisi' => 'Terisi', 'booking' => 'Booking', 'diperbaiki' => 'Diperbaiki'][$dnRoom['status']];
            ?>
            <<?= $tag ?> class="denah-room st-<?= e($dnRoom['status']) ?> <?= $highlight === $dnId ? 'is-mark' : '' ?>"
                style="grid-area: <?= e($dnCell) ?>" data-id="<?= e($dnId) ?>"
                <?= $denahClickable ? 'type="button" data-room="' . e($dnId) . '"' : '' ?>>
                <strong><?= e($dnId) ?></strong>
                <span><?= e($dnLbl) ?></span>
            </<?= $tag ?>>
        <?php endif;
    endforeach; ?>
</div>