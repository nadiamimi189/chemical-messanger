<?php
require_once __DIR__ . '/../includes/init.php';

if (!isAdmin()) {
    jsonResponse(['success' => false, 'message' => 'Admin only.'], 403);
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Invalid request method.'], 405);
}
if (!csrfCheck()) {
    jsonResponse(['success' => false, 'message' => 'Invalid request, please try again.'], 403);
}

$postId = (int)($_POST['post_id'] ?? 0);
$postType = $_POST['post_type'] ?? '';
if ($postId <= 0) {
    jsonResponse(['success' => false, 'message' => 'Invalid post.'], 400);
}

$postTables = [
    'admin' => 'admin_posts',
    'member' => 'user_posts',
    'legacy_member' => 'admin_posts',
    'private' => 'user_posts',
    'legacy_private' => 'admin_posts',
];
if (!isset($postTables[$postType])) {
    jsonResponse(['success' => false, 'message' => 'Invalid post type.'], 400);
}

$stmt = $pdo->prepare('DELETE FROM ' . $postTables[$postType] . ' WHERE id = ?');
$stmt->execute([$postId]);
if ($stmt->rowCount() === 0) {
    jsonResponse(['success' => false, 'message' => 'Post not found.'], 404);
}

jsonResponse(['success' => true]);
