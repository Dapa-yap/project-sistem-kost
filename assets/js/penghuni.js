/* =========================================================
   KostKu - penghuni.js
   Dashboard (pindah kamar), pembayaran (VA BNI + bukti), pengaduan.
   ========================================================= */
(function () {
    'use strict';
    var $ = KK.$, $$ = KK.$$;
    var page = document.body.dataset.page;
    var DATA = window.KOSTKU_DATA || {};

    /* ---------- Dashboard: ajukan pindah kamar ---------- */
    function initDashboard() {
        var form = $('#pindah-form');
        if (!form) return;
        $('#p-tgl').min = KK.today();

        form.addEventListener('submit', function (e) {
            e.preventDefault();
            if (!KK.validate(form)) return;
            var f = new FormData(form);
            // TODO (database): INSERT INTO pindah_kamar (penghuni, kamar_asal, kamar_tujuan, tanggal, alasan, status='menunggu')
            KK.closeModal('modal-pindah');
            KK.success('Pengajuan terkirim',
                'Pengajuan pindah ke kamar ' + f.get('tujuan') + ' pada ' + KK.fmtDate(f.get('tgl')) +
                ' sudah diterima. Pengelola akan meninjau dan menghubungi Anda.', 'Tutup');
            KK.resetForm(form);
        });
    }

    /* ---------- Pembayaran ---------- */
    function initPembayaran() {
        var body = $('#pay-body');
        var current = null; // <tr> yang sedang diproses

        function rowData(tr) { return JSON.parse(tr.getAttribute('data-row')); }
        function saveRow(tr, d) { tr.setAttribute('data-row', JSON.stringify(d)); }

        // nomor VA contoh, dibuat dari periode supaya konsisten
        function vaNumber(periode) {
            var h = 0;
            for (var i = 0; i < periode.length; i++) h = (h * 31 + periode.charCodeAt(i)) % 100000000;
            var d = String(h).padStart(8, '0');
            return '8077 1037 ' + d.slice(0, 4) + ' ' + d.slice(4);
        }

        function refreshStats() {
            var rows = $$('tr[data-row]', body).map(rowData);
            var lunas = rows.filter(function (r) { return r.status === 'lunas'; });
            var aktif = rows.filter(function (r) { return r.status === 'belum'; })[0];
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
            $('.bukti', tr).innerHTML = d.bukti ? '<span class="file">' + KK.esc(d.bukti) + '</span>' : '—';
            $('.col-aksi', tr).innerHTML = '<div class="actions">' + (d.status === 'belum'
                ? '<button class="btn btn-outline btn-sm" type="button" data-act="bukti">Kirim Bukti</button><button class="btn btn-accent btn-sm" type="button" data-act="bayar">Bayar</button>'
                : '<button class="btn btn-outline btn-sm" type="button" data-act="detail">Detail</button>') + '</div>';
            KK.applyChipFilter($('.chips[data-filter-target="#pay-body"]'));
        }

        function openBayar(tr) {
            current = tr;
            var d = rowData(tr);
            $('#mby-title').textContent = 'Bayar tagihan ' + d.periode;
            $('#mby-total').textContent = KK.rupiah(d.jumlah);
            $('#mby-tempo').textContent = KK.fmtDate(d.jatuh_tempo);
            $('#mby-va').checked = false;
            KK.openModal('modal-bayar');
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

        // Pilih cara bayar -> tampilkan nomor VA
        $('#mby-go').addEventListener('click', function () {
            if (!$('#mby-va').checked) { KK.toast('Pilih cara bayar dulu', 'error'); return; }
            var d = rowData(current);
            // TODO (database/payment gateway): minta VA dari gateway untuk tagihan ini
            $('#mva-sub').textContent = d.periode + ' · ' + KK.rupiah(d.jumlah);
            $('#mva-number').textContent = vaNumber(d.periode);
            $('#mva-batas').textContent = KK.fmtDate(d.jatuh_tempo) + ', 23.59 WIB';
            $('#mva-steps').innerHTML =
                '<li>Buka m-banking BNI atau ATM.</li>' +
                '<li>Pilih Transfer, lalu Virtual Account.</li>' +
                '<li>Masukkan nomor di atas. Jumlah ' + KK.rupiah(d.jumlah) + ' terisi otomatis.</li>';
            KK.closeModal('modal-bayar');
            KK.openModal('modal-va');
        });

        $('#mva-copy').addEventListener('click', function () {
            var no = $('#mva-number').textContent;
            var el = document.createElement('span');
            el.setAttribute('data-copy', no);
            document.body.appendChild(el);
            el.click();
            el.remove();
        });

        $('#mva-upload').addEventListener('click', function () {
            KK.closeModal('modal-va');
            openBukti(current);
        });

        // Kirim bukti
        var form = $('#bukti-form');
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            var input = form.querySelector('input[type=file]');
            if (!input.files.length) { KK.toast('Pilih foto bukti pembayaran dulu', 'error'); return; }
            var d = rowData(current);
            // TODO (database): upload file ke server, UPDATE pembayaran SET status='menunggu', bukti=...
            d.status = 'menunggu';
            d.bukti = input.files[0].name;
            saveRow(current, d);
            updateRowUI(current, d);
            refreshStats();
            KK.closeModal('modal-bukti');
            KK.success('Bukti terkirim', 'Pembayaran ' + d.periode + ' sedang menunggu verifikasi pengelola.', 'Tutup');
            KK.resetForm(form);
        });

        // Dibuka dari dashboard: pembayaran.php?bayar=Nov%202026
        if (DATA.bukaBayar) {
            var tr = $$('tr[data-periode]', body).filter(function (r) { return r.dataset.periode === DATA.bukaBayar && r.dataset.status === 'belum'; })[0];
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
            var text = $('#a-desk').value.trim();
            var judul = text.length > 48 ? text.slice(0, 48).trim() + '…' : text;
            var d = new Date();
            var iso = d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');

            // TODO (database): INSERT INTO pengaduan (...) + simpan foto
            var item = document.createElement('div');
            item.className = 'list-item';
            item.innerHTML = '<div class="row"><strong>' + KK.esc(judul) + '</strong>' + KK.badge('aduan', 'diajukan') + '</div>' +
                '<small>Dilaporkan ' + KK.fmtDate(iso).replace(/(\w{3})\w*/, '$1') + ' · Menunggu ditinjau pengelola</small>';
            list.insertBefore(item, list.firstChild);

            KK.resetForm(form);
            KK.success('Pengaduan terkirim', 'Pengelola akan meninjau laporan Anda. Status bisa dipantau di Riwayat Pengaduan.', 'Tutup');
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        if (page === 'penghuni-dashboard') initDashboard();
        if (page === 'penghuni-pembayaran') initPembayaran();
        if (page === 'penghuni-pengaduan') initPengaduan();
    });
})();
