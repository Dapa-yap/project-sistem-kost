/* =========================================================
   KostKu - admin.js
   Dashboard, kamar, penghuni, pembayaran, pengaduan, booking,
   kunjungan & survey.
   Data awal datang dari PHP lewat window.KOSTKU_DATA.
   Semua aksi disimpan ke database lewat api/admin.php (KK.api).
   Tampilan hanya diubah setelah server menjawab berhasil.
   ========================================================= */
(function () {
    'use strict';
    var $ = KK.$, $$ = KK.$$;
    var page = document.body.dataset.page;
    var DATA = window.KOSTKU_DATA || {};

    function byId(arr, id) {
        for (var i = 0; i < arr.length; i++) if (+arr[i].id === +id) return arr[i];
        return null;
    }
    function short(iso) { return KK.fmtDate(iso).replace(/([A-Za-z]{3})[a-z]*/, '$1'); }
    function shortTime(s) {
        if (!s) return '-';
        var p = s.split(' ');
        return short(p[0]) + (p[1] ? ', ' + p[1].replace(':', '.') : '');
    }
    function refilter(selector) {
        var g = $(selector);
        if (g) KK.applyChipFilter(g);
    }
    function fail(err) { KK.toast(err && err.message ? err.message : 'Gagal menyimpan', 'error'); }
    function berkas(f) { return KK.url('api/berkas.php?f=' + encodeURIComponent(f)); }
    function buktiHtml(f) {
        return f ? '<a class="link" href="' + KK.esc(berkas(f)) + '" target="_blank" rel="noopener">Lihat foto bukti</a>' : 'Belum ada bukti';
    }
    function selectRow(tbodySel, tr) {
        $$(tbodySel + ' tr').forEach(function (r) { r.classList.toggle('is-selected', r === tr); });
    }

    /* =====================================================
       PEMBAYARAN  (dashboard + halaman pembayaran)
       ===================================================== */
    function initPayment() {
        var pay = DATA.pembayaran || [];
        var currentId = null, selectedId = null;

        function openVerif(id) {
            var p = byId(pay, id);
            if (!p) return;
            currentId = id;
            $('#mv-sub').textContent = p.nama + ' · Kamar ' + p.kamar;
            $('#mv-file').innerHTML = buktiHtml(p.bukti);
            if ($('#mv-periode')) $('#mv-periode').textContent = p.periode || '-';
            $('#modal-verif .proof').classList.toggle('is-empty', !p.bukti);
            $('#mv-jumlah').textContent = KK.rupiah(p.jumlah);
            $('#mv-dikirim').textContent = shortTime(p.dikirim);
            $('#mv-status').innerHTML = KK.badge('bayar', p.status);
            var pending = p.status === 'menunggu';
            $('#mv-terima').classList.toggle('hidden', !pending);
            $('#mv-tolak').classList.toggle('hidden', !pending);
            KK.openModal('modal-verif');
        }

        function setPay(id, status) {
            return KK.api('admin', 'verifikasi_bayar', { id: id, status: status }).then(function () {
                byId(pay, id).status = status;
                refreshPay();
            });
        }

        function refreshPay() {
            // dashboard
            var vb = $('#verif-body');
            if (vb) {
                $$('tr[data-pay]', vb).forEach(function (tr) {
                    var p = byId(pay, tr.dataset.pay);
                    if (p && p.status !== 'menunggu') tr.remove();
                });
                var n = $$('tr[data-pay]', vb).length;
                var empty = $('[data-empty]', vb);
                if (empty) empty.classList.toggle('hidden', n > 0);
                var st = $('#stat-menunggu');
                if (st) st.textContent = pay.filter(function (p) { return p.status === 'menunggu'; }).length;
            }
            // halaman pembayaran
            var pb = $('#pay-body');
            if (pb) {
                $$('tr[data-pay]', pb).forEach(function (tr) {
                    var p = byId(pay, tr.dataset.pay);
                    tr.dataset.status = p.status;
                    $('.col-status', tr).innerHTML = KK.badge('bayar', p.status);
                });
                refilter('.chips[data-filter-target="#pay-body"]');
                if (selectedId) renderPanel(selectedId);
            }
        }

        function renderPanel(id) {
            var p = byId(pay, id);
            selectedId = id;
            selectRow('#pay-body', $('#pay-body tr[data-pay="' + id + '"]'));
            $('#pay-panel').innerHTML =
                '<div class="proof' + (p.bukti ? '' : ' is-empty') + '"><strong>Bukti transfer</strong><span>' + buktiHtml(p.bukti) + '</span></div>' +
                '<div class="kv-item"><small>Periode</small><strong>' + KK.esc(p.periode || '-') + '</strong></div>' +
                '<div class="kv">' +
                '<div class="kv-item"><small>Penghuni</small><strong>' + KK.esc(p.nama) + ' · ' + KK.esc(p.kamar) + '</strong></div>' +
                '<div class="kv-item"><small>Jumlah</small><strong>' + KK.rupiah(p.jumlah) + '</strong></div>' +
                '<div class="kv-item"><small>Dikirim</small><strong>' + KK.esc(shortTime(p.dikirim)) + '</strong></div>' +
                '<div class="kv-item"><small>Status</small><strong>' + KK.badge('bayar', p.status) + '</strong></div>' +
                '</div>' +
                '<div class="side-actions"><button class="btn btn-outline" type="button" id="pay-detail">' + (p.status === 'menunggu' ? 'Verifikasi Bukti' : 'Lihat Detail') + '</button></div>';
        }

        document.addEventListener('click', function (e) {
            var v = e.target.closest('[data-act="verifikasi"]');
            if (v) { openVerif(v.closest('tr').dataset.pay); return; }

            var row = e.target.closest('#pay-body tr[data-pay]');
            if (row) { renderPanel(row.dataset.pay); return; }

            if (e.target.closest('#pay-detail')) {
                var p = byId(pay, selectedId);
                if (!p.bukti) { KK.toast('Penghuni belum mengirim bukti pembayaran'); return; }
                openVerif(selectedId);
            }
        });

        $('#mv-terima').addEventListener('click', function () {
            var p = byId(pay, currentId);
            KK.confirm({
                type: 'success', title: 'Terima pembayaran ini?',
                text: 'Pembayaran ' + p.nama + ' (Kamar ' + p.kamar + ') sebesar ' + KK.rupiah(p.jumlah) + ' akan ditandai Lunas.',
                okText: 'Ya, Terima',
                onOk: function () {
                    setPay(currentId, 'lunas').then(function () {
                        KK.closeModal('modal-verif');
                        KK.toast('Pembayaran diterima. Status: Lunas', 'success');
                    }).catch(fail);
                }
            });
        });

        $('#mv-tolak').addEventListener('click', function () {
            var p = byId(pay, currentId);
            KK.confirm({
                type: 'danger', title: 'Tolak bukti pembayaran?',
                text: 'Penghuni ' + p.nama + ' akan diminta mengirim ulang bukti transfer.',
                okText: 'Ya, Tolak', okClass: 'btn-danger',
                onOk: function () {
                    setPay(currentId, 'ditolak').then(function () {
                        KK.closeModal('modal-verif');
                        KK.toast('Bukti ditolak. Penghuni diminta mengirim ulang.');
                    }).catch(fail);
                }
            });
        });
    }

    /* =====================================================
       PENGADUAN  (dashboard + halaman pengaduan)
       ===================================================== */
    function initAduan() {
        var list = DATA.aduan || [];
        var currentId = null;

        function open(id) {
            var a = byId(list, id);
            currentId = id;
            $('#ma-sub').textContent = 'Kamar ' + a.kamar + ' · ' + a.deskripsi;
            $('#ma-tgl').textContent = KK.fmtDate(a.tgl);
            $('#ma-foto').innerHTML = a.foto_file
                ? '<a class="link" href="' + KK.esc(berkas(a.foto_file)) + '" target="_blank" rel="noopener">Lihat foto</a>'
                : 'Tidak ada foto';
            $('#ma-status').value = a.status;
            $('#ma-catatan').value = '';
            KK.openModal('modal-aduan');
        }

        function refresh() {
            $$('tr[data-aduan]').forEach(function (tr) {
                var a = byId(list, tr.dataset.aduan);
                tr.dataset.status = a.status;
                $('.col-status', tr).innerHTML = KK.badge('aduan', a.status);
                var btn = $('[data-act="aduan"]', tr);
                if (btn && btn.textContent.trim() === 'Ubah Status') {
                    btn.classList.toggle('btn-brown', a.status === 'diajukan');
                    btn.classList.toggle('btn-outline', a.status !== 'diajukan');
                }
            });
            ['diajukan', 'diproses', 'selesai'].forEach(function (s) {
                var el = $('#st-' + s);
                if (el) el.textContent = list.filter(function (a) { return a.status === s; }).length;
            });
            var dash = $('#stat-aduan');
            if (dash) dash.textContent = list.filter(function (a) { return a.status === 'diajukan'; }).length;
            refilter('.chips[data-filter-target="#aduan-body"]');
        }

        document.addEventListener('click', function (e) {
            var b = e.target.closest('[data-act="aduan"]');
            if (b) open(b.closest('tr').dataset.aduan);
        });

        $('#aduan-status-form').addEventListener('submit', function (e) {
            e.preventDefault();
            var a = byId(list, currentId);
            var status = $('#ma-status').value;
            KK.api('admin', 'ubah_aduan', { id: currentId, status: status, catatan: $('#ma-catatan').value }).then(function () {
                a.status = status;
                refresh();
                KK.closeModal('modal-aduan');
                KK.toast('Status pengaduan diubah menjadi ' + KK.label('aduan', a.status), 'success');
            }).catch(fail);
        });
    }

    /* =====================================================
       BOOKING
       ===================================================== */
    function initBooking() {
        var list = DATA.booking || [];
        var selectedId = null;

        function stats() {
            ['menunggu', 'dikonfirmasi', 'kedaluwarsa'].forEach(function (s) {
                $('#st-' + s).textContent = list.filter(function (b) { return b.status === s; }).length;
            });
            $('#st-urgent').textContent = list.filter(function (b) { return b.status === 'menunggu' && b.urgent; }).length;
        }

        function render(id) {
            var b = byId(list, id);
            selectedId = id;
            selectRow('#booking-body', $('#booking-body tr[data-booking="' + id + '"]'));
            var html =
                '<div class="kv">' +
                '<div class="kv-item"><small>Calon penghuni</small><strong>' + KK.esc(b.nama) + '</strong></div>' +
                '<div class="kv-item"><small>No. HP</small><strong>' + KK.esc(b.hp) + '</strong></div>' +
                '<div class="kv-item"><small>Kamar</small><strong>' + KK.esc(b.kamar) + ' · ' + KK.esc(b.tipe) + '</strong></div>' +
                '<div class="kv-item"><small>Tanggal booking</small><strong>' + short(b.tgl) + '</strong></div>' +
                '<div class="kv-item"><small>Batas waktu</small><strong>' + short(b.batas) + '</strong>' +
                (b.status === 'menunggu' && b.urgent ? ' <span class="badge badge-orange">Kurang dari 24 jam</span>' : '') + '</div>' +
                '<div class="kv-item"><small>Status</small><strong>' + KK.badge('booking', b.status) + '</strong></div>' +
                '</div>';
            if (b.status === 'menunggu') {
                html += '<div class="side-actions"><button class="btn btn-accent" type="button" id="bk-ok">Konfirmasi Booking</button>' +
                    '<button class="btn btn-outline" type="button" id="bk-no">Tolak Booking</button></div>';
            }
            $('#booking-panel').innerHTML = html;
        }

        function setStatus(id, status) {
            return KK.api('admin', 'ubah_booking', { id: id, status: status }).then(function () {
                var b = byId(list, id);
                b.status = status;
                var tr = $('#booking-body tr[data-booking="' + id + '"]');
                tr.dataset.status = status;
                $('.col-status', tr).innerHTML = KK.badge('booking', status);
                stats();
                refilter('.chips[data-filter-target="#booking-body"]');
                render(id);
            });
        }

        document.addEventListener('click', function (e) {
            var row = e.target.closest('#booking-body tr[data-booking]');
            if (row) { render(row.dataset.booking); return; }

            if (e.target.closest('#bk-ok')) {
                var b = byId(list, selectedId);
                KK.confirm({
                    type: 'success', title: 'Konfirmasi booking?',
                    text: 'Kamar ' + b.kamar + ' untuk ' + b.nama + ' ditahan dan statusnya menjadi Booking. Buat akun penghuni di menu Penghuni saat dia masuk.',
                    okText: 'Ya, Konfirmasi',
                    onOk: function () { setStatus(selectedId, 'dikonfirmasi').then(function () { KK.toast('Booking dikonfirmasi', 'success'); }).catch(fail); }
                });
            }
            if (e.target.closest('#bk-no')) {
                var c = byId(list, selectedId);
                KK.confirm({
                    type: 'danger', title: 'Tolak booking ini?',
                    text: 'Kamar ' + c.kamar + ' akan kembali tersedia untuk calon penghuni lain.',
                    okText: 'Ya, Tolak', okClass: 'btn-danger',
                    onOk: function () { setStatus(selectedId, 'ditolak').then(function () { KK.toast('Booking ditolak'); }).catch(fail); }
                });
            }
        });
    }

    /* =====================================================
       KUNJUNGAN & SURVEY  (tab + detail + ubah status)
       ===================================================== */
    function initKunjunganSurvey() {
        var STATUS = ['menunggu', 'dikonfirmasi', 'selesai', 'dibatalkan'];
        var LABEL = { menunggu: 'Menunggu', dikonfirmasi: 'Dikonfirmasi', selesai: 'Selesai', dibatalkan: 'Dibatalkan' };
        var TONE = { menunggu: 'blue', dikonfirmasi: 'green', selesai: 'grey', dibatalkan: 'red-solid' };
        var AKSI = {
            menunggu:     [['dikonfirmasi', 'Konfirmasi', 'btn-accent'], ['dibatalkan', 'Batalkan', 'btn-outline']],
            dikonfirmasi: [['selesai', 'Tandai Selesai', 'btn-accent'], ['dibatalkan', 'Batalkan', 'btn-outline']],
            selesai: [],
            dibatalkan: []
        };

        function badge(st) {
            return '<span class="badge badge-' + (TONE[st] || 'grey') + '">' + KK.esc(LABEL[st] || st) + '</span>';
        }

        /* ----- Tab Kunjungan / Survey ----- */
        var tabs = $('#ks-tabs');
        if (!tabs) return;
        $$('[data-ks-tab]', tabs).forEach(function (btn) {
            btn.addEventListener('click', function () {
                $$('[data-ks-tab]', tabs).forEach(function (b) { b.classList.toggle('active', b === btn); });
                $$('[data-ks-section]').forEach(function (s) {
                    s.classList.toggle('hidden', s.dataset.ksSection !== btn.dataset.ksTab);
                });
            }); 
        });

        /* ----- Satu bagian: tabel + panel detail ----- */
        function setup(key, noun) {
            var list = DATA[key] || [];
            var body = $('#' + key + '-body');
            var panel = $('#' + key + '-panel');
            if (!body || !panel) return;
            var selectedId = null;

            function stats() {
                STATUS.forEach(function (s) {
                    var el = document.getElementById('st-' + key + '-' + s);
                    if (el) el.textContent = list.filter(function (x) { return x.status === s; }).length;
                });
            }

            function render(id) {
                var x = byId(list, id);
                if (!x) return;
                selectedId = id;
                selectRow('#' + key + '-body', $('#' + key + '-body tr[data-' + key + '="' + id + '"]'));

                var tgl = x.tanggal || x.tgl || '';
                var hp = x.hp || x.wa || '';
                var wa = hp ? 'https://wa.me/' + String(hp).replace(/\D+/g, '').replace(/^0/, '62') : '';

                var html =
                    '<div class="kv">' +
                    '<div class="kv-item"><small>Nama</small><strong>' + KK.esc(x.nama) + '</strong></div>' +
                    '<div class="kv-item"><small>No. HP</small><strong>' +
                        (hp ? '<a class="link" href="' + KK.esc(wa) + '" target="_blank" rel="noopener">' + KK.esc(hp) + '</a>' : '-') + '</strong></div>' +
                    '<div class="kv-item"><small>Kamar</small><strong>' + (x.kamar ? KK.esc(x.kamar) : 'Belum menentukan') + '</strong></div>' +
                    '<div class="kv-item"><small>Tanggal</small><strong>' + (tgl ? short(tgl) : '-') + '</strong></div>' +
                    '<div class="kv-item"><small>Jam</small><strong>' + KK.esc(x.jam || '-') + '</strong></div>' +
                    (x.catatan ? '<div class="kv-item"><small>Catatan</small><strong>' + KK.esc(x.catatan) + '</strong></div>' : '') +
                    '<div class="kv-item"><small>Status</small><strong>' + badge(x.status) + '</strong></div>' +
                    '</div>';

                var aksi = AKSI[x.status] || [];
                if (aksi.length) {
                    html += '<div class="side-actions">' + aksi.map(function (a) {
                        return '<button class="btn ' + a[2] + '" type="button" data-ks-aksi="' + a[0] + '">' + a[1] + '</button>';
                    }).join('') + '</div>';
                }
                panel.innerHTML = html;
            }

            function setStatus(id, status) {
                return KK.api('admin', 'ubah_kunjungan', { id: id, status: status }).then(function () {
                    var x = byId(list, id);
                    x.status = status;
                    var tr = $('#' + key + '-body tr[data-' + key + '="' + id + '"]');
                    tr.dataset.status = status;
                    $('.col-status', tr).innerHTML = badge(status);
                    stats();
                    refilter('.chips[data-filter-target="#' + key + '-body"]');
                    render(id);
                });
            }

            body.addEventListener('click', function (e) {
                var row = e.target.closest('tr[data-' + key + ']');
                if (row) render(row.getAttribute('data-' + key));
            });

            panel.addEventListener('click', function (e) {
                var b = e.target.closest('[data-ks-aksi]');
                if (!b) return;
                var st = b.dataset.ksAksi, x = byId(list, selectedId);
                var batal = st === 'dibatalkan';
                KK.confirm({
                    type: batal ? 'danger' : 'success',
                    title: batal ? 'Batalkan ' + noun + ' ini?' : (st === 'selesai' ? 'Tandai selesai?' : 'Konfirmasi ' + noun + '?'),
                    text: batal
                        ? noun.charAt(0).toUpperCase() + noun.slice(1) + ' ' + x.nama + ' akan dibatalkan.'
                        : 'Status ' + noun + ' ' + x.nama + ' menjadi ' + LABEL[st] + '.',
                    okText: batal ? 'Ya, Batalkan' : 'Ya, Lanjutkan',
                    okClass: batal ? 'btn-danger' : undefined,
                    onOk: function () {
                        setStatus(selectedId, st).then(function () {
                            KK.toast('Status ' + noun + ' diubah menjadi ' + LABEL[st], batal ? undefined : 'success');
                        }).catch(fail);
                    }
                });
            });
        }

        setup('survey', 'survey');
    }

    /* =====================================================
       KAMAR
       ===================================================== */
    function initKamar() {
        var K = DATA.kamar || {};
        var selected = null;

        function render(id) {
            var k = K[id];
            selected = id;
            $$('[data-room]').forEach(function (el) { el.classList.toggle('is-mark', el.dataset.room === id); });
            $('#kp-id').textContent = k.id;
            $('#kp-badge').innerHTML = KK.badge('kamar', k.status).replace('badge-green', 'badge-soft-green');
            $('#kp-tipe').textContent = k.tipe;
            $('#kp-lantai').textContent = 'Lantai ' + k.lantai;
            $('#kp-ukuran').textContent = k.ukuran;
            $('#kp-harga').textContent = KK.rupiah(k.harga) + ' / bulan';
            $('#kp-penghuni').textContent = k.penghuni || '—';
            $('#kp-fasilitas').textContent = k.fasilitas.join(' · ');
        }

        document.addEventListener('click', function (e) {
            var tile = e.target.closest('.denah-room[data-room]');
            if (tile) {
                render(tile.dataset.room);
                var m = tile.closest('#modal-denah');
                if (m) KK.closeModal(m);
            }
        });

        $('#kp-ubah').addEventListener('click', function () {
            var k = K[selected];
            $('#mu-title').textContent = 'Ubah data kamar ' + k.id;
            $('#u-nomor').value = k.id;
            $('#u-lantai').value = 'Lantai ' + k.lantai;
            $('#u-tipe').value = k.tipe;
            $('#u-ukuran').value = k.ukuran;
            $('#u-status').value = k.status;
            $('#u-harga').value = k.harga;
            $$('#u-fasilitas input').forEach(function (cb) { cb.checked = k.fasilitas.indexOf(cb.value) !== -1; });
            KK.openModal('modal-ubah');
        });

        $('#ubah-form').addEventListener('submit', function (e) {
            e.preventDefault();
            var form = e.target;
            if (!KK.validate(form)) return;
            var k = K[selected];
            var data = {
                nomor: selected,
                tipe: $('#u-tipe').value,
                ukuran: $('#u-ukuran').value.trim(),
                status: $('#u-status').value,
                harga: parseInt($('#u-harga').value, 10),
                fasilitas: $$('#u-fasilitas input:checked').map(function (cb) { return cb.value; })
            };
            KK.api('admin', 'ubah_kamar', data).then(function () {
                try { sessionStorage.setItem('kk_kamar', selected); } catch (err) {}
                KK.reloadWith({ title: 'Perubahan Berhasil di Simpan', text: 'Perubahan data pada kamar ' + k.id + ' sudah berhasil.', btn: 'Kembali' });
            }).catch(fail);
        });

        var awal = null;
        try { awal = sessionStorage.getItem('kk_kamar'); sessionStorage.removeItem('kk_kamar'); } catch (err) {}
        render(awal && K[awal] ? awal : (K['A-03'] ? 'A-03' : Object.keys(K)[0]));
    }

    /* =====================================================
       PENGHUNI
       ===================================================== */
    function initPenghuni() {
        var P = DATA.penghuni || [];
        var PIN = DATA.pindah || [];
        var K = DATA.kamar || {};
        var body = $('#penghuni-body');
        var curKamar = null, curPindah = null;

        var TAGIHAN = { 'Belum Dibayar': 'belum', Lunas: 'lunas', Menunggu: 'menunggu', Terlambat: 'terlambat', Ditolak: 'ditolak' };

        function stats() {
            var kosong = Object.keys(K).filter(function (id) { return K[id].status === 'kosong'; }).length;
            $('#st-total').textContent = P.length;
            $('#st-terisi').textContent = P.length;
            $('#st-terisi-note').textContent = 'dari ' + DATA.totalKamar + ' kamar';
            $('#st-kosong').textContent = kosong;
            var pend = PIN.filter(function (p) { return p.status === 'menunggu'; }).length;
            $('#pindah-count').textContent = pend + ' menunggu';
            var empty = $('[data-empty]', body);
            empty.classList.toggle('hidden', $$('tr[data-kamar]', body).length > 0);
        }

        function findP(kamar) { return P.filter(function (p) { return p.kamar === kamar; })[0]; }

        /* ---- detail / keluarkan ---- */
        body.addEventListener('click', function (e) {
            var btn = e.target.closest('[data-act]');
            if (!btn) return;
            var tr = btn.closest('tr');
            var p = findP(tr.dataset.kamar);
            if (!p) return;

            if (btn.dataset.act === 'detail') {
                $('#md2-title').textContent = p.nama;
                $('#md2-info').innerHTML =
                    '<div><span>Kamar</span><strong>' + KK.esc(p.kamar) + ' · Lantai ' + p.lantai + ' · ' + KK.esc(p.tipe) + '</strong></div>' +
                    '<div><span>Tanggal masuk</span><strong>' + KK.fmtDate(p.masuk) + '</strong></div>' +
                    '<div><span>Nomor HP</span><strong>' + KK.esc(p.hp) + '</strong></div>' +
                    '<div><span>Tagihan terakhir</span><strong>' + (TAGIHAN[p.tagihan] ? KK.badge('bayar', TAGIHAN[p.tagihan]) : '-') + '</strong></div>' +
                    '<div><span>Pengaduan aktif</span><strong>' + (p.aduan || 0) + '</strong></div>';
                KK.openModal('modal-detail');
            }
            if (btn.dataset.act === 'keluar') {
                curKamar = p.kamar;
                $('#mk-sub').textContent = p.nama + ' · Kamar ' + p.kamar + '. Dipakai saat penghuni berhenti atau kontrak selesai.';
                $('#mk-tagihan').innerHTML = (TAGIHAN[p.tagihan] ? KK.badge('bayar', TAGIHAN[p.tagihan]) : '-');
                $('#mk-deposit').textContent = KK.rupiah(p.deposit);
                $('#k-tgl').value = KK.today();
                $('#k-kondisi').value = 'baik';
                updateWarn();
                KK.openModal('modal-keluar');
            }
        });

        function updateWarn() {
            var baik = $('#k-kondisi').value === 'baik';
            $('#mk-warn').textContent = 'Setelah ini, akun penghuni dinonaktifkan dan kamar ' + curKamar +
                (baik ? ' kembali tersedia (kosong).' : ' berstatus Diperbaiki sampai perbaikan selesai.');
        }
        $('#k-kondisi').addEventListener('change', updateWarn);

        $('#keluar-form').addEventListener('submit', function (e) {
            e.preventDefault();
            if (!KK.validate(e.target)) return;
            var p = findP(curKamar);
            var kamar = curKamar, tgl = $('#k-tgl').value, kondisi = $('#k-kondisi').value;
            KK.confirm({
                type: 'warn', title: 'Keluarkan ' + p.nama + '?',
                text: 'Akun penghuni akan dinonaktifkan. Pastikan deposit sudah diselesaikan.',
                okText: 'Ya, Keluarkan',
                onOk: function () {
                    KK.api('admin', 'keluarkan_penghuni', { kamar: kamar, tgl: tgl, kondisi: kondisi }).then(function () {
                        KK.reloadWith({ toast: p.nama + ' sudah dikeluarkan dari kamar ' + kamar, type: 'success' });
                    }).catch(fail);
                }
            });
        });

        /* ---- tambah penghuni ---- */
        $('#t-tgl').value = KK.today();
        $('#tambah-form').addEventListener('submit', function (e) {
            e.preventDefault();
            var form = e.target;
            if (!KK.validate(form)) return;
            var f = new FormData(form);
            var btn = $('button[type=submit]', form);
            btn.disabled = true;
            KK.api('admin', 'tambah_penghuni', {
                nama: String(f.get('nama')).trim(), hp: String(f.get('hp')).trim(),
                username: String(f.get('username')).trim(), password: f.get('password'),
                kamar: f.get('kamar'), tgl: f.get('tgl')
            }).then(function () {
                KK.reloadWith({
                    title: 'Penghuni ditambahkan',
                    text: 'Akun "' + String(f.get('username')).trim() + '" dibuat dan ' + String(f.get('nama')).trim() + ' ditempatkan di kamar ' + f.get('kamar') + '. Berikan username dan password ke penghuni.'
                });
            }).catch(function (err) { btn.disabled = false; fail(err); });
        });

        /* ---- pindah kamar ---- */
        $('#pindah-body').addEventListener('click', function (e) {
            var btn = e.target.closest('[data-act]');
            if (!btn) return;
            var p = byId(PIN, btn.closest('tr').dataset.pindah);

            if (btn.dataset.act === 'proses') {
                if (!K[p.tujuan] || K[p.tujuan].status !== 'kosong') {
                    KK.toast('Kamar ' + p.tujuan + ' sudah tidak kosong. Pengajuan tidak bisa diproses.', 'error');
                    return;
                }
                KK.confirm({
                    type: 'success', title: 'Setujui pindah kamar?',
                    text: p.nama + ' pindah dari ' + p.asal + ' ke ' + p.tujuan + ' pada ' + KK.fmtDate(p.tanggal) +
                        '. Kamar ' + p.asal + ' menjadi kosong.',
                    okText: 'Ya, Setujui',
                    onOk: function () {
                        KK.api('admin', 'proses_pindah', { id: p.id }).then(function () {
                            KK.reloadWith({ toast: 'Pindah kamar disetujui', type: 'success' });
                        }).catch(fail);
                    }
                });
            }
            if (btn.dataset.act === 'tolak') {
                curPindah = p.id;
                $('#tl-alasan').value = '';
                KK.openModal('modal-tolak');
            }
        });

        $('#tolak-form').addEventListener('submit', function (e) {
            e.preventDefault();
            if (!KK.validate(e.target)) return;
            KK.api('admin', 'tolak_pindah', { id: curPindah, alasan: $('#tl-alasan').value }).then(function () {
                KK.reloadWith({ toast: 'Pengajuan pindah ditolak' });
            }).catch(fail);
        });

        stats();
    }

    document.addEventListener('DOMContentLoaded', function () {
        if (page === 'admin-dashboard') { initPayment(); initAduan(); }
        if (page === 'admin-pembayaran') initPayment();
        if (page === 'admin-pengaduan') initAduan();
        if (page === 'admin-booking') initBooking();
        if (page === 'admin-kamar') initKamar();
        if (page === 'admin-penghuni') initPenghuni();
        if (page === 'admin-kunjungan-survey') initKunjunganSurvey();
    });
})();