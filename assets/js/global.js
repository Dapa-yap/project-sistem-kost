/* =========================================================
   KostKu - global.js
   Fungsi umum yang dipakai semua peran.
   Akses lewat objek global:  KK.toast(), KK.dialog(), KK.confirm(), KK.openModal() ...
   ========================================================= */
(function () {
    'use strict';

    var KK = {};
    var BASE = (window.KOSTKU && window.KOSTKU.base) || '';
    KK.url = function (p) { return BASE + '/' + String(p).replace(/^\/+/, ''); };

    /* ---------- Util ---------- */
    KK.$ = function (sel, root) { return (root || document).querySelector(sel); };
    KK.$$ = function (sel, root) { return Array.prototype.slice.call((root || document).querySelectorAll(sel)); };

    KK.esc = function (s) {
        return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    };

    KK.rupiah = function (n) {
        return 'Rp ' + Number(n).toLocaleString('id-ID');
    };

    var BULAN = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    var HARI = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    KK.fmtDate = function (iso, withDay) {
        if (!iso) return '-';
        var p = String(iso).split('-');
        var d = new Date(+p[0], +p[1] - 1, +p[2]);
        if (isNaN(d)) return iso;
        return (withDay ? HARI[d.getDay()] + ', ' : '') + d.getDate() + ' ' + BULAN[d.getMonth()] + ' ' + d.getFullYear();
    };
    KK.today = function () {
        var d = new Date();
        return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
    };

    /* ---------- Toast ---------- */
    KK.toast = function (msg, type) {
        var root = KK.$('#toast-root');
        if (!root) return;
        var el = document.createElement('div');
        el.className = 'toast' + (type ? ' is-' + type : '');
        el.setAttribute('role', 'status');
        el.textContent = msg;
        root.appendChild(el);
        setTimeout(function () {
            el.style.transition = 'opacity .25s';
            el.style.opacity = '0';
            setTimeout(function () { el.remove(); }, 260);
        }, 3200);
    };

    /* ---------- Modal ---------- */
    var lastFocus = null;

    KK.openModal = function (idOrEl) {
        var el = typeof idOrEl === 'string' ? document.getElementById(idOrEl) : idOrEl;
        if (!el) return null;
        lastFocus = document.activeElement;
        el.classList.add('is-open');
        document.body.classList.add('modal-open');
        var focusable = KK.$('input:not([type=hidden]), select, textarea, button.btn-primary, button.btn-accent, button', el.querySelector('.modal'));
        if (focusable) setTimeout(function () { focusable.focus({ preventScroll: true }); }, 30);
        return el;
    };

    KK.closeModal = function (idOrEl) {
        var el = typeof idOrEl === 'string' ? document.getElementById(idOrEl) : idOrEl;
        if (!el) return;
        el.classList.remove('is-open');
        if (el.dataset.dynamic === '1') {
            setTimeout(function () { el.remove(); }, 10);
        }
        if (!KK.$('.modal-overlay.is-open')) document.body.classList.remove('modal-open');
        if (lastFocus && lastFocus.focus) { try { lastFocus.focus({ preventScroll: true }); } catch (e) {} }
    };

    KK.closeAllModals = function () {
        KK.$$('.modal-overlay.is-open').forEach(KK.closeModal);
    };

    /**
     * Dialog dinamis (konfirmasi / sukses / info).
     * KK.dialog({ type:'success', title:'', text:'', actions:[{label, cls:'btn-accent', onClick: fn, keepOpen:false}] })
     */
    var ICON = {
        success: '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg>',
        warn: '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 8v5M12 17h.01"/><circle cx="12" cy="12" r="9"/></svg>',
        danger: '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 6l12 12M18 6L6 18"/></svg>',
        info: '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8h.01"/></svg>'
    };

    KK.dialog = function (opt) {
        var type = opt.type || 'info';
        var overlay = document.createElement('div');
        overlay.className = 'modal-overlay';
        overlay.dataset.dynamic = '1';
        overlay.innerHTML =
            '<div class="modal is-dialog" role="dialog" aria-modal="true">' +
            '<div class="dialog-icon is-' + type + '">' + (ICON[type] || ICON.info) + '</div>' +
            '<h3>' + KK.esc(opt.title || '') + '</h3>' +
            (opt.text ? '<p>' + (opt.html ? opt.text : KK.esc(opt.text)) + '</p>' : '') +
            '<div class="modal-foot"></div></div>';
        var foot = overlay.querySelector('.modal-foot');
        (opt.actions || [{ label: 'Tutup', cls: 'btn-primary' }]).forEach(function (a) {
            var b = document.createElement('button');
            b.type = 'button';
            b.className = 'btn ' + (a.cls || 'btn-ghost');
            b.textContent = a.label;
            b.addEventListener('click', function () {
                if (!a.keepOpen) KK.closeModal(overlay);
                if (a.onClick) a.onClick();
            });
            foot.appendChild(b);
        });
        document.body.appendChild(overlay);
        KK.openModal(overlay);
        return overlay;
    };

    KK.confirm = function (opt) {
        return KK.dialog({
            type: opt.type || 'warn',
            title: opt.title,
            text: opt.text,
            html: opt.html,
            actions: [
                { label: opt.cancelText || 'Batal', cls: 'btn-ghost' },
                { label: opt.okText || 'Ya, lanjutkan', cls: opt.okClass || 'btn-accent', onClick: opt.onOk }
            ]
        });
    };

    KK.success = function (title, text, btnLabel, onClose) {
        return KK.dialog({
            type: 'success', title: title, text: text,
            actions: [{ label: btnLabel || 'Kembali', cls: 'btn-accent', onClick: onClose }]
        });
    };

    /* ---------- Validasi form sederhana ---------- */
    KK.validate = function (form) {
        var ok = true;
        KK.$$('.field.has-error', form).forEach(function (f) {
            f.classList.remove('has-error');
            var m = f.querySelector('.field-error'); if (m) m.remove();
        });
        KK.$$('[required]', form).forEach(function (input) {
            var empty = (input.type === 'radio')
                ? !form.querySelector('input[name="' + input.name + '"]:checked')
                : !String(input.value).trim();
            var field = input.closest('.field');
            var msg = '';
            if (empty) msg = 'Wajib diisi';
            else if (input.dataset.minlength && input.value.length < +input.dataset.minlength) msg = 'Minimal ' + input.dataset.minlength + ' karakter';
            else if (input.dataset.pattern && !new RegExp(input.dataset.pattern).test(input.value)) msg = input.dataset.patternMsg || 'Format tidak sesuai';
            if (msg && field) {
                ok = false;
                field.classList.add('has-error');
                var s = document.createElement('span');
                s.className = 'field-error'; s.textContent = msg;
                field.appendChild(s);
            } else if (msg) { ok = false; }
        });
        if (!ok) {
            var first = form.querySelector('.has-error input, .has-error select, .has-error textarea');
            if (first) first.focus();
        }
        return ok;
    };

    KK.resetForm = function (form) {
        form.reset();
        KK.$$('.field.has-error', form).forEach(function (f) {
            f.classList.remove('has-error');
            var m = f.querySelector('.field-error'); if (m) m.remove();
        });
        KK.$$('.dropzone', form).forEach(function (d) {
            d.classList.remove('has-file');
            var s = d.querySelector('strong'); if (s && d.dataset.label) s.textContent = d.dataset.label;
        });
    };

    /* ---------- Event delegation ---------- */
    document.addEventListener('click', function (ev) {
        var t = ev.target;

        // buka modal
        var op = t.closest('[data-open]');
        if (op) { ev.preventDefault(); KK.openModal(op.getAttribute('data-open')); return; }

        // tutup modal
        var cl = t.closest('[data-close]');
        if (cl) {
            var ov = cl.closest('.modal-overlay');
            if (ov) KK.closeModal(ov);
            return;
        }

        // klik overlay (di luar modal)
        if (t.classList && t.classList.contains('modal-overlay')) { KK.closeModal(t); return; }

        // logout
        var lo = t.closest('[data-logout]');
        if (lo) {
            ev.preventDefault();
            KK.confirm({
                type: 'warn',
                title: 'Keluar dari akun?',
                text: 'Anda perlu masuk lagi untuk mengakses halaman ini.',
                okText: 'Ya, Keluar',
                onOk: function () { window.location.href = KK.url('guest/logout.php'); }
            });
            return;
        }

        // menu mobile
        var nt = t.closest('[data-nav-toggle]');
        if (nt) {
            var nav = KK.$('[data-nav]');
            if (nav) nav.classList.toggle('is-open');
            return;
        }

        // tampil/sembunyi password
        var tp = t.closest('[data-toggle-password]');
        if (tp) {
            var input = tp.closest('.input-icon').querySelector('input');
            var show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            tp.setAttribute('aria-label', show ? 'Sembunyikan password' : 'Tampilkan password');
            tp.classList.toggle('is-on', show);
            return;
        }

        // salin teks
        var cp = t.closest('[data-copy]');
        if (cp) {
            var text = cp.getAttribute('data-copy');
            var done = function () { KK.toast('Nomor disalin: ' + text, 'success'); };
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(text).then(done, done);
            } else {
                var ta = document.createElement('textarea'); ta.value = text; document.body.appendChild(ta);
                ta.select(); try { document.execCommand('copy'); } catch (e) {} ta.remove(); done();
            }
            return;
        }

        // filter chips
        var chip = t.closest('.chips[data-filter-target] .chip');
        if (chip) {
            var group = chip.closest('.chips');
            KK.$$('.chip', group).forEach(function (c) { c.classList.toggle('active', c === chip); });
            applyChipFilter(group);
            return;
        }
    });

    document.addEventListener('keydown', function (ev) {
        if (ev.key === 'Escape') {
            var open = KK.$$('.modal-overlay.is-open');
            if (open.length) KK.closeModal(open[open.length - 1]);
        }
    });

    /* ---------- Filter chips (generik) ----------
       <div class="chips" data-filter-target="#tabel tbody" data-filter-attr="status">
           <button class="chip active" data-filter="all">Semua</button>
           <button class="chip" data-filter="lunas">Lunas</button>
           <button class="chip" data-attr="tipe" data-filter="Premium">Premium</button>   (override atribut)
       </div>
       Item yang difilter: elemen anak langsung target yang punya data-<attr>.
    */
    function applyChipFilter(group) {
        var target = KK.$(group.getAttribute('data-filter-target'));
        if (!target) return;
        var active = KK.$('.chip.active', group);
        var val = active ? active.getAttribute('data-filter') : 'all';
        var attr = (active && active.getAttribute('data-attr')) || group.getAttribute('data-filter-attr') || 'status';
        var items = Array.prototype.slice.call(target.children).filter(function (el) { return !el.hasAttribute('data-empty'); });
        var shown = 0;
        items.forEach(function (el) {
            var match = val === 'all' || el.getAttribute('data-' + attr) === val;
            el.classList.toggle('hidden', !match);
            if (match) shown++;
        });
        var empty = target.querySelector('[data-empty]');
        if (empty) empty.classList.toggle('hidden', shown > 0);
        group.dispatchEvent(new CustomEvent('kk:filtered', { detail: { value: val, attr: attr, shown: shown } }));
    }
    KK.applyChipFilter = applyChipFilter;

    /* ---------- Dropzone (upload foto/bukti) ---------- */
    function initDropzones(root) {
        KK.$$('.dropzone', root).forEach(function (dz) {
            if (dz.dataset.ready) return;
            dz.dataset.ready = '1';
            var input = dz.querySelector('input[type=file]');
            var label = dz.querySelector('strong');
            dz.dataset.label = label ? label.textContent : '';
            var max = (+dz.getAttribute('data-max-mb') || 5) * 1024 * 1024;

            function setFile(file) {
                if (!file) return;
                if (!/^image\/(jpeg|png)$/.test(file.type)) { KK.toast('Format harus JPG atau PNG', 'error'); input.value = ''; return; }
                if (file.size > max) { KK.toast('Ukuran file maksimal ' + (max / 1048576) + ' MB', 'error'); input.value = ''; return; }
                dz.classList.add('has-file');
                if (label) label.textContent = file.name;
            }
            dz.addEventListener('click', function () { input.click(); });
            dz.addEventListener('keydown', function (e) { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); input.click(); } });
            input.addEventListener('click', function (e) { e.stopPropagation(); });
            input.addEventListener('change', function () { setFile(input.files[0]); });
            ['dragenter', 'dragover'].forEach(function (n) {
                dz.addEventListener(n, function (e) { e.preventDefault(); dz.classList.add('is-over'); });
            });
            ['dragleave', 'drop'].forEach(function (n) {
                dz.addEventListener(n, function (e) { e.preventDefault(); dz.classList.remove('is-over'); });
            });
            dz.addEventListener('drop', function (e) {
                var f = e.dataTransfer.files[0];
                if (f) {
                    try { var dt = new DataTransfer(); dt.items.add(f); input.files = dt.files; } catch (err) {}
                    setFile(f);
                }
            });
        });
    }
    KK.initDropzones = initDropzones;

    /* ---------- Search tabel (generik) ----------
       <input data-search-target="#tbl tbody"> -> baris dengan teks yang cocok akan tampil */
    document.addEventListener('input', function (ev) {
        var inp = ev.target.closest && ev.target.closest('[data-search-target]');
        if (!inp) return;
        var target = KK.$(inp.getAttribute('data-search-target'));
        if (!target) return;
        var q = inp.value.trim().toLowerCase();
        var shown = 0;
        Array.prototype.slice.call(target.children).forEach(function (row) {
            if (row.hasAttribute('data-empty')) return;
            var match = !q || row.textContent.toLowerCase().indexOf(q) !== -1;
            row.classList.toggle('hidden', !match);
            if (match) shown++;
        });
        var empty = target.querySelector('[data-empty]');
        if (empty) empty.classList.toggle('hidden', shown > 0);
    });

    document.addEventListener('DOMContentLoaded', function () {
        initDropzones(document);
        // pastikan filter awal konsisten
        KK.$$('.chips[data-filter-target]').forEach(applyChipFilter);
    });

    /* ---------- Badge status (sama dengan status_badge() di PHP) ---------- */
    var BADGES = {
        kamar:   { kosong: ['Tersedia', 'green'], terisi: ['Terisi', 'grey'], booking: ['Terbooking', 'brown'], diperbaiki: ['Diperbaiki', 'orange'] },
        bayar:   { belum: ['Belum Dibayar', 'orange'], menunggu: ['Menunggu Verifikasi', 'blue'], lunas: ['Lunas', 'green'], terlambat: ['Terlambat', 'red'], ditolak: ['Ditolak', 'red-solid'] },
        aduan:   { diajukan: ['Diajukan', 'blue'], diproses: ['Diproses', 'orange'], selesai: ['Selesai', 'green'] },
        booking: { menunggu: ['Menunggu', 'blue'], dikonfirmasi: ['Dikonfirmasi', 'green'], kedaluwarsa: ['Kedaluwarsa', 'orange'], ditolak: ['Ditolak', 'red-solid'] },
        pindah:  { menunggu: ['Menunggu', 'orange'], selesai: ['Selesai', 'green'], ditolak: ['Ditolak', 'red-solid'] }
    };
    KK.badge = function (type, status) {
        var m = (BADGES[type] || {})[status] || [status, 'grey'];
        return '<span class="badge badge-' + m[1] + '">' + KK.esc(m[0]) + '</span>';
    };
    KK.label = function (type, status) { return ((BADGES[type] || {})[status] || [status])[0]; };

    window.KK = KK;
})();
