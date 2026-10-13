/* =========================================================
   KostKu - guest.js
   Halaman publik: daftar kamar (urutkan), detail (booking & survei), login.
   Dipilih berdasarkan <body data-page="...">
   ========================================================= */
(function () {
    'use strict';
    var $ = KK.$, $$ = KK.$$;
    var page = document.body.dataset.page;
    var DATA = window.KOSTKU_DATA || {};

    /* ---------- Daftar kamar: urutkan ---------- */
    function initKamar() {
        var list = $('#room-list');
        var sel = $('#sort-room');
        if (!list || !sel) return;

        function sortRooms() {
            var cards = $$('.room-card', list);
            var mode = sel.value;
            cards.sort(function (a, b) {
                if (mode === 'nomor') {
                    return $('h3', a).textContent.localeCompare($('h3', b).textContent, 'id', { numeric: true });
                }
                var d = (+a.dataset.harga) - (+b.dataset.harga);
                return mode === 'asc' ? d : -d;
            });
            var empty = $('[data-empty]', list);
            cards.forEach(function (c) { list.insertBefore(c, empty); });
        }
        sel.addEventListener('change', sortRooms);
        sortRooms();
    }

    /* ---------- Detail kamar: booking & survei ---------- */
    function initDetail() {
        var kamar = DATA.kamar;
        var today = KK.today();
        ['b-tgl', 's-tgl'].forEach(function (id) { var el = document.getElementById(id); if (el) el.min = today; });

        var bookingForm = $('#booking-form');
        var surveyForm = $('#survey-form');

        if (bookingForm) {
            // Form dikirim ke guest/booking-simpan.php (POST), lalu diarahkan ke halaman Status
            bookingForm.addEventListener('submit', function (e) {
                if (!KK.validate(bookingForm)) e.preventDefault();
            });
        }

        if (surveyForm) {
            surveyForm.addEventListener('submit', function (e) {
                e.preventDefault();
                if (!KK.validate(surveyForm)) return;
                var f = new FormData(surveyForm);

                // Jam kunjungan hanya Senin–Sabtu
                var p = String(f.get('tgl')).split('-');
                var day = new Date(+p[0], +p[1] - 1, +p[2]).getDay();
                if (day === 0) {
                    var field = $('#s-tgl').closest('.field');
                    field.classList.add('has-error');
                    var s = document.createElement('span');
                    s.className = 'field-error'; s.textContent = 'Kunjungan hanya Senin–Sabtu';
                    field.appendChild(s);
                    return;
                }

                var nama = f.get('nama'), wa = f.get('wa');
                // TODO (database): INSERT jadwal survei
                KK.closeModal('modal-survey');
                KK.dialog({
                    type: 'success',
                    title: 'Jadwal survei terkirim',
                    text: 'Kunjungan ke kamar ' + kamar + ' pada ' + KK.fmtDate(f.get('tgl'), true) +
                        ', pukul ' + f.get('jam') + '. Pengelola akan mengonfirmasi lewat WhatsApp.',
                    actions: [
                        {
                            label: 'Booking Kamar Ini', cls: 'btn-outline', onClick: function () {
                                $('#b-nama').value = nama; $('#b-wa').value = wa;
                                KK.openModal('modal-booking');
                            }
                        },
                        { label: 'Tutup', cls: 'btn-primary' }
                    ]
                });
                KK.resetForm(surveyForm);
            });
        }
    }

    /* ---------- Login ---------- */
    function initLogin() {
        var roleInput = $('#login-role');
        $$('.role-tab').forEach(function (tab) {
            tab.addEventListener('click', function () {
                $$('.role-tab').forEach(function (t) { t.classList.toggle('active', t === tab); });
                roleInput.value = tab.dataset.role;
                $('#l-user').focus();
            });
        });

        var form = $('#login-form');
        form.addEventListener('submit', function (e) {
            if (!KK.validate(form)) e.preventDefault();
        });

        var reset = $('#reset-form');
        reset.addEventListener('submit', function (e) {
            e.preventDefault();
            if (!KK.validate(reset)) return;
            // TODO (database): verifikasi password lama, UPDATE password_hash
            KK.closeModal('modal-reset');
            KK.success('Password berhasil diubah', 'Silakan masuk dengan kata sandi baru Anda.', 'Kembali');
            KK.resetForm(reset);
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        if (page === 'guest-kamar') initKamar();
        if (page === 'guest-detail') initDetail();
        if (page === 'guest-login') initLogin();
    });
})();
