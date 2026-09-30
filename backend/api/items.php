<?php
require_once __DIR__ . '/common.php';
try {
    $pdo = db();
    $search = trim((string)($_GET['search'] ?? ''));
    $category = trim((string)($_GET['category'] ?? ''));

    $sql = "SELECT i.id,i.user_id,i.name,i.category,i.item_condition,i.description,i.image,i.image_data,i.image_mime,i.phone,i.location,i.status,i.created_at,u.name AS owner_name
            FROM items i JOIN users u ON u.id=i.user_id WHERE i.status='available'";
    $params = [];
    if ($search !== '') {
        $sql .= " AND (i.name ILIKE ? OR COALESCE(i.description,'') ILIKE ? OR COALESCE(i.location,'') ILIKE ?)";
        $like = '%' . $search . '%'; $params[] = $like; $params[] = $like; $params[] = $like;
    }
    if ($category !== '' && strtolower($category) !== 'all') { $sql .= ' AND i.category = ?'; $params[] = $category; }
    $sql .= ' ORDER BY i.created_at DESC, i.id DESC';

    $stmt = $pdo->prepare($sql); $stmt->execute($params);
    $items = array_map('serialize_item', $stmt->fetchAll());
    json_response(true, '', ['items' => $items]);
} catch (Throwable $e) {
    error_log('items.php: ' . $e->getMessage());
    json_response(false, 'Unable to load items.', [], 500);
}
