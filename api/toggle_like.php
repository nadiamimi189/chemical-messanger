<?php
require_once __DIR__ . '/../includes/init.php';

if (!isLoggedIn()) {
    jsonResponse(['success' => false, 'message' => 'Not logged in.'], 401);
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Invalid request method.'], 405);
}

$postId = (int)($_POST['post_id'] ?? 0);
$userId = currentUserId();

if ($postId <= 0) {
    jsonResponse(['success' => false, 'message' => 'Invalid post.'], 400);
}

$check = $pdo->prepare('SELECT id FROM post_likes WHERE post_id = ? AND user_id = ?');
$check->execute([$postId, $userId]);
$existing = $check->fetch();

if ($existing) {
    $del = $pdo->prepare('DELETE FROM post_likes WHERE id = ?');
    $del->execute([$existing['id']]);
    $liked = false;
} else {
    $ins = $pdo->prepare('INSERT INTO post_likes (post_id, user_id) VALUES (?, ?)');
    $ins->execute([$postId, $userId]);
    $liked = true;
}

$countStmt = $pdo->prepare('SELECT COUNT(*) AS c FROM post_likes WHERE post_id = ?');
$countStmt->execute([$postId]);
$count = (int)$countStmt->fetch()['c'];

jsonResponse(['success' => true, 'liked' => $liked, 'count' => $count]);
