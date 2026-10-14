<?php
/**
 * Database Connection & Environment Config
 * Sistem Manager Hosting
 */

// Set default timezone Asia/Jakarta (WIB)
date_default_timezone_set('Asia/Jakarta');

/**
 * Bulletproof Environment Loader
 * Supports .env direct parsing even when putenv/getenv is disabled on shared hosting
 */
function getEnvValue(string $key, $default = '') {
    static $envData = null;

    if ($envData === null) {
        $envData = [];
        $path = __DIR__ . '/../.env';
        if (file_exists($path)) {
            $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line) || str_starts_with($line, '#')) {
                    continue;
                }
                if (str_contains($line, '=')) {
                    list($k, $v) = explode('=', $line, 2);
                    $k = trim($k);
                    $v = trim($v, " \t\n\r\0\x0B\"'");
                    $envData[$k] = $v;
                    $_ENV[$k] = $v;
                    $_SERVER[$k] = $v;
                    if (function_exists('putenv')) {
                        @putenv("$k=$v");
                    }
                }
            }
        }
    }

    if (isset($envData[$key])) {
        return $envData[$key];
    }
    if (isset($_ENV[$key])) {
        return $_ENV[$key];
    }
    if (isset($_SERVER[$key])) {
        return $_SERVER[$key];
    }
    if (function_exists('getenv')) {
        $val = @getenv($key);
        if ($val !== false) {
            return $val;
        }
    }
    return $default;
}

function getDBConnection(): PDO {
    static $pdo = null;

    if ($pdo === null) {
        $host = getEnvValue('DB_HOST', '127.0.0.1');
        $port = getEnvValue('DB_PORT', '3306');
        $dbname = getEnvValue('DB_NAME', 'manager_hosting');
        $user = getEnvValue('DB_USER', 'root');
        $pass = getEnvValue('DB_PASS', '');

        $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $pdo = new PDO($dsn, $user, $pass, $options);
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            die("Koneksi database gagal: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
        }
    }

    return $pdo;
}
