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
    echo json_encode(
        array_merge(['success' => $success, 'message' => $message], $extra),
        JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE
    );
    exit;
}

function auth_secret(): string
{
    $secret = getenv('APP_AUTH_SECRET');
    if ($secret !== false && trim($secret) !== '') return trim($secret);

    // Stable server-side fallback so no extra Render variable is required.
    $dbUrl = getenv('DATABASE_URL');
    if ($dbUrl !== false && trim($dbUrl) !== '') return hash('sha256', trim($dbUrl));

    return 'ecoswap-local-auth-secret-change-me';
}

function create_auth_token(int $userId): string
{
    $payload = base64_encode(json_encode([
        'uid' => $userId,
        'exp' => time() + (60 * 60 * 24 * 30)
    ], JSON_UNESCAPED_SLASHES));
    $payload = rtrim(strtr($payload, '+/', '-_'), '=');
    $sig = hash_hmac('sha256', $payload, auth_secret());
    return $payload . '.' . $sig;
}

function verify_auth_token(string $token): ?int
{
    $parts = explode('.', $token, 2);
    if (count($parts) !== 2) return null;
    [$payload, $signature] = $parts;
    $expected = hash_hmac('sha256', $payload, auth_secret());
    if (!hash_equals($expected, $signature)) return null;

    $raw = strtr($payload, '-_', '+/');
    $raw .= str_repeat('=', (4 - strlen($raw) % 4) % 4);
    $data = json_decode(base64_decode($raw) ?: '', true);
    if (!is_array($data)) return null;
    $uid = (int)($data['uid'] ?? 0);
    $exp = (int)($data['exp'] ?? 0);
    if ($uid <= 0 || $exp < time()) return null;
    return $uid;
}

function bearer_user_id(): ?int
{
    $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if ($header === '' && function_exists('getallheaders')) {
        $headers = getallheaders();
        $header = $headers['Authorization'] ?? $headers['authorization'] ?? '';
    }
    if (preg_match('/^Bearer\s+(.+)$/i', trim($header), $m)) {
        return verify_auth_token(trim($m[1]));
    }
    return null;
}

function current_user_id(): ?int
{
    $tokenUser = bearer_user_id();
    if ($tokenUser !== null) return $tokenUser;

    start_app_session();
    return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
}

function require_user(): int
{
    $id = current_user_id();
    if (!$id) json_response(false, 'Please login first.', [], 401);
    return $id;
}

function safe_file_data($data, ?string $mime): ?string
{
    if ($data === null || $data === false || !$mime) return null;
    if (is_resource($data)) {
        $data = stream_get_contents($data);
        if ($data === false) return null;
    }
    if (!is_string($data) || $data === '') return null;
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
