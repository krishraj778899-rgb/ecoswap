<?php
require_once __DIR__ . '/common.php';
try {
    $requesterId = require_user();
    $pdo = db();
    $data = json_input();
    $itemId = (int)($data['item_id'] ?? 0);
    $message = trim((string)($data['message'] ?? ''));
    if ($itemId <= 0) json_response(false, 'Invalid item.', [], 400);

    $stmt = $pdo->prepare('SELECT id,user_id,status FROM items WHERE id=? LIMIT 1');
    $stmt->execute([$itemId]);
    $item = $stmt->fetch();
    if (!$item) json_response(false, 'Item not found.', [], 404);
    $ownerId = (int)$item['user_id'];
    if ($ownerId === $requesterId) json_response(false, 'You cannot request a swap for your own item.', [], 400);
    if ($item['status'] !== 'available') json_response(false, 'This item is no longer available.', [], 409);

    $pdo->beginTransaction();
    $check = $pdo->prepare('SELECT id FROM conversations WHERE item_id=? AND requester_id=? LIMIT 1');
    $check->execute([$itemId, $requesterId]);
    $conversationId = $check->fetchColumn();
    if (!$conversationId) {
        $c = $pdo->prepare('INSERT INTO conversations (item_id,owner_id,requester_id) VALUES (?,?,?) RETURNING id');
        $c->execute([$itemId,$ownerId,$requesterId]);
        $conversationId = (int)$c->fetchColumn();
    } else {
        $conversationId = (int)$conversationId;
        $pdo->prepare('UPDATE conversations SET status=\'open\',updated_at=CURRENT_TIMESTAMP WHERE id=?')->execute([$conversationId]);
    }

    $r = $pdo->prepare('INSERT INTO swap_requests (item_id,requester_id,owner_id,message) VALUES (?,?,?,?) RETURNING id');
    $r->execute([$itemId,$requesterId,$ownerId,$message]);
    $requestId = (int)$r->fetchColumn();

    if ($message !== '') {
        $m = $pdo->prepare('INSERT INTO messages (conversation_id,sender_id,body) VALUES (?,?,?)');
        $m->execute([$conversationId,$requesterId,$message]);
    }
    $pdo->commit();
    json_response(true, 'Swap chat opened.', ['conversation_id' => $conversationId, 'request_id' => $requestId]);
} catch (Throwable $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) $pdo->rollBack();
    error_log('swap-request.php: ' . $e->getMessage());
    json_response(false, 'Unable to open swap chat.', [], 500);
}
