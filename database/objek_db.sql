-- =====================================================================
-- KostKu - objek database: VIEW dan FUNCTION
-- Aman dijalankan berulang kali (dipanggil otomatis oleh database/setup.php).
--
-- Semua objek di bawah DIPAKAI langsung oleh aplikasi (lihat data/data.php & data/aksi.php).
-- Perbandingan status memakai status::text agar jalan di kolom ENUM maupun VARCHAR,
-- dan daftar alias (mis. 'belum' / 'belumdibayar') mengikuti norm_status() di PHP.
-- =====================================================================

-- ---------------------------------------------------------------------
-- VIEW 1: v_pembayaran_detail  (JOIN pembayaran + user + kamar)
-- Dipakai: admin/pembayaran.php & admin/dashboard.php  -> get_pembayaran_admin()
-- Alasan : gabungan 3 tabel ini dibutuhkan setiap kali pengelola melihat tagihan.
--          Dengan view, join ditulis sekali dan PHP cukup menyaring + mengurutkan.
-- ---------------------------------------------------------------------
DROP VIEW IF EXISTS v_pembayaran_detail;
CREATE VIEW v_pembayaran_detail AS
SELECT p.id,
       p.userid,
       p.kamarid,
       u.nama,
       k.nomor_kamar,
       p.tanggal_tagihan,
       p.jatuh_tempo,
       p.jumlah,
       p.status::text AS status,
       p.bukti_pembayaran,
       p.tanggal_bayar
FROM pembayaran p
JOIN "user" u ON u.id = p.userid
JOIN kamar  k ON k.id = p.kamarid;

-- ---------------------------------------------------------------------
-- VIEW 2: v_pengaduan_detail  (LEFT JOIN 4 tabel)
-- Dipakai: admin/pengaduan.php & admin/dashboard.php  -> get_pengaduan_admin()
-- Alasan : nomor kamar sebuah pengaduan bisa berasal dari 3 jalur
--          (kolom kamarid, lewat fasilitas kamar, atau kamar yang dihuni pelapor).
--          Aturan prioritas COALESCE itu cukup disimpan di satu tempat.
-- ---------------------------------------------------------------------
DROP VIEW IF EXISTS v_pengaduan_detail;
CREATE VIEW v_pengaduan_detail AS
SELECT a.id,
       a.userid,
       COALESCE(kb.nomor_kamar, kfk.nomor_kamar, ku.nomor_kamar, '-') AS nomor_kamar,
       a.deskripsi,
       a.foto,
       a.tanggal_pengaduan,
       a.status::text AS status
FROM pengaduan a
LEFT JOIN kamar kb            ON kb.id  = a.kamarid
LEFT JOIN kamar_fasilitas kf  ON kf.id  = a.kamar_fasilitas_id
LEFT JOIN kamar kfk           ON kfk.id = kf.kamarid
LEFT JOIN kamar ku            ON ku.userid = a.userid;

-- ---------------------------------------------------------------------
-- VIEW 3: v_statistik_kamar  (AGREGASI)
-- Dipakai: admin/dashboard.php (kartu Total Kamar & Tingkat Hunian) -> get_statistik_kamar()
-- Alasan : sebelumnya seluruh kamar + fasilitasnya ditarik ke PHP hanya untuk dihitung.
--          Sekarang database mengembalikan satu baris ringkasan.
-- ---------------------------------------------------------------------
DROP VIEW IF EXISTS v_statistik_kamar;
CREATE VIEW v_statistik_kamar AS
SELECT COUNT(*)                                                                     AS total,
       COUNT(*) FILTER (WHERE lower(status::text) = 'terisi')                       AS terisi,
       COUNT(*) FILTER (WHERE lower(status::text) IN ('kosong', 'tersedia', 'available')) AS kosong
FROM kamar;

-- ---------------------------------------------------------------------
-- FUNCTION 1: fn_sinkron_tagihan(hari, label_belum, label_terlambat)
-- Dipakai: require_role() di config/config.php -> sinkron_tagihan(), dipanggil di
--          SETIAP pemuatan halaman pengelola maupun penghuni.
-- Alasan : versi PHP lama memakai loop (1 SELECT + 1 UPDATE per tagihan + 1 INSERT per penghuni)
--          pada tiap request. Fungsi ini mengerjakan semuanya set-based dalam satu panggilan
--          dan satu transaksi, sehingga dua halaman yang dibuka bersamaan tidak membuat
--          tagihan ganda / setengah jadi.
--          Label status dikirim dari PHP (db_enum_value) agar cocok dengan label enum yang
--          sebenarnya ('belumdibayar', 'belum', ...).
-- Hasil  : jumlah tagihan yang berubah jadi terlambat & jumlah tagihan baru.
-- ---------------------------------------------------------------------
CREATE OR REPLACE FUNCTION fn_sinkron_tagihan(p_hari date, p_belum text, p_terlambat text)
RETURNS TABLE (tagihan_terlambat integer, tagihan_baru integer)
LANGUAGE plpgsql
AS $fn$
DECLARE
    v_terlambat integer;
    v_baru      integer;
BEGIN
    -- 1) tagihan yang belum dibayar & sudah lewat jatuh tempo -> terlambat
    EXECUTE format(
        $q$UPDATE pembayaran SET status = %L
           WHERE jatuh_tempo < $1
             AND lower(status::text) IN ('belum', 'belumdibayar', 'belum_dibayar', 'belum dibayar')$q$,
        p_terlambat) USING p_hari;
    GET DIAGNOSTICS v_terlambat = ROW_COUNT;

    -- 2) tagihan siklus berjalan untuk tiap penghuni aktif (siklus bulanan mengikuti tanggal masuk).
    --    CTE "penghuni_aktif" -> hitung selisih bulan; CTE "siklus" -> tanggal tagihan terbaru yang <= hari ini.
    --    Tanggal dihitung dari tanggal masuk (bukan akumulasi), jadi 31 Jan -> 28 Feb -> 31 Mar.
    EXECUTE format(
        $q$WITH penghuni_aktif AS (
               SELECT u.id AS uid, k.id AS kid, k.harga,
                      COALESCE(u.tanggal_masuk, $1) AS masuk
               FROM kamar k
               JOIN "user" u ON u.id = k.userid
               WHERE COALESCE(u.aktif, TRUE)
                 AND COALESCE(u.tanggal_masuk, $1) <= $1
           ),
           selisih AS (
               SELECT pa.*,
                      (EXTRACT(YEAR FROM $1)::int - EXTRACT(YEAR FROM masuk)::int) * 12
                    + (EXTRACT(MONTH FROM $1)::int - EXTRACT(MONTH FROM masuk)::int) AS n
               FROM penghuni_aktif pa
           ),
           siklus AS (
               SELECT s.uid, s.kid, s.harga,
                      CASE WHEN (s.masuk + make_interval(months => s.n))::date > $1
                           THEN (s.masuk + make_interval(months => s.n - 1))::date
                           ELSE (s.masuk + make_interval(months => s.n))::date
                      END AS tgl_tagihan
               FROM selisih s
           )
           INSERT INTO pembayaran (userid, kamarid, tanggal_tagihan, jatuh_tempo, jumlah, status)
           SELECT sk.uid, sk.kid, sk.tgl_tagihan, sk.tgl_tagihan + 7, sk.harga, %L
           FROM siklus sk
           WHERE NOT EXISTS (SELECT 1 FROM pembayaran p
                             WHERE p.userid = sk.uid AND p.tanggal_tagihan = sk.tgl_tagihan)$q$,
        p_belum) USING p_hari;
    GET DIAGNOSTICS v_baru = ROW_COUNT;

    RETURN QUERY SELECT v_terlambat, v_baru;
END;
$fn$;

-- ---------------------------------------------------------------------
-- FUNCTION 2: fn_kedaluwarsakan_booking(hari, label_kedaluwarsa, label_kosong)
-- Dipakai: require_role() -> kedaluwarsakan_booking(), tiap pemuatan halaman.
-- Alasan : dua perubahan harus terjadi bersama-sama: booking jadi kedaluwarsa DAN kamar
--          yang ditahan dilepas lagi. Di dalam satu fungsi keduanya atomik (tidak mungkin
--          booking sudah kedaluwarsa tapi kamar tetap tertahan), dan tidak ada loop per baris.
--          Pelepasan kamar memakai SUBQUERY pada booking yang masih 'dikonfirmasi'
--          dan harus dijalankan SEBELUM status booking diubah.
-- Hasil  : jumlah booking yang dikedaluwarsakan & jumlah kamar yang dilepas.
-- ---------------------------------------------------------------------
CREATE OR REPLACE FUNCTION fn_kedaluwarsakan_booking(p_hari date, p_kedaluwarsa text, p_kosong text)
RETURNS TABLE (booking_kedaluwarsa integer, kamar_dilepas integer)
LANGUAGE plpgsql
AS $fn$
DECLARE
    v_booking integer;
    v_kamar   integer;
BEGIN
    -- 1) lepas kamar yang ditahan booking dikonfirmasi yang sudah lewat batas (dan belum dihuni)
    EXECUTE format(
        $q$UPDATE kamar SET status = %L
           WHERE userid IS NULL
             AND lower(status::text) IN ('booking', 'dibooking', 'terbooking')
             AND id IN (SELECT b.kamarid FROM booking b
                        WHERE b.batas_waktu < $1
                          AND lower(b.status::text) IN ('dikonfirmasi', 'disetujui', 'ditahan'))$q$,
        p_kosong) USING p_hari;
    GET DIAGNOSTICS v_kamar = ROW_COUNT;

    -- 2) booking menunggu / dikonfirmasi yang lewat batas -> kedaluwarsa
    EXECUTE format(
        $q$UPDATE booking SET status = %L
           WHERE batas_waktu < $1
             AND lower(status::text) IN ('menunggu', 'dikonfirmasi', 'disetujui', 'ditahan')$q$,
        p_kedaluwarsa) USING p_hari;
    GET DIAGNOSTICS v_booking = ROW_COUNT;

    RETURN QUERY SELECT v_booking, v_kamar;
END;
$fn$;
