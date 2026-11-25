<?php
require_once __DIR__ . '/../config/config.php';

$role = 'guest'; $pageTitle = 'Syarat & Ketentuan'; $pageId = 'guest-legal'; $activeMenu = '';

include ROOT_PATH . '/header/header.php';
?>
<main>
    <section class="page-head is-center">
        <div class="container">
            <h1>Syarat &amp; Ketentuan</h1>
            <p>Terakhir diperbarui: 25 November 2026</p>
        </div>
    </section>

    <section class="container legal-wrap">
        <article class="card legal-doc">

            <h2>1. Tentang KostKu</h2>
            <p>KostKu adalah sistem informasi pengelolaan kost di Jl. Kembang Turi Gg. IIIA, Jatimulyo, Lowokwaru, Kota Malang. Layanan ini membantu calon penghuni mencari kamar, melakukan booking, dan menjadwalkan kunjungan atau survei. Penghuni dapat mengelola pembayaran, pengaduan, dan pindah kamar, sedangkan pengelola mengelola seluruh data kost.</p>

            <h2>2. Jenis Pengguna</h2>
            <ul>
                <li><strong>Pengunjung/calon penghuni:</strong> tidak memerlukan akun. Dapat melihat kamar, mengajukan booking, dan menjadwalkan kunjungan atau survei.</li>
                <li><strong>Penghuni:</strong> akun dibuat oleh pengelola dan diberikan lewat WhatsApp. Akun tidak bisa didaftarkan sendiri.</li>
                <li><strong>Pengelola:</strong> satu akun yang bertugas mengelola kamar, penghuni, pembayaran, pengaduan, booking, dan kunjungan.</li>
            </ul>

            <h2>3. Akun dan Keamanan</h2>
            <ul>
                <li>Penghuni wajib menjaga kerahasiaan username dan kata sandi, serta disarankan menggantinya setelah login pertama (minimal 8 karakter).</li>
                <li>Semua aktivitas dari akun penghuni menjadi tanggung jawab pemilik akun.</li>
                <li>Akun penghuni dinonaktifkan oleh pengelola ketika penghuni keluar atau melanggar ketentuan.</li>
                <li>Pengelola dapat membatasi percobaan login atau pengiriman form yang berlebihan demi keamanan.</li>
            </ul>

            <h2>4. Booking Kamar</h2>
            <ul>
                <li>Booking hanya bisa dilakukan untuk kamar berstatus kosong, dengan data yang benar (nama, nomor WhatsApp aktif, dan tanggal masuk yang belum lewat).</li>
                <li>Booking <strong>belum final</strong> sebelum disetujui pengelola. Statusnya dapat dicek di menu <a href="<?= url('guest/status.php') ?>">Cek Status Pengajuan</a>.</li>
                <li>Booking yang menunggu diberi batas waktu 3 hari. Jika disetujui, kamar ditahan 3 hari sejak persetujuan (atau sampai tanggal masuk, mana yang lebih lama).</li>
                <li>Booking yang melewati batas waktu menjadi <strong>kedaluwarsa</strong> dan kamar dilepas kembali agar bisa dipesan orang lain.</li>
                <li>Pengelola berhak menolak booking. Calon penghuni dapat memilih kamar lain.</li>
            </ul>

            <h2>5. Kunjungan dan Survei</h2>
            <ul>
                <li>Jadwal dapat diajukan Senin–Sabtu pada jam kunjungan (09.00–17.00), dan tidak boleh di tanggal yang sudah lewat.</li>
                <li>Jadwal baru berlaku setelah dikonfirmasi pengelola, dan dapat dibatalkan atau diubah oleh pengelola bila perlu.</li>
            </ul>

            <h2>6. Pembayaran Sewa</h2>
            <ul>
                <li>Harga sewa mengikuti harga kamar yang tertera di sistem.</li>
                <li>Tagihan pertama jatuh tempo 7 hari setelah tanggal masuk. Tagihan berikutnya dibuat otomatis setiap bulan.</li>
                <li>Pembayaran dilakukan ke rekening yang tertera di halaman Pembayaran, lalu penghuni mengunggah bukti pembayaran.</li>
                <li>Status tagihan: belum dibayar, menunggu verifikasi, lunas, terlambat, atau ditolak. Bukti yang ditolak harus diunggah ulang.</li>
                <li>Tagihan yang lewat jatuh tempo otomatis berstatus <strong>terlambat</strong>.</li>
            </ul>

            <h2>7. Pengaduan Kerusakan</h2>
            <ul>
                <li>Penghuni dapat melaporkan kerusakan fasilitas dengan deskripsi (minimal 10 karakter) dan foto opsional.</li>
                <li>Status pengaduan: diajukan, diproses, selesai. Pengelola dapat memberi catatan.</li>
                <li>Penghuni diminta melapor dengan jujur dan tidak menyalahgunakan fitur ini.</li>
            </ul>

            <h2>8. Pindah Kamar dan Keluar Kost</h2>
            <ul>
                <li>Penghuni dapat mengajukan pindah ke kamar kosong dengan alasan dan tanggal yang jelas. Hanya satu pengajuan yang boleh aktif, dan pengelola dapat menyetujui atau menolaknya.</li>
                <li>Saat penghuni keluar, pengelola mencatat tanggal keluar dan kondisi kamar. Akun penghuni lalu dinonaktifkan.</li>
            </ul>

            <h2>9. Unggahan File</h2>
            <p>Foto hanya boleh berformat JPG atau PNG, maksimal 5 MB. Dilarang mengunggah konten yang melanggar hukum, tidak pantas, atau tidak berkaitan dengan layanan.</p>

            <h2>10. Larangan</h2>
            <p>Dilarang memberikan data palsu, mencoba masuk ke akun orang lain, mengganggu atau meretas sistem, atau memakai layanan untuk tujuan melanggar hukum.</p>

            <h2>11. Batasan Tanggung Jawab</h2>
            <p>KostKu berupaya menjaga informasi kamar (harga, fasilitas, ketersediaan) tetap akurat, namun informasi dapat berubah sewaktu-waktu. KostKu tidak bertanggung jawab atas gangguan layanan di luar kendali kami atau kerugian akibat kelalaian pengguna menjaga akunnya.</p>

            <h2>12. Perubahan Ketentuan</h2>
            <p>Ketentuan dapat diperbarui sewaktu-waktu. Penggunaan layanan setelah perubahan berarti pengguna menyetujui ketentuan terbaru.</p>

            <h2>13. Kontak</h2>
            <p>Pertanyaan dapat disampaikan lewat <a href="<?= url('guest/kontak.php') ?>">halaman Kontak</a>, email <a href="mailto:admin@kostku.com">admin@kostku.com</a>, atau WhatsApp/telepon 0821-4033-9455 (Senin–Sabtu, 09.00–17.00).</p>

            <p class="legal-see">Lihat juga: <a href="<?= url('guest/kebijakan-privasi.php') ?>">Kebijakan Privasi</a></p>
        </article>
    </section>
</main>
<?php include ROOT_PATH . '/footer/footer.php'; ?>