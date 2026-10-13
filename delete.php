<?php

declare(strict_types=1);

require __DIR__ . '/config/database.php';
require __DIR__ . '/includes/functions.php';

start_secure_session();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    render_error_page(405, 'Metode tidak diizinkan', 'Penghapusan barang hanya dapat dilakukan melalui formulir.');
}

if (!valid_csrf_token($_POST['csrf_token'] ?? null)) {
    set_flash('error', 'Sesi penghapusan tidak valid. Silakan coba kembali.');
    redirect('index.php');
}

$id = positive_id($_POST['id'] ?? null);
if ($id === null) {
    set_flash('error', 'ID barang tidak valid.');
    redirect('index.php');
}

try {
    $statement = db()->prepare('DELETE FROM products WHERE id = :id');
    $statement->execute(['id' => $id]);

    if ($statement->rowCount() === 0) {
        set_flash('error', 'Barang tidak ditemukan atau sudah dihapus.');
    } else {
        set_flash('success', 'Barang berhasil dihapus.');
    }
} catch (Throwable $exception) {
    report_exception($exception);
    set_flash('error', 'Terjadi kesalahan. Barang tidak dapat dihapus.');
}

redirect('index.php');
