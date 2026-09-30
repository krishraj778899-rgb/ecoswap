<?php

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: GET, OPTIONS");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(200);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "GET") {

    http_response_code(405);

    echo json_encode([
        "success" => false,
        "message" => "Only GET requests are allowed."
    ]);

    exit;
}

require_once "../config/database.php";


/* =========================================
   GET SEARCH & CATEGORY
========================================= */

$search = trim($_GET["search"] ?? "");
$category = trim($_GET["category"] ?? "");


/* =========================================
   BASE QUERY
========================================= */

$sql = "
    SELECT
        items.id,
        items.name,
        items.category,
        items.item_condition,
        items.description,
        items.image,
        items.status,
        items.created_at,
        users.id AS owner_id,
        users.name AS owner_name
    FROM items
    INNER JOIN users
        ON items.user_id = users.id
    WHERE items.status = 'available'
";

$params = [];


/* =========================================
   SEARCH FILTER
========================================= */

if ($search !== "") {

    $sql .= "
        AND (
            items.name LIKE ?
            OR items.description LIKE ?
            OR items.category LIKE ?
        )
    ";

    $searchValue = "%" . $search . "%";

    $params[] = $searchValue;
    $params[] = $searchValue;
    $params[] = $searchValue;
}


/* =========================================
   CATEGORY FILTER
========================================= */

if ($category !== "" && strtolower($category) !== "all") {

    $sql .= " AND items.category = ? ";

    $params[] = $category;
}


/* =========================================
   SORTING
========================================= */

$sql .= "
    ORDER BY items.created_at DESC
";


/* =========================================
   EXECUTE QUERY
========================================= */

try {

    $stmt = $pdo->prepare($sql);

    $stmt->execute($params);

    $items = $stmt->fetchAll();


    /* =========================================
       IMAGE URL
    ========================================= */

    foreach ($items as &$item) {

        if (!empty($item["image"])) {

            $item["image"] = "/backend/uploads/" . $item["image"];

        } else {

            $item["image"] = null;
        }
    }

    unset($item);


    /* =========================================
       RESPONSE
    ========================================= */

    echo json_encode([
        "success" => true,
        "count" => count($items),
        "items" => $items
    ]);

} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Unable to fetch items.",
        "error" => $e->getMessage()
    ]);
}

?>