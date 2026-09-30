<?php
declare(strict_types=1);
require_once __DIR__ . '/common.php';
header('Content-Type: application/json; charset=utf-8');
try {
    $pdo = db();
    $checks = [];
    $checks['database'] = 'connected';
    $checks['items_table'] = (bool)$pdo->query("SELECT to_regclass('public.items')")->fetchColumn();
    $checks['users_table'] = (bool)$pdo->query("SELECT to_regclass('public.users')")->fetchColumn();
    if ($checks['items_table']) {
        $checks['item_count'] = (int)$pdo->query("SELECT COUNT(*) FROM items")->fetchColumn();
        $checks['available_count'] = (int)$pdo->query("SELECT COUNT(*) FROM items WHERE COALESCE(NULLIF(LOWER(status),''),'available')='available'")->fetchColumn();
    }
    json_response(true, 'Items API diagnostic passed.', ['checks' => $checks]);
} catch (Throwable $e) {
    error_log('items-debug.php ERROR: ' . $e->getMessage());
    json_response(false, 'Items diagnostic failed: ' . $e->getMessage(), [], 500);
}
