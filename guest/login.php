<?php
require_once __DIR__ . '/../config/config.php';

$role = 'guest'; $pageTitle = 'Masuk'; $pageId = 'guest-login'; $activeMenu = '';

// Sudah login? langsung arahkan ke dashboard
if (is_logged_in()) {
    $u = current_user();
    redirect($u['role'] === 'admin' ? 'admin/dashboard.php' : 'penghuni/dashboard.php');
}

$tab = in_array($_GET['role'] ?? '', ['admin', 'penghuni'], true) ? $_GET['role'] : 'penghuni';
$error = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tab      = in_array($_POST['role'] ?? '', ['admin', 'penghuni'], true) ? $_POST['role'] : 'penghuni';
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    // TODO (database): ganti dengan SELECT ... WHERE username=? lalu password_verify()
    foreach (get_users() as $u) {
        if ($u['role'] === $tab && $u['username'] === $username && $u['password'] === $password) {
            session_regenerate_id(true);
            $_SESSION['user'] = $u;
            unset($_SESSION['user']['password']);
            redirect($tab === 'admin' ? 'admin/dashboard.php' : 'penghuni/dashboard.php');
        }
    }
    $error = 'Username atau password salah, atau peran yang dipilih tidak sesuai.';
}

include ROOT_PATH . '/header/header.php';
?>
<main class="auth-wrap">
    <div class="auth-card">
        <h1>Masuk ke KostKu</h1>
        <p class="sub">Pilih peran Anda, lalu masuk dengan akun yang terdaftar.</p>

        <?php if (isset($_GET['logout'])): ?><div class="alert alert-info">Anda sudah keluar dari akun.</div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-error" role="alert"><?= e($error) ?></div><?php endif; ?>

        <form method="post" id="login-form" novalidate>
            <input type="hidden" name="role" id="login-role" value="<?= e($tab) ?>">
            <div class="role-tabs" role="tablist">
                <button type="button" class="role-tab <?= $tab === 'penghuni' ? 'active' : '' ?>" data-role="penghuni" role="tab">Penghuni</button>
                <button type="button" class="role-tab <?= $tab === 'admin' ? 'active' : '' ?>" data-role="admin" role="tab">Pengelola</button>
            </div>

            <div class="field">
                <label for="l-user">Username</label>
                <input class="input" id="l-user" name="username" placeholder="Masukkan username" value="<?= e($username) ?>" autocomplete="username" required>
            </div>
            <div class="field">
                <label for="l-pass">Password</label>
                <div class="input-icon">
                    <input class="input" style="padding-left:14px" id="l-pass" type="password" name="password" placeholder="Masukkan password" autocomplete="current-password" required>
                    <button class="trail" type="button" data-toggle-password aria-label="Tampilkan password"><?= icon('eye', 18) ?></button>
                </div>
            </div>

            <div class="auth-row">
                <label class="checkbox"><input type="checkbox" name="ingat"> Ingat saya</label>
                <button type="button" class="link" data-open="modal-reset">Lupa password?</button>
            </div>

            <button class="btn btn-accent btn-block" type="submit">Masuk</button>
        </form>

        <p class="auth-foot" id="login-foot">Belum punya akun? <a href="<?= url('guest/kamar.php?filter=kosong') ?>">Booking kamar dulu</a></p>

        <div class="demo-box">
            <strong>Akun demo (sementara, sebelum database):</strong><br>
            Penghuni: <code>rina</code> / <code>rina12345</code><br>
            Pengelola: <code>admin</code> / <code>admin123</code>
        </div>
    </div>
</main>

<!-- Modal: atur ulang password -->
<div class="modal-overlay" id="modal-reset">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="mr-title">
        <div class="modal-head">
            <div>
                <h3 id="mr-title">Atur ulang password</h3>
                <p>Masukkan username dan kata sandi lama, lalu buat kata sandi baru.</p>
            </div>
            <button class="modal-x" type="button" data-close aria-label="Tutup"><?= icon('x', 16) ?></button>
        </div>
        <form id="reset-form" novalidate>
            <div class="field"><label for="r-user">Username</label><input class="input" id="r-user" name="username" placeholder="Masukkan username" required></div>
            <div class="field"><label for="r-old">Masukkan kata sandi lama</label><input class="input" id="r-old" type="password" name="lama" placeholder="Masukkan kata sandi" required></div>
            <div class="field">
                <label for="r-new">Masukkan kata sandi baru</label>
                <div class="input-icon">
                    <span class="lead"><?= icon('lock', 18) ?></span>
                    <input class="input" id="r-new" type="password" name="baru" placeholder="Buat kata sandi" required data-minlength="8">
                    <button class="trail" type="button" data-toggle-password aria-label="Tampilkan password"><?= icon('eye', 18) ?></button>
                </div>
            </div>
            <div class="modal-foot">
                <button class="btn btn-ghost" type="button" data-close>Batal</button>
                <button class="btn btn-accent" type="submit">Konfirmasi</button>
            </div>
        </form>
    </div>
</div>

<?php include ROOT_PATH . '/footer/footer.php'; ?>
