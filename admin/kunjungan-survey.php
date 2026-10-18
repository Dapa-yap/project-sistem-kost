<?php

require_once __DIR__ . '/../config/config.php';

require_role('admin');

$role = 'admin';
$pageTitle = 'Kunjungan & Survey';
$pageId = 'admin-kunjungan-survey';
$activeMenu = 'jadwal';


/*
 * =========================================================
 * DATA KUNJUNGAN
 * =========================================================
 */

$kunjungan = get_kunjungan();


/*
 * =========================================================
 * DATA SURVEY
 *
 * Saat ini masih prototype/frontend.
 * Belum menggunakan database.
 * =========================================================
 */

$survey = [
    [
        'id' => 1,
        'nama' => 'Budi Santoso',
        'hp' => '0812-1111-2222',
        'kamar' => 'A-03',
        'tanggal' => '2026-10-14',
        'jam' => '10:00',
        'catatan' => 'Ingin melakukan survey kamar sebelum booking.',
        'status' => 'menunggu',
    ],
    [
        'id' => 2,
        'nama' => 'Sinta Maharani',
        'hp' => '0857-2222-3333',
        'kamar' => 'B-02',
        'tanggal' => '2026-10-15',
        'jam' => '13:00',
        'catatan' => 'Ingin mengetahui fasilitas kamar.',
        'status' => 'dikonfirmasi',
    ],
    [
        'id' => 3,
        'nama' => 'Doni Pratama',
        'hp' => '0896-3333-4444',
        'kamar' => 'A-05',
        'tanggal' => '2026-10-12',
        'jam' => '15:00',
        'catatan' => 'Survey sudah dilakukan.',
        'status' => 'selesai',
    ],
    [
        'id' => 4,
        'nama' => 'Rina Amelia',
        'hp' => '0813-4444-5555',
        'kamar' => 'B-04',
        'tanggal' => '2026-10-11',
        'jam' => '11:00',
        'catatan' => '',
        'status' => 'dibatalkan',
    ],
];


$pageData = [
    'kunjungan' => $kunjungan,
    'survey' => $survey,
];

include ROOT_PATH . '/header/header.php';

?>

<main>

    <section class="page-head">
        <div class="container">

            <h1>Kunjungan & Survey</h1>

            <p>
                Kelola jadwal kunjungan dan survey calon penghuni.
            </p>

        </div>
    </section>


    <section
        class="page-body"
        style="padding-top:28px"
    >

        <div class="container">


            <!-- =================================================
                 TAB
                 ================================================= -->

            <div
                class="chips"
                id="ks-tabs"
                style="margin-bottom:20px"
            >

                <button
                    type="button"
                    class="chip active"
                    data-ks-tab="kunjungan"
                >
                    Kunjungan
                </button>

                <button
                    type="button"
                    class="chip"
                    data-ks-tab="survey"
                >
                    Survey
                </button>

            </div>


            <!-- =================================================
                 HALAMAN KUNJUNGAN
                 ================================================= -->

            <section
                id="ks-kunjungan"
                data-ks-section="kunjungan"
            >

                <div class="stats cols-4">

                    <div class="stat">
                        <div class="stat-label">
                            Menunggu
                        </div>

                        <div
                            class="stat-value"
                            id="st-kunjungan-menunggu"
                        >
                            <?= count(array_filter(
                                $kunjungan,
                                fn($k) =>
                                    $k['status'] === 'menunggu'
                            )) ?>
                        </div>

                        <div class="stat-note">
                            Perlu dikonfirmasi
                        </div>
                    </div>


                    <div class="stat">
                        <div class="stat-label">
                            Dikonfirmasi
                        </div>

                        <div
                            class="stat-value"
                            id="st-kunjungan-dikonfirmasi"
                        >
                            <?= count(array_filter(
                                $kunjungan,
                                fn($k) =>
                                    $k['status'] === 'dikonfirmasi'
                            )) ?>
                        </div>

                        <div class="stat-note">
                            Jadwal aktif
                        </div>
                    </div>


                    <div class="stat">
                        <div class="stat-label">
                            Selesai
                        </div>

                        <div
                            class="stat-value"
                            id="st-kunjungan-selesai"
                        >
                            <?= count(array_filter(
                                $kunjungan,
                                fn($k) =>
                                    $k['status'] === 'selesai'
                            )) ?>
                        </div>

                        <div class="stat-note">
                            Sudah berkunjung
                        </div>
                    </div>


                    <div class="stat">
                        <div class="stat-label">
                            Dibatalkan
                        </div>

                        <div
                            class="stat-value"
                            id="st-kunjungan-dibatalkan"
                        >
                            <?= count(array_filter(
                                $kunjungan,
                                fn($k) =>
                                    $k['status'] === 'dibatalkan'
                            )) ?>
                        </div>

                        <div class="stat-note">
                            Tidak dilanjutkan
                        </div>
                    </div>

                </div>


                <div class="split">

                    <div class="card">

                        <div class="card-head">

                            <h2>Daftar Kunjungan</h2>

                            <div
                                class="chips"
                                data-filter-target="#kunjungan-body"
                                data-filter-attr="status"
                            >

                                <button
                                    class="chip active"
                                    data-filter="all"
                                >
                                    Semua
                                </button>

                                <button
                                    class="chip"
                                    data-filter="menunggu"
                                >
                                    Menunggu
                                </button>

                                <button
                                    class="chip"
                                    data-filter="dikonfirmasi"
                                >
                                    Dikonfirmasi
                                </button>

                                <button
                                    class="chip"
                                    data-filter="selesai"
                                >
                                    Selesai
                                </button>

                                <button
                                    class="chip"
                                    data-filter="dibatalkan"
                                >
                                    Dibatalkan
                                </button>

                            </div>

                        </div>


                        <div class="table-wrap">

                            <table class="table">

                                <thead>

                                    <tr>
                                        <th>Nama</th>
                                        <th>Kamar</th>
                                        <th>Tanggal</th>
                                        <th>Jam</th>
                                        <th>Status</th>
                                    </tr>

                                </thead>


                                <tbody id="kunjungan-body">

                                    <?php foreach ($kunjungan as $k): ?>

                                        <tr
                                            class="is-clickable"
                                            data-kunjungan="<?= (int) $k['id'] ?>"
                                            data-status="<?= e($k['status']) ?>"
                                        >

                                            <td>
                                                <?= e($k['nama']) ?>
                                            </td>

                                            <td>
                                                <?= e($k['kamar']) ?>
                                            </td>

                                            <td class="nowrap">
                                                <?= e(tgl($k['tanggal'])) ?>
                                            </td>

                                            <td class="nowrap">
                                                <?= e($k['jam']) ?>
                                            </td>

                                            <td class="col-status">
                                                <?= status_badge(
                                                    'kunjungan',
                                                    $k['status']
                                                ) ?>
                                            </td>

                                        </tr>

                                    <?php endforeach; ?>


                                    <tr
                                        data-empty
                                        class="empty-row hidden"
                                    >
                                        <td colspan="5">
                                            Tidak ada kunjungan pada kategori ini.
                                        </td>
                                    </tr>

                                </tbody>

                            </table>

                        </div>

                    </div>


                    <aside class="card panel-sticky">

                        <h2 class="side-title">
                            Detail Kunjungan
                        </h2>

                        <div id="kunjungan-panel">

                            <p class="side-empty">
                                Pilih salah satu kunjungan
                                untuk melihat detailnya.
                            </p>

                        </div>

                    </aside>

                </div>

            </section>


            <!-- =================================================
                 HALAMAN SURVEY
                 ================================================= -->

            <section
                id="ks-survey"
                data-ks-section="survey"
                class="hidden"
            >

                <div class="stats cols-4">

                    <div class="stat">

                        <div class="stat-label">
                            Menunggu
                        </div>

                        <div
                            class="stat-value"
                            id="st-survey-menunggu"
                        >
                            <?= count(array_filter(
                                $survey,
                                fn($s) =>
                                    $s['status'] === 'menunggu'
                            )) ?>
                        </div>

                        <div class="stat-note">
                            Perlu dikonfirmasi
                        </div>

                    </div>


                    <div class="stat">

                        <div class="stat-label">
                            Dikonfirmasi
                        </div>

                        <div
                            class="stat-value"
                            id="st-survey-dikonfirmasi"
                        >
                            <?= count(array_filter(
                                $survey,
                                fn($s) =>
                                    $s['status'] === 'dikonfirmasi'
                            )) ?>
                        </div>

                        <div class="stat-note">
                            Jadwal aktif
                        </div>

                    </div>


                    <div class="stat">

                        <div class="stat-label">
                            Selesai
                        </div>

                        <div
                            class="stat-value"
                            id="st-survey-selesai"
                        >
                            <?= count(array_filter(
                                $survey,
                                fn($s) =>
                                    $s['status'] === 'selesai'
                            )) ?>
                        </div>

                        <div class="stat-note">
                            Survey selesai
                        </div>

                    </div>


                    <div class="stat">

                        <div class="stat-label">
                            Dibatalkan
                        </div>

                        <div
                            class="stat-value"
                            id="st-survey-dibatalkan"
                        >
                            <?= count(array_filter(
                                $survey,
                                fn($s) =>
                                    $s['status'] === 'dibatalkan'
                            )) ?>
                        </div>

                        <div class="stat-note">
                            Tidak dilanjutkan
                        </div>

                    </div>

                </div>


                <div class="split">

                    <div class="card">

                        <div class="card-head">

                            <h2>Daftar Survey</h2>

                            <div
                                class="chips"
                                data-filter-target="#survey-body"
                                data-filter-attr="status"
                            >

                                <button
                                    class="chip active"
                                    data-filter="all"
                                >
                                    Semua
                                </button>

                                <button
                                    class="chip"
                                    data-filter="menunggu"
                                >
                                    Menunggu
                                </button>

                                <button
                                    class="chip"
                                    data-filter="dikonfirmasi"
                                >
                                    Dikonfirmasi
                                </button>

                                <button
                                    class="chip"
                                    data-filter="selesai"
                                >
                                    Selesai
                                </button>

                                <button
                                    class="chip"
                                    data-filter="dibatalkan"
                                >
                                    Dibatalkan
                                </button>

                            </div>

                        </div>


                        <div class="table-wrap">

                            <table class="table">

                                <thead>

                                    <tr>
                                        <th>Nama</th>
                                        <th>Kamar</th>
                                        <th>Tanggal</th>
                                        <th>Jam</th>
                                        <th>Status</th>
                                    </tr>

                                </thead>


                                <tbody id="survey-body">

                                    <?php foreach ($survey as $s): ?>

                                        <tr
                                            class="is-clickable"
                                            data-survey="<?= (int) $s['id'] ?>"
                                            data-status="<?= e($s['status']) ?>"
                                        >

                                            <td>
                                                <?= e($s['nama']) ?>
                                            </td>

                                            <td>
                                                <?= e($s['kamar']) ?>
                                            </td>

                                            <td class="nowrap">
                                                <?= e(tgl($s['tanggal'])) ?>
                                            </td>

                                            <td class="nowrap">
                                                <?= e($s['jam']) ?>
                                            </td>

                                            <td class="col-status">
                                                <?= status_badge(
                                                    'kunjungan',
                                                    $s['status']
                                                ) ?>
                                            </td>

                                        </tr>

                                    <?php endforeach; ?>


                                    <tr
                                        data-empty
                                        class="empty-row hidden"
                                    >
                                        <td colspan="5">
                                            Tidak ada survey pada kategori ini.
                                        </td>
                                    </tr>

                                </tbody>

                            </table>

                        </div>

                    </div>


                    <aside class="card panel-sticky">

                        <h2 class="side-title">
                            Detail Survey
                        </h2>

                        <div id="survey-panel">

                            <p class="side-empty">
                                Pilih salah satu survey
                                untuk melihat detailnya.
                            </p>

                        </div>

                    </aside>

                </div>

            </section>

        </div>

    </section>

</main>


<?php include ROOT_PATH . '/footer/footer.php'; ?>