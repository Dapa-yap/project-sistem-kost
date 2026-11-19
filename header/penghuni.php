<?php
$activeMenu = $activeMenu ?? '';
$logoHref = 'penghuni/dashboard.php';
$menu = [
    'dashboard'  => ['Dashboard',  'penghuni/dashboard.php'],
    'pembayaran' => ['Pembayaran', 'penghuni/pembayaran.php'],
    'pengaduan'  => ['Pengaduan',  'penghuni/pengaduan.php'],
];
$u = current_user();
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
        </nav>

        <div class="nav-right">
            <!-- Desktop: seperti semula -->
            <div class="user-chip">
                <span class="avatar"><?= e(strtoupper(substr($u['nama'], 0, 1))) ?></span>
                <span class="user-meta"><strong><?= e($u['nama']) ?></strong><small><?= e($u['label']) ?></small></span>
            </div>
            <button type="button" class="btn btn-outline btn-sm" data-logout>Keluar</button>

            <!-- Mobile: dropdown (disembunyikan di desktop oleh CSS) -->
            <div class="user-menu" data-user-menu>
                <button type="button" class="user-trigger" data-user-toggle aria-haspopup="true" aria-expanded="false">
                    <span class="avatar"><?= e(strtoupper(substr($u['nama'], 0, 1))) ?></span>
                    <span class="user-meta"><strong><?= e($u['nama']) ?></strong></span>
                    <svg class="chev" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 9l6 6 6-6"/></svg>
                </button>
                <div class="user-dropdown" role="menu">
                    <button type="button" class="user-dropdown-item" role="menuitem" data-logout>Keluar</button>
                </div>
            </div>
        </div>
    </div>
</header>