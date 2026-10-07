<?php
require_once __DIR__ . '/includes/init.php';
requireLogin();

$postId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$postId) {
    http_response_code(404);
    exit;
}

$stmt = $pdo->prepare("
    SELECT p.admin_id, p.media_path, p.media_type, u.role AS author_role
    FROM admin_posts p
    JOIN users u ON u.id = p.admin_id
    WHERE p.id = ?
");
$stmt->execute([$postId]);
$post = $stmt->fetch();

if (!$post || ($post['author_role'] !== 'admin' && !isAdmin() && (int)$post['admin_id'] !== (int)currentUserId())) {
    http_response_code(404);
    exit;
}

sendUploadedMedia($post['media_path'], $post['media_type'], 'uploads/posts');