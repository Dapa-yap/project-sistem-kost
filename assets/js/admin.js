/* =========================================================
   KostKu - admin.js
   Dashboard, kamar, penghuni, pembayaran, pengaduan, booking,
   kunjungan & survey.
   Data awal datang dari PHP lewat window.KOSTKU_DATA.
   Semua aksi di sini baru mengubah tampilan (belum ke database).
   Titik yang perlu diganti ke database ditandai: TODO (database)
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
            $('#mv-file').textContent = p.bukti || 'Belum ada bukti';
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
            var p = byId(pay, id);
            p.status = status;
            // TODO (database): UPDATE pembayaran SET status=? WHERE id=?
            refreshPay();
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
                '<div class="proof' + (p.bukti ? '' : ' is-empty') + '"><strong>Bukti transfer</strong><span>' + KK.esc(p.bukti || 'Belum ada bukti') + '</span></div>' +
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
                    setPay(currentId, 'lunas');
                    KK.closeModal('modal-verif');
                    KK.toast('Pembayaran diterima. Status: Lunas', 'success');
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
                    setPay(currentId, 'ditolak');
                    KK.closeModal('modal-verif');
                    KK.toast('Bukti ditolak. Penghuni diminta mengirim ulang.');
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
            $('#ma-foto').textContent = a.foto + ' foto';
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
            a.status = $('#ma-status').value;
            // TODO (database): UPDATE pengaduan SET status=?, catatan=? WHERE id=?
            refresh();
            KK.closeModal('modal-aduan');
            KK.toast('Status pengaduan diubah menjadi ' + KK.label('aduan', a.status), 'success');
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
            var b = byId(list, id);
            b.status = status;
            // TODO (database): UPDATE booking SET status=? WHERE id=?  (+ ubah status kamar)
            var tr = $('#booking-body tr[data-booking="' + id + '"]');
            tr.dataset.status = status;
            $('.col-status', tr).innerHTML = KK.badge('booking', status);
            stats();
            refilter('.chips[data-filter-target="#booking-body"]');
            render(id);
        }

        document.addEventListener('click', function (e) {
            var row = e.target.closest('#booking-body tr[data-booking]');
            if (row) { render(row.dataset.booking); return; }

            if (e.target.closest('#bk-ok')) {
                var b = byId(list, selectedId);
                KK.confirm({
                    type: 'success', title: 'Konfirmasi booking?',
                    text: 'Kamar ' + b.kamar + ' untuk ' + b.nama + ' ditahan dan statusnya menjadi Booking. Buat akun penghuni saat dia masuk.',
                    okText: 'Ya, Konfirmasi',
                    onOk: function () { setStatus(selectedId, 'dikonfirmasi'); KK.toast('Booking dikonfirmasi', 'success'); }
                });
            }
            if (e.target.closest('#bk-no')) {
                var c = byId(list, selectedId);
                KK.confirm({
                    type: 'danger', title: 'Tolak booking ini?',
                    text: 'Kamar ' + c.kamar + ' akan kembali tersedia untuk calon penghuni lain.',
                    okText: 'Ya, Tolak', okClass: 'btn-danger',
                    onOk: function () { setStatus(selectedId, 'ditolak'); KK.toast('Booking ditolak'); }
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
                var x = byId(list, id);
                x.status = status;
                // TODO (database): UPDATE kunjungan / survey SET status=? WHERE id=?
                var tr = $('#' + key + '-body tr[data-' + key + '="' + id + '"]');
                tr.dataset.status = status;
                $('.col-status', tr).innerHTML = badge(status);
                stats();
                refilter('.chips[data-filter-target="#' + key + '-body"]');
                render(id);
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
                        setStatus(selectedId, st);
                        KK.toast('Status ' + noun + ' diubah menjadi ' + LABEL[st], batal ? undefined : 'success');
                    }
                });
            });
        }

        setup('kunjungan', 'kunjungan');
        setup('survey', 'survey');
    }

    /* =====================================================
       KAMAR
       ===================================================== */
    function initKamar() {
        var K = DATA.kamar || {};
        var selected = null;

        function statusLabel(s) { return { kosong: 'Kosong', terisi: 'Terisi', booking: 'Booking', diperbaiki: 'Diperbaiki' }[s]; }

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

        function paintTile(id) {
            var k = K[id];
            $$('[data-room="' + id + '"]').forEach(function (el) {
                el.className = el.className.replace(/\bst-\w+/g, '').trim() + ' st-' + k.status;
                var span = $('span', el);
                span.textContent = el.closest('#modal-denah') ? statusLabel(k.status) : k.tipe;
            });
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
            k.tipe = $('#u-tipe').value;
            k.ukuran = $('#u-ukuran').value.trim();
            k.status = $('#u-status').value;
            k.harga = parseInt($('#u-harga').value, 10);
            k.fasilitas = $$('#u-fasilitas input:checked').map(function (cb) { return cb.value; });
            if (k.status !== 'terisi' && k.penghuni && k.status === 'kosong') k.penghuni = null;
            // TODO (database): UPDATE kamar SET tipe=?, ukuran=?, status=?, harga=?, fasilitas=? WHERE nomor=?
            paintTile(selected);
            render(selected);
            KK.closeModal('modal-ubah');
            KK.success('Perubahan Berhasil di Simpan', 'Perubahan data pada kamar ' + k.id + ' sudah berhasil.', 'Kembali');
        });

        render(K['A-03'] ? 'A-03' : Object.keys(K)[0]);
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

        var TAGIHAN = { Lunas: 'lunas', Menunggu: 'menunggu', Terlambat: 'terlambat' };

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

        function rebuildKamarSelect() {
            var sel = $('#t-kamar');
            var val = sel.value;
            sel.innerHTML = '<option value="">Pilih kamar kosong</option>';
            Object.keys(K).filter(function (id) { return K[id].status === 'kosong'; }).sort().forEach(function (id) {
                var o = document.createElement('option');
                o.value = id; o.textContent = id + ' · ' + K[id].tipe;
                sel.appendChild(o);
            });
            sel.value = val;
        }

        function renumber() {
            $$('tr[data-kamar]', body).forEach(function (tr, i) { $('.c-no', tr).textContent = i + 1; });
            P.forEach(function (p, i) { p.no = i + 1; });
        }

        function rowHtml(p) {
            return '<td class="c-no">' + p.no + '</td><td class="c-nama">' + KK.esc(p.nama) + '</td><td class="c-kamar">' + KK.esc(p.kamar) + '</td>' +
                '<td class="c-tipe nowrap">Lantai ' + p.lantai + ' · ' + KK.esc(p.tipe) + '</td>' +
                '<td class="nowrap">' + short(p.masuk) + '</td><td class="nowrap">' + KK.esc(p.hp) + '</td>' +
                '<td><div class="actions"><button class="btn btn-outline btn-sm" type="button" data-act="detail">Detail</button>' +
                '<button class="btn btn-outline btn-sm" type="button" data-act="keluar">Keluarkan</button></div></td>';
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
                    '<div><span>Tagihan terakhir</span><strong>' + KK.badge('bayar', TAGIHAN[p.tagihan] || 'menunggu') + '</strong></div>' +
                    '<div><span>Pengaduan aktif</span><strong>' + (p.aduan || 0) + '</strong></div>';
                KK.openModal('modal-detail');
            }
            if (btn.dataset.act === 'keluar') {
                curKamar = p.kamar;
                $('#mk-sub').textContent = p.nama + ' · Kamar ' + p.kamar + '. Dipakai saat penghuni berhenti atau kontrak selesai.';
                $('#mk-tagihan').innerHTML = KK.badge('bayar', TAGIHAN[p.tagihan] || 'menunggu');
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
            var baik = $('#k-kondisi').value === 'baik';
            KK.confirm({
                type: 'warn', title: 'Keluarkan ' + p.nama + '?',
                text: 'Akun penghuni akan dinonaktifkan. Pastikan deposit sudah diselesaikan.',
                okText: 'Ya, Keluarkan',
                onOk: function () {
                    // TODO (database): nonaktifkan akun, UPDATE kamar SET status=?, catat tanggal keluar
                    P.splice(P.indexOf(p), 1);
                    $('tr[data-kamar="' + p.kamar + '"]', body).remove();
                    K[p.kamar].status = baik ? 'kosong' : 'diperbaiki';
                    K[p.kamar].penghuni = null;
                    renumber(); rebuildKamarSelect(); stats();
                    KK.closeModal('modal-keluar');
                    KK.toast(p.nama + ' sudah dikeluarkan dari kamar ' + p.kamar, 'success');
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
            var id = f.get('kamar');
            if (!K[id] || K[id].status !== 'kosong') { KK.toast('Kamar tidak tersedia', 'error'); return; }

            var p = {
                no: P.length + 1, nama: String(f.get('nama')).trim(), kamar: id, lantai: K[id].lantai, tipe: K[id].tipe,
                harga: K[id].harga, masuk: f.get('tgl'), hp: String(f.get('hp')).trim(), tagihan: 'Menunggu', aduan: 0, deposit: K[id].harga
            };
            // TODO (database): INSERT user (password_hash) + penghuni, UPDATE kamar status='terisi'
            P.push(p);
            K[id].status = 'terisi'; K[id].penghuni = p.nama;
            var tr = document.createElement('tr');
            tr.dataset.kamar = id;
            tr.innerHTML = rowHtml(p);
            body.insertBefore(tr, $('[data-empty]', body));
            rebuildKamarSelect(); stats();
            KK.closeModal('modal-tambah');
            KK.success('Penghuni ditambahkan', 'Akun "' + f.get('username') + '" dibuat dan ' + p.nama + ' ditempatkan di kamar ' + id + '.', 'Tutup');
            KK.resetForm(form);
            $('#t-tgl').value = KK.today();
        });

        /* ---- pindah kamar ---- */
        function refreshPindah(p) {
            var tr = $('#pindah-body tr[data-pindah="' + p.id + '"]');
            tr.dataset.status = p.status;
            $('.col-status', tr).innerHTML = KK.badge('pindah', p.status);
            $('.col-aksi', tr).textContent = '-';
            stats();
        }

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
                        // TODO (database): UPDATE penghuni SET kamar=?, UPDATE kamar status (asal=kosong, tujuan=terisi)
                        p.status = 'selesai';
                        K[p.asal].status = 'kosong'; K[p.asal].penghuni = null;
                        K[p.tujuan].status = 'terisi'; K[p.tujuan].penghuni = p.nama;
                        var ph = findP(p.asal);
                        if (ph) {
                            ph.kamar = p.tujuan; ph.lantai = K[p.tujuan].lantai; ph.tipe = K[p.tujuan].tipe; ph.harga = K[p.tujuan].harga;
                            var tr = $('tr[data-kamar="' + p.asal + '"]', body);
                            tr.dataset.kamar = p.tujuan;
                            $('.c-kamar', tr).textContent = p.tujuan;
                            $('.c-tipe', tr).textContent = 'Lantai ' + ph.lantai + ' · ' + ph.tipe;
                        }
                        refreshPindah(p); rebuildKamarSelect();
                        KK.toast('Pindah kamar disetujui', 'success');
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
            var p = byId(PIN, curPindah);
            // TODO (database): UPDATE pindah_kamar SET status='ditolak', alasan_tolak=?
            p.status = 'ditolak';
            refreshPindah(p);
            KK.closeModal('modal-tolak');
            KK.toast('Pengajuan pindah ditolak');
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