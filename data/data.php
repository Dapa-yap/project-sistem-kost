<?php
/**
 * Data dummy KostKu.
 * Semua fungsi get_*() di sini nanti diganti dengan query database (PDO/MySQLi).
 * Selama return-nya tetap array dengan key yang sama, halaman tidak perlu diubah.
 */

/* ---------- Akun login sementara ---------- */
function get_users() {
    return [
        ['username' => 'admin', 'password' => 'admin123',  'role' => 'admin',    'nama' => 'Admin KostKu', 'label' => 'Pengelola'],
        ['username' => 'rina',  'password' => 'rina12345', 'role' => 'penghuni', 'nama' => 'Rina Aulia',   'label' => 'Penghuni', 'kamar' => 'A-03'],
    ];
}

/* ---------- Kamar ----------
   Sesuai denah:
   - Lantai 1 = parkir (bukan kamar)
   - Lantai 2 = A-01 s/d A-11
   - Lantai 3 = B-01 s/d B-07
   Harga: semua 600k, kecuali B-03 dan B-07 = 750k (tipe Premium)
*/
function get_kamar() {
    $jumlah  = ['A' => 11, 'B' => 7];
    $lantai  = ['A' => 2,  'B' => 3];
    $premium = ['B-03', 'B-07'];
    $penghuni = [
        'A-03' => 'Rina Aulia', 'A-10' => 'Putri Anggraini',
        'B-02' => 'Citra Lestari', 'B-03' => 'Dimas Putra', 'B-07' => 'Fajar Maulana',
    ];
    // kamar yang sedang ditahan oleh calon penghuni (lihat get_booking)
    $ditahan = ['A-04', 'A-07', 'A-08', 'B-04', 'B-05'];

    $out = [];
    foreach ($jumlah as $blok => $n) {
        for ($i = 1; $i <= $n; $i++) {
            $id = sprintf('%s-%02d', $blok, $i);
            $isPrem = in_array($id, $premium, true);
            if (isset($penghuni[$id]))             $st = 'terisi';
            elseif (in_array($id, $ditahan, true)) $st = 'booking';
            else                                   $st = 'kosong';

            $out[$id] = [
                'id'        => $id,
                'lantai'    => $lantai[$blok],
                'tipe'      => $isPrem ? 'Premium' : 'Standar',
                'ukuran'    => $isPrem ? '3 × 4 m' : '3 × 3 m',
                'harga'     => $isPrem ? 750000 : 600000,
                'status'    => $st,
                'penghuni'  => $penghuni[$id] ?? null,
                'kapasitas' => '1 orang',
                'fasilitas' => $isPrem
                    ? ['AC', 'Wi-Fi', 'Kasur & bantal', 'Lemari pakaian', 'Meja & kursi belajar', 'Jendela berventilasi']
                    : ['Kipas angin', 'Wi-Fi', 'Kasur & bantal', 'Lemari pakaian', 'Meja & kursi belajar', 'Jendela berventilasi'],
            ];
        }
    }
    return $out;
}

function get_kamar_by_id($id) {
    $all = get_kamar();
    return $all[$id] ?? null;
}

function kamar_ringkas($k) {
    $f = array_slice($k['fasilitas'], 0, 3);
    return implode(' · ', $f);
}

function get_fasilitas_bersama() {
    return [
        ['nama' => 'Parkir Luas',     'desc' => 'Motor dan mobil, area beratap, tersedia 24 jam.', 'icon' => 'parking'],
        ['nama' => 'Kamar mandi luar','desc' => 'Bersih dan nyaman',                               'icon' => 'bath'],
        ['nama' => 'Keamanan CCTV',   'desc' => 'Kamera aktif 24 jam di area umum dan gerbang.',  'icon' => 'cctv'],
        ['nama' => 'Layanan Bersih',  'desc' => 'Area bersama dibersihkan setiap hari.',           'icon' => 'clean'],
        ['nama' => 'Wi-Fi Cepat',     'desc' => 'Jaringan stabil untuk kuliah dan kerja daring.',  'icon' => 'wifi'],
        ['nama' => 'Rooftop',         'desc' => 'Digunakan untuk menjemur baju.',                  'icon' => 'roof'],
        ['nama' => 'Ruang Belajar',   'desc' => 'Ruang tenang ber-AC dengan meja bersama.',        'icon' => 'book'],
        ['nama' => 'Tempat Kumpul',   'desc' => 'Area bersama saat santai atau bergurau.',         'icon' => 'users'],
    ];
}

function get_aturan() {
    return [
        'Tamu dibatasi hingga pukul 21.00',
        'Jaga ketenangan setelah pukul 22.00',
        'Bersihkan dapur setelah dipakai',
        'Parkir hanya di area yang ditentukan',
        'Lapor kerusakan ke pengelola',
    ];
}

/* ---------- Penghuni ---------- */
function get_penghuni() {
    $kamar = get_kamar();
    $info = [
        'A-03' => ['2026-01-03', '0812-3456-7890', 'Menunggu'],
        'A-10' => ['2026-06-02', '0896-5533-1020', 'Lunas'],
        'B-02' => ['2026-04-05', '0878-4410-2299', 'Lunas'],
        'B-03' => ['2026-02-15', '0813-1122-3344', 'Menunggu'],
        'B-07' => ['2026-05-12', '0812-7788-4455', 'Terlambat'],
    ];
    $out = []; $i = 1;
    foreach ($kamar as $id => $k) {
        if ($k['status'] !== 'terisi') continue;
        [$masuk, $hp, $tagihan] = $info[$id];
        $out[] = [
            'no' => $i++, 'nama' => $k['penghuni'], 'kamar' => $id, 'lantai' => $k['lantai'],
            'tipe' => $k['tipe'], 'harga' => $k['harga'], 'masuk' => $masuk, 'hp' => $hp,
            'tagihan' => $tagihan, 'aduan' => $id === 'A-03' ? 1 : 0, 'deposit' => $k['harga'],
        ];
    }
    return $out;
}

function get_pindah_kamar() {
    return [
        ['id' => 1, 'nama' => 'Rina Aulia',      'asal' => 'A-03', 'tujuan' => 'B-01', 'tanggal' => '2026-11-01', 'status' => 'menunggu', 'alasan' => 'Ingin kamar lantai 3 yang lebih tenang'],
        ['id' => 2, 'nama' => 'Dimas Putra',     'asal' => 'B-03', 'tujuan' => 'A-02', 'tanggal' => '2026-11-05', 'status' => 'menunggu', 'alasan' => 'Lebih dekat dengan tangga'],
        ['id' => 3, 'nama' => 'Putri Anggraini', 'asal' => 'A-06', 'tujuan' => 'A-10', 'tanggal' => '2026-10-01', 'status' => 'selesai',  'alasan' => 'Ingin kamar yang lebih luas'],
    ];
}

/* ---------- Pembayaran ---------- */
// Tagihan milik 1 penghuni (Rina Aulia, A-03, Rp 600.000/bulan)
function get_tagihan_penghuni() {
    $rows = [
        ['Nov 2026', '2026-11-01', '2026-11-10', 'belum',     null],
        ['Okt 2026', '2026-10-01', '2026-10-10', 'menunggu',  'bukti_okt.jpg'],
        ['Sep 2026', '2026-09-01', '2026-09-10', 'lunas',     'bukti_sep.jpg'],
        ['Agu 2026', '2026-08-01', '2026-08-10', 'lunas',     'bukti_agu.jpg'],
        ['Jul 2026', '2026-07-01', '2026-07-10', 'lunas',     'bukti_jul.jpg'],
        ['Jun 2026', '2026-06-01', '2026-06-10', 'terlambat', 'bukti_jun.jpg'],
    ];
    $out = [];
    foreach ($rows as [$periode, $tt, $jt, $st, $bk]) {
        $out[] = ['periode' => $periode, 'tgl_tagihan' => $tt, 'jatuh_tempo' => $jt, 'jumlah' => 600000, 'status' => $st, 'bukti' => $bk];
    }
    return $out;
}

// Semua tagihan periode Okt 2026 (sisi pengelola)
function get_pembayaran_admin() {
    return [
        ['id' => 1, 'nama' => 'Rina Aulia',      'kamar' => 'A-03', 'jumlah' => 600000, 'jatuh_tempo' => '2026-10-10', 'status' => 'menunggu',  'bukti' => 'bukti_okt.jpg',   'dikirim' => '2026-10-06 19:12'],
        ['id' => 2, 'nama' => 'Dimas Putra',     'kamar' => 'B-03', 'jumlah' => 750000, 'jatuh_tempo' => '2026-10-10', 'status' => 'menunggu',  'bukti' => 'bukti_dimas.jpg', 'dikirim' => '2026-10-07 08:45'],
        ['id' => 3, 'nama' => 'Citra Lestari',   'kamar' => 'B-02', 'jumlah' => 600000, 'jatuh_tempo' => '2026-10-10', 'status' => 'lunas',     'bukti' => 'bukti_citra.jpg', 'dikirim' => '2026-10-01 14:03'],
        ['id' => 4, 'nama' => 'Putri Anggraini', 'kamar' => 'A-10', 'jumlah' => 600000, 'jatuh_tempo' => '2026-10-10', 'status' => 'lunas',     'bukti' => 'bukti_putri.jpg', 'dikirim' => '2026-10-02 10:20'],
        ['id' => 5, 'nama' => 'Fajar Maulana',   'kamar' => 'B-07', 'jumlah' => 750000, 'jatuh_tempo' => '2026-10-10', 'status' => 'terlambat', 'bukti' => null,              'dikirim' => null],
    ];
}

/* ---------- Pengaduan ---------- */
function get_pengaduan_penghuni() {
    return [
        ['judul' => 'Kran kamar mandi bocor', 'tgl' => '2026-09-28', 'status' => 'diproses', 'info' => 'Perbaikan: belum dijadwalkan'],
        ['judul' => 'Lampu kamar mati',       'tgl' => '2026-08-12', 'status' => 'selesai',  'info' => 'Diperbaiki 14 Agu 2026'],
        ['judul' => 'Wi-Fi sering terputus',  'tgl' => '2026-10-02', 'status' => 'diajukan', 'info' => 'Menunggu ditinjau pengelola'],
    ];
}

function get_pengaduan_admin() {
    return [
        ['id' => 1, 'kamar' => 'B-02', 'deskripsi' => 'Wi-Fi sering terputus',     'foto' => 2, 'tgl' => '2026-10-03', 'status' => 'diajukan'],
        ['id' => 2, 'kamar' => 'A-03', 'deskripsi' => 'Kran wastafel bocor',       'foto' => 1, 'tgl' => '2026-09-28', 'status' => 'diproses'],
        ['id' => 3, 'kamar' => 'B-03', 'deskripsi' => 'AC tidak dingin',           'foto' => 2, 'tgl' => '2026-09-15', 'status' => 'diproses'],
        ['id' => 4, 'kamar' => 'A-10', 'deskripsi' => 'Lampu kamar mati',          'foto' => 1, 'tgl' => '2026-09-10', 'status' => 'selesai'],
        ['id' => 5, 'kamar' => 'B-07', 'deskripsi' => 'Pintu kamar susah dikunci', 'foto' => 1, 'tgl' => '2026-09-02', 'status' => 'selesai'],
        ['id' => 6, 'kamar' => 'B-02', 'deskripsi' => 'Kran kamar mandi macet',    'foto' => 1, 'tgl' => '2026-08-28', 'status' => 'selesai'],
    ];
}

/* ---------- Booking (calon penghuni) ---------- */
function get_booking() {
    return [
        ['id' => 1, 'nama' => 'Fajar Hidayat', 'hp' => '0812-7788-4455', 'kamar' => 'A-04', 'tipe' => 'Standar', 'tgl' => '2026-10-10', 'batas' => '2026-10-13', 'status' => 'menunggu',     'urgent' => false],
        ['id' => 2, 'nama' => 'Reza Pratama',  'hp' => '0857-1200-3321', 'kamar' => 'A-07', 'tipe' => 'Standar', 'tgl' => '2026-10-09', 'batas' => '2026-10-12', 'status' => 'menunggu',     'urgent' => true],
        ['id' => 3, 'nama' => 'Kevin Wijaya',  'hp' => '0821-4400-9812', 'kamar' => 'B-04', 'tipe' => 'Standar', 'tgl' => '2026-10-11', 'batas' => '2026-10-14', 'status' => 'menunggu',     'urgent' => false],
        ['id' => 4, 'nama' => 'Nadia Safitri', 'hp' => '0878-3321-7765', 'kamar' => 'A-08', 'tipe' => 'Standar', 'tgl' => '2026-10-08', 'batas' => '2026-10-11', 'status' => 'dikonfirmasi', 'urgent' => false],
        ['id' => 5, 'nama' => 'Bayu Prakoso',  'hp' => '0896-2210-4543', 'kamar' => 'B-05', 'tipe' => 'Standar', 'tgl' => '2026-10-07', 'batas' => '2026-10-10', 'status' => 'dikonfirmasi', 'urgent' => false],
        ['id' => 6, 'nama' => 'Laras Ayu',     'hp' => '0813-9087-1122', 'kamar' => 'A-09', 'tipe' => 'Standar', 'tgl' => '2026-10-05', 'batas' => '2026-10-08', 'status' => 'kedaluwarsa', 'urgent' => false],
    ];
}
