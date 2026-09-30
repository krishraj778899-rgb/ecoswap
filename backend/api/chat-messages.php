<?php
require_once __DIR__ . '/common.php';
try {
    $userId = require_user(); $pdo = db();
    $conversationId = (int)($_GET['conversation_id'] ?? 0);
    if ($conversationId <= 0) json_response(false,'Invalid conversation.',[],400);
    $c=$pdo->prepare('SELECT c.id,c.item_id,c.owner_id,c.requester_id,c.status,i.name AS item_name,i.phone,i.location,owner.name AS owner_name,owner.email AS owner_email,requester.name AS requester_name FROM conversations c JOIN items i ON i.id=c.item_id JOIN users owner ON owner.id=c.owner_id JOIN users requester ON requester.id=c.requester_id WHERE c.id=? AND (c.owner_id=? OR c.requester_id=?) LIMIT 1');
    $c->execute([$conversationId,$userId,$userId]); $conv=$c->fetch();
    if(!$conv) json_response(false,'Conversation not found.',[],404);

    if($_SERVER['REQUEST_METHOD']==='POST'){
        $data=json_input(); $body=trim((string)($data['body']??''));
        $offer=$data['offer_price'] ?? null;
        if($body==='' && ($offer===null || $offer==='')) json_response(false,'Write a message or enter an offer.',[],400);
        $offerValue=null;
        if($offer!==null && $offer!==''){
            if(!is_numeric($offer) || (float)$offer<0) json_response(false,'Enter a valid offer price.',[],400);
            $offerValue=number_format((float)$offer,2,'.','');
        }
        $m=$pdo->prepare('INSERT INTO messages (conversation_id,sender_id,body,offer_price) VALUES (?,?,?,?) RETURNING id,created_at');
        $m->execute([$conversationId,$userId,$body,$offerValue]); $msg=$m->fetch();
        $pdo->prepare('UPDATE conversations SET updated_at=CURRENT_TIMESTAMP WHERE id=?')->execute([$conversationId]);
        json_response(true,'Message sent.',['message'=>array_merge($msg,['sender_id'=>$userId,'body'=>$body,'offer_price'=>$offerValue])],201);
    }

    $m=$pdo->prepare('SELECT m.id,m.sender_id,u.name AS sender_name,m.body,m.offer_price,m.created_at FROM messages m JOIN users u ON u.id=m.sender_id WHERE m.conversation_id=? ORDER BY m.created_at ASC,m.id ASC');
    $m->execute([$conversationId]);
    json_response(true,'',['conversation'=>$conv,'messages'=>$m->fetchAll(),'current_user_id'=>$userId]);
} catch(Throwable $e){ error_log('chat-messages.php: '.$e->getMessage()); json_response(false,'Unable to load chat.',[],500); }
