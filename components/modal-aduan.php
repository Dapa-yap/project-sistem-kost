<?php /** Modal ubah status pengaduan (dipakai dashboard & pengaduan admin). Diisi lewat admin.js */ ?>
<div class="modal-overlay" id="modal-aduan">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="ma-title">
        <div class="modal-head">
            <div><h3 id="ma-title">Ubah status pengaduan</h3><p id="ma-sub">-</p></div>
            <button class="modal-x" type="button" data-close aria-label="Tutup"><?= icon('x', 16) ?></button>
        </div>
        <form id="aduan-status-form" novalidate>
            <div class="modal-info"><div><span>Tanggal diajukan</span><strong id="ma-tgl">-</strong></div><div><span>Foto</span><strong id="ma-foto">-</strong></div></div>
            <div class="field">
                <label for="ma-status">Status</label>
                <select class="select" id="ma-status" name="status">
                    <option value="diajukan">Diajukan</option>
                    <option value="diproses">Diproses</option>
                    <option value="selesai">Selesai</option>
                </select>
            </div>
            <div class="field"><label for="ma-catatan">Catatan untuk penghuni (opsional)</label><textarea class="textarea" id="ma-catatan" name="catatan" placeholder="Contoh: teknisi datang besok pagi"></textarea></div>
            <div class="modal-foot">
                <button class="btn btn-ghost" type="button" data-close>Batal</button>
                <button class="btn btn-accent" type="submit">Simpan Status</button>
            </div>
        </form>
    </div>
</div>
