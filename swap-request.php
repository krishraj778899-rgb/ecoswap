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

session_start();


/* =========================================
   CHECK LOGIN
========================================= */

if (!isset($_SESSION["user_id"])) {

    http_response_code(401);

    echo json_encode([
        "success" => false,
        "message" => "Please login before sending a swap request."
    ]);

    exit;
}

$requesterId = (int) $_SESSION["user_id"];


/* =========================================
   GET REQUEST DATA
========================================= */

$data = json_decode(
    file_get_contents("php://input"),
    true
);

$itemId = (int) ($data["item_id"] ?? 0);
$message = trim($data["message"] ?? "");


/* =========================================
   VALIDATE ITEM ID
========================================= */

if ($itemId <= 0) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Invalid item ID."
    ]);

    exit;
}


/* =========================================
   GET ITEM + OWNER
========================================= */

try {

    $stmt = $pdo->prepare("
        SELECT
            id,
            user_id,
            name,
            status
        FROM items
        WHERE id = ?
        LIMIT 1
    ");

    $stmt->execute([$itemId]);

    $item = $stmt->fetch();


    /* =========================================
       ITEM NOT FOUND
    ========================================= */

    if (!$item) {

        http_response_code(404);

        echo json_encode([
            "success" => false,
            "message" => "Item not found."
        ]);

        exit;
    }


    $ownerId = (int) $item["user_id"];


    /* =========================================
       PREVENT SELF REQUEST
    ========================================= */

    if ($ownerId === $requesterId) {

        http_response_code(400);

        echo json_encode([
            "success" => false,
            "message" => "You cannot request your own item."
        ]);

        exit;
    }


    /* =========================================
       CHECK ITEM STATUS
    ========================================= */

    if ($item["status"] !== "available") {

        http_response_code(400);

        echo json_encode([
            "success" => false,
            "message" => "This item is no longer available."
        ]);

        exit;
    }


    /* =========================================
       CHECK DUPLICATE REQUEST
    ========================================= */

    $stmt = $pdo->prepare("
        SELECT id
        FROM swap_requests
        WHERE item_id = ?
        AND requester_id = ?
        AND status = 'pending'
        LIMIT 1
    ");

    $stmt->execute([
        $itemId,
        $requesterId
    ]);

    $existingRequest = $stmt->fetch();


    if ($existingRequest) {

        http_response_code(409);

        echo json_encode([
            "success" => false,
            "message" => "You already have a pending request for this item."
        ]);

        exit;
    }


    /* =========================================
       CREATE SWAP REQUEST
    ========================================= */

    $stmt = $pdo->prepare("
        INSERT INTO swap_requests
        (
            item_id,
            requester_id,
            owner_id,
            message,
            status
        )
        VALUES
        (
            ?,
            ?,
            ?,
            ?,
            'pending'
        )
    ");

    $stmt->execute([
        $itemId,
        $requesterId,
        $ownerId,
        $message
    ]);


    $requestId = $pdo->lastInsertId();


    /* =========================================
       RESPONSE
    ========================================= */

    http_response_code(201);

    echo json_encode([
        "success" => true,
        "message" => "Swap request sent successfully.",
        "request" => [
            "id" => (int) $requestId,
            "item_id" => $itemId,
            "item_name" => $item["name"],
            "status" => "pending"
        ]
    ]);

} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Unable to send swap request.",
        "error" => $e->getMessage()
    ]);
}

?>