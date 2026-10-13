<?php

declare(strict_types=1);

require __DIR__ . '/config/database.php';
require __DIR__ . '/includes/functions.php';

start_secure_session();

$id = positive_id($_GET['id'] ?? null);
if ($id === null) {
    render_error_page(400, 'ID barang tidak valid', 'Gunakan ID barang berupa angka positif.');
}

$errors = [];

try {
    $pdo = db();
    $statement = $pdo->prepare(
        'SELECT id, category_id, name, price, stock FROM products WHERE id = :id LIMIT 1'
    );
    $statement->execute(['id' => $id]);
    $product = $statement->fetch();

    if ($product === false) {
        render_error_page(404, 'Barang tidak ditemukan', 'Barang yang ingin diubah tidak tersedia.');
    }

    $values = [
        'name' => (string) $product['name'],
        'category_id' => (string) $product['category_id'],
        'price' => (string) $product['price'],
        'stock' => (string) $product['stock'],
    ];
    $categories = fetch_categories($pdo);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!valid_csrf_token($_POST['csrf_token'] ?? null)) {
            $errors['_global'] = 'Sesi formulir tidak valid. Muat ulang halaman lalu coba kembali.';
        } else {
            [$values, $errors] = validate_product_input($pdo, $_POST);

            if ($errors === []) {
                $statement = $pdo->prepare(
                    'UPDATE products
                     SET category_id = :category_id, name = :name, price = :price, stock = :stock
                     WHERE id = :id'
                );
                $statement->execute([
                    'category_id' => (int) $values['category_id'],
                    'name' => $values['name'],
                    'price' => $values['price'],
                    'stock' => (int) $values['stock'],
                    'id' => $id,
                ]);

                set_flash('success', 'Barang berhasil diperbarui.');
                redirect('index.php');
            }
        }
    }
} catch (Throwable $exception) {
    report_exception($exception);
    render_error_page(500, 'Data tidak dapat diproses', 'Terjadi kesalahan. Silakan coba kembali.');
}

$pageTitle = 'Edit Barang';
$activePage = 'products';
require __DIR__ . '/includes/header.php';
?>
<main id="main-content" class="page-shell main-content form-page">
    <div class="form-heading">
        <a class="back-link" href="index.php" aria-label="Kembali ke daftar barang">&larr; Kembali</a>
        <h1>Edit Barang</h1>
        <p>Perbarui informasi barang dengan data terbaru.</p>
    </div>

    <section class="form-panel" aria-labelledby="form-title">
        <div class="form-panel-heading">
            <h2 id="form-title">Informasi barang</h2>
            <p>Semua kolom wajib diisi.</p>
        </div>

        <?php if (isset($errors['_global'])): ?>
            <div class="alert alert-error" role="alert"><?= e($errors['_global']) ?></div>
        <?php endif; ?>

        <form method="post" action="edit.php?id=<?= e($id) ?>" novalidate>
            <?= csrf_field() ?>
            <?php require __DIR__ . '/includes/product-form.php'; ?>
        </form>
    </section>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
