<?php
/** Endpoint pengelola. POST: aksi=<nama>&csrf=... */
require_once __DIR__ . '/_boot.php';
api_guard('admin');

api_run(function () {
    switch (post('aksi')) {
        case 'verifikasi_bayar':
            verifikasi_bayar(post('id'), post('status'));
            return [];
        case 'ubah_aduan':
            ubah_aduan(post('id'), post('status'), post('catatan'));
            return [];
        case 'ubah_booking':
            ubah_booking(post('id'), post('status'));
            return [];
        case 'ubah_kunjungan':
            ubah_kunjungan(post('id'), post('status'));
            return [];
        case 'ubah_kamar':
            $fas = $_POST['fasilitas'] ?? [];
            ubah_kamar(post('nomor'), post('tipe'), post('ukuran'), post('status'), post('harga'), is_array($fas) ? $fas : []);
            return [];
        case 'tambah_penghuni':
            tambah_penghuni(post('nama'), post('hp'), post('username'), post('password'), post('kamar'), post('tgl'));
            return [];
        case 'keluarkan_penghuni':
            keluarkan_penghuni(post('kamar'), post('tgl'), post('kondisi'));
            return [];
        case 'proses_pindah':
            proses_pindah(post('id'));
            return [];
        case 'tolak_pindah':
            tolak_pindah(post('id'), post('alasan'));
            return [];
    }
    gagal('Aksi tidak dikenal.');
});
