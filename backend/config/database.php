<?php

$databaseUrl = getenv('DATABASE_URL');

if (!$databaseUrl) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'DATABASE_URL is not configured.'
    ]);
    exit;
}

$parts = parse_url($databaseUrl);

if ($parts === false || empty($parts['host']) || empty($parts['user']) || !isset($parts['pass']) || empty($parts['path'])) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid DATABASE_URL.'
    ]);
    exit;
}

$host = $parts['host'];
$port = $parts['port'] ?? 5432;
$dbname = ltrim($parts['path'], '/');
$username = $parts['user'];
$password = $parts['pass'];

try {
    $dsn = "pgsql:host={$host};port={$port};dbname={$dbname};sslmode=require";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed.'
    ]);
    exit;
}
