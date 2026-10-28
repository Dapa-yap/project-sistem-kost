<?php
/**
 * KostKu - setup database (jalankan SEKALI lewat terminal, bukan lewat browser)
 *
 *   php database/setup.php
 *   php database/setup.php <username> "<Nama Pengelola>" <password>
 *
 * Yang dilakukan:
 *   1. Membuat tabel / kolom yang belum ada (database/schema.sql).
 *   2. Menambahkan nilai enum yang dibutuhkan aplikasi (kalau kolom kamu bertipe enum).
 *   3. Mengisi data kamar (A-01..A-11, B-01..B-07) & fasilitas bila tabel kamar masih kosong.
 *   4. Membuat akun pengelola. Pengelola hanya boleh SATU: kalau sudah ada, tidak ditambah lagi.
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Skrip ini hanya boleh dijalankan lewat terminal: php database/setup.php\n");
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../data/data.php';

function say($s) { echo $s . PHP_EOL; }
function ask($label) { echo $label; return trim((string) fgets(STDIN)); }

/* ---------- 1. Skema ---------- */
say('1. Menyiapkan tabel ...');
db()->exec(file_get_contents(__DIR__ . '/schema.sql'));
say('   selesai.');

/* ---------- 2. Nilai enum ---------- */
say('2. Mengecek tipe enum ...');
$perlu = [
    ['user', 'role', ['admin', 'penghuni']],            // 'pengelola' dianggap sama dengan 'admin'
    ['kamar', 'status', ['kosong', 'terisi', 'booking', 'diperbaiki']],
    ['pembayaran', 'status', ['belum', 'menunggu', 'lunas', 'terlambat', 'ditolak']],
    ['pengaduan', 'status', ['diajukan', 'diproses', 'selesai']],
    ['booking', 'status', ['menunggu', 'dikonfirmasi', 'kedaluwarsa', 'ditolak']],
    ['kunjungan', 'status', ['menunggu', 'dikonfirmasi', 'selesai', 'dibatalkan']],
    ['pindah_kamar', 'status', ['menunggu', 'selesai', 'ditolak']],
];
foreach ($perlu as [$tabel, $kolom, $nilai]) {
    $tipe = db_enum_type($tabel, $kolom);
    if (!$tipe) continue;                       // bukan enum -> teks biasa, tidak perlu apa-apa
    $ada = array_map('strtolower', db_enum_labels($tabel, $kolom));
    foreach ($nilai as $v) {
        // sudah ada padanannya (mis. 'Tersedia' untuk 'kosong')? lewati
        $padanan = array_filter($ada, fn($l) => norm_status($l) === norm_status($v));
        if ($padanan) continue;
        db()->exec('ALTER TYPE "' . $tipe . '" ADD VALUE IF NOT EXISTS ' . db()->quote($v));
        say("   + enum {$tipe}: '{$v}' ditambahkan");
    }
}
db_enum_cache_reset();

/* ---------- 3. Data awal kamar ---------- */
$jumlahKamar = (int) db_rows('SELECT COUNT(*) AS n FROM kamar')[0]['n'];
if ($jumlahKamar === 0) {
    say('3. Tabel kamar kosong, mengisi data kamar sesuai denah ...');
    $master = ['AC', 'Kipas angin', 'Wi-Fi', 'Kasur & bantal', 'SpringBed & bantal', 'Lemari pakaian', 'Meja & kursi belajar', 'Jendela berventilasi'];
    $idFas = [];
    foreach ($master as $nama) {
        $ada = db_rows("SELECT id FROM fasilitas WHERE nama = ? AND kategori::text = 'private'", [$nama]);
        if ($ada) { $idFas[$nama] = (int) $ada[0]['id']; continue; }
        $r = db_rows("INSERT INTO fasilitas (nama, kategori) VALUES (?, ?) RETURNING id", [$nama, db_enum_value('fasilitas', 'kategori', 'private')]);
        $idFas[$nama] = (int) $r[0]['id'];
    }
    $premium = ['B-03', 'B-07'];
    foreach (['A' => 11, 'B' => 7] as $blok => $n) {
        for ($i = 1; $i <= $n; $i++) {
            $nomor = sprintf('%s-%02d', $blok, $i);
            $prem = in_array($nomor, $premium, true);
            $r = db_rows('INSERT INTO kamar (nomor_kamar, tipe, ukuran, harga, status) VALUES (?, ?, ?, ?, ?) RETURNING id', [
                $nomor,
                db_enum_value('kamar', 'tipe', $prem ? 'Premium' : 'Standar'),
                $prem ? '3 × 4 m' : '3 × 3 m',
                $prem ? 750000 : 600000,
                db_enum_value('kamar', 'status', 'kosong'),
            ]);
            $fas = ['Wi-Fi', $prem ? 'SpringBed & bantal' : 'Kasur & bantal', 'Lemari pakaian', 'Meja & kursi belajar', 'Jendela berventilasi'];
            foreach ($fas as $f) {
                db_exec('INSERT INTO kamar_fasilitas (kamarid, fasilitasid, status) VALUES (?, ?, ?)', [$r[0]['id'], $idFas[$f], db_enum_value('kamar_fasilitas', 'status', 'normal')]);
            }
        }
    }
    say('   18 kamar dibuat.');
} else {
    say("3. Tabel kamar sudah berisi {$jumlahKamar} kamar, tidak diubah.");
}

/* ---------- 4. Akun pengelola (hanya satu) ---------- */
say('4. Akun pengelola ...');
$sudahAda = db_rows("SELECT username FROM \"user\" WHERE role::text IN ('admin','pengelola') LIMIT 1");
if ($sudahAda) {
    say("   Akun pengelola sudah ada (username: {$sudahAda[0]['username']}). Tidak ada akun baru dibuat.");
    say('   Untuk mengganti password pengelola: php database/reset-pengelola.php');
} else {
    $username = $argv[1] ?? ask('   Username pengelola      : ');
    $nama     = $argv[2] ?? ask('   Nama pengelola          : ');
    $password = $argv[3] ?? ask('   Password (min. 8 karakter): ');
    if (!preg_match('/^[A-Za-z0-9_.]{4,30}$/', $username)) exit("   Username harus 4-30 karakter (huruf, angka, _ atau .).\n");
    if ($nama === '')            exit("   Nama tidak boleh kosong.\n");
    if (strlen($password) < 8)   exit("   Password minimal 8 karakter.\n");
    db_exec('INSERT INTO "user" (role, username, password, nama) VALUES (?, ?, ?, ?)', [
        db_enum_value('user', 'role', 'admin'), $username, password_hash($password, PASSWORD_DEFAULT), $nama,
    ]);
    say("   Akun pengelola '{$username}' dibuat. Silakan login di /guest/login.php (tab Pengelola).");
}
say('Selesai.');
