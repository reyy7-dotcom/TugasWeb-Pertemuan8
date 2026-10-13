<?php

declare(strict_types=1);

require __DIR__ . '/config/database.php';
require __DIR__ . '/includes/functions.php';

start_secure_session();

$values = ['name' => '', 'category_id' => '', 'price' => '', 'stock' => ''];
$errors = [];

try {
    $pdo = db();
    $categories = fetch_categories($pdo);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!valid_csrf_token($_POST['csrf_token'] ?? null)) {
            $errors['_global'] = 'Sesi formulir tidak valid. Muat ulang halaman lalu coba kembali.';
        } else {
            [$values, $errors] = validate_product_input($pdo, $_POST);

            if ($errors === []) {
                $statement = $pdo->prepare(
                    'INSERT INTO products (category_id, name, price, stock)
                     VALUES (:category_id, :name, :price, :stock)'
                );
                $statement->execute([
                    'category_id' => (int) $values['category_id'],
                    'name' => $values['name'],
                    'price' => $values['price'],
                    'stock' => (int) $values['stock'],
                ]);

                set_flash('success', 'Barang berhasil ditambahkan.');
                redirect('index.php');
            }
        }
    }
} catch (Throwable $exception) {
    report_exception($exception);
    $categories = $categories ?? [];
    $errors['_global'] = 'Terjadi kesalahan. Silakan coba kembali.';
}

$pageTitle = 'Tambah Barang';
$activePage = 'create';
require __DIR__ . '/includes/header.php';
?>
<main id="main-content" class="page-shell main-content form-page">
    <div class="form-heading">
        <a class="back-link" href="index.php" aria-label="Kembali ke daftar barang">&larr; Kembali</a>
        <h1>Tambah Barang</h1>
        <p>Lengkapi informasi barang yang akan disimpan.</p>
    </div>

    <section class="form-panel" aria-labelledby="form-title">
        <div class="form-panel-heading">
            <h2 id="form-title">Informasi barang</h2>
            <p>Semua kolom wajib diisi.</p>
        </div>

        <?php if (isset($errors['_global'])): ?>
            <div class="alert alert-error" role="alert"><?= e($errors['_global']) ?></div>
        <?php endif; ?>

        <?php if ($categories === []): ?>
            <div class="alert alert-error" role="alert">Belum ada kategori. Impor database.sql terlebih dahulu.</div>
        <?php endif; ?>

        <form method="post" action="create.php" novalidate>
            <?= csrf_field() ?>
            <?php require __DIR__ . '/includes/product-form.php'; ?>
        </form>
    </section>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
