# Konsep SQL yang dipakai aplikasi KostKu

Semua objek di bawah dibuat oleh `database/objek_db.sql` (otomatis lewat `php database/setup.php`,
sudah ikut di `dump-db_pbl-*.sql`, dan dipasang sendiri oleh aplikasi bila function belum ada).
Tidak ada tabel/fitur yang dihapus; hasil tampilan tiap halaman tetap sama.

| Konsep | Di mana | Dipakai fitur | Alasan |
|---|---|---|---|
| **JOIN multi-tabel** | `get_kamar()` (kamar ⟕ user ⟕ kamar_fasilitas ⟕ fasilitas) · `v_pembayaran_detail` (pembayaran ⋈ user ⋈ kamar) · `v_pengaduan_detail` (pengaduan ⟕ 3× kamar ⟕ kamar_fasilitas) · `get_pindah_kamar()` | Denah & daftar kamar, Pembayaran admin, Pengaduan admin, Pindah kamar | Data tersebar di banyak tabel; sebelumnya kamar + fasilitas butuh 2 query lalu digabung di PHP |
| **GROUP BY** | `get_kamar()` (json_agg fasilitas per kamar) · `get_ringkasan_status()` (kartu Pengaduan & Booking) · CTE di `get_penghuni()` · `get_ringkasan_lunas_penghuni()` | Kartu statistik & daftar penghuni | Hitung di database, bukan menarik semua baris lalu `count(array_filter())` di PHP |
| **HAVING** | `ajukan_pindah()` (`aksi.php`) | Penghuni › Ajukan Pindah Kamar | Satu baris hanya muncul bila penghuni punya ≥ 1 tagihan belum lunas → syarat "tagihan harus lunas" |
| **Subquery** | `get_badge_admin()` (4 subquery skalar, 1 query) · `fn_kedaluwarsakan_booking()` (`id IN (SELECT …)`) · `fn_sinkron_tagihan()` (`NOT EXISTS`) | Badge menu admin (tiap halaman), pembersihan booking, pembuatan tagihan | Badge dimuat di setiap halaman → 1 round-trip, bukan 4 |
| **CTE** | `get_penghuni()` (3 CTE) · `fn_sinkron_tagihan()` (3 CTE + INSERT) | Daftar penghuni admin, tagihan bulanan otomatis | Menggantikan 3 subquery berkorelasi yang diulang per penghuni; CTE membuat logika siklus tagihan bisa dibaca bertahap |
| **VIEW** | `v_pembayaran_detail`, `v_pengaduan_detail`, `v_statistik_kamar` | Pembayaran, Pengaduan, Dashboard admin | Join/aturan COALESCE ditulis sekali, dipakai halaman lain; view dipilih (bukan materialized) karena data harus selalu real-time |
| **FUNCTION** | `fn_sinkron_tagihan()`, `fn_kedaluwarsakan_booking()` | Dipanggil `require_role()` di **setiap** halaman admin & penghuni | Dulu loop PHP (1 query per tagihan/penghuni/booking) di tiap request. Kini 1 panggilan set-based dan atomik: booking kedaluwarsa + kamar dilepas terjadi bersamaan, dan dua tab yang dibuka bersamaan tidak membuat tagihan ganda |

Catatan teknis: perbandingan status memakai `status::text` supaya jalan di kolom ENUM maupun VARCHAR;
label enum yang benar (mis. `belumdibayar`, `kadaluarsa`) dikirim dari PHP lewat `db_enum_value()`.
Kolom `kamar.foto2` (dipakai halaman Kamar admin) ikut ditambahkan ke `schema.sql` dan dump karena belum ada di dump lama.
