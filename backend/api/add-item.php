<?php
require_once __DIR__ . '/common.php';
try {
    $userId = require_user();
    $pdo = db();
    $name = trim((string)($_POST['name'] ?? ''));
    $category = trim((string)($_POST['category'] ?? ''));
    $condition = trim((string)($_POST['condition'] ?? ''));
    $description = trim((string)($_POST['description'] ?? ''));
    $phone = trim((string)($_POST['phone'] ?? ''));
    $location = trim((string)($_POST['location'] ?? ''));

    if ($name === '' || $category === '' || $condition === '' || $phone === '' || $location === '') {
        json_response(false, 'Please fill all required fields.', [], 400);
    }
    if (!preg_match('/^[0-9+()\-\s]{7,20}$/', $phone)) json_response(false, 'Enter a valid phone number.', [], 400);

    $imageData = null; $mime = null;
    if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) json_response(false, 'Please choose an item image.', [], 400);
    if ($_FILES['image']['size'] > 5 * 1024 * 1024) json_response(false, 'Image must be less than 5 MB.', [], 400);

    $tmp = $_FILES['image']['tmp_name'];
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($tmp);
    $allowed = ['image/jpeg','image/png','image/webp'];
    if (!in_array($mime, $allowed, true)) json_response(false, 'Only JPG, PNG and WEBP images are allowed.', [], 400);
    $imageData = file_get_contents($tmp);
    if ($imageData === false) json_response(false, 'Unable to read the image.', [], 400);

    $stmt = $pdo->prepare('INSERT INTO items (user_id,name,category,item_condition,description,image_data,image_mime,phone,location) VALUES (?,?,?,?,?,?,?,?,?) RETURNING id');
    $stmt->bindValue(1, $userId, PDO::PARAM_INT);
    $stmt->bindValue(2, $name);
    $stmt->bindValue(3, $category);
    $stmt->bindValue(4, $condition);
    $stmt->bindValue(5, $description);
    $stmt->bindValue(6, $imageData, PDO::PARAM_LOB);
    $stmt->bindValue(7, $mime);
    $stmt->bindValue(8, $phone);
    $stmt->bindValue(9, $location);
    $stmt->execute();
    $id = (int)$stmt->fetchColumn();
    json_response(true, 'Item added successfully.', ['item_id' => $id], 201);
} catch (Throwable $e) {
    error_log('add-item.php: ' . $e->getMessage());
    json_response(false, 'Unable to add item. ' . ($e instanceof RuntimeException ? $e->getMessage() : ''), [], 500);
}
