<?php /** Modal verifikasi bukti pembayaran (dipakai dashboard & pembayaran admin). Diisi lewat admin.js */ ?>
<div class="modal-overlay" id="modal-verif">
    <div class="modal is-wide" role="dialog" aria-modal="true" aria-labelledby="mv-title">
        <div class="modal-head">
            <div><h3 id="mv-title">Verifikasi pembayaran</h3><p id="mv-sub">-</p></div>
            <button class="modal-x" type="button" data-close aria-label="Tutup"><?= icon('x', 16) ?></button>
        </div>
        <div class="proof"><strong>Bukti transfer</strong><span id="mv-file">-</span></div>
        <div class="modal-info">
            <div><span>Periode</span><strong>Okt 2026</strong></div>
            <div><span>Jumlah</span><strong id="mv-jumlah">-</strong></div>
            <div><span>Dikirim</span><strong id="mv-dikirim">-</strong></div>
            <div><span>Status</span><strong id="mv-status">-</strong></div>
        </div>
        <div class="modal-foot">
            <button class="btn btn-ghost" type="button" data-close id="mv-tutup">Tutup</button>
            <button class="btn btn-outline" type="button" id="mv-tolak">Tolak Bukti</button>
            <button class="btn btn-accent" type="button" id="mv-terima">Terima Pembayaran</button>
        </div>
    </div>
</div>
