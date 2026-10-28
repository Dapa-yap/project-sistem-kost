<?php

require_once __DIR__ . '/../config/config.php';

require_role('admin');

$role = 'admin';
$pageTitle = 'Kunjungan & Survey';
$pageId = 'admin-kunjungan-survey';
$activeMenu = 'jadwal';


/*
 * =========================================================
 * DATA SURVEY (jadwal survei kamar dari halaman detail kamar)
 * =========================================================
 */

$survey = get_kunjungan('survey');


$pageData = [
    'survey' => $survey,
];

include ROOT_PATH . '/header/header.php';

?>

<main>

    <section class="page-head">
        <div class="container">

            <h1>Survey</h1>

            <p>
                Kelola survey calon penghuni.
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
                    data-ks-tab="survey"
                >
                    Survey
                </button>

            </div>


            <!-- =================================================
                 HALAMAN SURVEY
                 ================================================= -->

            <section
                id="ks-survey"
                data-ks-section="survey"
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
                                                <?= e($s['kamar'] ?: '-') ?>
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