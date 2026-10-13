<?php

declare(strict_types=1);

require dirname(__DIR__) . '/config/database.php';

$pdo = db();
$pdo->beginTransaction();

try {
    $suffix = bin2hex(random_bytes(4));
    $categoryName = 'Test Kategori ' . $suffix;
    $injectionPayload = "' OR 1=1 --";

    $insertCategory = $pdo->prepare('INSERT INTO categories (name) VALUES (:name)');
    $insertCategory->execute(['name' => $categoryName]);
    $categoryId = (int) $pdo->lastInsertId();

    $insertProduct = $pdo->prepare(
        'INSERT INTO products (category_id, name, price, stock) VALUES (:category_id, :name, :price, :stock)'
    );
    $insertProduct->execute([
        'category_id' => $categoryId,
        'name' => $injectionPayload,
        'price' => '125000.00',
        'stock' => 7,
    ]);
    $productId = (int) $pdo->lastInsertId();

    $read = $pdo->prepare(
        'SELECT products.name, products.price, products.stock, categories.name AS category_name
         FROM products
         INNER JOIN categories ON products.category_id = categories.id
         WHERE products.id = :id'
    );
    $read->execute(['id' => $productId]);
    $product = $read->fetch();
    if (
        $product === false
        || $product['category_name'] !== $categoryName
        || $product['name'] !== $injectionPayload
    ) {
        throw new RuntimeException('CREATE atau READ JOIN gagal.');
    }

    $update = $pdo->prepare('UPDATE products SET name = :name, price = :price, stock = :stock WHERE id = :id');
    $update->execute(['name' => 'Produk Diperbarui', 'price' => '99000.50', 'stock' => 3, 'id' => $productId]);

    $verify = $pdo->prepare('SELECT name, price, stock FROM products WHERE id = :id');
    $verify->execute(['id' => $productId]);
    $updated = $verify->fetch();
    if ($updated === false || $updated['name'] !== 'Produk Diperbarui' || (int) $updated['stock'] !== 3) {
        throw new RuntimeException('UPDATE gagal.');
    }

    $delete = $pdo->prepare('DELETE FROM products WHERE id = :id');
    $delete->execute(['id' => $productId]);
    if ($delete->rowCount() !== 1) {
        throw new RuntimeException('DELETE gagal.');
    }

    $pdo->rollBack();
    echo "[PASS] CREATE\n[PASS] READ dengan JOIN\n[PASS] UPDATE\n[PASS] DELETE\n[PASS] Prepared statements\n";
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    fwrite(STDERR, '[FAIL] ' . $exception->getMessage() . PHP_EOL);
    exit(1);
}
