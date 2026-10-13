<?php

declare(strict_types=1);

require dirname(__DIR__) . '/includes/functions.php';

$passed = 0;
$failed = 0;

function check(bool $condition, string $description): void
{
    global $passed, $failed;

    if ($condition) {
        $passed++;
        echo "[PASS] {$description}\n";
        return;
    }

    $failed++;
    echo "[FAIL] {$description}\n";
}

$pdo = new PDO('sqlite::memory:', null, null, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);
$pdo->exec('CREATE TABLE categories (id INTEGER PRIMARY KEY, name TEXT NOT NULL UNIQUE)');
$pdo->exec("INSERT INTO categories (id, name) VALUES (1, 'Elektronik')");

check(e("<script>alert('xss')</script>") === '&lt;script&gt;alert(&#039;xss&#039;)&lt;/script&gt;', 'Output XSS disanitasi');
check(positive_id('12') === 12, 'ID numerik positif diterima');
check(positive_id('abc') === null, 'ID non-numerik ditolak');
check(positive_id('-1') === null, 'ID negatif ditolak');

[$validValues, $validErrors] = validate_product_input($pdo, [
    'name' => '  Keyboard  ',
    'category_id' => '1',
    'price' => '250000.50',
    'stock' => '10',
]);
check($validErrors === [], 'Input produk valid diterima');
check($validValues['name'] === 'Keyboard', 'Whitespace nama dibersihkan');

[, $invalidErrors] = validate_product_input($pdo, [
    'name' => '',
    'category_id' => '999',
    'price' => '-1',
    'stock' => '-2',
]);
check(isset($invalidErrors['name']), 'Nama kosong ditolak');
check(isset($invalidErrors['category_id']), 'Kategori yang tidak ada ditolak');
check(isset($invalidErrors['price']), 'Harga negatif ditolak');
check(isset($invalidErrors['stock']), 'Stok negatif ditolak');

[, $injectionErrors] = validate_product_input($pdo, [
    'name' => "' OR 1=1 --",
    'category_id' => "1 OR 1=1",
    'price' => '0 OR 1=1',
    'stock' => '0 OR 1=1',
]);
check(isset($injectionErrors['category_id'], $injectionErrors['price'], $injectionErrors['stock']), 'Payload SQL injection ditolak pada field terstruktur');
check(format_rupiah('250000.00') === 'Rp250.000', 'Format Rupiah tanpa desimal benar');
check(format_rupiah('250000.50') === 'Rp250.000,50', 'Format Rupiah dengan desimal benar');

echo "\n{$passed} test lulus, {$failed} test gagal.\n";
exit($failed === 0 ? 0 : 1);
