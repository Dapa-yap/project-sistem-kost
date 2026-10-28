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
function get_kamar() {
    $rows = db_rows('SELECT k.id, k.nomor_kamar, k.tipe::text AS tipe, k.ukuran, k.harga, k.status::text AS status,
                            k.foto, u.nama AS penghuni
                     FROM kamar k LEFT JOIN "user" u ON u.id = k.userid
                     ORDER BY k.nomor_kamar');
    $fas = [];
    foreach (db_rows('SELECT kf.kamarid, f.nama FROM kamar_fasilitas kf JOIN fasilitas f ON f.id = kf.fasilitasid WHERE f.kategori::text = \'private\' ORDER BY f.id') as $f) {
        $fas[$f['kamarid']][] = $f['nama'];
    }
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
            'kapasitas' => '1 orang',
            'fasilitas' => $fas[$r['id']] ?? [],
        ];
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
    $rows = db_rows('SELECT u.id AS uid, u.nama, u.no_hp, u.tanggal_masuk, k.nomor_kamar, k.tipe::text AS tipe, k.harga,
                            (SELECT MIN(p.tanggal_tagihan) FROM pembayaran p WHERE p.userid = u.id) AS tagihan_pertama,
                            (SELECT p.status::text FROM pembayaran p WHERE p.userid = u.id ORDER BY p.tanggal_tagihan DESC, p.id DESC LIMIT 1) AS tagihan,
                            (SELECT COUNT(*) FROM pengaduan a WHERE a.userid = u.id AND a.status::text <> \'selesai\') AS aduan
                     FROM kamar k JOIN "user" u ON u.id = k.userid
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

// Tagihan semua penghuni pada periode (bulan) terbaru
function get_pembayaran_admin() {
    $rows = db_rows("SELECT p.id, u.nama, k.nomor_kamar, p.tanggal_tagihan, p.jumlah, p.jatuh_tempo, p.status::text AS status,
                            p.bukti_pembayaran, p.tanggal_bayar
                     FROM pembayaran p JOIN \"user\" u ON u.id = p.userid JOIN kamar k ON k.id = p.kamarid
                     WHERE date_trunc('month', p.tanggal_tagihan) = date_trunc('month', CURRENT_DATE)
                        OR lower(p.status::text) <> 'lunas'
                     ORDER BY p.tanggal_tagihan DESC, k.nomor_kamar, p.id");
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

/* ---------- Pengaduan ---------- */
function get_pengaduan_penghuni() {
    $u = current_user();
    if (!$u) return [];
    $rows = db_rows('SELECT a.deskripsi, a.tanggal_pengaduan, a.tanggal_perbaikan, a.status::text AS status
                     FROM pengaduan a WHERE a.userid = ? ORDER BY a.tanggal_pengaduan DESC, a.id DESC', [$u['id']]);
    $out = [];
    foreach ($rows as $r) {
        $st = norm_status($r['status']);
        $info = $st === 'selesai' && $r['tanggal_perbaikan'] ? 'Diperbaiki ' . tgl($r['tanggal_perbaikan'])
              : ($st === 'diproses' ? 'Sedang diproses pengelola' : 'Menunggu ditinjau pengelola');
        $out[] = ['judul' => $r['deskripsi'], 'tgl' => $r['tanggal_pengaduan'], 'status' => $st, 'info' => $info];
    }
    return $out;
}

function get_pengaduan_admin() {
    $rows = db_rows('SELECT a.id, COALESCE(kb.nomor_kamar, kf_k.nomor_kamar, ku.nomor_kamar, \'-\') AS nomor_kamar,
                            a.deskripsi, a.foto, a.tanggal_pengaduan, a.status::text AS status
                     FROM pengaduan a
                     LEFT JOIN kamar kb ON kb.id = a.kamarid
                     LEFT JOIN kamar_fasilitas kf ON kf.id = a.kamar_fasilitas_id
                     LEFT JOIN kamar kf_k ON kf_k.id = kf.kamarid
                     LEFT JOIN kamar ku ON ku.userid = a.userid
                     ORDER BY a.tanggal_pengaduan DESC, a.id DESC');
    $out = [];
    foreach ($rows as $r) {
        $out[] = ['id' => (int) $r['id'], 'kamar' => $r['nomor_kamar'], 'deskripsi' => $r['deskripsi'],
                  'foto' => $r['foto'] ? 1 : 0, 'foto_file' => $r['foto'], 'tgl' => $r['tanggal_pengaduan'], 'status' => norm_status($r['status'])];
    }
    return $out;
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
