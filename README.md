# KostKu (PHP native + JS + CSS)

## Cara menjalankan
1. Taruh folder `kostku` di `htdocs` (XAMPP) lalu buka `http://localhost/kostku/guest/index.php`
   atau jalankan: `php -S localhost:8000` di dalam folder ini, lalu buka `http://localhost:8000/guest/index.php`
2. BASE_URL dihitung otomatis, tidak perlu diatur.

## Akun demo (sementara, sebelum database)
- Penghuni : `rina` / `rina12345`   (Kamar A-03)
- Pengelola: `admin` / `admin123`

## Struktur
- `header/`  : header.php (pemanggil), head.php, guest.php, admin.php, penghuni.php
- `footer/`  : footer.php (footer + toast + script)
- `components/` : icons, room-card, denah, modal-verifikasi, modal-aduan
- `assets/css`, `assets/js`, `assets/img`
- `guest/`, `admin/`, `penghuni/` : halaman per peran
- `data/data.php` : data dummy -> nanti diganti query database
- `config/config.php` : helper + session

## Cara memanggil header/footer di halaman baru
```php
require_once __DIR__ . '/../config/config.php';
$role = 'admin'; $pageTitle = 'Judul'; $pageId = 'admin-xxx'; $activeMenu = 'dashboard';
include ROOT_PATH . '/header/header.php';
// ... isi halaman ...
include ROOT_PATH . '/footer/footer.php';
```

## Data kamar (sesuai denah)
- Lantai 1 = parkir. Lantai 2 = A-01..A-11. Lantai 3 = B-01..B-07.
- Harga 600.000, kecuali B-03 dan B-07 = 750.000.

## Saat database dipasang
Cari komentar `TODO (database)` di `assets/js/*.js`, `guest/login.php`, `guest/booking-simpan.php`
dan fungsi `get_*()` di `data/data.php`.
