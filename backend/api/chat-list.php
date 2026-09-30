<?php
require_once __DIR__ . '/common.php';
try {
    $userId = require_user(); $pdo = db();
    $sql = "SELECT c.id,c.item_id,c.updated_at,i.name AS item_name,i.image_data,i.image_mime,
                   owner.name AS owner_name, requester.name AS requester_name,
                   (SELECT body FROM messages m WHERE m.conversation_id=c.id ORDER BY m.created_at DESC,m.id DESC LIMIT 1) AS last_message
            FROM conversations c
            JOIN items i ON i.id=c.item_id
            JOIN users owner ON owner.id=c.owner_id
            JOIN users requester ON requester.id=c.requester_id
            WHERE c.owner_id=? OR c.requester_id=?
            ORDER BY c.updated_at DESC,c.id DESC";
    $stmt=$pdo->prepare($sql); $stmt->execute([$userId,$userId]);
    $rows=$stmt->fetchAll();
    foreach($rows as &$row){
        $row['image'] = isset($row['image_data']) ? safe_file_data($row['image_data'],$row['image_mime'] ?? 'image/jpeg') : null;
        unset($row['image_data'],$row['image_mime']);
    }
    json_response(true,'',['conversations'=>$rows]);
} catch(Throwable $e){ error_log('chat-list.php: '.$e->getMessage()); json_response(false,'Unable to load chats.',[],500); }
