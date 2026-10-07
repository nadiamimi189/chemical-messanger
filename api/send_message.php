<?php
require_once __DIR__ . '/../includes/init.php';

if (!isLoggedIn()) {
    jsonResponse(['success' => false, 'message' => 'Not logged in.'], 401);
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Invalid request method.'], 405);
}

$receiverId = (int)($_POST['receiver_id'] ?? 0);
$message    = trim($_POST['message'] ?? '');
$senderId   = currentUserId();

if ($receiverId <= 0 || $message === '') {
    jsonResponse(['success' => false, 'message' => 'Message cannot be empty.'], 400);
}
if (mb_strlen($message) > 3000) {
    jsonResponse(['success' => false, 'message' => 'Message is too long.'], 400);
}

// Access rule: a member may only message the Admin; the Admin may message any member.
if (!isAdmin()) {
    $adminId = getAdminId($pdo);
    if ($receiverId !== $adminId) {
        jsonResponse(['success' => false, 'message' => 'Members can only message the Admin.'], 403);
    }
} else {
    $recvCheck = $pdo->prepare("SELECT id FROM users WHERE id = ? AND role = 'user'");
    $recvCheck->execute([$receiverId]);
    if (!$recvCheck->fetch()) {
        jsonResponse(['success' => false, 'message' => 'Recipient not found.'], 404);
    }
}

$stmt = $pdo->prepare('INSERT INTO messages (sender_id, receiver_id, message) VALUES (?, ?, ?)');
$stmt->execute([$senderId, $receiverId, $message]);

$row = [
    'sender_id'  => $senderId,
    'message'    => $message,
    'created_at' => date('Y-m-d H:i:s'),
];

jsonResponse([
    'success' => true,
    'html'    => renderChatMsgHtml($row, $senderId),
]);
