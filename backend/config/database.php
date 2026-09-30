<?php
declare(strict_types=1);

/*
 * EcoSwap - Neon PostgreSQL connection.
 *
 * Set DATABASE_URL in Render Environment Variables to the Neon
 * PostgreSQL connection string from Neon Console.
 *
 * Example:
 * postgresql://USER:PASSWORD@HOST/DBNAME?sslmode=require
 */

$databaseUrl = getenv('DATABASE_URL');

if (!$databaseUrl) {
    http_response_code(500);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode([
        'success' => false,
        'message' => 'Database is not configured. Set DATABASE_URL in Render Environment Variables.'
    ]);
    exit;
}

try {
    $parts = parse_url($databaseUrl);

    if ($parts === false || empty($parts['host']) || empty($parts['path'])) {
        throw new RuntimeException('Invalid Neon DATABASE_URL.');
    }

    $host = $parts['host'];
    $port = $parts['port'] ?? 5432;
    $dbname = ltrim($parts['path'], '/');
    $username = isset($parts['user']) ? urldecode($parts['user']) : '';
    $password = isset($parts['pass']) ? urldecode($parts['pass']) : '';

    if ($username === '' || $password === '') {
        throw new RuntimeException('DATABASE_URL is missing database credentials.');
    }

    // Neon requires an SSL connection for normal production usage.
    $dsn = "pgsql:host={$host};port={$port};dbname={$dbname};sslmode=require";

    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    header('Content-Type: application/json; charset=UTF-8');

    echo json_encode([
        'success' => false,
        'message' => 'Neon database connection failed.',
        'error' => getenv('APP_DEBUG') === '1'
            ? $e->getMessage()
            : 'Check DATABASE_URL and Neon database availability.'
    ]);

    exit;
}
?>
