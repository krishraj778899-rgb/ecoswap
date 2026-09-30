<?php

header("Content-Type: application/json; charset=UTF-8");
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
        "message" => "Please login before adding an item."
    ]);

    exit;
}

$userId = $_SESSION["user_id"];


/* =========================================
   GET FORM DATA
========================================= */

$name = trim($_POST["name"] ?? "");
$category = trim($_POST["category"] ?? "");
$condition = trim($_POST["condition"] ?? "");
$description = trim($_POST["description"] ?? "");


/* =========================================
   VALIDATION
========================================= */

if ($name === "" || $category === "" || $condition === "") {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Name, category and condition are required."
    ]);

    exit;
}


/* =========================================
   ALLOWED CATEGORIES
========================================= */

$allowedCategories = [
    "Electronics",
    "Fashion",
    "Books",
    "Furniture",
    "Sports",
    "Others"
];

if (!in_array($category, $allowedCategories, true)) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Invalid category."
    ]);

    exit;
}


/* =========================================
   ALLOWED CONDITIONS
========================================= */

$allowedConditions = [
    "New",
    "Like New",
    "Good",
    "Fair"
];

if (!in_array($condition, $allowedConditions, true)) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Invalid item condition."
    ]);

    exit;
}


/* =========================================
   IMAGE VALIDATION
========================================= */

$imageName = null;

if (isset($_FILES["image"]) && $_FILES["image"]["error"] !== UPLOAD_ERR_NO_FILE) {

    $image = $_FILES["image"];

    if ($image["error"] !== UPLOAD_ERR_OK) {

        http_response_code(400);

        echo json_encode([
            "success" => false,
            "message" => "Image upload failed."
        ]);

        exit;
    }


    /* Maximum 5 MB */

    if ($image["size"] > 5 * 1024 * 1024) {

        http_response_code(400);

        echo json_encode([
            "success" => false,
            "message" => "Image size must be less than 5 MB."
        ]);

        exit;
    }


    /* Detect MIME type */

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $image["tmp_name"]);
    finfo_close($finfo);


    $allowedMimeTypes = [
        "image/jpeg" => "jpg",
        "image/png" => "png",
        "image/webp" => "webp"
    ];


    if (!isset($allowedMimeTypes[$mimeType])) {

        http_response_code(400);

        echo json_encode([
            "success" => false,
            "message" => "Only JPG, PNG and WEBP images are allowed."
        ]);

        exit;
    }


    /* =========================================
       CREATE UNIQUE IMAGE NAME
    ========================================= */

    $extension = $allowedMimeTypes[$mimeType];

    $imageName = uniqid("item_", true) . "." . $extension;


    /* =========================================
       UPLOAD FOLDER
    ========================================= */

    $uploadFolder = dirname(__DIR__) . "/uploads/";

    if (!is_dir($uploadFolder)) {

        mkdir($uploadFolder, 0777, true);
    }


    $uploadPath = $uploadFolder . $imageName;


    /* =========================================
       MOVE IMAGE
    ========================================= */

    if (!move_uploaded_file($image["tmp_name"], $uploadPath)) {

        http_response_code(500);

        echo json_encode([
            "success" => false,
            "message" => "Unable to save uploaded image."
        ]);

        exit;
    }
}


/* =========================================
   INSERT ITEM INTO DATABASE
========================================= */

try {

    $stmt = $pdo->prepare("
        INSERT INTO items
        (
            user_id,
            name,
            category,
            item_condition,
            description,
            image,
            status
        )
        VALUES
        (
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            'available'
        )
    ");

    $stmt->execute([
        $userId,
        $name,
        $category,
        $condition,
        $description,
        $imageName
    ]);


    $itemId = $pdo->lastInsertId();


    /* =========================================
       RESPONSE
    ========================================= */

    http_response_code(201);

    echo json_encode([
        "success" => true,
        "message" => "Item added successfully.",
        "item" => [
            "id" => (int)$itemId,
            "name" => $name,
            "category" => $category,
            "condition" => $condition,
            "description" => $description,
            "image" => $imageName,
            "status" => "available"
        ]
    ]);

} catch (PDOException $e) {

    /* Delete image if database insert fails */

    if ($imageName !== null) {

        $uploadedFile = dirname(__DIR__) . "/uploads/" . $imageName;

        if (file_exists($uploadedFile)) {
            unlink($uploadedFile);
        }
    }


    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Unable to add item.",
        "error" => $e->getMessage()
    ]);
}

?>