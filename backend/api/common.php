<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

function start_app_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) return;
    session_set_cookie_params([
        'httponly' => true,
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'samesite' => 'Lax',
        'path' => '/',
    ]);
    session_start();
}

function json_input(): array
{
    $raw = file_get_contents('php://input');
    if ($raw === false || trim($raw) === '') return [];
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function json_response(bool $success, string $message = '', array $extra = [], int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array_merge(['success' => $success, 'message' => $message], $extra), JSON_UNESCAPED_SLASHES);
    exit;
}

function current_user_id(): ?int
{
    start_app_session();
    return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
}

function require_user(): int
{
    $id = current_user_id();
    if (!$id) json_response(false, 'Please login first.', [], 401);
    return $id;
}

function safe_file_data(?string $data, ?string $mime): ?string
{
    if (!$data || !$mime) return null;
    return 'data:' . $mime . ';base64,' . base64_encode($data);
}

function serialize_item(array $row): array
{
    $image = null;
    if (isset($row['image_data']) && $row['image_data'] !== null) {
        $image = safe_file_data($row['image_data'], $row['image_mime'] ?? 'image/jpeg');
    } elseif (!empty($row['image'])) {
        $image = $row['image'];
    }

    unset($row['image_data'], $row['image_mime']);
    $row['image'] = $image;
    return $row;
}
