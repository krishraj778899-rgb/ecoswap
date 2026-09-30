<?php
declare(strict_types=1);
require_once __DIR__ . '/common.php';

try {
    $pdo = db();
    $search = trim((string)($_GET['search'] ?? ''));
    $category = trim((string)($_GET['category'] ?? ''));

    // Keep the listing endpoint independent of login. Anyone can browse items.
    $sql = "SELECT
                i.id,
                i.user_id,
                i.name,
                i.category,
                i.item_condition,
                i.description,
                i.image,
                i.image_data,
                i.image_mime,
                i.phone,
                i.location,
                COALESCE(NULLIF(i.status,''),'available') AS status,
                i.created_at,
                COALESCE(u.name, 'EcoSwap User') AS owner_name
            FROM items i
            LEFT JOIN users u ON u.id = i.user_id
            WHERE COALESCE(NULLIF(LOWER(i.status),''),'available') = 'available'";
    $params = [];

    if ($search !== '') {
        $sql .= " AND (
            COALESCE(i.name,'') ILIKE ? OR
            COALESCE(i.description,'') ILIKE ? OR
            COALESCE(i.location,'') ILIKE ?
        )";
        $like = '%' . $search . '%';
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
    }

    if ($category !== '' && strtolower($category) !== 'all') {
        $sql .= ' AND LOWER(COALESCE(i.category,\'\')) = LOWER(?)';
        $params[] = $category;
    }

    $sql .= ' ORDER BY i.created_at DESC NULLS LAST, i.id DESC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $items = [];
    foreach ($rows as $row) {
        $items[] = serialize_item($row);
    }

    json_response(true, 'Items loaded successfully.', ['items' => $items, 'count' => count($items)]);
} catch (Throwable $e) {
    error_log('items.php ERROR: ' . $e->getMessage());
    // Return a safe but useful server error so the frontend always receives valid JSON.
    json_response(false, 'Unable to load items. Database query failed.', [], 500);
}
