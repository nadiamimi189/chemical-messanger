<?php
require_once __DIR__ . '/../includes/init.php';
requireAdminRoot();

$adminId = currentUserId();

$membersStmt = $pdo->query("
    SELECT u.id, u.name,
           (SELECT MAX(created_at) FROM messages m WHERE m.sender_id = u.id OR m.receiver_id = u.id) AS last_activity,
           (SELECT COUNT(*) FROM messages m WHERE m.sender_id = u.id AND m.receiver_id = " . (int)$adminId . " AND m.is_read = 0) AS unread
    FROM users u
    WHERE u.role = 'user'
    ORDER BY last_activity IS NULL, last_activity DESC, u.name ASC
");
$members = $membersStmt->fetchAll();

$withId = (int)($_GET['with'] ?? ($members[0]['id'] ?? 0));
$activeMember = null;
foreach ($members as $member) {
    if ((int)$member['id'] === $withId) {
        $activeMember = $member;
        break;
    }
}

$chatMessages = [];
if ($activeMember) {
    $chatStmt = $pdo->prepare("
        SELECT * FROM messages
        WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)
        ORDER BY created_at ASC, id ASC
    ");
    $chatStmt->execute([$adminId, $withId, $withId, $adminId]);
    $chatMessages = $chatStmt->fetchAll();

    $mark = $pdo->prepare('UPDATE messages SET is_read = 1 WHERE sender_id = ? AND receiver_id = ? AND is_read = 0');
    $mark->execute([$withId, $adminId]);
}

$pageTitle = 'Messages - Chemical Connect';
$assetPrefix = '../';
$bodyClass = 'admin-messages-page';
$activeAdminPage = 'messages';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/navbar.php';
?>
<div class="admin-shell">
  <?php require __DIR__ . '/../includes/admin_sidebar.php'; ?>

  <main class="admin-main admin-messages-main">
    <div class="dashboard-header">
      <button class="admin-sidebar-toggle" type="button" aria-controls="adminSidebar" aria-expanded="true" aria-label="Hide navigation" title="Hide navigation">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
      </button>
      <div>
        <h1>Messages</h1>
        <p>Private conversations with community members.</p>
      </div>
    </div>

    <div class="admin-messages-layout">
      <aside class="admin-conversations" aria-label="Conversations">
        <div class="admin-conversations-heading">
          <h2>Inbox</h2>
          <span><?php echo count($members); ?></span>
        </div>
        <div class="admin-conversation-list">
          <?php if (empty($members)): ?>
            <p class="admin-conversations-empty">No members yet.</p>
          <?php endif; ?>
          <?php foreach ($members as $member): ?>
            <a class="admin-conversation <?php echo (int)$member['id'] === $withId ? 'active' : ''; ?>" href="messages.php?with=<?php echo (int)$member['id']; ?>" <?php echo (int)$member['id'] === $withId ? 'aria-current="page"' : ''; ?>>
              <img class="avatar" src="https://api.dicebear.com/7.x/initials/svg?seed=<?php echo urlencode($member['name']); ?>&backgroundColor=1d5aa8" alt="">
              <span class="admin-conversation-info">
                <strong><?php echo e($member['name']); ?></strong>
                <small><?php echo $member['last_activity'] ? timeAgo($member['last_activity']) : 'No messages yet'; ?></small>
              </span>
              <?php if ($member['unread'] > 0): ?><span class="admin-unread-count"><?php echo (int)$member['unread']; ?></span><?php endif; ?>
            </a>
          <?php endforeach; ?>
        </div>
      </aside>

      <section class="admin-message-widget chat-widget" aria-label="Selected conversation">
        <?php if (!$activeMember): ?>
          <div class="admin-message-placeholder">
            <strong>Select a conversation</strong>
            <span>Choose a member from the inbox to view the full message history.</span>
          </div>
        <?php else: ?>
          <div class="chat-header">
            <img class="avatar" src="https://api.dicebear.com/7.x/initials/svg?seed=<?php echo urlencode($activeMember['name']); ?>&backgroundColor=1d5aa8" alt="">
            <div class="admin-message-heading">
              <strong><?php echo e($activeMember['name']); ?></strong>
              <div class="status">Private conversation · Full history</div>
            </div>
          </div>
          <div class="chat-body" id="chatBody" aria-live="polite" aria-label="Message history">
            <?php if (empty($chatMessages)): ?>
              <div class="chat-empty">No messages yet with <?php echo e($activeMember['name']); ?>.</div>
            <?php else: ?>
              <?php foreach ($chatMessages as $message): ?>
                <?php echo renderChatMsgHtml($message, $adminId); ?>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
          <form class="chat-footer" id="chatForm" data-with-id="<?php echo (int)$activeMember['id']; ?>">
            <input type="text" name="message" maxlength="3000" placeholder="Reply to <?php echo e($activeMember['name']); ?>..." autocomplete="off" aria-label="Write a message">
            <button type="submit" aria-label="Send message"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m3 11 18-8-8 18-2.5-7.5zM10.5 13.5 21 3"/></svg></button>
          </form>
        <?php endif; ?>
      </section>
    </div>
  </main>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
