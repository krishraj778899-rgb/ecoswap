<?php
require_once __DIR__ . '/common.php';
try {
    $pdo = db();
    $data = json_input();
    $name = trim((string)($data['name'] ?? ''));
    $email = strtolower(trim((string)($data['email'] ?? '')));
    $password = (string)($data['password'] ?? '');

    if ($name === '' || $email === '' || $password === '') json_response(false, 'Please fill all fields.', [], 400);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) json_response(false, 'Enter a valid email address.', [], 400);
    if (strlen($password) < 6) json_response(false, 'Password must be at least 6 characters.', [], 400);

    $check = $pdo->prepare('SELECT id FROM users WHERE LOWER(email) = LOWER(?) LIMIT 1');
    $check->execute([$email]);
    if ($check->fetch()) json_response(false, 'An account with this email already exists.', [], 409);

    $stmt = $pdo->prepare('INSERT INTO users (name,email,password) VALUES (?,?,?) RETURNING id,name,email,created_at');
    $stmt->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT)]);
    $user = $stmt->fetch();
    json_response(true, 'Account created successfully.', ['user' => $user], 201);
} catch (Throwable $e) {
    error_log('register.php: ' . $e->getMessage());
    json_response(false, 'Registration failed. Please check the database connection.', [], 500);
}
