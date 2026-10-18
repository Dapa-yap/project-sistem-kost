<?php
/**
 * components/room-card.php -> kartu kamar (guest).
 * Variabel: $k (array kamar)
 */

$status   = $k['status'];
$tersedia = $status === 'kosong';
$dimmed   = in_array($status, ['terisi', 'diperbaiki'], true);

$badgeMap = [
    'kosong'     => ['Tersedia',   'green'],
    'terisi'     => ['Terisi',     'grey'],
    'booking'    => ['Terbooking', 'brown'],
    'diperbaiki' => ['Diperbaiki', 'orange'],
];

[$bLabel, $bColor] = $badgeMap[$status];
?>

<article
    class="room-card <?= $dimmed ? 'is-dimmed' : '' ?>"
    data-status="<?= e($status) ?>"
    data-tipe="<?= e($k['tipe']) ?>"
    data-harga="<?= (int) $k['harga'] ?>"
>

    <div class="room-thumb">

        <?php if ($k['id'] === 'A-01'): ?>

            <img
                src="<?= url('assets/image/a01.1.png') ?>"
                alt="Foto Kamar A-01"
                class="room-image">

         <?php elseif ($k['id'] === 'A-02'): ?>

            <img
                src="<?= url('assets/image/a02.1.jpg') ?>"
                alt="Foto Kamar A-01"
                class="room-image">

        <?php endif; ?>


        <?php if ($k['tipe'] === 'Premium'): ?>

            <span class="badge badge-peach room-tag">
                Premium
            </span>

        <?php endif; ?>


        <span class="room-status">
            <?= badge($bLabel, $bColor) ?>
        </span>


        <span class="room-size">
            <?= e($k['ukuran']) ?>
        </span>

    </div>


    <div class="room-body">

        <h3>
            Kamar <?= e($k['id']) ?>
        </h3>

        <p class="room-meta">
            <?= e(kamar_ringkas($k)) ?>
        </p>

        <div class="room-foot">

            <div class="room-price">
                <?= e(rupiah_k($k['harga'])) ?>
                <small>/bulan</small>
            </div>

            <?php if ($tersedia): ?>

                <a
                    class="btn btn-outline btn-sm"
                    href="<?= url('guest/detail.php?id=' . urlencode($k['id'])) ?>"
                >
                    Detail
                </a>

            <?php else: ?>

                <span
                    class="btn btn-outline btn-sm is-disabled"
                    aria-disabled="true"
                >
                    Detail
                </span>

            <?php endif; ?>

        </div>

    </div>

</article>