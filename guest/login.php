
<?php
require_once __DIR__ . '/../config/config.php';

$role = 'guest';
$pageTitle = 'Masuk';
$pageId = 'guest-login';
$activeMenu = '';

// Jika sudah login, arahkan sesuai role
if (is_logged_in()) {
    $u = current_user();

    redirect(
        $u['role'] === 'admin'
            ? 'admin/dashboard.php'
            : 'penghuni/dashboard.php'
    );
}

$error = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!throttle_ok('login', 8, 10)) {
        $error = 'Terlalu banyak percobaan masuk. Coba lagi dalam beberapa menit.';
    } else {

        // Mencari akun tanpa memilih role
        $u = find_user($username, $password);

        if ($u) {
            session_regenerate_id(true);
            $_SESSION['user'] = $u;

            unset($_SESSION['throttle']['login']);

            // Redirect otomatis berdasarkan role akun
            redirect(
                $u['role'] === 'admin'
                    ? 'admin/dashboard.php'
                    : 'penghuni/dashboard.php'
            );
        }

        throttle_hit('login');
        $error = 'Username atau password salah, atau akun tidak aktif.';
    }
}

include ROOT_PATH . '/header/header.php';
?>

<main class="auth-wrap">
    <div class="auth-card">

        <aside class="auth-art">
            <div class="auth-art-text">
                <h2>Selamat datang di KostKu</h2>
                <p>Kelola kamar, pembayaran, dan status hunian Anda dalam satu tempat.</p>
            </div>

            <img src="<?= asset('img/hero.png') ?>" alt="Gedung KostKu">
        </aside>

        <div class="auth-form">

            <h1>Masuk ke KostKu</h1>
            <p class="sub">
                Masukkan username dan password akun Anda untuk masuk.
            </p>

            <?php if (isset($_GET['logout'])): ?>
                <div class="alert alert-info">
                    Anda sudah keluar dari akun.
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-error" role="alert">
                    <?= e($error) ?>
                </div>
            <?php endif; ?>

            <form method="post" id="login-form" novalidate>

                <div class="field">
                    <label for="l-user">Username</label>
                    <input
                        class="input"
                        id="l-user"
                        name="username"
                        placeholder="Masukkan username"
                        value="<?= e($username) ?>"
                        autocomplete="username"
                        required
                    >
                </div>

                <div class="field">
                    <label for="l-pass">Password</label>

                    <div class="input-icon">
                        <input
                            class="input"
                            style="padding-left:14px"
                            id="l-pass"
                            type="password"
                            name="password"
                            placeholder="Masukkan password"
                            autocomplete="current-password"
                            required
                        >

                        <button
                            class="trail"
                            type="button"
                            data-toggle-password
                            aria-label="Tampilkan password"
                        >
                            <?= icon('eye', 18) ?>
                        </button>
                    </div>
                </div>

                <div class="auth-row">
                    <label class="checkbox">
                        <input type="checkbox" name="ingat">
                        Ingat saya
                    </label>

                    <button
                        type="button"
                        class="link"
                        data-open="modal-reset"
                    >
                        Lupa password?
                    </button>
                </div>

                <button class="btn btn-accent btn-block" type="submit">
                    Masuk
                </button>

            </form>

            <p class="auth-foot" id="login-foot">
                Belum punya akun?
                <a href="<?= url('guest/kamar.php?filter=kosong') ?>">
                    Booking kamar dulu
                </a>
            </p>

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

            <button
                class="modal-x"
                type="button"
                data-close
                aria-label="Tutup"
            >
                <?= icon('x', 16) ?>
            </button>
        </div>

        <form id="reset-form" novalidate>

            <div class="field">
                <label for="r-user">Username</label>
                <input
                    class="input"
                    id="r-user"
                    name="username"
                    placeholder="Masukkan username"
                    required
                >
            </div>

            <div class="field">
                <label for="r-old">Masukkan kata sandi lama</label>
                <input
                    class="input"
                    id="r-old"
                    type="password"
                    name="lama"
                    placeholder="Masukkan kata sandi"
                    required
                >
            </div>

            <div class="field">
                <label for="r-new">Masukkan kata sandi baru</label>

                <div class="input-icon">
                    <span class="lead">
                        <?= icon('lock', 18) ?>
                    </span>

                    <input
                        class="input"
                        id="r-new"
                        type="password"
                        name="baru"
                        placeholder="Buat kata sandi"
                        required
                        data-minlength="8"
                    >

                    <button
                        class="trail"
                        type="button"
                        data-toggle-password
                        aria-label="Tampilkan password"
                    >
                        <?= icon('eye', 18) ?>
                    </button>
                </div>
            </div>

            <div class="modal-foot">
                <button class="btn btn-ghost" type="button" data-close>
                    Batal
                </button>

                <button class="btn btn-accent" type="submit">
                    Konfirmasi
                </button>
            </div>

        </form>
    </div>
</div>

<?php include ROOT_PATH . '/footer/footer.php'; ?>
