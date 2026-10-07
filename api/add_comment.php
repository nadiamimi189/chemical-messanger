<?php
require_once __DIR__ . '/../includes/init.php';

if (!isLoggedIn()) {
    jsonResponse(['success' => false, 'message' => 'Not logged in.'], 401);
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Invalid request method.'], 405);
}

$postId  = (int)($_POST['post_id'] ?? 0);
$comment = trim($_POST['comment'] ?? '');

if ($postId <= 0 || $comment === '') {
    jsonResponse(['success' => false, 'message' => 'Comment cannot be empty.'], 400);
}
if (mb_strlen($comment) > 2000) {
    jsonResponse(['success' => false, 'message' => 'Comment is too long.'], 400);
}

// Confirm the post actually exists.
$check = $pdo->prepare('SELECT id FROM admin_posts WHERE id = ?');
$check->execute([$postId]);
if (!$check->fetch()) {
    jsonResponse(['success' => false, 'message' => 'Post not found.'], 404);
}

$authorId = currentUserId();

if (isAdmin()) {
    // Admin is replying privately into a specific member's thread.
    $ownerUserId = (int)($_POST['owner_user_id'] ?? 0);
    if ($ownerUserId <= 0) {
        jsonResponse(['success' => false, 'message' => 'Missing target member for this reply.'], 400);
    }
    $ownerCheck = $pdo->prepare("SELECT id FROM users WHERE id = ? AND role = 'user'");
    $ownerCheck->execute([$ownerUserId]);
    if (!$ownerCheck->fetch()) {
        jsonResponse(['success' => false, 'message' => 'Member not found.'], 404);
    }
} else {
    // A member always owns their own thread.
    $ownerUserId = $authorId;
}

$stmt = $pdo->prepare('INSERT INTO post_comments (post_id, owner_user_id, author_id, comment) VALUES (?, ?, ?, ?)');
$stmt->execute([$postId, $ownerUserId, $authorId, $comment]);

$row = [
    'author_name' => currentUserName(),
    'author_role' => $_SESSION['role'],
    'comment'     => $comment,
    'created_at'  => date('Y-m-d H:i:s'),
];

jsonResponse([
    'success' => true,
    'html'    => renderCommentHtml($row),
]);
