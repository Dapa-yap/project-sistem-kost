<?php
/**
 * header/header.php  -> satu pintu untuk memanggil header.
 *
 * Cara pakai di setiap halaman:
 *   $role = 'guest' | 'admin' | 'penghuni';
 *   $pageTitle = 'Beranda'; $pageId = 'guest-beranda'; $activeMenu = 'beranda';
 *   include ROOT_PATH . '/header/header.php';
 */
$role = $role ?? 'guest';
include __DIR__ . '/head.php';
include __DIR__ . '/' . $role . '.php';
