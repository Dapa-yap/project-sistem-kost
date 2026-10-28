<?php
$activeMenu = $activeMenu ?? '';
$menu = [
    'beranda'    => ['Beranda',      'guest/index.php'],
    'kamar'      => ['Daftar Kamar', 'guest/kamar.php'],
    'fasilitas'  => ['Fasilitas',    'guest/fasilitas.php'],
    'kontak'     => ['Kontak',       'guest/kontak.php'],
    'status'     => ['Status',       'guest/status.php'],
    'survey'     => ['Survey',       'guest/survey.php'],
];
$u = current_user();
$dashHref = $u ? ($u['role'] === 'admin' ? 'admin/dashboard.php' : 'penghuni/dashboard.php') : null;
?>
<header class="navbar">
    <div class="container navbar-inner">
        <?php include __DIR__ . '/logo.php'; ?>

        <button class="nav-toggle" type="button" aria-label="Buka menu" data-nav-toggle>
            <span></span><span></span><span></span>
        </button>

        <nav class="nav-links" data-nav>
            <?php foreach ($menu as $key => [$label, $href]): ?>
                <a href="<?= url($href) ?>" class="<?= $activeMenu === $key ? 'active' : '' ?>"><?= e($label) ?></a>
            <?php endforeach; ?>
            <div class="nav-mobile-cta">
                <?php if ($dashHref): ?>
                    <a class="btn btn-primary" href="<?= url($dashHref) ?>">Dashboard</a>
                <?php else: ?>
                    <a class="btn btn-primary" href="<?= url('guest/login.php') ?>">Masuk</a>
                <?php endif; ?>
            </div>
        </nav>

        <div class="nav-right">
            <?php if ($dashHref): ?>
                <a class="btn btn-primary" href="<?= url($dashHref) ?>">Dashboard</a>
            <?php else: ?>
                <a class="btn btn-primary" href="<?= url('guest/login.php') ?>">Masuk</a>
            <?php endif; ?>
        </div>
    </div>
</header>
