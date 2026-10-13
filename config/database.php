<?php

declare(strict_types=1);

function db(): PDO
{
    static $connection = null;

    if ($connection instanceof PDO) {
        return $connection;
    }

    $localConfigPath = __DIR__ . '/database.local.php';
    $localConfig = [];

    if (is_file($localConfigPath)) {
        $loadedConfig = require $localConfigPath;

        if (!is_array($loadedConfig)) {
            throw new RuntimeException('Konfigurasi database lokal harus berupa array.');
        }

        $localConfig = $loadedConfig;
    }

    $configValue = static function (array $environmentNames, string $localKey, string $default) use ($localConfig): string {
        foreach ($environmentNames as $environmentName) {
            $value = getenv($environmentName);

            if ($value !== false) {
                return $value;
            }
        }

        if (array_key_exists($localKey, $localConfig)) {
            return (string) $localConfig[$localKey];
        }

        return $default;
    };

    $host = $configValue(['DB_HOST'], 'DB_HOST', '127.0.0.1');
    $port = $configValue(['DB_PORT'], 'DB_PORT', '3306');
    $database = $configValue(['DB_DATABASE', 'DB_NAME'], 'DB_DATABASE', 'tr8_inventory');
    $username = $configValue(['DB_USERNAME', 'DB_USER'], 'DB_USERNAME', 'root');
    $password = $configValue(['DB_PASSWORD', 'DB_PASS'], 'DB_PASSWORD', '');

    $validatedPort = filter_var($port, FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 1, 'max_range' => 65535],
    ]);

    if ($host === '' || $database === '' || $username === '' || $validatedPort === false) {
        throw new RuntimeException('Konfigurasi koneksi database tidak valid.');
    }

    $dsn = "mysql:host={$host};port={$validatedPort};dbname={$database};charset=utf8mb4";

    $connection = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    return $connection;
}
