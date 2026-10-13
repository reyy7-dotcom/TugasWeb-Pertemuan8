<?php

declare(strict_types=1);

require dirname(__DIR__) . '/config/database.php';

$baseUrl = rtrim(getenv('APP_URL') ?: 'http://127.0.0.1:8000', '/');
$pdo = db();
$curl = curl_init();
$passed = 0;
$failed = 0;
$testMarker = bin2hex(random_bytes(4));
$createdName = "<script>alert('xss')</script> HTTP {$testMarker}";
$updatedName = "Keyboard HTTP {$testMarker}";

curl_setopt_array($curl, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_MAXREDIRS => 5,
    CURLOPT_COOKIEFILE => '',
    CURLOPT_TIMEOUT => 10,
]);

/** @return array{status: int, body: string} */
function request(CurlHandle $curl, string $baseUrl, string $path, string $method = 'GET', array $data = []): array
{
    curl_setopt($curl, CURLOPT_URL, $baseUrl . $path);

    if ($method === 'POST') {
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
    } else {
        curl_setopt($curl, CURLOPT_POSTFIELDS, null);
        curl_setopt($curl, CURLOPT_POST, false);
        curl_setopt($curl, CURLOPT_HTTPGET, true);
    }

    $body = curl_exec($curl);
    if ($body === false) {
        throw new RuntimeException(curl_error($curl));
    }

    return [
        'status' => (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE),
        'body' => $body,
    ];
}

function token_from(string $body): string
{
    if (!preg_match('/name="csrf_token" value="([^"]+)"/', $body, $matches)) {
        throw new RuntimeException('Token CSRF tidak ditemukan.');
    }

    return html_entity_decode($matches[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

function assert_http(bool $condition, string $description): void
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

try {
    $createPage = request($curl, $baseUrl, '/create.php');
    assert_http($createPage['status'] === 200, 'Halaman create dapat dibuka');
    $token = token_from($createPage['body']);

    $invalid = request($curl, $baseUrl, '/create.php', 'POST', [
        'csrf_token' => $token,
        'name' => '',
        'category_id' => '999999',
        'price' => '-1',
        'stock' => '-1',
    ]);
    assert_http($invalid['status'] === 200, 'Input invalid tidak menyebabkan warning atau redirect');
    assert_http(str_contains($invalid['body'], 'Nama barang wajib diisi.'), 'Error nama kosong tampil');
    assert_http(str_contains($invalid['body'], 'Pilih kategori yang valid.'), 'Error kategori invalid tampil');
    assert_http(str_contains($invalid['body'], 'Harga harus berupa angka'), 'Harga negatif ditolak');
    assert_http(str_contains($invalid['body'], 'Stok harus berupa bilangan bulat'), 'Stok negatif ditolak');

    $validCreate = request($curl, $baseUrl, '/create.php', 'POST', [
        'csrf_token' => $token,
        'name' => $createdName,
        'category_id' => '1',
        'price' => '250000.50',
        'stock' => '11',
    ]);
    assert_http($validCreate['status'] === 200, 'Create berhasil dan redirect ke index');
    assert_http(str_contains($validCreate['body'], 'Barang berhasil ditambahkan.'), 'Flash create tampil');
    assert_http(str_contains($validCreate['body'], '&lt;script&gt;alert(&#039;xss&#039;)&lt;/script&gt;'), 'Payload XSS tampil sebagai teks');
    assert_http(!str_contains($validCreate['body'], $createdName), 'Payload XSS mentah tidak tampil');

    $find = $pdo->prepare('SELECT id FROM products WHERE name = :name LIMIT 1');
    $find->execute(['name' => $createdName]);
    $productId = (int) $find->fetchColumn();
    assert_http($productId > 0, 'Data create tersimpan di MySQL');

    $editPage = request($curl, $baseUrl, '/edit.php?id=' . $productId);
    assert_http($editPage['status'] === 200, 'Halaman edit data existing dapat dibuka');
    $editToken = token_from($editPage['body']);

    $updated = request($curl, $baseUrl, '/edit.php?id=' . $productId, 'POST', [
        'csrf_token' => $editToken,
        'name' => $updatedName,
        'category_id' => '2',
        'price' => '99000.50',
        'stock' => '3',
    ]);
    assert_http(str_contains($updated['body'], 'Barang berhasil diperbarui.'), 'Update berhasil dan flash tampil');

    $verify = $pdo->prepare('SELECT category_id, name, price, stock FROM products WHERE id = :id');
    $verify->execute(['id' => $productId]);
    $row = $verify->fetch();
    assert_http(
        $row !== false
        && (int) $row['category_id'] === 2
        && $row['name'] === $updatedName
        && (int) $row['stock'] === 3,
        'Semua field update tersimpan'
    );

    $invalidId = request($curl, $baseUrl, '/edit.php?id=abc');
    assert_http($invalidId['status'] === 400 && str_contains($invalidId['body'], 'ID barang tidak valid'), 'ID non-numerik menghasilkan halaman 400');

    $missingId = request($curl, $baseUrl, '/edit.php?id=999999999');
    assert_http($missingId['status'] === 404 && str_contains($missingId['body'], 'Barang tidak ditemukan'), 'ID tidak ditemukan menghasilkan halaman 404');

    $deleteGet = request($curl, $baseUrl, '/delete.php');
    assert_http($deleteGet['status'] === 405, 'DELETE melalui GET ditolak');

    $deleted = request($curl, $baseUrl, '/delete.php', 'POST', [
        'csrf_token' => $editToken,
        'id' => (string) $productId,
    ]);
    assert_http(str_contains($deleted['body'], 'Barang berhasil dihapus.'), 'Delete POST berhasil dan flash tampil');

    $verify->execute(['id' => $productId]);
    assert_http($verify->fetch() === false, 'Data benar-benar terhapus dari MySQL');
} catch (Throwable $exception) {
    $failed++;
    fwrite(STDERR, '[FAIL] ' . $exception->getMessage() . PHP_EOL);
} finally {
    $cleanup = $pdo->prepare('DELETE FROM products WHERE name IN (:created_name, :updated_name)');
    $cleanup->execute(['created_name' => $createdName, 'updated_name' => $updatedName]);
    curl_close($curl);
}

echo "\n{$passed} test HTTP lulus, {$failed} test gagal.\n";
exit($failed === 0 ? 0 : 1);
