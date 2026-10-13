<?php
/**
 * footer/footer.php -> footer situs + container modal/toast + semua script.
 * Variabel opsional: $role, $pageData (array yang diteruskan ke JS sebagai window.KOSTKU_DATA)
 */
$role = $role ?? 'guest';
?>
<footer class="site-footer">
    <div class="container">
        <div class="footer-grid">
            <div class="footer-about">
                <div class="footer-brand">KostKu.</div>
                <p>Sistem informasi Pengelolaan Kost modern yang memudahkan pencarian, pembayaran, dan pelayanan bagi penghuni dan pengelola.</p>
            </div>
            <div>
                <h4>Tautan Cepat</h4>
                <ul>
                    <li><a href="<?= url('guest/index.php') ?>">Beranda</a></li>
                    <li><a href="<?= url('guest/kamar.php') ?>">Daftar Kamar</a></li>
                    <li><a href="<?= url('guest/fasilitas.php') ?>">Fasilitas Kost</a></li>
                    <li><a href="<?= url('guest/login.php?role=penghuni') ?>">Login Penghuni</a></li>
                    <li><a href="<?= url('guest/login.php?role=admin') ?>">Login Pengelola</a></li>
                </ul>
            </div>
            <div>
                <h4>Hubungi Kami</h4>
                <ul>
                    <li>Jalan Jalan dengan sepatu rodaku</li>
                    <li>0812-3456-7890</li>
                    <li>admin@kostku.com</li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <span>© <?= date('Y') ?> KostKu. Semua hak dilindungi.</span>
            <span class="footer-legal"><a href="#">Syarat &amp; Ketentuan</a><a href="#">Kebijakan Privasi</a></span>
        </div>
    </div>
</footer>

<div id="toast-root" class="toast-root" aria-live="polite"></div>

<script>
    window.KOSTKU = { base: <?= json_encode(BASE_URL) ?>, role: <?= json_encode($role) ?> };
    window.KOSTKU_DATA = <?= json_encode($pageData ?? new stdClass(), JSON_UNESCAPED_UNICODE) ?>;
</script>
<script src="<?= asset('js/global.js') ?>"></script>
<script src="<?= asset('js/' . $role . '.js') ?>"></script>
</body>
</html>
