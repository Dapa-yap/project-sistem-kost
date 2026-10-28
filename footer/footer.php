<?php
/**
 * footer/footer.php
 * Footer berbeda berdasarkan role:
 * - guest     = pengunjung
 * - penghuni  = penghuni kost
 * - admin     = pengelola kost
 */

$role = $role ?? 'guest';
?>

<footer class="site-footer">
    <div class="container">

        <div class="footer-grid">

            <!-- =========================
                 GUEST
                 ========================= -->
            <?php if ($role === 'guest'): ?>

                <div class="footer-about">
                    <div class="footer-brand">KostKu.</div>
                    <p>
                        Sistem informasi pengelolaan kost yang memudahkan
                        calon penghuni dalam mencari kamar dan mendapatkan
                        informasi kost.
                    </p>
                </div>

                <div>
                    <h4>Tautan Cepat</h4>
                    <ul>
                        <li>
                            <a href="<?= url('guest/index.php') ?>">
                                Beranda
                            </a>
                        </li>
                        <li>
                            <a href="<?= url('guest/kamar.php') ?>">
                                Daftar Kamar
                            </a>
                        </li>
                        <li>
                            <a href="<?= url('guest/fasilitas.php') ?>">
                                Fasilitas Kost
                            </a>
                        </li>
                        <li>
                            <a href="<?= url('guest/kontak.php') ?>">
                                Kontak
                            </a>
                        </li>
                    </ul>
                </div>

                <div>
                    <h4>Akses Akun</h4>
                    <ul>
                        <li>
                            <a href="<?= url('guest/login.php?role=penghuni') ?>">
                                Login Penghuni
                            </a>
                        </li>
                        <li>
                            <a href="<?= url('guest/login.php?role=admin') ?>">
                                Login Pengelola
                            </a>
                        </li>
                        <li>
                            <a href="<?= url('guest/status.php') ?>">
                                Cek Status Pengajuan
                            </a>
                        </li>
                    </ul>
                </div>


            <!-- =========================
                 PENGHUNI
                 ========================= -->
            <?php elseif ($role === 'penghuni'): ?>

                <div class="footer-about">
                    <div class="footer-brand">KostKu.</div>
                    <p>
                        Sistem informasi pengelolaan kost untuk membantu
                        penghuni mengelola pembayaran, pengaduan, dan
                        informasi kamar.
                    </p>
                </div>

                <div>
                    <h4>Menu Penghuni</h4>
                    <ul>
                        <li>
                            <a href="<?= url('penghuni/dashboard.php') ?>">
                                Dashboard
                            </a>
                        </li>
                        <li>
                            <a href="<?= url('penghuni/pembayaran.php') ?>">
                                Pembayaran
                            </a>
                        </li>
                        <li>
                            <a href="<?= url('penghuni/pengaduan.php') ?>">
                                Pengaduan
                            </a>
                        </li>
                    </ul>
                </div>

                <div>
                    <h4>Akun</h4>
                    <ul>
                        <li>
                            <a href="<?= url('guest/logout.php') ?>">
                                Keluar
                            </a>
                        </li>
                    </ul>
                </div>


            <!-- =========================
                 PENGELOLA
                 ========================= -->
            <?php elseif ($role === 'admin'): ?>

                <div class="footer-about">
                    <div class="footer-brand">KostKu.</div>
                    <p>
                        Sistem informasi pengelolaan kost untuk membantu
                        pengelola mengelola kamar, penghuni, pembayaran,
                        pengaduan, booking, serta kunjungan dan survey.
                    </p>
                </div>

                <div>
                    <h4>Menu Pengelola</h4>
                    <ul>
                        <li>
                            <a href="<?= url('admin/dashboard.php') ?>">
                                Dashboard
                            </a>
                        </li>
                        <li>
                            <a href="<?= url('admin/kamar.php') ?>">
                                Kamar
                            </a>
                        </li>
                        <li>
                            <a href="<?= url('admin/penghuni.php') ?>">
                                Penghuni
                            </a>
                        </li>
                        <li>
                            <a href="<?= url('admin/pembayaran.php') ?>">
                                Pembayaran
                            </a>
                        </li>
                    </ul>
                </div>

                <div>
                    <h4>Layanan Kost</h4>
                    <ul>
                        <li>
                            <a href="<?= url('admin/pengaduan.php') ?>">
                                Pengaduan
                            </a>
                        </li>
                        <li>
                            <a href="<?= url('admin/booking.php') ?>">
                                Booking
                            </a>
                        </li>
                        <li>
                            <a href="<?= url('admin/kunjungan-survey.php') ?>">
                                Kunjungan &amp; Survey
                            </a>
                        </li>
                        <li>
                            <a href="<?= url('guest/logout.php') ?>">
                                Keluar
                            </a>
                        </li>
                    </ul>
                </div>

            <?php endif; ?>

        </div>

        <div class="footer-bottom">
            <span>
                © <?= date('Y') ?> KostKu. Semua hak dilindungi.
            </span>

            <span class="footer-legal">
                <a href="#">Syarat &amp; Ketentuan</a>
                <a href="#">Kebijakan Privasi</a>
            </span>
        </div>

    </div>
</footer>


<!-- Toast -->
<div id="toast-root" class="toast-root" aria-live="polite"></div>


<!-- Data global -->
<script>
    window.KOSTKU = {
        base: <?= json_encode(BASE_URL) ?>,
        role: <?= json_encode($role) ?>,
        csrf: <?= json_encode(csrf_token()) ?>
    };

    window.KOSTKU_DATA =
        <?= json_encode($pageData ?? new stdClass(), JSON_UNESCAPED_UNICODE) ?>;
</script>


<!-- JavaScript -->
<script src="<?= asset('js/global.js') ?>?v=<?= filemtime(ROOT_PATH . '/assets/js/global.js') ?>"></script>

<script src="<?= asset('js/' . $role . '.js') ?>?v=<?= filemtime(ROOT_PATH . '/assets/js/' . $role . '.js') ?>"></script>

</body>
</html>