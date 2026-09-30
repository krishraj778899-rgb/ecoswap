<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/database.php';
header('Content-Type: application/json; charset=utf-8');
try {
    db();
    echo json_encode([
        'success' => true,
        'database' => 'neon',
        'message' => 'Neon database connection is working.'
    ], JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    http_response_code(500);
    error_log('backend/api/health.php: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'database' => 'neon',
        'message' => 'Neon database connection/setup failed. Check Render DATABASE_URL and deployment logs.'
    ], JSON_UNESCAPED_SLASHES);
}
