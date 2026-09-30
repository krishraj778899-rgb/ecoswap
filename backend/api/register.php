<?php

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: http://localhost");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(200);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);

    echo json_encode([
        "success" => false,
        "message" => "Only POST requests are allowed."
    ]);

    exit;
}

require_once "../config/database.php";

$data = json_decode(file_get_contents("php://input"), true);

$name = trim($data["name"] ?? "");
$email = trim($data["email"] ?? "");
$password = $data["password"] ?? "";


/* =========================================
   VALIDATION
========================================= */

if ($name === "" || $email === "" || $password === "") {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "All fields are required."
    ]);

    exit;
}


if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Please enter a valid email address."
    ]);

    exit;
}


if (strlen($password) < 6) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Password must be at least 6 characters."
    ]);

    exit;
}


/* =========================================
   CHECK EXISTING USER
========================================= */

try {

    $stmt = $pdo->prepare(
        "SELECT id FROM users WHERE email = ? LIMIT 1"
    );

    $stmt->execute([$email]);

    $existingUser = $stmt->fetch();

    if ($existingUser) {

        http_response_code(409);

        echo json_encode([
            "success" => false,
            "message" => "An account with this email already exists."
        ]);

        exit;
    }


    /* =========================================
       HASH PASSWORD
    ========================================= */

    $hashedPassword = password_hash(
        $password,
        PASSWORD_DEFAULT
    );


    /* =========================================
       INSERT USER
    ========================================= */

    $stmt = $pdo->prepare(
        "INSERT INTO users (name, email, password)
         VALUES (?, ?, ?)"
    );

    $stmt->execute([
        $name,
        $email,
        $hashedPassword
    ]);


    $userId = $pdo->lastInsertId();


    /* =========================================
       RESPONSE
    ========================================= */

    http_response_code(201);

    echo json_encode([
        "success" => true,
        "message" => "Registration successful.",
        "user" => [
            "id" => (int)$userId,
            "name" => $name,
            "email" => $email
        ]
    ]);

} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Registration failed.",
        "error" => $e->getMessage()
    ]);
}

?>