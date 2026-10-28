/* =========================================================
   KostKu - penghuni.js
   Dashboard (pindah kamar), pembayaran (bukti transfer), pengaduan. Semua disimpan lewat api/penghuni.php.
   ========================================================= */
(function () {
    'use strict';
    var $ = KK.$, $$ = KK.$$;
    var page = document.body.dataset.page;
    var DATA = window.KOSTKU_DATA || {};
    function fail(err) { KK.toast(err && err.message ? err.message : 'Gagal menyimpan', 'error'); }
    var BISA_BAYAR = ['belum', 'terlambat', 'ditolak'];   // status yang masih boleh bayar / kirim bukti

    /* ---------- Dashboard: ajukan pindah kamar ---------- */
    function initDashboard() {
        var form = $('#pindah-form');
        if (!form) return;
        $('#p-tgl').min = KK.today();

        form.addEventListener('submit', function (e) {
            e.preventDefault();
            if (!KK.validate(form)) return;
            var f = new FormData(form);
            KK.api('penghuni', 'ajukan_pindah', { tujuan: f.get('tujuan'), tgl: f.get('tgl'), alasan: f.get('alasan') }).then(function () {
                KK.reloadWith({
                    title: 'Pengajuan terkirim',
                    text: 'Pengajuan pindah ke kamar ' + f.get('tujuan') + ' pada ' + KK.fmtDate(f.get('tgl')) +
                        ' sudah diterima. Pengelola akan meninjau dan menghubungi Anda.'
                });
            }).catch(fail);
        });
    }

    /* ---------- Pembayaran ---------- */
    function initPembayaran() {
        var body = $('#pay-body');
        var current = null; // <tr> yang sedang diproses

        function rowData(tr) { return JSON.parse(tr.getAttribute('data-row')); }
        function saveRow(tr, d) { tr.setAttribute('data-row', JSON.stringify(d)); }

        function refreshStats() {
            var rows = $$('tr[data-row]', body).map(rowData);
            var lunas = rows.filter(function (r) { return r.status === 'lunas'; });
            var aktif = rows.filter(function (r) { return BISA_BAYAR.indexOf(r.status) !== -1; }).pop();   // tagihan tertua yang belum beres
            $('#stat-lunas').textContent = KK.rupiah(lunas.reduce(function (a, r) { return a + r.jumlah; }, 0));
            $('#stat-lunas-note').textContent = lunas.length + ' pembayaran lunas';
            if (aktif) {
                $('#stat-aktif').textContent = KK.rupiah(aktif.jumlah);
                $('#stat-aktif-note').textContent = 'Periode ' + aktif.periode;
                var p = aktif.jatuh_tempo.split('-');
                $('#stat-tempo').textContent = (+p[2]) + ' ' + KK.fmtDate(aktif.jatuh_tempo).split(' ')[1].slice(0, 3);
                var days = Math.ceil((new Date(+p[0], +p[1] - 1, +p[2]) - new Date(new Date().toDateString())) / 86400000);
                $('#stat-tempo-note').textContent = 'Sisa ' + Math.max(0, days) + ' hari';
            } else {
                $('#stat-aktif').textContent = 'Rp 0';
                $('#stat-aktif-note').textContent = 'Tidak ada tagihan aktif';
                $('#stat-tempo').textContent = '-';
                $('#stat-tempo-note').textContent = 'Semua tagihan tertangani';
            }
        }

        function updateRowUI(tr, d) {
            tr.dataset.status = d.status;
            $('.col-status', tr).innerHTML = KK.badge('bayar', d.status);
            $('.bukti', tr).innerHTML = d.bukti ? '<a class="file" target="_blank" rel="noopener" href="' + KK.esc(KK.url('api/berkas.php?f=' + encodeURIComponent(d.bukti))) + '">Lihat foto</a>' : '—';
            $('.col-aksi', tr).innerHTML = '<div class="actions">' + (BISA_BAYAR.indexOf(d.status) !== -1
                ? '<button class="btn btn-outline btn-sm" type="button" data-act="bukti">Kirim Bukti</button><button class="btn btn-accent btn-sm" type="button" data-act="bayar">Bayar</button>'
                : '<button class="btn btn-outline btn-sm" type="button" data-act="detail">Detail</button>') + '</div>';
            KK.applyChipFilter($('.chips[data-filter-target="#pay-body"]'));
        }

        function openBayar(tr) {
            current = tr;
            var d = rowData(tr);
            $('#mrk-title').textContent = 'Bayar tagihan ' + d.periode;
            $('#mrk-total').textContent = KK.rupiah(d.jumlah);
            $('#mrk-tempo').textContent = KK.fmtDate(d.jatuh_tempo);
            KK.openModal('modal-rekening');
        }

        function openBukti(tr) {
            current = tr;
            var d = rowData(tr);
            $('#mbk-title').textContent = 'Bayar tagihan ' + d.periode;
            $('#mbk-jumlah').textContent = KK.rupiah(d.jumlah);
            $('#mbk-tempo').textContent = KK.fmtDate(d.jatuh_tempo);
            KK.resetForm($('#bukti-form'));
            KK.openModal('modal-bukti');
        }

        function openDetail(tr) {
            var d = rowData(tr);
            $('#mdt-title').textContent = 'Tagihan ' + d.periode;
            $('#mdt-sub').textContent = 'Rincian pembayaran sewa kamar.';
            $('#mdt-info').innerHTML =
                '<div><span>Periode</span><strong>' + KK.esc(d.periode) + '</strong></div>' +
                '<div><span>Tanggal tagihan</span><strong>' + KK.fmtDate(d.tgl_tagihan) + '</strong></div>' +
                '<div><span>Jatuh tempo</span><strong>' + KK.fmtDate(d.jatuh_tempo) + '</strong></div>' +
                '<div><span>Jumlah</span><strong>' + KK.rupiah(d.jumlah) + '</strong></div>' +
                '<div><span>Status</span><strong>' + KK.badge('bayar', d.status) + '</strong></div>' +
                '<div><span>Bukti</span><strong>' + (d.bukti ? KK.esc(d.bukti) : '—') + '</strong></div>';
            KK.openModal('modal-detail');
        }

        body.addEventListener('click', function (e) {
            var btn = e.target.closest('[data-act]');
            if (!btn) return;
            var tr = btn.closest('tr');
            var act = btn.dataset.act;
            if (act === 'bayar') openBayar(tr);
            else if (act === 'bukti') openBukti(tr);
            else if (act === 'detail') openDetail(tr);
        });

        $('#mrk-copy').addEventListener('click', function () {
            var no = $('#mrk-number').textContent.replace(/\s+/g, '');
            var el = document.createElement('span');
            el.setAttribute('data-copy', no);
            document.body.appendChild(el);
            el.click();
            el.remove();
        });

        $('#mrk-upload').addEventListener('click', function () {
            KK.closeModal('modal-rekening');
            openBukti(current);
        });

        // Kirim bukti
        var form = $('#bukti-form');
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            var input = form.querySelector('input[type=file]');
            if (!input.files.length) { KK.toast('Pilih foto bukti pembayaran dulu', 'error'); return; }
            var d = rowData(current);
            var btn = $('button[type=submit]', form);
            btn.disabled = true;
            KK.api('penghuni', 'kirim_bukti', { id: d.id }, { bukti: input.files[0] }).then(function (res) {
                d.status = 'menunggu';
                d.bukti = res.bukti;
                saveRow(current, d);
                updateRowUI(current, d);
                refreshStats();
                KK.closeModal('modal-bukti');
                KK.success('Bukti terkirim', 'Pembayaran ' + d.periode + ' sedang menunggu verifikasi pengelola.', 'Tutup');
                KK.resetForm(form);
            }).catch(fail).then(function () { btn.disabled = false; });
        });

        // Dibuka dari dashboard: pembayaran.php?bayar=Nov%202026
        if (DATA.bukaBayar) {
            var tr = $$('tr[data-periode]', body).filter(function (r) { return r.dataset.periode === DATA.bukaBayar && BISA_BAYAR.indexOf(r.dataset.status) !== -1; })[0];
            if (tr) openBayar(tr);
        }
    }

    /* ---------- Pengaduan ---------- */
    function initPengaduan() {
        var form = $('#aduan-form');
        var list = $('#aduan-list');
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            if (!KK.validate(form)) return;
            var btn = $('button[type=submit]', form);
            btn.disabled = true;
            KK.api('penghuni', 'buat_aduan', { deskripsi: $('#a-desk').value.trim() }, { foto: (form.querySelector('input[type=file]').files || [])[0] }).then(function () {
                KK.reloadWith({ title: 'Pengaduan terkirim', text: 'Pengelola akan meninjau laporan Anda. Status bisa dipantau di Riwayat Pengaduan.' });
            }).catch(function (err) { btn.disabled = false; fail(err); });
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        if (page === 'penghuni-dashboard') initDashboard();
        if (page === 'penghuni-pembayaran') initPembayaran();
        if (page === 'penghuni-pengaduan') initPengaduan();
    });
})();
