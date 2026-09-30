<?php
require_once __DIR__ . '/common.php';
$userId = current_user_id();
json_response(true, $userId ? 'Authenticated.' : 'Not authenticated.', [
    'authenticated' => $userId !== null,
    'user_id' => $userId
]);
