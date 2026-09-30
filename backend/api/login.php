<?php
require_once __DIR__ . '/common.php';
try {
    $pdo = db();
    $data = json_input();
    $email = strtolower(trim((string)($data['email'] ?? '')));
    $password = (string)($data['password'] ?? '');
    if ($email === '' || $password === '') json_response(false, 'Please enter email and password.', [], 400);

    $stmt = $pdo->prepare('SELECT id,name,email,password FROM users WHERE LOWER(email)=LOWER(?) LIMIT 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    if (!$user || !password_verify($password, $user['password'])) json_response(false, 'Invalid email or password.', [], 401);

    start_app_session();
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int)$user['id'];
    $token = create_auth_token((int)$user['id']);
    unset($user['password']);
    json_response(true, 'Login successful.', ['user' => $user, 'auth_token' => $token]);
} catch (Throwable $e) {
    error_log('login.php: ' . $e->getMessage());
    json_response(false, 'Login failed. Please check the database connection.', [], 500);
}
