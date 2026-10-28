<?php
$activeMenu = $activeMenu ?? '';
$logoHref = 'admin/dashboard.php';

$menu = [
    'dashboard'  => ['Dashboard',          'admin/dashboard.php'],
    'kamar'      => ['Kamar',              'admin/kamar.php'],
    'penghuni'   => ['Penghuni',           'admin/penghuni.php'],
    'pembayaran' => ['Pembayaran',         'admin/pembayaran.php'],
    'pengaduan'  => ['Pengaduan',          'admin/pengaduan.php'],
    'booking'    => ['Booking',             'admin/booking.php'],
    'jadwal'     => ['Survey', 'admin/kunjungan-survey.php'],
];

$u = current_user();
?>

<header class="navbar">
    <div class="container navbar-inner navbar-wide">

        <?php include __DIR__ . '/logo.php'; ?>

        <button
            class="nav-toggle"
            type="button"
            aria-label="Buka menu"
            data-nav-toggle
        >
            <span></span>
            <span></span>
            <span></span>
        </button>

        <nav class="nav-links" data-nav>

            <?php foreach ($menu as $key => [$label, $href]): ?>

                <a
                    href="<?= url($href) ?>"
                    class="<?= $activeMenu === $key ? 'active' : '' ?>"
                >
                    <?= e($label) ?>
                </a>

            <?php endforeach; ?>

        </nav>

        <div class="nav-right">

            <div class="user-chip">

                <span class="avatar">
                    <?= e(strtoupper(substr($u['nama'], 0, 1))) ?>
                </span>

                <span class="user-meta">
                    <strong><?= e($u['nama']) ?></strong>
                    <small><?= e($u['label']) ?></small>
                </span>

            </div>

            <button
                type="button"
                class="btn btn-outline btn-sm"
                data-logout
            >
                Keluar
            </button>

        </div>

    </div>
</header>