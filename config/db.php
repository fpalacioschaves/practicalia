<?php
// practicalia/lib/db.php
declare(strict_types=1);

/**
 * Detecta entorno (local vs production) y carga config opcional.
 * - Si existe ../config/env.php lo carga (puede definir $APP_ENV y/o $DATABASE_URL).
 * - Si NO existe: local si host es localhost/127.0.0.1/::1 o si estás en CLI; si no, production.
 */
$APP_ENV = null;
$DATABASE_URL = null;

$configPath = __DIR__ . '/../config/env.php';
if (file_exists($configPath)) {
    require $configPath; // puede definir $APP_ENV y/o $DATABASE_URL
}

if (!$APP_ENV) {
    $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? '';
    $isLocalHost = in_array($host, ['localhost', '127.0.0.1', '::1'], true);
    $isCli = (PHP_SAPI === 'cli');
    $APP_ENV = ($isLocalHost || $isCli) ? 'local' : 'production';
}

/** Helper: parsea mysql://user:pass@host:port/db?charset=utf8mb4 */
function parse_database_url(string $url): array {
    $parts = parse_url($url);
    if ($parts === false) throw new RuntimeException('DATABASE_URL no válida');

    $scheme = $parts['scheme'] ?? 'mysql';
    if ($scheme !== 'mysql') throw new RuntimeException('Solo mysql soportado');

    $host = $parts['host'] ?? '127.0.0.1';
    $port = (int)($parts['port'] ?? 3306);
    $user = $parts['user'] ?? 'root';
    $pass = $parts['pass'] ?? '';
    $dbname = ltrim($parts['path'] ?? '/practicalia', '/');
    parse_str($parts['query'] ?? '', $q);
    $charset = $q['charset'] ?? 'utf8mb4';

    return compact('host','port','user','pass','dbname','charset');
}

// === Config según entorno ===
if ($APP_ENV === 'production' && $DATABASE_URL) {
    // Producción con DATABASE_URL en config/env.php
    $cfg = parse_database_url($DATABASE_URL);
    $DB_HOST = $cfg['host'];
    $DB_PORT = $cfg['port'];
    $DB_USER = $cfg['user'];
    $DB_PASS = $cfg['pass'];
    $DB_NAME = $cfg['dbname'];
    $DB_CHARSET = $cfg['charset'];
} else {
    // Local por defecto (XAMPP típico)
    $DB_HOST = '127.0.0.1';
    $DB_PORT = 3306;   // OJO: en algunos XAMPP es 3307. Si te falla, cámbialo a 3307.
    $DB_USER = 'root';
    $DB_PASS = '';
    $DB_NAME = 'practicalia';
    $DB_CHARSET = 'utf8mb4';
}

$dsn = "mysql:host={$DB_HOST};port={$DB_PORT};dbname={$DB_NAME};charset={$DB_CHARSET}";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $DB_USER, $DB_PASS, $options);
} catch (Throwable $e) {
    if ($APP_ENV === 'local') {
        // En local, muestra el error exacto para depurar
        die('Error de conexión PDO: ' . $e->getMessage() . "\nDSN: " . $dsn);
    }
    http_response_code(500);
    die('Error de conexión a base de datos.');
}
