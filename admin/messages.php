<?php
require_once __DIR__ . '/../includes/init.php';
requireAdminRoot();

$adminId = currentUserId();

/* All members, with last message time + unread count, for the left list */
$membersStmt = $pdo->query("
    SELECT u.id, u.name,
           (SELECT MAX(created_at) FROM messages m WHERE m.sender_id = u.id OR m.receiver_id = u.id) AS last_activity,
           (SELECT COUNT(*) FROM messages m WHERE m.sender_id = u.id AND m.receiver_id = " . (int)$adminId . " AND m.is_read = 0) AS unread
    FROM users u
    WHERE u.role = 'user'
    ORDER BY last_activity IS NULL, last_activity DESC
");
$members = $membersStmt->fetchAll();

$withId = (int)($_GET['with'] ?? ($members[0]['id'] ?? 0));

$activeMember = null;
foreach ($members as $m) {
    if ((int)$m['id'] === $withId) { $activeMember = $m; break; }
}

$chatMessages = [];
if ($activeMember) {
    $chatStmt = $pdo->prepare("
        SELECT * FROM messages
        WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)
        ORDER BY created_at ASC
    ");
    $chatStmt->execute([$adminId, $withId, $withId, $adminId]);
    $chatMessages = $chatStmt->fetchAll();

    $mark = $pdo->prepare('UPDATE messages SET is_read = 1 WHERE sender_id = ? AND receiver_id = ? AND is_read = 0');
    $mark->execute([$withId, $adminId]);
}

$pageTitle = 'Messages - Chemical Connect';
$assetPrefix = '../';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/navbar.php';
?>
<div class="layout no-right">
  <div class="sidebar-left">
    <div class="card side-menu">
      <a href="dashboard.php"><span class="icon">🏠</span> Dashboard</a>
      <a href="users.php"><span class="icon">👥</span> Members</a>
      <a href="messages.php" class="active"><span class="icon">✉️</span> Messages</a>
    </div>
  </div>

  <div style="display:grid; grid-template-columns: 280px 1fr; gap:20px;">
    <div class="card" style="padding:8px; max-height:640px; overflow-y:auto;">
      <h4 class="panel-title" style="padding:0 8px;">Conversations</h4>
      <?php if (empty($members)): ?>
        <p style="font-size:13px; color:#66788c; padding:0 8px;">No members yet.</p>
      <?php endif; ?>
      <?php foreach ($members as $m): ?>
        <a href="messages.php?with=<?php echo $m['id']; ?>" style="text-decoration:none; color:inherit;">
          <div class="user-list-item" style="<?php echo (int)$m['id'] === $withId ? 'background:#e8f1fc;' : ''; ?>">
            <img class="avatar" src="https://api.dicebear.com/7.x/initials/svg?seed=<?php echo urlencode($m['name']); ?>&backgroundColor=1d5aa8" alt="">
            <div class="info">
              <div class="uname"><?php echo e($m['name']); ?></div>
              <div class="uemail"><?php echo $m['last_activity'] ? timeAgo($m['last_activity']) : 'No messages yet'; ?></div>
            </div>
            <?php if ($m['unread'] > 0): ?><span class="badge" style="position:static;"><?php echo (int)$m['unread']; ?></span><?php endif; ?>
          </div>
        </a>
      <?php endforeach; ?>
    </div>

    <div class="card chat-widget" style="height:640px;">
      <?php if (!$activeMember): ?>
        <div class="empty-state" style="margin:auto;"><div class="icon">✉️</div>Select a member to start chatting.</div>
      <?php else: ?>
        <div class="chat-header">
          <img class="avatar" src="https://api.dicebear.com/7.x/initials/svg?seed=<?php echo urlencode($activeMember['name']); ?>&backgroundColor=1d5aa8" alt="">
          <div><?php echo e($activeMember['name']); ?><div class="status">Private conversation</div></div>
        </div>
        <div class="chat-body" id="chatBody">
          <?php if (empty($chatMessages)): ?>
            <div class="chat-empty">No messages yet with <?php echo e($activeMember['name']); ?>.</div>
          <?php else: ?>
            <?php foreach ($chatMessages as $m): ?>
              <?php echo renderChatMsgHtml($m, $adminId); ?>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
        <form class="chat-footer" id="chatForm" data-with-id="<?php echo $activeMember['id']; ?>">
          <input type="text" name="message" placeholder="Reply to <?php echo e($activeMember['name']); ?>..." autocomplete="off">
          <button type="submit">➤</button>
        </form>
      <?php endif; ?>
    </div>
  </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
