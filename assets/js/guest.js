/* =========================================================
   KostKu - guest.js
   Halaman publik: daftar kamar (urutkan), detail (booking & survei),
   kunjungan (pop up di halaman Fasilitas), login.
   Dipilih berdasarkan <body data-page="...">
   ========================================================= */
(function () {
    'use strict';

    var $ = KK.$, $$ = KK.$$;
    var page = document.body.dataset.page;
    var DATA = window.KOSTKU_DATA || {};

    /* Kirim form pengunjung (survey / kunjungan / reset password) ke server sebagai JSON */
    function kirim(url, fields) {
        var fd = new FormData();
        Object.keys(fields).forEach(function (k) { fd.append(k, fields[k] == null ? '' : fields[k]); });
        return fetch(KK.url(url), { method: 'POST', body: fd, credentials: 'same-origin' })
            .then(function (r) {
                return r.json().catch(function () { throw new Error('Respon server tidak dikenali.'); })
                    .then(function (j) { if (!j.ok) throw new Error(j.error || 'Terjadi kesalahan.'); return j; });
            }, function () { throw new Error('Tidak dapat terhubung ke server.'); });
    }
    function gagal(err) { KK.toast(err && err.message ? err.message : 'Gagal mengirim', 'error'); }

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
                    return $('h3', a).textContent.localeCompare(
                        $('h3', b).textContent,
                        'id',
                        { numeric: true }
                    );
                }

                var d = (+a.dataset.harga) - (+b.dataset.harga);
                return mode === 'asc' ? d : -d;
            });

            var empty = $('[data-empty]', list);
            cards.forEach(function (c) {
                list.insertBefore(c, empty);
            });
        }

        sel.addEventListener('change', sortRooms);
        sortRooms();
    }

    /* ---------- Detail kamar: booking & survei ---------- */
    function initDetail() {
        var kamar = DATA.kamar;
        var today = KK.today();

        ['b-tgl', 's-tgl'].forEach(function (id) {
            var el = document.getElementById(id);
            if (el) el.min = today;
        });

        var bookingForm = $('#booking-form');
        var surveyForm = $('#survey-form');

        /* ---------- Booking ---------- */
        if (bookingForm) {
            // Form dikirim ke guest/booking-simpan.php (POST),
            // lalu diarahkan ke halaman Status
            bookingForm.addEventListener('submit', function (e) {
                if (!KK.validate(bookingForm)) {
                    e.preventDefault();
                }
            });
        }

        /* ---------- Survey ---------- */
        if (surveyForm) {
            surveyForm.addEventListener('submit', function (e) {
                e.preventDefault();

                if (!KK.validate(surveyForm)) return;

                var f = new FormData(surveyForm);

                // Survey hanya Senin-Sabtu
                var p = String(f.get('tgl')).split('-');
                var day = new Date(
                    +p[0],
                    +p[1] - 1,
                    +p[2]
                ).getDay();

                if (day === 0) {
                    var field = $('#s-tgl').closest('.field');

                    field.classList.add('has-error');

                    var s = document.createElement('span');
                    s.className = 'field-error';
                    s.textContent = 'Kunjungan hanya Senin–Sabtu';
                    field.appendChild(s);

                    return;
                }

                var nama = f.get('nama');
                var wa = f.get('wa');

                kirim('guest/kunjungan-simpan.php', {
                    jenis: 'survey', nama: nama, wa: wa, tgl: f.get('tgl'), jam: f.get('jam'),
                    kamar: kamar, catatan: f.get('catatan')
                }).then(function () {

                    KK.closeModal('modal-survey');

                    KK.dialog({
                        type: 'success',
                        title: 'Jadwal survei terkirim',
                        text:
                            'Kunjungan ke kamar ' +
                            kamar +
                            ' pada ' +
                            KK.fmtDate(f.get('tgl'), true) +
                            ', pukul ' +
                            f.get('jam') +
                            '. Pengelola akan mengonfirmasi lewat WhatsApp.',

                        actions: [
                            {
                                label: 'Booking Kamar Ini',
                                cls: 'btn-outline',

                                onClick: function () {
                                    $('#b-nama').value = nama;
                                    $('#b-wa').value = wa;

                                    KK.openModal('modal-booking');
                                }
                            },

                            {
                                label: 'Tutup',
                                cls: 'btn-primary'
                            }
                        ]
                    });


                    KK.resetForm(surveyForm);
                }).catch(gagal);
            });
        }
    }

    /* ---------- Kunjungan: pop up dari halaman Fasilitas ---------- */
    function initKunjungan() {
        var form = $('#kunjungan-form');

        if (!form) return;

        var tgl = $('#k-tgl');

        if (tgl) {
            tgl.min = KK.today();
        }

        form.addEventListener('submit', function (e) {
            e.preventDefault();

            if (!KK.validate(form)) return;

            var f = new FormData(form);

            // Kunjungan hanya Senin-Sabtu
            var p = String(f.get('tgl')).split('-');

            var day = new Date(
                +p[0],
                +p[1] - 1,
                +p[2]
            ).getDay();

            if (day === 0) {
                var field = tgl.closest('.field');

                if (!$('.field-error', field)) {
                    var s = document.createElement('span');

                    s.className = 'field-error';
                    s.textContent = 'Kunjungan hanya Senin–Sabtu';

                    field.appendChild(s);
                }

                field.classList.add('has-error');

                return;
            }

            kirim('guest/kunjungan-simpan.php', {
                jenis: 'kunjungan', nama: f.get('nama'), wa: f.get('wa'), tgl: f.get('tgl'), catatan: f.get('catatan')
            }).then(function () {
                // Ambil URL daftar kamar dari atribut form
                var kamarUrl = form.dataset.kamarUrl;

                // Tutup modal Kunjungan
                KK.closeModal('modal-kunjungan');

                // Tampilkan pesan berhasil
                KK.dialog({
                    type: 'success',

                    title: 'Jadwal kunjungan terkirim',

                    text:
                        'Kunjungan pada ' +
                        KK.fmtDate(f.get('tgl'), true) +
                        '. Pengelola akan mengonfirmasi lewat WhatsApp.',

                    actions: [
                        {
                            label: 'Lihat Daftar Kamar',
                            cls: 'btn-outline',

                            onClick: function () {
                                if (kamarUrl) {
                                    window.location.href = kamarUrl;
                                }
                            }
                        },

                        {
                            label: 'Tutup',
                            cls: 'btn-primary'
                        }
                    ]
                });


                KK.resetForm(form);
            }).catch(gagal);
        });
    }

    /* ---------- Login ---------- */
    function initLogin() {
        var roleInput = $('#login-role');

        $$('.role-tab').forEach(function (tab) {
            tab.addEventListener('click', function () {

                $$('.role-tab').forEach(function (t) {
                    t.classList.toggle('active', t === tab);
                });

                roleInput.value = tab.dataset.role;

                $('#l-user').focus();
            });
        });

        var form = $('#login-form');

        form.addEventListener('submit', function (e) {
            if (!KK.validate(form)) {
                e.preventDefault();
            }
        });

        var reset = $('#reset-form');

        reset.addEventListener('submit', function (e) {
            e.preventDefault();

            if (!KK.validate(reset)) return;

            var rf = new FormData(reset);
            kirim('guest/reset-password.php', {
                username: String(rf.get('username')).trim(), lama: rf.get('lama'), baru: rf.get('baru')
            }).then(function () {
                KK.closeModal('modal-reset');
                KK.success('Password berhasil diubah', 'Silakan masuk dengan kata sandi baru Anda.', 'Kembali');
                KK.resetForm(reset);
            }).catch(gagal);
        });
    }

    /* ---------- Jalankan sesuai halaman ---------- */
    document.addEventListener('DOMContentLoaded', function () {

        if (page === 'guest-kamar') {
            initKamar();
        }

        if (page === 'guest-detail') {
            initDetail();
        }

        if (page === 'guest-login') {
            initLogin();
        }

        // Jalan di halaman mana pun yang punya
        // #kunjungan-form
        initKunjungan();
    });

})();