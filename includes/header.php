<?php

declare(strict_types=1);

$pageTitle = $pageTitle ?? 'Inventra';
$activePage = $activePage ?? '';
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Inventra, sistem inventaris barang yang ringkas dan aman.">
    <title><?= e($pageTitle) ?> | Inventra</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<a class="skip-link" href="#main-content">Lewati ke konten utama</a>
<header class="site-header">
    <nav class="navbar page-shell" aria-label="Navigasi utama">
        <a class="brand" href="index.php" aria-label="Inventra, halaman utama">
            <span class="brand-mark" aria-hidden="true">I</span>
            <span>Inventra</span>
        </a>
        <div class="nav-links">
            <a class="nav-link<?= $activePage === 'products' ? ' is-active' : '' ?>" href="index.php"<?= $activePage === 'products' ? ' aria-current="page"' : '' ?>>Barang</a>
            <a class="nav-link<?= $activePage === 'create' ? ' is-active' : '' ?>" href="create.php"<?= $activePage === 'create' ? ' aria-current="page"' : '' ?>>Tambah Barang</a>
        </div>
    </nav>
</header>
