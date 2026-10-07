<?php
require_once __DIR__ . '/../includes/init.php';

if (!isAdmin()) {
    jsonResponse(['success' => false, 'message' => 'Admin only.'], 403);
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Invalid request method.'], 405);
}

$postId = (int)($_POST['post_id'] ?? 0);
if ($postId <= 0) {
    jsonResponse(['success' => false, 'message' => 'Invalid post.'], 400);
}

$stmt = $pdo->prepare('DELETE FROM admin_posts WHERE id = ?');
$stmt->execute([$postId]);

jsonResponse(['success' => true]);
