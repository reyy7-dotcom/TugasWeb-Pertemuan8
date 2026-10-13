<?php

declare(strict_types=1);

function start_secure_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $sessionPath = dirname(__DIR__) . '/storage/sessions';
    if (!is_dir($sessionPath) && !mkdir($sessionPath, 0700, true) && !is_dir($sessionPath)) {
        throw new RuntimeException('Direktori session tidak dapat dibuat.');
    }

    ini_set('session.use_strict_mode', '1');
    session_save_path($sessionPath);
    session_set_cookie_params([
        'httponly' => true,
        'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'samesite' => 'Lax',
    ]);
    if (!session_start()) {
        throw new RuntimeException('Session tidak dapat dimulai.');
    }
}

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirect(string $location): never
{
    header('Location: ' . $location);
    exit;
}

function set_flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

/** @return array{type: string, message: string}|null */
function pull_flash(): ?array
{
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);

    return is_array($flash) ? $flash : null;
}

function csrf_token(): string
{
    if (!isset($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function valid_csrf_token(mixed $token): bool
{
    return is_string($token)
        && isset($_SESSION['csrf_token'])
        && is_string($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

function positive_id(mixed $value): ?int
{
    if (!is_string($value) && !is_int($value)) {
        return null;
    }

    $id = filter_var($value, FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 1],
    ]);

    return $id === false ? null : $id;
}

/** @return list<array{id: int, name: string}> */
function fetch_categories(PDO $pdo): array
{
    $statement = $pdo->query('SELECT id, name FROM categories ORDER BY name ASC');

    return $statement->fetchAll();
}

function category_exists(PDO $pdo, int $categoryId): bool
{
    $statement = $pdo->prepare('SELECT 1 FROM categories WHERE id = :id LIMIT 1');
    $statement->execute(['id' => $categoryId]);

    return $statement->fetchColumn() !== false;
}

/**
 * @param array<string, mixed> $input
 * @return array{
 *   0: array{name: string, category_id: string, price: string, stock: string},
 *   1: array<string, string>
 * }
 */
function validate_product_input(PDO $pdo, array $input): array
{
    $values = [
        'name' => trim(is_string($input['name'] ?? null) ? $input['name'] : ''),
        'category_id' => trim(is_string($input['category_id'] ?? null) ? $input['category_id'] : ''),
        'price' => trim(is_string($input['price'] ?? null) ? $input['price'] : ''),
        'stock' => trim(is_string($input['stock'] ?? null) ? $input['stock'] : ''),
    ];
    $errors = [];

    if ($values['name'] === '') {
        $errors['name'] = 'Nama barang wajib diisi.';
    } elseif (mb_strlen($values['name']) > 150) {
        $errors['name'] = 'Nama barang maksimal 150 karakter.';
    }

    $categoryId = positive_id($values['category_id']);
    if ($categoryId === null || !category_exists($pdo, $categoryId)) {
        $errors['category_id'] = 'Pilih kategori yang valid.';
    }

    if ($values['price'] === '') {
        $errors['price'] = 'Harga wajib diisi.';
    } elseif (!preg_match('/^\d+(?:\.\d{1,2})?$/', $values['price'])) {
        $errors['price'] = 'Harga harus berupa angka dengan maksimal 2 desimal.';
    } elseif ((float) $values['price'] > 9999999999.99) {
        $errors['price'] = 'Harga melebihi batas yang diperbolehkan.';
    }

    if ($values['stock'] === '') {
        $errors['stock'] = 'Stok wajib diisi.';
    } elseif (!preg_match('/^\d+$/', $values['stock'])) {
        $errors['stock'] = 'Stok harus berupa bilangan bulat minimal 0.';
    } elseif ((float) $values['stock'] > 4294967295) {
        $errors['stock'] = 'Stok melebihi batas yang diperbolehkan.';
    }

    return [$values, $errors];
}

function format_rupiah(string|int|float $amount): string
{
    $number = (float) $amount;
    $decimals = floor($number) === $number ? 0 : 2;

    return 'Rp' . number_format($number, $decimals, ',', '.');
}

function format_date(string $date): string
{
    $timestamp = strtotime($date);

    return $timestamp === false ? '-' : date('d/m/Y', $timestamp);
}

function render_error_page(int $status, string $title, string $message): never
{
    http_response_code($status);
    $pageTitle = $title;
    $activePage = '';
    require __DIR__ . '/header.php';
    ?>
    <main id="main-content" class="page-shell error-page">
        <section class="error-panel" aria-labelledby="error-title">
            <span class="error-code"><?= e((string) $status) ?></span>
            <h1 id="error-title"><?= e($title) ?></h1>
            <p><?= e($message) ?></p>
            <a class="button button-primary" href="index.php">Kembali ke daftar barang</a>
        </section>
    </main>
    <?php
    require __DIR__ . '/footer.php';
    exit;
}

function report_exception(Throwable $exception): void
{
    error_log(sprintf(
        '[Inventra] %s in %s:%d',
        $exception->getMessage(),
        $exception->getFile(),
        $exception->getLine()
    ));
}
