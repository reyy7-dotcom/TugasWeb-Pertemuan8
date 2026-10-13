<?php

declare(strict_types=1);

require __DIR__ . '/config/database.php';
require __DIR__ . '/includes/functions.php';

start_secure_session();

try {
    $statement = db()->query(
        'SELECT products.id, products.name, products.price, products.stock, products.created_at,
                categories.name AS category_name
         FROM products
         INNER JOIN categories ON products.category_id = categories.id
         ORDER BY products.created_at DESC, products.id DESC'
    );
    $products = $statement->fetchAll();
} catch (Throwable $exception) {
    report_exception($exception);
    render_error_page(500, 'Data tidak dapat dimuat', 'Terjadi kesalahan. Silakan coba kembali.');
}

$flash = pull_flash();
$pageTitle = 'Inventaris Barang';
$activePage = 'products';
require __DIR__ . '/includes/header.php';
?>
<main id="main-content" class="page-shell main-content">
    <?php if ($flash !== null): ?>
        <div class="alert alert-<?= e($flash['type']) ?>" role="status">
            <span><?= e($flash['message']) ?></span>
            <button class="alert-close" type="button" aria-label="Tutup pesan">&times;</button>
        </div>
    <?php endif; ?>

    <section class="page-heading">
        <div>
            <p class="section-label">Data inventaris</p>
            <h1>Inventaris Barang</h1>
            <p>Kelola data barang dan kategori inventaris.</p>
        </div>
        <a class="button button-primary" href="create.php"><span aria-hidden="true">+</span> Tambah Barang</a>
    </section>

    <?php if ($products === []): ?>
        <section class="empty-state" aria-labelledby="empty-title">
            <div class="empty-icon" aria-hidden="true">I</div>
            <h2 id="empty-title">Belum ada barang</h2>
            <p>Tambahkan barang pertama untuk mulai mengelola inventaris.</p>
            <a class="button button-primary" href="create.php"><span aria-hidden="true">+</span> Tambah Barang</a>
        </section>
    <?php else: ?>
        <section class="table-panel" aria-labelledby="product-list-title">
            <div class="table-panel-header">
                <div>
                    <h2 id="product-list-title">Daftar barang</h2>
                    <p><?= e(count($products)) ?> barang tercatat</p>
                </div>
            </div>
            <div class="table-wrap">
                <table>
                    <thead>
                    <tr>
                        <th scope="col">Nama Barang</th>
                        <th scope="col">Kategori</th>
                        <th scope="col">Harga</th>
                        <th scope="col">Stok</th>
                        <th scope="col">Ditambahkan</th>
                        <th scope="col" class="actions-heading">Aksi</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($products as $product): ?>
                        <tr>
                            <td data-label="Nama Barang"><strong><?= e($product['name']) ?></strong></td>
                            <td data-label="Kategori"><span class="category-badge"><?= e($product['category_name']) ?></span></td>
                            <td data-label="Harga" class="numeric-value"><?= e(format_rupiah($product['price'])) ?></td>
                            <td data-label="Stok" class="numeric-value"><?= e($product['stock']) ?></td>
                            <td data-label="Ditambahkan"><?= e(format_date($product['created_at'])) ?></td>
                            <td data-label="Aksi">
                                <div class="row-actions">
                                    <a class="button button-small button-edit" href="edit.php?id=<?= e($product['id']) ?>">Edit</a>
                                    <form class="delete-form" method="post" action="delete.php" data-product-name="<?= e($product['name']) ?>">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id" value="<?= e($product['id']) ?>">
                                        <button class="button button-small button-delete" type="submit">Hapus</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    <?php endif; ?>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
