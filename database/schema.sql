-- =====================================================================
-- KostKu - skema database (PostgreSQL)
-- Aman dijalankan berulang kali: tabel yang sudah ada tidak diubah,
-- hanya kolom/tabel yang belum ada yang ditambahkan.
-- Jalankan lewat:  php database/setup.php
-- =====================================================================

CREATE TABLE IF NOT EXISTS "user" (
    id        SERIAL PRIMARY KEY,
    role      VARCHAR(20)  NOT NULL DEFAULT 'penghuni',   -- 'admin' (pengelola) | 'penghuni'
    username  VARCHAR(50)  NOT NULL,
    password  VARCHAR(255) NOT NULL,                      -- hasil password_hash()
    nama      VARCHAR(100) NOT NULL
);
ALTER TABLE "user" ADD COLUMN IF NOT EXISTS no_hp          VARCHAR(20);
ALTER TABLE "user" ADD COLUMN IF NOT EXISTS aktif          BOOLEAN NOT NULL DEFAULT TRUE;
ALTER TABLE "user" ADD COLUMN IF NOT EXISTS tanggal_masuk  DATE;
ALTER TABLE "user" ADD COLUMN IF NOT EXISTS tanggal_keluar DATE;

CREATE TABLE IF NOT EXISTS kamar (
    id          SERIAL PRIMARY KEY,
    nomor_kamar VARCHAR(10)  NOT NULL UNIQUE,
    tipe        VARCHAR(30)  NOT NULL DEFAULT 'Standar',
    ukuran      VARCHAR(30),
    harga       NUMERIC(12,0) NOT NULL DEFAULT 600000,
    status      VARCHAR(20)  NOT NULL DEFAULT 'kosong',   -- kosong | terisi | booking | diperbaiki
    foto        VARCHAR(255),
    userid      INTEGER REFERENCES "user"(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS fasilitas (
    id       SERIAL PRIMARY KEY,
    nama     VARCHAR(100) NOT NULL,
    kategori VARCHAR(20)  NOT NULL DEFAULT 'private'      -- private (dalam kamar) | public
);

CREATE TABLE IF NOT EXISTS kamar_fasilitas (
    id          SERIAL PRIMARY KEY,
    kamarid     INTEGER NOT NULL REFERENCES kamar(id) ON DELETE CASCADE,
    fasilitasid INTEGER NOT NULL REFERENCES fasilitas(id) ON DELETE CASCADE
);
ALTER TABLE kamar_fasilitas ADD COLUMN IF NOT EXISTS status VARCHAR(20) NOT NULL DEFAULT 'normal'; -- normal | diperbaiki

CREATE TABLE IF NOT EXISTS pembayaran (
    id                SERIAL PRIMARY KEY,
    userid            INTEGER NOT NULL REFERENCES "user"(id),
    kamarid           INTEGER NOT NULL REFERENCES kamar(id),
    tanggal_tagihan   DATE    NOT NULL,
    jatuh_tempo       DATE    NOT NULL,
    jumlah            NUMERIC(12,0) NOT NULL,
    status            VARCHAR(20) NOT NULL DEFAULT 'belum', -- belum | menunggu | lunas | terlambat | ditolak
    bukti_pembayaran  VARCHAR(255)
);
ALTER TABLE pembayaran ADD COLUMN IF NOT EXISTS tanggal_bayar TIMESTAMP;

CREATE TABLE IF NOT EXISTS pengaduan (
    id                  SERIAL PRIMARY KEY,
    userid              INTEGER NOT NULL REFERENCES "user"(id),
    kamar_fasilitas_id  INTEGER REFERENCES kamar_fasilitas(id) ON DELETE SET NULL,
    deskripsi           TEXT NOT NULL,
    foto                VARCHAR(255),
    tanggal_pengaduan   DATE NOT NULL DEFAULT CURRENT_DATE,
    tanggal_perbaikan   DATE,
    status              VARCHAR(20) NOT NULL DEFAULT 'diajukan' -- diajukan | diproses | selesai
);
ALTER TABLE pengaduan ADD COLUMN IF NOT EXISTS catatan TEXT;
ALTER TABLE pengaduan ADD COLUMN IF NOT EXISTS kamarid INTEGER REFERENCES kamar(id) ON DELETE SET NULL;

CREATE TABLE IF NOT EXISTS booking (
    id              SERIAL PRIMARY KEY,
    calon_penghuni  VARCHAR(100) NOT NULL,
    kamarid         INTEGER NOT NULL REFERENCES kamar(id),
    tgl_booking     DATE NOT NULL DEFAULT CURRENT_DATE,
    batas_waktu     DATE NOT NULL,
    status          VARCHAR(20) NOT NULL DEFAULT 'menunggu' -- menunggu | dikonfirmasi | kedaluwarsa | ditolak
);
ALTER TABLE booking ADD COLUMN IF NOT EXISTS no_hp     VARCHAR(20);
ALTER TABLE booking ADD COLUMN IF NOT EXISTS tgl_masuk DATE;

CREATE TABLE IF NOT EXISTS kunjungan (
    id_kunjungan SERIAL PRIMARY KEY,
    nama         VARCHAR(100) NOT NULL,
    tanggal      DATE NOT NULL,
    status       VARCHAR(20) NOT NULL DEFAULT 'menunggu'  -- menunggu | dikonfirmasi | selesai | dibatalkan
);
ALTER TABLE kunjungan ADD COLUMN IF NOT EXISTS jenis   VARCHAR(20) NOT NULL DEFAULT 'kunjungan'; -- kunjungan | survey
ALTER TABLE kunjungan ADD COLUMN IF NOT EXISTS no_hp   VARCHAR(20);
ALTER TABLE kunjungan ADD COLUMN IF NOT EXISTS kamar   VARCHAR(10);
ALTER TABLE kunjungan ADD COLUMN IF NOT EXISTS jam     VARCHAR(10);
ALTER TABLE kunjungan ADD COLUMN IF NOT EXISTS catatan TEXT;

CREATE TABLE IF NOT EXISTS pindah_kamar (
    id           SERIAL PRIMARY KEY,
    userid       INTEGER NOT NULL REFERENCES "user"(id),
    kamar_asal   INTEGER NOT NULL REFERENCES kamar(id),
    kamar_tujuan INTEGER NOT NULL REFERENCES kamar(id),
    tanggal      DATE NOT NULL,
    alasan       TEXT,
    status       VARCHAR(20) NOT NULL DEFAULT 'menunggu', -- menunggu | selesai | ditolak
    alasan_tolak TEXT,
    dibuat       TIMESTAMP NOT NULL DEFAULT NOW()
);

-- ---------------------------------------------------------------------
-- Pengaman data
-- ---------------------------------------------------------------------
DO $$ BEGIN
    ALTER TABLE pengaduan ALTER COLUMN kamar_fasilitas_id DROP NOT NULL;
EXCEPTION WHEN others THEN NULL; END $$;

-- username unik (tanpa membedakan huruf besar/kecil)
DO $$ BEGIN
    CREATE UNIQUE INDEX IF NOT EXISTS uq_user_username ON "user" (lower(username));
EXCEPTION WHEN others THEN NULL; END $$;

-- Hanya boleh ada SATU akun pengelola
DO $$ BEGIN
    CREATE UNIQUE INDEX IF NOT EXISTS uq_satu_pengelola ON "user" (role) WHERE role = 'admin';
EXCEPTION WHEN others THEN NULL; END $$;
DO $$ BEGIN   -- bila enum role kamu memakai label 'pengelola'
    CREATE UNIQUE INDEX IF NOT EXISTS uq_satu_pengelola2 ON "user" (role) WHERE role = 'pengelola';
EXCEPTION WHEN others THEN NULL; END $$;

-- satu kamar hanya punya satu penghuni, satu tagihan per penghuni per tanggal
DO $$ BEGIN
    CREATE UNIQUE INDEX IF NOT EXISTS uq_kamar_userid ON kamar (userid) WHERE userid IS NOT NULL;
EXCEPTION WHEN others THEN NULL; END $$;
DO $$ BEGIN
    CREATE UNIQUE INDEX IF NOT EXISTS uq_tagihan_periode ON pembayaran (userid, tanggal_tagihan);
EXCEPTION WHEN others THEN NULL; END $$;
