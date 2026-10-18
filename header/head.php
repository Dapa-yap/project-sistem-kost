<?php
/**
 * header/head.php  -> <head> + pembuka <body>
 * Variabel yang dibaca: $pageTitle, $role, $pageId, $bodyClass
 */
$role      = $role ?? 'guest';
$pageTitle = $pageTitle ?? 'KostKu';
$pageId    = $pageId ?? '';
$bodyClass = $bodyClass ?? '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?> · KostKu</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
       <link rel="stylesheet" href="<?= asset('css/global.css') ?>?v=<?= filemtime(ROOT_PATH . '/assets/css/global.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/' . $role . '.css') ?>?v=<?= filemtime(ROOT_PATH . '/assets/css/' . $role . '.css') ?>">
</head>
<body class="role-<?= e($role) ?> <?= e($bodyClass) ?>" data-page="<?= e($pageId) ?>" data-base="<?= e(BASE_URL) ?>">
