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
        'rows'  => ['76px', '40px', '76px', '76px', '76px'],
        'areas' => ['a09 a10 a11 ws', 'j1 j1 j1 km', 'a08 a07 a06 a05', 'tg j2 j2 a04', 'tg a01 a02 a03'],
    ],
    3 => [
        'rows'  => ['76px', '40px', '76px', '76px', '76px'],
        'areas' => ['b07 sn sn ws', 'b07 sn sn km', 'b06 sn sn b05', 'b06 j1 j1 b04', 'b01 b02 b03 b03'],
    ],
];
$layout = $layouts[$lantai] ?? $layouts[2];
$special = [
    'ws' => ['Wastafel', 'is-service'], 'km' => ['KM Umum', 'is-service'],
    'j1' => ['Jalan', 'is-corridor'],   'j2' => ['Jalan', 'is-corridor'],
    'tg' => ['Tangga', 'is-stairs'],    'sn' => ['Ruang Santai', 'is-lounge'],
];
$names = [];
foreach ($layout['areas'] as $dnRow) { foreach (explode(' ', $dnRow) as $dnCell) { $names[$dnCell] = true; } }
$areaCss = implode(' ', array_map(fn($r) => '"' . $r . '"', $layout['areas']));
?>
<div class="denah" data-lantai="<?= $lantai ?>"
     style="grid-template-areas: <?= e($areaCss) ?>; grid-template-rows: <?= e(implode(' ', $layout['rows'])) ?>;">
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
