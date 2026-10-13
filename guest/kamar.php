<?php
require_once __DIR__ . '/../config/config.php';

$role = 'guest'; $pageTitle = 'Daftar Kamar'; $pageId = 'guest-kamar'; $activeMenu = 'kamar';
$semua = array_values(get_kamar());
$awal = $_GET['filter'] ?? 'all'; // ?filter=kosong dari tombol "Cari Kamar Kosong"

include ROOT_PATH . '/header/header.php';
?>
<main>
    <section class="page-head is-center">
        <div class="container">
            <h1>Pilihan Kamar KostKu</h1>
            <p>Bandingkan tipe, harga, dan ketersediaan kamar. Pilih yang paling cocok, lalu jadwalkan kunjungan.</p>
        </div>
    </section>

    <section class="page-body">
        <div class="container">
            <div class="list-toolbar">
                <div class="chips" data-filter-target="#room-list" data-filter-attr="status">
                    <button class="chip <?= $awal === 'all' ? 'active' : '' ?>" data-filter="all">Semua</button>
                    <button class="chip <?= $awal === 'kosong' ? 'active' : '' ?>" data-filter="kosong" data-attr="status">Tersedia</button>
                    <button class="chip" data-filter="Standar" data-attr="tipe">Standar</button>
                    <button class="chip" data-filter="Premium" data-attr="tipe">Premium</button>
                </div>
                <select class="select sort-select" id="sort-room" aria-label="Urutkan">
                    <option value="asc">Harga terendah</option>
                    <option value="desc">Harga tertinggi</option>
                    <option value="nomor">Nomor kamar</option>
                </select>
            </div>

            <div class="room-grid" id="room-list">
                <?php foreach ($semua as $k) { include ROOT_PATH . '/components/room-card.php'; } ?>
                <div class="empty-state hidden" data-empty>Belum ada kamar yang cocok dengan filter ini.</div>
            </div>
        </div>
    </section>
</main>
<?php include ROOT_PATH . '/footer/footer.php'; ?>
