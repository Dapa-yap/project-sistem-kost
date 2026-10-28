<?php
require_once __DIR__ . '/../config/config.php';

$role = 'guest'; $pageTitle = 'Survey'; $pageId = 'guest-survey'; $activeMenu = 'survey';
$semua = array_values(get_kamar());
$pilih = $_GET['kamar'] ?? '';

include ROOT_PATH . '/header/header.php';
?>
<main>
    <section class="page-head is-center">
        <div class="container">
            <h1>Jadwalkan Survey </h1>
            <p>Lihat kamar langsung sebelum memutuskan. Tanpa biaya dan tanpa kewajiban booking.</p>
        </div>
    </section>

    <section class="page-body">
        <div class="container">
            <form id="survey-form" class="card" style="max-width:560px;margin:0 auto;padding:26px" novalidate>

                    <div class="field">
                        <label for="s-nama">Nama lengkap</label>
                        <input class="input" id="s-nama" name="nama" placeholder="Nama Anda" required>
                    </div>

                <div class="field">
                    <label for="s-tgl">Tanggal survey</label>
                    <input class="input" type="date" id="s-tgl" name="tgl" required>
                </div>

                <div class="field">
                    <span class="field-label">Jam survey</span>
                    <label class="radio-card"><input type="radio" name="jam" value="09:00" checked required> 09.00 – 11.00</label>
                    <label class="radio-card"><input type="radio" name="jam" value="13:00"> 13.00 – 15.00</label>
                    <label class="radio-card"><input type="radio" name="jam" value="16:00"> 16.00 – 17.30</label>
                </div>

                <p class="field-hint">Pengelola membalas lewat WhatsApp untuk memastikan jadwal.</p>

                <div class="modal-foot">
                    <button class="btn btn-accent" type="submit">Kirim Jadwal</button>
                </div>
            </form>
        </div>
    </section>
</main>
<?php include ROOT_PATH . '/footer/footer.php'; ?>