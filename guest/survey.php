<?php
require_once __DIR__ . '/../config/config.php';

$role = 'guest'; $pageTitle = 'Survey'; $pageId = 'guest-Survey'; $activeMenu = 'Survey';

include ROOT_PATH . '/header/header.php';
?>

<main>
    <section class="page-head is-center">
<div
    class="modal-overlay"
    id="modal-survey"
>

    <div
        class="modal is-wide"
        role="dialog"
        aria-modal="true"
        aria-labelledby="ms-title"
    >

        <div class="modal-head">

            <div>

                <h3 id="ms-title">
                    Jadwalkan Survei Kamar <?= e($k['id']) ?>
                </h3>

                <p>
                    Lihat kamar langsung sebelum memutuskan.
                    Tanpa biaya dan tanpa kewajiban booking.
                </p>

            </div>


            <button
                class="modal-x"
                type="button"
                data-close
                aria-label="Tutup"
            >
                <?= icon('x', 16) ?>
            </button>

        </div>


        <form
            id="survey-form"
            novalidate
        >

            <div class="field-row">

                <div class="field">

                    <label for="s-nama">
                        Nama lengkap
                    </label>

                    <input
                        class="input"
                        id="s-nama"
                        name="nama"
                        placeholder="Nama Anda"
                        required
                    >

                </div>


                <div class="field">

                    <label for="s-wa">
                        Nomor WhatsApp
                    </label>

                    <input
                        class="input"
                        id="s-wa"
                        name="wa"
                        inputmode="tel"
                        placeholder="0812xxxxxxx"
                        required
                        data-pattern="^[0-9+\- ]{9,16}$"
                        data-pattern-msg="Nomor tidak valid"
                    >

                </div>

            </div>


            <div class="field">

                <label for="s-tgl">
                    Tanggal kunjungan
                </label>

                <input
                    class="input"
                    type="date"
                    id="s-tgl"
                    name="tgl"
                    required
                >

            </div>


            <div class="field">

                <span class="field-label">
                    Jam kunjungan
                </span>


                <label class="radio-card">

                    <input
                        type="radio"
                        name="jam"
                        value="09.00 – 11.00"
                        checked
                        required
                    >

                    09.00 – 11.00

                </label>


                <label class="radio-card">

                    <input
                        type="radio"
                        name="jam"
                        value="13.00 – 15.00"
                    >

                    13.00 – 15.00

                </label>


                <label class="radio-card">

                    <input
                        type="radio"
                        name="jam"
                        value="16.00 – 17.30"
                    >

                    16.00 – 17.30

                </label>

            </div>


            <p class="field-hint">
                Pengelola membalas lewat WhatsApp
                untuk memastikan jadwal.
            </p>


            <div class="modal-foot">

                <button
                    class="btn btn-ghost"
                    type="button"
                    data-close
                >
                    Batal
                </button>


                <button
                    class="btn btn-accent"
                    type="submit"
                >
                    Kirim Jadwal
                </button>

            </div>

        </form>

    </div>

</div>
   </section>
</main>
<?php include ROOT_PATH . '/footer/footer.php'; ?>