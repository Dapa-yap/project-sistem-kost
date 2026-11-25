<?php
/**
 * Data KostKu - sumber: PostgreSQL (db_pbl).
 * Return tiap fungsi get_*() sengaja sama dengan versi dummy (data/data_dummy.php),
 * jadi halaman tidak perlu diubah.
 * Kolom yang belum ada di database diisi nilai default (lihat catatan per fungsi).
 */
require_once __DIR__ . '/../config/database.php';

/* ---------- Helper ---------- */
function db() { global $pdo; return $pdo; }

function db_rows($sql, $params = []) {
    $st = db()->prepare($sql);
    $st->execute($params);
    return $st->fetchAll(PDO::FETCH_ASSOC);
}

/** Jalankan INSERT/UPDATE/DELETE, kembalikan jumlah baris terpengaruh. */
function db_exec($sql, $params = []) {
    $st = db()->prepare($sql);
    $st->execute($params);
    return $st->rowCount();
}

/** Ambil satu baris (atau null). */
function db_one($sql, $params = []) {
    $r = db_rows($sql, $params);
    return $r[0] ?? null;
}

/* ---------- Enum PostgreSQL ----------
   Kolom status/role/tipe bisa bertipe enum atau teks biasa. Saat MENULIS, nilai disamakan
   dengan label yang benar-benar ada di database (mis. 'kosong' -> 'Tersedia' kalau enum kamu begitu). */
function db_enum_type($tabel, $kolom) {
    $r = db_one("SELECT t.typname FROM pg_attribute a
                 JOIN pg_class c ON c.oid = a.attrelid
                 JOIN pg_namespace n ON n.oid = c.relnamespace AND n.nspname = current_schema()
                 JOIN pg_type t ON t.oid = a.atttypid
                 WHERE c.relname = ? AND a.attname = ? AND t.typtype = 'e' LIMIT 1", [$tabel, $kolom]);
    return $r['typname'] ?? null;
}

function db_enum_labels($tabel, $kolom) {
    $key = $tabel . '.' . $kolom;
    if (!isset($GLOBALS['_enum_cache'][$key])) {
        $tipe = db_enum_type($tabel, $kolom);
        $GLOBALS['_enum_cache'][$key] = $tipe
            ? array_column(db_rows('SELECT e.enumlabel FROM pg_enum e JOIN pg_type t ON t.oid = e.enumtypid WHERE t.typname = ? ORDER BY e.enumsortorder', [$tipe]), 'enumlabel')
            : [];
    }
    return $GLOBALS['_enum_cache'][$key];
}

function db_enum_cache_reset() { $GLOBALS['_enum_cache'] = []; }

function db_enum_value($tabel, $kolom, $nilai) {
    $labels = db_enum_labels($tabel, $kolom);
    if (!$labels) return $nilai;                       // kolom teks biasa
    foreach ($labels as $l) if (strcasecmp($l, $nilai) === 0) return $l;
    foreach ($labels as $l) if (norm_status($l) === norm_status($nilai)) return $l;
    return $nilai;
}

/** Samakan nilai enum database ke istilah yang dipakai tampilan. Sesuaikan jika enum kamu berbeda. */
function norm_status($v) {
    $v = strtolower(trim((string) $v));
    $alias = [
        'tersedia' => 'kosong', 'available' => 'kosong',
        'dibooking' => 'booking', 'terbooking' => 'booking',
        'perbaikan' => 'diperbaiki', 'maintenance' => 'diperbaiki',
        'disetujui' => 'dikonfirmasi',
        'belumdibayar' => 'belum', 'belum_dibayar' => 'belum', 'belum dibayar' => 'belum',
        'menunggu_verifikasi' => 'menunggu', 'menunggu verifikasi' => 'menunggu',
        'proses' => 'diproses', 'diajukan' => 'diajukan',
        'pengelola' => 'admin', 'kadaluarsa' => 'kedaluwarsa',
        'ditahan' => 'dikonfirmasi', 'expired' => 'kedaluwarsa',
    ];
    return $alias[$v] ?? $v;
}

function lantai_dari_nomor($nomor) {
    return strtoupper(substr($nomor, 0, 1)) === 'B' ? 3 : 2;
}

/* ---------- Akun / login ---------- */
/** Cari akun untuk login. Password boleh hash (password_hash) atau teks biasa. */
function find_user($username, $password, $role) {
    $r = db_one('SELECT u.id, u.role::text AS role, u.username, u.password, u.nama, COALESCE(u.aktif, TRUE) AS aktif, k.nomor_kamar
                 FROM "user" u LEFT JOIN kamar k ON k.userid = u.id
                 WHERE lower(u.username) = lower(?) LIMIT 1', [$username]);
    if (!$r || !$r['aktif']) return null;
    $dbRole = norm_status($r['role']);
    $dbRole = $dbRole === 'admin' ? 'admin' : 'penghuni';
    if ($dbRole !== $role) return null;

    $stored = (string) $r['password'];
    $hashed = (password_get_info($stored)['algo'] ?? null) !== null;
    $ok = $hashed ? password_verify($password, $stored) : hash_equals($stored, $password);
    if (!$ok) return null;

    // password lama masih teks biasa / hash usang -> simpan ulang sebagai hash baru
    if (!$hashed || password_needs_rehash($stored, PASSWORD_DEFAULT)) {
        db_exec('UPDATE "user" SET password = ? WHERE id = ?', [password_hash($password, PASSWORD_DEFAULT), $r['id']]);
    }

    $u = ['id' => (int) $r['id'], 'username' => $r['username'], 'role' => $dbRole,
          'nama' => $r['nama'], 'label' => $dbRole === 'admin' ? 'Pengelola' : 'Penghuni'];
    if ($r['nomor_kamar']) $u['kamar'] = $r['nomor_kamar'];
    return $u;
}

/* ---------- Kamar ---------- */
/**
 * Semua kamar + penghuni + fasilitas privatnya dalam SATU query:
 * JOIN kamar -> user -> kamar_fasilitas -> fasilitas, lalu GROUP BY per kamar dan
 * fasilitas dikumpulkan dengan json_agg (urutan mengikuti id fasilitas).
 * Dipakai denah, daftar kamar pengunjung, detail kamar, dan halaman admin.
 */
function get_kamar() {
    $rows = db_rows('SELECT k.id, k.nomor_kamar, k.tipe::text AS tipe, k.ukuran, k.harga, k.status::text AS status,
                            k.foto, k.foto2, u.nama AS penghuni,
                            COALESCE(json_agg(f.nama ORDER BY f.id) FILTER (WHERE f.kategori::text = \'private\'), \'[]\') AS fasilitas
                     FROM kamar k
                     LEFT JOIN "user" u ON u.id = k.userid
                     LEFT JOIN kamar_fasilitas kf ON kf.kamarid = k.id
                     LEFT JOIN fasilitas f ON f.id = kf.fasilitasid
                     GROUP BY k.id, u.nama
                     ORDER BY k.nomor_kamar');
    $out = [];
    foreach ($rows as $r) {
        $id = $r['nomor_kamar'];
        $out[$id] = [
            'id'        => $id,
            'db_id'     => (int) $r['id'],
            'lantai'    => lantai_dari_nomor($id),
            'tipe'      => ucfirst(strtolower($r['tipe'])),
            'ukuran'    => $r['ukuran'],
            'harga'     => (int) $r['harga'],
            'status'    => norm_status($r['status']),
            'penghuni'  => $r['penghuni'],
            'foto'      => $r['foto'],
            'foto2'     => $r['foto2'],
            'kapasitas' => '1 orang',
            'fasilitas' => json_decode($r['fasilitas'], true) ?: [],
        ];
    }
    return $out;
}

/** Ringkasan kamar (total / terisi / kosong) dari view v_statistik_kamar - dipakai dashboard pengelola. */
function get_statistik_kamar() {
    $r = db_one('SELECT total, terisi, kosong FROM v_statistik_kamar');
    return ['total' => (int) $r['total'], 'terisi' => (int) $r['terisi'], 'kosong' => (int) $r['kosong']];
}

/**
 * Jumlah baris per status (GROUP BY) untuk kartu ringkasan halaman Pengaduan & Booking.
 * Status dinormalkan lewat norm_status(), jadi 'kadaluarsa' dan 'kedaluwarsa' dihitung satu.
 */
function get_ringkasan_status($tabel) {
    if (!in_array($tabel, ['pengaduan', 'booking'], true)) throw new InvalidArgumentException('Tabel tidak didukung.');
    $out = [];
    foreach (db_rows("SELECT status::text AS status, COUNT(*) AS n FROM $tabel GROUP BY status::text") as $r) {
        $st = norm_status($r['status']);
        $out[$st] = ($out[$st] ?? 0) + (int) $r['n'];
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

/* Belum dipindah ke database (butuh ikon & teks tampilan) */
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
    // Tiga CTE (masing-masing GROUP BY / DISTINCT ON per penghuni) menggantikan 3 subquery
    // berkorelasi yang dulu dijalankan ulang untuk setiap baris penghuni.
    $rows = db_rows('WITH tagihan_pertama AS (
                         SELECT userid, MIN(tanggal_tagihan) AS tanggal FROM pembayaran GROUP BY userid
                     ),
                     tagihan_terakhir AS (
                         SELECT DISTINCT ON (userid) userid, status::text AS status
                         FROM pembayaran ORDER BY userid, tanggal_tagihan DESC, id DESC
                     ),
                     aduan_aktif AS (
                         SELECT userid, COUNT(*) AS jumlah FROM pengaduan WHERE status::text <> \'selesai\' GROUP BY userid
                     )
                     SELECT u.id AS uid, u.nama, u.no_hp, u.tanggal_masuk, k.nomor_kamar, k.tipe::text AS tipe, k.harga,
                            tp.tanggal AS tagihan_pertama, tt.status AS tagihan, COALESCE(ad.jumlah, 0) AS aduan
                     FROM kamar k
                     JOIN "user" u ON u.id = k.userid
                     LEFT JOIN tagihan_pertama tp ON tp.userid = u.id
                     LEFT JOIN tagihan_terakhir tt ON tt.userid = u.id
                     LEFT JOIN aduan_aktif ad ON ad.userid = u.id
                     WHERE COALESCE(u.aktif, TRUE)
                     ORDER BY k.nomor_kamar');
    $map = ['belum' => 'Belum Dibayar', 'menunggu' => 'Menunggu', 'lunas' => 'Lunas', 'terlambat' => 'Terlambat', 'ditolak' => 'Ditolak'];
    $out = []; $i = 1;
    foreach ($rows as $r) {
        $st = norm_status($r['tagihan']);
        $out[] = [
            'no' => $i++, 'uid' => (int) $r['uid'], 'nama' => $r['nama'], 'kamar' => $r['nomor_kamar'],
            'lantai' => lantai_dari_nomor($r['nomor_kamar']), 'tipe' => ucfirst(strtolower($r['tipe'])),
            'harga' => (int) $r['harga'], 'masuk' => $r['tanggal_masuk'] ?: ($r['tagihan_pertama'] ?: date('Y-m-d')),
            'hp' => $r['no_hp'] ?: '-',
            'tagihan' => $map[$st] ?? ($st === '' ? '-' : ucfirst($st)),
            'aduan' => (int) $r['aduan'], 'deposit' => (int) $r['harga'],
        ];
    }
    return $out;
}

function get_pindah_kamar() {
    $rows = db_rows('SELECT m.id, u.nama, ka.nomor_kamar AS asal, kt.nomor_kamar AS tujuan, m.tanggal, m.status::text AS status
                     FROM pindah_kamar m
                     JOIN "user" u ON u.id = m.userid
                     JOIN kamar ka ON ka.id = m.kamar_asal
                     JOIN kamar kt ON kt.id = m.kamar_tujuan
                     ORDER BY (m.status::text = \'menunggu\') DESC, m.tanggal DESC, m.id DESC');
    foreach ($rows as &$r) { $r['id'] = (int) $r['id']; $r['status'] = norm_status($r['status']); }
    return $rows;
}

/** Pengajuan pindah milik penghuni yang login (terbaru). */
function get_pindah_penghuni() {
    $u = current_user();
    if (!$u) return null;
    return db_one('SELECT m.status::text AS status, m.tanggal, m.alasan_tolak, kt.nomor_kamar AS tujuan
                   FROM pindah_kamar m JOIN kamar kt ON kt.id = m.kamar_tujuan
                   WHERE m.userid = ? ORDER BY m.id DESC LIMIT 1', [$u['id']]);
}

/* ---------- Pembayaran ---------- */
function periode_label($date) {
    $bulan = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    $t = strtotime($date);
    return $bulan[(int) date('n', $t)] . ' ' . date('Y', $t);
}

// Tagihan milik penghuni yang sedang login
function get_tagihan_penghuni() {
    $u = current_user();
    if (!$u) return [];
    $rows = db_rows('SELECT id, tanggal_tagihan, jatuh_tempo, jumlah, status::text AS status, bukti_pembayaran
                     FROM pembayaran WHERE userid = ? ORDER BY tanggal_tagihan DESC, id DESC', [$u['id']]);
    $out = [];
    foreach ($rows as $r) {
        $out[] = ['id' => (int) $r['id'], 'periode' => periode_label($r['tanggal_tagihan']), 'tgl_tagihan' => $r['tanggal_tagihan'],
                  'jatuh_tempo' => $r['jatuh_tempo'], 'jumlah' => (int) $r['jumlah'],
                  'status' => norm_status($r['status']), 'bukti' => $r['bukti_pembayaran']];
    }
    return $out;
}
/** Jumlah & total tagihan lunas milik penghuni yang login (kartu "Dibayar Tahun Ini") - agregasi di database. */
function get_ringkasan_lunas_penghuni() {
    $u = current_user();
    $r = $u ? db_one("SELECT COUNT(*) AS n, COALESCE(SUM(jumlah), 0) AS total
                      FROM pembayaran WHERE userid = ? AND lower(status::text) = 'lunas'", [$u['id']]) : null;
    return ['jumlah' => (int) ($r['n'] ?? 0), 'total' => (int) ($r['total'] ?? 0)];
}
/** Jumlah tagihan penghuni yang masih harus dibayar / dikirim ulang buktinya (untuk badge menu). */
function get_badge_penghuni() {
    $n = 0;
    foreach (get_tagihan_penghuni() as $t) {
        if (in_array($t['status'], ['belum', 'terlambat', 'ditolak'], true)) $n++;
    }
    return ['pembayaran' => $n];
}

// Tagihan semua penghuni pada periode (bulan) terbaru - dibaca dari view v_pembayaran_detail
// (join pembayaran + user + kamar sudah ada di view)
function get_pembayaran_admin() {
    $rows = db_rows("SELECT id, nama, nomor_kamar, tanggal_tagihan, jumlah, jatuh_tempo, status,
                            bukti_pembayaran, tanggal_bayar
                     FROM v_pembayaran_detail
                     WHERE date_trunc('month', tanggal_tagihan) = date_trunc('month', CURRENT_DATE)
                        OR lower(status) <> 'lunas'
                     ORDER BY tanggal_tagihan DESC, nomor_kamar, id");
    $out = [];
    foreach ($rows as $r) {
        $out[] = ['id' => (int) $r['id'], 'nama' => $r['nama'], 'kamar' => $r['nomor_kamar'],
                  'periode' => periode_label($r['tanggal_tagihan']),
                  'jumlah' => (int) $r['jumlah'], 'jatuh_tempo' => $r['jatuh_tempo'],
                  'status' => norm_status($r['status']), 'bukti' => $r['bukti_pembayaran'],
                  'dikirim' => $r['tanggal_bayar'] ? substr($r['tanggal_bayar'], 0, 16) : null];
    }
    return $out;
}
/* ---------- Laporan keuangan ---------- */
/** $bulan berformat 'YYYY-MM'. Mengembalikan ringkasan, tren 6 bulan, dan rincian tagihan. */
function get_laporan_keuangan($bulan) {
    $awal = $bulan . '-01';

    // 1. Semua tagihan pada bulan yang dipilih
    $rows = db_rows("SELECT id, nama, nomor_kamar, jatuh_tempo, jumlah, status
                     FROM v_pembayaran_detail
                     WHERE date_trunc('month', tanggal_tagihan) = date_trunc('month', ?::date)
                     ORDER BY nomor_kamar, id", [$awal]);

    $urutan = ['terlambat' => 0, 'ditolak' => 1, 'menunggu' => 2, 'belum' => 3, 'lunas' => 4];
    $hitung = ['lunas' => 0, 'menunggu' => 0, 'belum' => 0, 'terlambat' => 0, 'ditolak' => 0];
    $total = 0; $diterima = 0; $tunggakan = 0; $tagihan = [];

    foreach ($rows as $r) {
        $s   = norm_status($r['status']);
        $jml = (int) $r['jumlah'];
        $total += $jml;
        if (isset($hitung[$s])) $hitung[$s]++;
        if ($s === 'lunas') $diterima += $jml;

        $hari = 0;
        if ($s === 'terlambat') {
            $tunggakan += $jml;
            $hari = max(0, (int) floor((strtotime(date('Y-m-d')) - strtotime($r['jatuh_tempo'])) / 86400));
        }
        $tagihan[] = ['kamar' => $r['nomor_kamar'], 'nama' => $r['nama'], 'jatuh_tempo' => $r['jatuh_tempo'],
                      'jumlah' => $jml, 'status' => $s, 'hari' => $hari];
    }
    // yang terlambat tampil paling atas
    usort($tagihan, fn($a, $b) => (($urutan[$a['status']] ?? 9) <=> ($urutan[$b['status']] ?? 9)) ?: strcmp($a['kamar'], $b['kamar']));

    // 2. Uang masuk (lunas) 6 bulan terakhir, bulan yang kosong diisi 0
    $map = [];
    foreach (db_rows("SELECT to_char(tanggal_tagihan, 'YYYY-MM') AS bln, SUM(jumlah) AS total
                      FROM v_pembayaran_detail
                      WHERE lower(status) = 'lunas'
                        AND tanggal_tagihan >= (?::date - interval '5 months')
                        AND tanggal_tagihan <  (?::date + interval '1 month')
                      GROUP BY 1", [$awal, $awal]) as $r) {
        $map[$r['bln']] = (int) $r['total'];
    }
    $label = []; $nilai = [];
    for ($i = 5; $i >= 0; $i--) {
        $t = strtotime("$awal -$i month");
        $label[] = periode_label(date('Y-m-01', $t));
        $nilai[] = $map[date('Y-m', $t)] ?? 0;
    }

    return [
        'tagihan'      => $tagihan,
        'hitung'       => $hitung,
        'total'        => $total,
        'diterima'     => $diterima,
        'tunggakan'    => $tunggakan,
        'persen'       => $total ? (int) round($diterima / $total * 100) : 0,
        'tren'         => ['label' => $label, 'nilai' => $nilai],
    ];
}

/* ---------- Pengaduan ---------- */
function get_pengaduan_penghuni() {
    $u = current_user();
    if (!$u) return [];
    $rows = db_rows('SELECT a.deskripsi, a.tanggal_pengaduan, a.tanggal_perbaikan, a.status::text AS status, a.catatan
                     FROM pengaduan a WHERE a.userid = ? ORDER BY a.tanggal_pengaduan DESC, a.id DESC', [$u['id']]);
    $out = [];
    foreach ($rows as $r) {
        $st = norm_status($r['status']);
        $info = $st === 'selesai' && $r['tanggal_perbaikan'] ? 'Diperbaiki ' . tgl($r['tanggal_perbaikan'])
              : ($st === 'diproses' ? 'Sedang diproses pengelola' : 'Menunggu ditinjau pengelola');
        $out[] = ['judul' => $r['deskripsi'], 'tgl' => $r['tanggal_pengaduan'], 'status' => $st,
                  'info' => $info, 'catatan' => trim((string) $r['catatan'])];
    }
    return $out;
}

function get_pengaduan_admin() {
    // view v_pengaduan_detail sudah menyelesaikan nomor kamar dari 3 jalur (COALESCE)
    $rows = db_rows('SELECT id, nomor_kamar, deskripsi, foto, tanggal_pengaduan, status
                     FROM v_pengaduan_detail
                     ORDER BY tanggal_pengaduan DESC, id DESC');
    $out = [];
    foreach ($rows as $r) {
        $out[] = ['id' => (int) $r['id'], 'kamar' => $r['nomor_kamar'], 'deskripsi' => $r['deskripsi'],
                  'foto' => $r['foto'] ? 1 : 0, 'foto_file' => $r['foto'], 'tgl' => $r['tanggal_pengaduan'], 'status' => norm_status($r['status'])];
    }
    return $out;
}
/**
 * Jumlah item yang perlu ditindaklanjuti admin (untuk badge di menu).
 * Header admin dimuat di setiap halaman, jadi 4 hitungan dikumpulkan dalam SATU query
 * (4 subquery skalar) - bukan 4 round-trip terpisah.
 * pembayaran = bukti transfer belum diverifikasi, pengaduan = aduan baru,
 * booking = belum dikonfirmasi, jadwal = survey belum dikonfirmasi.
 */
function get_badge_admin() {
    $r = db_one("SELECT
                    (SELECT COUNT(*) FROM pembayaran WHERE lower(status::text) = 'menunggu') AS pembayaran,
                    (SELECT COUNT(*) FROM pengaduan  WHERE lower(status::text) = 'diajukan') AS pengaduan,
                    (SELECT COUNT(*) FROM booking    WHERE lower(status::text) = 'menunggu') AS booking,
                    (SELECT COUNT(*) FROM kunjungan  WHERE lower(status::text) = 'menunggu'
                                                         AND COALESCE(jenis, 'kunjungan') = 'survey') AS jadwal");
    return ['pembayaran' => (int) $r['pembayaran'], 'pengaduan' => (int) $r['pengaduan'],
            'booking' => (int) $r['booking'], 'jadwal' => (int) $r['jadwal']];
}

/* ---------- Booking (calon penghuni) ---------- */
function get_booking() {
    $rows = db_rows('SELECT b.id, b.calon_penghuni, b.no_hp, b.tgl_masuk, k.nomor_kamar, k.tipe::text AS tipe, b.tgl_booking, b.batas_waktu, b.status::text AS status
                     FROM booking b JOIN kamar k ON k.id = b.kamarid ORDER BY b.tgl_booking DESC, b.id DESC');
    $out = [];
    foreach ($rows as $r) {
        $st = norm_status($r['status']);
        $out[] = ['id' => (int) $r['id'], 'nama' => $r['calon_penghuni'], 'hp' => $r['no_hp'] ?: '-', 'kamar' => $r['nomor_kamar'],
                  'tipe' => ucfirst(strtolower($r['tipe'])), 'tgl' => $r['tgl_booking'], 'batas' => $r['batas_waktu'],
                  'tgl_masuk' => $r['tgl_masuk'],
                  'status' => $st,
                  'urgent' => $st === 'menunggu' && strtotime($r['batas_waktu']) <= strtotime('+1 day')];
    }
    return $out;
}

/* ---------- Kunjungan ----------
   Tabel kunjungan baru punya nama, tanggal, status -> hp/kamar/jam/catatan diisi default */
function get_kunjungan($jenis = 'kunjungan') {
    $rows = db_rows('SELECT id_kunjungan, nama, no_hp, kamar, tanggal, jam, catatan, status::text AS status
                     FROM kunjungan WHERE COALESCE(jenis, \'kunjungan\') = ? ORDER BY tanggal DESC, id_kunjungan DESC', [$jenis]);
    $out = [];
    foreach ($rows as $r) {
        $out[] = ['id' => (int) $r['id_kunjungan'], 'nama' => $r['nama'], 'hp' => $r['no_hp'] ?: '', 'kamar' => $r['kamar'] ?: '',
                  'tanggal' => $r['tanggal'], 'jam' => $r['jam'] ?: '-', 'catatan' => (string) $r['catatan'], 'status' => norm_status($r['status'])];
    }
    return $out;
}

/** Booking milik calon penghuni (dipakai halaman status). */
function get_booking_by_id($id) {
    $r = db_one('SELECT b.id, b.calon_penghuni, b.no_hp, b.tgl_masuk, b.batas_waktu, b.status::text AS status, k.nomor_kamar
                 FROM booking b JOIN kamar k ON k.id = b.kamarid WHERE b.id = ?', [(int) $id]);
    if (!$r) return null;
    $st = norm_status($r['status']);
    return ['id' => (int) $r['id'], 'kamar' => $r['nomor_kamar'], 'nama' => $r['calon_penghuni'], 'wa' => $r['no_hp'],
            'tgl' => $r['tgl_masuk'], 'batas' => $r['batas_waktu'],
            'status' => $st === 'dikonfirmasi' ? 'disetujui' : ($st === 'kedaluwarsa' ? 'ditolak' : $st)];
}
