<?php
require_once __DIR__ . '/../config/config.php';

$role = 'guest'; $pageTitle = 'Kebijakan Privasi'; $pageId = 'guest-legal'; $activeMenu = '';

include ROOT_PATH . '/header/header.php';
?>
<main>
    <section class="page-head is-center">
        <div class="container">
            <h1>Kebijakan Privasi</h1>
            <p>Terakhir diperbarui: 25 November 2026</p>
        </div>
    </section>

    <section class="container legal-wrap">
        <article class="card legal-doc">

            <h2>1. Data yang Kami Kumpulkan</h2>
            <ul>
                <li><strong>Pengunjung/calon penghuni:</strong> nama, nomor WhatsApp, tanggal masuk (booking), serta tanggal, jam, kamar, dan catatan (jadwal kunjungan/survei).</li>
                <li><strong>Penghuni:</strong> nama, nomor HP, username, kata sandi, nomor kamar, tanggal masuk dan keluar, riwayat tagihan dan pembayaran, bukti pembayaran (foto), pengaduan (deskripsi dan foto), serta riwayat pengajuan pindah kamar.</li>
                <li><strong>Teknis:</strong> cookie sesi untuk menjaga login dan keamanan form (token CSRF), serta pencatatan jumlah percobaan login/pengiriman form untuk mencegah penyalahgunaan.</li>
            </ul>
            <p>KostKu <strong>tidak</strong> meminta KTP, NIK, atau data finansial seperti nomor kartu.</p>

            <h2>2. Tujuan Penggunaan</h2>
            <ul>
                <li>Memproses booking, kunjungan, dan survei.</li>
                <li>Membuat dan mengelola akun penghuni.</li>
                <li>Menagih dan memverifikasi pembayaran sewa.</li>
                <li>Menindaklanjuti pengaduan dan pindah kamar.</li>
                <li>Menghubungi kamu lewat WhatsApp atau telepon terkait layanan.</li>
                <li>Menjaga keamanan sistem.</li>
            </ul>

            <h2>3. Siapa yang Dapat Mengakses Data</h2>
            <ul>
                <li>Data booking, kunjungan, dan penghuni hanya diakses oleh pengelola.</li>
                <li>Bukti pembayaran dan foto pengaduan hanya dapat dilihat oleh pengelola dan penghuni pemilik file tersebut.</li>
                <li>Kami tidak menjual atau menyewakan data pribadi. Data hanya dibuka ke pihak lain jika diwajibkan hukum.</li>
            </ul>

            <h2>4. Keamanan</h2>
            <p>Kata sandi disimpan dalam bentuk hash (bukan teks asli). Kami memakai cookie sesi yang diamankan, perlindungan CSRF, pembatasan percobaan, serta kontrol akses berdasarkan peran. Meski begitu, tidak ada sistem yang 100% bebas risiko, jadi jaga kerahasiaan kata sandimu.</p>

            <h2>5. Cookie</h2>
            <p>KostKu hanya memakai cookie sesi yang diperlukan agar login dan form berfungsi. Kami tidak memakai cookie iklan atau pelacak pihak ketiga.</p>

            <h2>6. Penyimpanan Data</h2>
            <ul>
                <li>Data booking dan kunjungan disimpan selama diperlukan untuk proses pengelolaan kost.</li>
                <li>Data penghuni dan riwayat pembayaran disimpan selama masa sewa dan sesudahnya untuk pencatatan keuangan dan penyelesaian sengketa.</li>
                <li>Saat penghuni keluar, akun dinonaktifkan, tetapi riwayat tetap tersimpan.</li>
            </ul>

            <h2>7. Hak Kamu</h2>
            <p>Kamu dapat meminta untuk melihat, memperbaiki, atau menghapus data pribadimu (selama tidak bertentangan dengan kewajiban pencatatan) lewat kontak di bawah.</p>

            <h2>8. Layanan Pihak Ketiga</h2>
            <p>Halaman Kontak menampilkan peta Google Maps. Saat dibuka, Google dapat memproses data sesuai kebijakannya sendiri.</p>

            <h2>9. Perubahan Kebijakan</h2>
            <p>Kebijakan dapat diperbarui, dan versi terbaru selalu tersedia di halaman ini.</p>

            <h2>10. Kontak</h2>
            <p>Pertanyaan terkait privasi dapat disampaikan lewat <a href="<?= url('guest/kontak.php') ?>">halaman Kontak</a>, email <a href="mailto:admin@kostku.com">admin@kostku.com</a>, atau WhatsApp/telepon 0821-4033-9455.</p>

            <p class="legal-see">Lihat juga: <a href="<?= url('guest/syarat-ketentuan.php') ?>">Syarat &amp; Ketentuan</a></p>
        </article>
    </section>
</main>
<?php include ROOT_PATH . '/footer/footer.php'; ?>