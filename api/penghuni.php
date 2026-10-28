<?php
/** Endpoint penghuni. POST: aksi=<nama>&csrf=... */
require_once __DIR__ . '/_boot.php';
$u = api_guard('penghuni');

api_run(function () use ($u) {
    switch (post('aksi')) {
        case 'ajukan_pindah':
            ajukan_pindah($u['id'], post('tujuan'), post('tgl'), post('alasan'));
            return [];
        case 'kirim_bukti':
            $nama = kirim_bukti($u['id'], post('id'), $_FILES['bukti'] ?? null);
            return ['bukti' => $nama];
        case 'buat_aduan':
            buat_aduan($u['id'], post('deskripsi'), $_FILES['foto'] ?? null);
            return [];
    }
    gagal('Aksi tidak dikenal.');
});
