<?php
/**
 * KostKu - semua proses TULIS ke database (tambah penghuni, verifikasi bayar, booking, dst).
 * Setiap fungsi melempar AksiError dengan pesan yang aman ditampilkan ke pengguna
 * kalau datanya tidak valid. Dipanggil dari api/*.php dan guest/*-simpan.php.
 */
require_once __DIR__ . '/data.php';

class AksiError extends RuntimeException {}

function gagal($pesan) { throw new AksiError($pesan); }

function dalam_transaksi(callable $fn) {
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $hasil = $fn();
        $pdo->commit();
        return $hasil;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
}

function valid_tanggal($s) {
    $d = DateTime::createFromFormat('Y-m-d', (string) $s);
    return $d && $d->format('Y-m-d') === $s;
}

function valid_hp($s) { return (bool) preg_match('/^[0-9+\- ]{9,16}$/', (string) $s); }

/** Tanggal + n bulan, tetap di hari yang sama (dipotong ke akhir bulan kalau perlu: 31 Jan -> 28 Feb). */
function tambah_bulan($tgl, $n) {
    $d = new DateTime($tgl);
    $hari = (int) $d->format('j');
    $d->modify('first day of this month')->modify(($n >= 0 ? '+' : '') . $n . ' month');
    $d->setDate((int) $d->format('Y'), (int) $d->format('n'), min($hari, (int) $d->format('t')));
    return $d->format('Y-m-d');
}

/** Kamar berdasarkan nomor (terkunci untuk update di dalam transaksi). */
function kamar_row($nomor, $kunci = false) {
    $r = db_one('SELECT id, nomor_kamar, tipe::text AS tipe, ukuran, harga, status::text AS status, userid
                 FROM kamar WHERE nomor_kamar = ?' . ($kunci ? ' FOR UPDATE' : ''), [$nomor]);
    if ($r) $r['status'] = norm_status($r['status']);
    return $r;
}

function set_status_kamar($kamarId, $status, $userid = false) {
    $v = db_enum_value('kamar', 'status', $status);
    if ($userid === false) db_exec('UPDATE kamar SET status = ? WHERE id = ?', [$v, $kamarId]);
    else                   db_exec('UPDATE kamar SET status = ?, userid = ? WHERE id = ?', [$v, $userid, $kamarId]);
}

/* =====================================================================
   PEMELIHARAAN OTOMATIS (dipanggil tiap kali admin / penghuni membuka halaman)
   ===================================================================== */

/** Tagihan lewat jatuh tempo -> 'terlambat'; buat tagihan bulan berjalan untuk semua penghuni aktif. */
function sinkron_tagihan() {
    $today = date('Y-m-d');

    foreach (db_rows('SELECT id, status::text AS s FROM pembayaran WHERE jatuh_tempo < CURRENT_DATE') as $r) {
        if (norm_status($r['s']) === 'belum') {
            db_exec('UPDATE pembayaran SET status = ? WHERE id = ?', [db_enum_value('pembayaran', 'status', 'terlambat'), $r['id']]);
        }
    }

    $rows = db_rows('SELECT u.id AS uid, k.id AS kid, k.harga, COALESCE(u.tanggal_masuk, CURRENT_DATE) AS masuk
                     FROM kamar k JOIN "user" u ON u.id = k.userid WHERE COALESCE(u.aktif, TRUE)');
    foreach ($rows as $r) {
        // tagihan terbaru yang jatuh pada tanggal <= hari ini (siklus bulanan mengikuti tanggal masuk)
        $n = 0;
        if ($r['masuk'] > $today) continue;               // belum masuk, tagihan pertama dibuat saat penghuni ditambahkan
        while (tambah_bulan($r['masuk'], $n + 1) <= $today) $n++;
        $due = tambah_bulan($r['masuk'], $n);
        db_exec('INSERT INTO pembayaran (userid, kamarid, tanggal_tagihan, jatuh_tempo, jumlah, status)
                 SELECT ?, ?, ?::date, (?::date + 7), ?, ?
                 WHERE NOT EXISTS (SELECT 1 FROM pembayaran WHERE userid = ? AND tanggal_tagihan = ?::date)',
            [$r['uid'], $r['kid'], $due, $due, $r['harga'], db_enum_value('pembayaran', 'status', 'belum'), $r['uid'], $due]);
    }
}

/** Booking yang lewat batas waktu -> kedaluwarsa, kamar yang ditahan dilepas. */
function kedaluwarsakan_booking() {
    foreach (db_rows('SELECT id, kamarid, status::text AS s FROM booking WHERE batas_waktu < CURRENT_DATE') as $b) {
        $st = norm_status($b['s']);
        if ($st !== 'menunggu' && $st !== 'dikonfirmasi') continue;
        db_exec('UPDATE booking SET status = ? WHERE id = ?', [db_enum_value('booking', 'status', 'kedaluwarsa'), $b['id']]);
        if ($st === 'dikonfirmasi') {
            $k = db_one('SELECT status::text AS s, userid FROM kamar WHERE id = ?', [$b['kamarid']]);
            if ($k && norm_status($k['s']) === 'booking' && !$k['userid']) set_status_kamar($b['kamarid'], 'kosong');
        }
    }
}

/* =====================================================================
   FILE UPLOAD (foto bukti bayar & foto pengaduan)
   ===================================================================== */
function simpan_upload($file, $prefix) {
    if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return null;
    if ($file['error'] !== UPLOAD_ERR_OK) gagal('Upload gagal. Coba lagi dengan file yang lebih kecil.');
    if ($file['size'] > 5 * 1024 * 1024) gagal('Ukuran foto maksimal 5 MB.');
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    $ext = ['image/jpeg' => 'jpg', 'image/png' => 'png'][$mime] ?? null;
    if (!$ext) gagal('Foto harus berformat JPG atau PNG.');
    $dir = ROOT_PATH . '/uploads';
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    $nama = $prefix . '-' . bin2hex(random_bytes(8)) . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $nama)) gagal('Foto tidak dapat disimpan.');
    return $nama;
}

/* =====================================================================
   AKUN
   ===================================================================== */
function ganti_password($username, $lama, $baru) {
    if (strlen($baru) < 8) gagal('Kata sandi baru minimal 8 karakter.');
    $r = db_one('SELECT id, password, COALESCE(aktif, TRUE) AS aktif FROM "user" WHERE lower(username) = lower(?)', [$username]);
    $stored = (string) ($r['password'] ?? '');
    $hashed = (password_get_info($stored)['algo'] ?? null) !== null;
    $ok = $r && $r['aktif'] && ($hashed ? password_verify($lama, $stored) : hash_equals($stored, $lama));
    if (!$ok) gagal('Username atau kata sandi lama salah.');
    db_exec('UPDATE "user" SET password = ? WHERE id = ?', [password_hash($baru, PASSWORD_DEFAULT), $r['id']]);
}

/* =====================================================================
   PENGELOLA: PENGHUNI
   ===================================================================== */
function tambah_penghuni($nama, $hp, $username, $password, $nomor, $tgl) {
    $nama = trim($nama); $username = trim($username); $hp = trim($hp);
    if ($nama === '') gagal('Nama wajib diisi.');
    if (!valid_hp($hp)) gagal('Nomor HP tidak valid.');
    if (!preg_match('/^[A-Za-z0-9_.]{4,30}$/', $username)) gagal('Username 4-30 karakter: huruf, angka, titik atau garis bawah.');
    if (strlen($password) < 8) gagal('Password awal minimal 8 karakter.');
    if (!valid_tanggal($tgl)) gagal('Tanggal masuk tidak valid.');

    return dalam_transaksi(function () use ($nama, $hp, $username, $password, $nomor, $tgl) {
        if (db_one('SELECT 1 FROM "user" WHERE lower(username) = lower(?)', [$username])) gagal('Username sudah dipakai, pilih yang lain.');
        $k = kamar_row($nomor, true);
        if (!$k) gagal('Kamar tidak ditemukan.');
        if ($k['userid'] || !in_array($k['status'], ['kosong', 'booking'], true)) gagal('Kamar ' . $nomor . ' tidak tersedia.');

        $u = db_one('INSERT INTO "user" (role, username, password, nama, no_hp, tanggal_masuk, aktif)
                     VALUES (?, ?, ?, ?, ?, ?, TRUE) RETURNING id',
            [db_enum_value('user', 'role', 'penghuni'), $username, password_hash($password, PASSWORD_DEFAULT), $nama, $hp, $tgl]);
        set_status_kamar($k['id'], 'terisi', $u['id']);

        // tagihan pertama = tanggal masuk, jatuh tempo 7 hari kemudian
        db_exec('INSERT INTO pembayaran (userid, kamarid, tanggal_tagihan, jatuh_tempo, jumlah, status) VALUES (?, ?, ?, ?::date + 7, ?, ?)',
            [$u['id'], $k['id'], $tgl, $tgl, $k['harga'], db_enum_value('pembayaran', 'status', 'belum')]);
        return (int) $u['id'];
    });
}

function keluarkan_penghuni($nomor, $tgl, $kondisi) {
    if (!valid_tanggal($tgl)) gagal('Tanggal keluar tidak valid.');
    if (!in_array($kondisi, ['baik', 'perbaikan'], true)) gagal('Kondisi kamar tidak valid.');
    dalam_transaksi(function () use ($nomor, $tgl, $kondisi) {
        $k = kamar_row($nomor, true);
        if (!$k || !$k['userid']) gagal('Kamar ini tidak sedang dihuni.');
        db_exec('UPDATE "user" SET aktif = FALSE, tanggal_keluar = ? WHERE id = ?', [$tgl, $k['userid']]);
        db_exec('UPDATE pindah_kamar SET status = ?, alasan_tolak = ? WHERE userid = ? AND status::text = ?',
            [db_enum_value('pindah_kamar', 'status', 'ditolak'), 'Penghuni sudah keluar', $k['userid'], db_enum_value('pindah_kamar', 'status', 'menunggu')]);
        set_status_kamar($k['id'], $kondisi === 'baik' ? 'kosong' : 'diperbaiki', null);
    });
}

/* =====================================================================
   PENGELOLA: KAMAR
   ===================================================================== */
function ubah_kamar($nomor, $tipe, $ukuran, $status, $harga, array $fasilitas) {
    $ukuran = trim($ukuran);
    if (!in_array($tipe, ['Standar', 'Premium'], true)) gagal('Tipe kamar tidak valid.');
    if (!in_array($status, ['kosong', 'terisi', 'booking', 'diperbaiki'], true)) gagal('Status kamar tidak valid.');
    if ($ukuran === '') gagal('Ukuran wajib diisi.');
    if (!is_numeric($harga) || $harga < 100000) gagal('Harga tidak valid.');

    dalam_transaksi(function () use ($nomor, $tipe, $ukuran, $status, $harga, $fasilitas) {
        $k = kamar_row($nomor, true);
        if (!$k) gagal('Kamar tidak ditemukan.');
        if ($k['userid'] && $status !== 'terisi') gagal('Kamar masih dihuni. Gunakan "Keluarkan" di halaman Penghuni dulu.');
        if (!$k['userid'] && $status === 'terisi') gagal('Untuk mengisi kamar, gunakan "Tambah Penghuni" di halaman Penghuni.');

        db_exec('UPDATE kamar SET tipe = ?, ukuran = ?, harga = ?, status = ? WHERE id = ?',
            [db_enum_value('kamar', 'tipe', $tipe), $ukuran, (int) $harga, db_enum_value('kamar', 'status', $status), $k['id']]);

        // sinkron fasilitas kamar (hanya yang kategori private)
        $dipilih = [];
        foreach ($fasilitas as $nama) {
            $nama = trim((string) $nama);
            if ($nama === '') continue;
            $f = db_one("SELECT id FROM fasilitas WHERE nama = ? AND kategori::text = 'private' LIMIT 1", [$nama]);
            if (!$f) $f = db_one('INSERT INTO fasilitas (nama, kategori) VALUES (?, ?) RETURNING id', [$nama, db_enum_value('fasilitas', 'kategori', 'private')]);
            $dipilih[] = (int) $f['id'];
        }
        $ada = db_rows("SELECT kf.id, kf.fasilitasid FROM kamar_fasilitas kf JOIN fasilitas f ON f.id = kf.fasilitasid
                        WHERE kf.kamarid = ? AND f.kategori::text = 'private'", [$k['id']]);
        $adaIds = [];
        foreach ($ada as $row) {
            $adaIds[] = (int) $row['fasilitasid'];
            if (!in_array((int) $row['fasilitasid'], $dipilih, true)) {
                db_exec('UPDATE pengaduan SET kamar_fasilitas_id = NULL WHERE kamar_fasilitas_id = ?', [$row['id']]);
                db_exec('DELETE FROM kamar_fasilitas WHERE id = ?', [$row['id']]);
            }
        }
        foreach ($dipilih as $fid) {
            if (!in_array($fid, $adaIds, true)) db_exec('INSERT INTO kamar_fasilitas (kamarid, fasilitasid, status) VALUES (?, ?, ?)', [$k['id'], $fid, db_enum_value('kamar_fasilitas', 'status', 'normal')]);
        }
    });
}

/* =====================================================================
   PENGELOLA: PEMBAYARAN, PENGADUAN, BOOKING, KUNJUNGAN
   ===================================================================== */
function verifikasi_bayar($id, $status) {
    if (!in_array($status, ['lunas', 'ditolak'], true)) gagal('Status tidak valid.');
    $p = db_one('SELECT status::text AS s FROM pembayaran WHERE id = ?', [(int) $id]);
    if (!$p) gagal('Tagihan tidak ditemukan.');
    if (norm_status($p['s']) !== 'menunggu') gagal('Tagihan ini tidak sedang menunggu verifikasi.');
    db_exec('UPDATE pembayaran SET status = ? WHERE id = ?', [db_enum_value('pembayaran', 'status', $status), (int) $id]);
}

function ubah_aduan($id, $status, $catatan) {
    if (!in_array($status, ['diajukan', 'diproses', 'selesai'], true)) gagal('Status tidak valid.');
    if (!db_one('SELECT 1 FROM pengaduan WHERE id = ?', [(int) $id])) gagal('Pengaduan tidak ditemukan.');
    db_exec('UPDATE pengaduan SET status = ?, tanggal_perbaikan = ?, catatan = COALESCE(NULLIF(?, \'\'), catatan) WHERE id = ?',
        [db_enum_value('pengaduan', 'status', $status), $status === 'selesai' ? date('Y-m-d') : null, trim((string) $catatan), (int) $id]);
}

function ubah_booking($id, $status) {
    if (!in_array($status, ['dikonfirmasi', 'ditolak'], true)) gagal('Status tidak valid.');
    dalam_transaksi(function () use ($id, $status) {
        $b = db_one('SELECT id, kamarid, tgl_masuk, status::text AS s FROM booking WHERE id = ? FOR UPDATE', [(int) $id]);
        if (!$b) gagal('Booking tidak ditemukan.');
        if (norm_status($b['s']) !== 'menunggu') gagal('Booking ini sudah diproses.');
        if ($status === 'dikonfirmasi') {
            $k = db_one('SELECT status::text AS s, userid FROM kamar WHERE id = ? FOR UPDATE', [$b['kamarid']]);
            if (!$k || $k['userid'] || norm_status($k['s']) !== 'kosong') gagal('Kamar sudah tidak kosong, booking tidak bisa dikonfirmasi.');
            set_status_kamar($b['kamarid'], 'booking');
            $batas = max(date('Y-m-d', strtotime('+3 days')), (string) $b['tgl_masuk']);
            db_exec('UPDATE booking SET status = ?, batas_waktu = ? WHERE id = ?', [db_enum_value('booking', 'status', 'dikonfirmasi'), $batas, $b['id']]);
        } else {
            db_exec('UPDATE booking SET status = ? WHERE id = ?', [db_enum_value('booking', 'status', 'ditolak'), $b['id']]);
        }
    });
}

function ubah_kunjungan($id, $status) {
    $alur = ['menunggu' => ['dikonfirmasi', 'dibatalkan'], 'dikonfirmasi' => ['selesai', 'dibatalkan']];
    $r = db_one('SELECT status::text AS s FROM kunjungan WHERE id_kunjungan = ?', [(int) $id]);
    if (!$r) gagal('Data tidak ditemukan.');
    $sekarang = norm_status($r['s']);
    if (!in_array($status, $alur[$sekarang] ?? [], true)) gagal('Status tidak bisa diubah dari ' . $sekarang . ' ke ' . $status . '.');
    db_exec('UPDATE kunjungan SET status = ? WHERE id_kunjungan = ?', [db_enum_value('kunjungan', 'status', $status), (int) $id]);
}

/* =====================================================================
   PENGELOLA: PINDAH KAMAR
   ===================================================================== */
function proses_pindah($id) {
    dalam_transaksi(function () use ($id) {
        $m = db_one('SELECT id, userid, kamar_asal, kamar_tujuan, status::text AS s FROM pindah_kamar WHERE id = ? FOR UPDATE', [(int) $id]);
        if (!$m) gagal('Pengajuan tidak ditemukan.');
        if (norm_status($m['s']) !== 'menunggu') gagal('Pengajuan ini sudah diproses.');
        $tujuan = db_one('SELECT id, nomor_kamar, harga, status::text AS s, userid FROM kamar WHERE id = ? FOR UPDATE', [$m['kamar_tujuan']]);
        if (!$tujuan || $tujuan['userid'] || norm_status($tujuan['s']) !== 'kosong') gagal('Kamar tujuan sudah tidak kosong. Pengajuan tidak bisa diproses.');
        $asal = db_one('SELECT id, userid FROM kamar WHERE id = ? FOR UPDATE', [$m['kamar_asal']]);
        if (!$asal || (int) $asal['userid'] !== (int) $m['userid']) gagal('Penghuni sudah tidak berada di kamar asal.');

        set_status_kamar($m['kamar_asal'], 'kosong', null);
        set_status_kamar($m['kamar_tujuan'], 'terisi', $m['userid']);
        // tagihan yang belum dibayar mengikuti harga kamar baru
        db_exec('UPDATE pembayaran SET kamarid = ?, jumlah = ? WHERE userid = ? AND status::text = ? AND tanggal_tagihan > CURRENT_DATE',
            [$m['kamar_tujuan'], $tujuan['harga'], $m['userid'], db_enum_value('pembayaran', 'status', 'belum')]);
        db_exec('UPDATE pindah_kamar SET status = ? WHERE id = ?', [db_enum_value('pindah_kamar', 'status', 'selesai'), $m['id']]);
    });
}

function tolak_pindah($id, $alasan) {
    $alasan = trim((string) $alasan);
    if ($alasan === '') gagal('Alasan penolakan wajib diisi.');
    $m = db_one('SELECT status::text AS s FROM pindah_kamar WHERE id = ?', [(int) $id]);
    if (!$m) gagal('Pengajuan tidak ditemukan.');
    if (norm_status($m['s']) !== 'menunggu') gagal('Pengajuan ini sudah diproses.');
    db_exec('UPDATE pindah_kamar SET status = ?, alasan_tolak = ? WHERE id = ?', [db_enum_value('pindah_kamar', 'status', 'ditolak'), $alasan, (int) $id]);
}

/* =====================================================================
   PENGHUNI
   ===================================================================== */
function ajukan_pindah($userId, $tujuanNomor, $tgl, $alasan) {
    $alasan = trim((string) $alasan);
    if ($alasan === '') gagal('Alasan pindah wajib diisi.');
    if (!valid_tanggal($tgl) || $tgl < date('Y-m-d')) gagal('Tanggal pindah tidak valid.');
    $asal = db_one('SELECT id FROM kamar WHERE userid = ?', [$userId]);
    if (!$asal) gagal('Anda belum menempati kamar.');
    $tujuan = kamar_row($tujuanNomor);
    if (!$tujuan || $tujuan['userid'] || $tujuan['status'] !== 'kosong') gagal('Kamar tujuan tidak kosong.');
    if (db_one('SELECT 1 FROM pindah_kamar WHERE userid = ? AND status::text = ?', [$userId, db_enum_value('pindah_kamar', 'status', 'menunggu')]))
        gagal('Anda masih punya pengajuan pindah yang belum diproses.');
    foreach (db_rows('SELECT status::text AS s FROM pembayaran WHERE userid = ? AND tanggal_tagihan <= CURRENT_DATE', [$userId]) as $p) {
        if (norm_status($p['s']) !== 'lunas') gagal('Tagihan bulan berjalan harus lunas sebelum mengajukan pindah.');
    }
    db_exec('INSERT INTO pindah_kamar (userid, kamar_asal, kamar_tujuan, tanggal, alasan, status) VALUES (?, ?, ?, ?, ?, ?)',
        [$userId, $asal['id'], $tujuan['id'], $tgl, $alasan, db_enum_value('pindah_kamar', 'status', 'menunggu')]);
}

function kirim_bukti($userId, $pembayaranId, $file) {
    $p = db_one('SELECT id, status::text AS s FROM pembayaran WHERE id = ? AND userid = ?', [(int) $pembayaranId, $userId]);
    if (!$p) gagal('Tagihan tidak ditemukan.');
    if (!in_array(norm_status($p['s']), ['belum', 'terlambat', 'ditolak'], true)) gagal('Tagihan ini tidak bisa diberi bukti lagi.');
    $nama = simpan_upload($file, 'bukti');
    if (!$nama) gagal('Pilih foto bukti pembayaran dulu.');
    db_exec('UPDATE pembayaran SET status = ?, bukti_pembayaran = ?, tanggal_bayar = NOW() WHERE id = ?',
        [db_enum_value('pembayaran', 'status', 'menunggu'), $nama, $p['id']]);
    return $nama;
}

function buat_aduan($userId, $deskripsi, $file) {
    $deskripsi = trim((string) $deskripsi);
    if (mb_strlen($deskripsi) < 10) gagal('Deskripsi minimal 10 karakter.');
    $k = db_one('SELECT id FROM kamar WHERE userid = ?', [$userId]);
    if (!$k) gagal('Anda belum menempati kamar.');
    $foto = simpan_upload($file, 'aduan');
    db_exec('INSERT INTO pengaduan (userid, kamarid, deskripsi, foto, tanggal_pengaduan, status) VALUES (?, ?, ?, ?, CURRENT_DATE, ?)',
        [$userId, $k['id'], $deskripsi, $foto, db_enum_value('pengaduan', 'status', 'diajukan')]);
}

/* =====================================================================
   PENGUNJUNG (tanpa login)
   ===================================================================== */
function buat_booking($nomor, $nama, $wa, $tglMasuk) {
    $nama = trim((string) $nama); $wa = trim((string) $wa);
    if ($nama === '' || !valid_hp($wa) || !valid_tanggal($tglMasuk) || $tglMasuk < date('Y-m-d')) gagal('Data booking belum lengkap atau tidak valid.');
    return dalam_transaksi(function () use ($nomor, $nama, $wa, $tglMasuk) {
        $k = kamar_row($nomor, true);
        if (!$k || $k['userid'] || $k['status'] !== 'kosong') gagal('Kamar ini sudah tidak tersedia.');
        $ada = db_one('SELECT id FROM booking WHERE kamarid = ? AND no_hp = ? AND status::text = ?', [$k['id'], $wa, db_enum_value('booking', 'status', 'menunggu')]);
        if ($ada) return (int) $ada['id'];                // jangan buat pengajuan ganda
        $b = db_one('INSERT INTO booking (calon_penghuni, kamarid, tgl_booking, batas_waktu, status, no_hp, tgl_masuk)
                     VALUES (?, ?, CURRENT_DATE, CURRENT_DATE + 3, ?, ?, ?) RETURNING id',
            [$nama, $k['id'], db_enum_value('booking', 'status', 'menunggu'), $wa, $tglMasuk]);
        return (int) $b['id'];
    });
}

function buat_kunjungan($jenis, $nama, $wa, $tgl, $jam, $kamar, $catatan) {
    $nama = trim((string) $nama); $wa = trim((string) $wa);
    if (!in_array($jenis, ['kunjungan', 'survey'], true)) gagal('Jenis jadwal tidak valid.');
    if ($nama === '' || !valid_hp($wa) || !valid_tanggal($tgl)) gagal('Data wajib belum lengkap atau tidak valid.');
    if ($tgl < date('Y-m-d')) gagal('Tanggal tidak boleh di masa lalu.');
    if ((int) date('w', strtotime($tgl)) === 0) gagal('Kunjungan hanya Senin–Sabtu.');
    $kamar = trim((string) $kamar);
    if ($kamar !== '' && !kamar_row($kamar)) gagal('Kamar tidak ditemukan.');
    $jam = preg_match('/^\d{2}:\d{2}$/', (string) $jam) ? $jam : null;
    $r = db_one('INSERT INTO kunjungan (nama, tanggal, status, jenis, no_hp, kamar, jam, catatan) VALUES (?, ?, ?, ?, ?, ?, ?, ?) RETURNING id_kunjungan',
        [$nama, $tgl, db_enum_value('kunjungan', 'status', 'menunggu'), $jenis, $wa, $kamar ?: null, $jam, trim((string) $catatan) ?: null]);
    return (int) $r['id_kunjungan'];
}
