<?php
require_once __DIR__ . '/../includes/init.php';

if (!isLoggedIn()) {
    jsonResponse(['success' => false, 'message' => 'Not logged in.'], 401);
}

$withId    = (int)($_GET['with'] ?? 0);
$currentId = currentUserId();

if ($withId <= 0) {
    jsonResponse(['success' => false, 'message' => 'Missing conversation target.'], 400);
}

// A member may only ever load their conversation with the Admin.
if (!isAdmin() && $withId !== getAdminId($pdo)) {
    jsonResponse(['success' => false, 'message' => 'Not allowed.'], 403);
}
// The admin may load a conversation with any member (validated as a user).
if (isAdmin()) {
    $check = $pdo->prepare("SELECT id FROM users WHERE id = ? AND role = 'user'");
    $check->execute([$withId]);
    if (!$check->fetch()) {
        jsonResponse(['success' => false, 'message' => 'Member not found.'], 404);
    }
}

$stmt = $pdo->prepare("
    SELECT * FROM messages
    WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)
    ORDER BY created_at ASC
");
$stmt->execute([$currentId, $withId, $withId, $currentId]);
$rows = $stmt->fetchAll();

// Mark incoming messages as read.
$mark = $pdo->prepare('UPDATE messages SET is_read = 1 WHERE sender_id = ? AND receiver_id = ? AND is_read = 0');
$mark->execute([$withId, $currentId]);

$html = '';
if (empty($rows)) {
    $html = '<div class="chat-empty">Say hello! This conversation is private.</div>';
} else {
    foreach ($rows as $row) {
        $html .= renderChatMsgHtml($row, $currentId);
    }
}

jsonResponse(['success' => true, 'html' => $html, 'count' => count($rows)]);
