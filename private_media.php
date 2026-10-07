<?php
require_once __DIR__ . '/includes/init.php';
requireLogin();

$postId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$postId) {
    http_response_code(404);
    exit;
}

$stmt = $pdo->prepare('SELECT user_id, media_path, media_type FROM user_posts WHERE id = ?');
$stmt->execute([$postId]);
$post = $stmt->fetch();

if (!$post || (!isAdmin() && (int)$post['user_id'] !== (int)currentUserId())) {
    http_response_code(404);
    exit;
}

sendUploadedMedia($post['media_path'], $post['media_type'], 'uploads/user_posts');