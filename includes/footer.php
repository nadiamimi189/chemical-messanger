<script>window.API_PREFIX = <?php echo json_encode($assetPrefix ?? ''); ?>;</script>
<?php
$jsFile = __DIR__ . '/../assets/js/main.js';
$jsVersion = file_exists($jsFile) ? filemtime($jsFile) : time();
$chatWithId = 0;
$chatWithName = '';
$chatIsLoggedIn = isLoggedIn();

if ($chatIsLoggedIn) {
    if (isAdmin()) {
        $requestedChatWith = isset($activeMember['id'])
            ? (int)$activeMember['id']
            : (int)($_GET['with'] ?? 0);

        if ($requestedChatWith > 0) {
            $chatTargetStmt = $pdo->prepare("SELECT id, name FROM users WHERE id = ? AND role = 'user'");
            $chatTargetStmt->execute([$requestedChatWith]);
            $chatTarget = $chatTargetStmt->fetch();
        } else {
            $chatTarget = false;
        }

        if (!$chatTarget) {
            $chatTargetStmt = $pdo->query("SELECT id, name FROM users WHERE role = 'user' ORDER BY created_at DESC LIMIT 1");
            $chatTarget = $chatTargetStmt->fetch();
        }

        if ($chatTarget) {
            $chatWithId = (int)$chatTarget['id'];
            $chatWithName = $chatTarget['name'];
        }
    } else {
        $chatWithId = (int)getAdminId($pdo);
        $chatWithName = 'Admin';
    }
}
?>
<div class="chat-launcher-wrap" id="chat">
  <button type="button" class="chat-launcher" aria-label="Open chat" aria-expanded="false" aria-controls="chatPopup">
    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 11.5a7.5 7.5 0 0 1-7.5 7.5H6l-3 2v-6.5A7.5 7.5 0 1 1 20 11.5Z"/><path d="M8 11.5h.01M12 11.5h.01M16 11.5h.01"/></svg>
  </button>

  <section class="chat-popup chat-widget" id="chatPopup" role="dialog" aria-labelledby="chatPopupTitle" aria-hidden="true">
    <div class="chat-header">
      <?php if ($chatIsLoggedIn && $chatWithId > 0): ?>
        <img class="avatar" src="https://api.dicebear.com/7.x/initials/svg?seed=<?php echo urlencode($chatWithName); ?>&backgroundColor=14549e" alt="">
        <div class="chat-popup-heading">
          <strong id="chatPopupTitle"><?php echo isAdmin() ? e($chatWithName) : 'Chat with Admin'; ?></strong>
          <div class="status">● Private conversation</div>
        </div>
      <?php else: ?>
        <div class="chat-popup-heading">
          <strong id="chatPopupTitle">Chat</strong>
          <div class="status">Chemical Connect support</div>
        </div>
      <?php endif; ?>
      <button type="button" class="chat-close" aria-label="Close chat"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18"/></svg></button>
    </div>

    <?php if (!$chatIsLoggedIn): ?>
      <div class="chat-popup-notice">
        Please log in to chat.
        <a href="<?php echo e(($assetPrefix ?? '') . 'login.php'); ?>">Log in</a>
      </div>
    <?php elseif ($chatWithId <= 0): ?>
      <div class="chat-popup-notice">No members are available to chat with yet.</div>
    <?php else: ?>
      <div class="chat-body" id="floatingChatBody" aria-live="polite">
        <div class="chat-empty">Loading conversation...</div>
      </div>
      <form class="chat-footer" id="floatingChatForm" data-with-id="<?php echo $chatWithId; ?>">
        <input type="text" name="message" placeholder="Type a message..." autocomplete="off" aria-label="Type a message">
        <button type="submit" aria-label="Send message"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m3 11 18-8-8 18-2.5-7.5zM10.5 13.5 21 3"/></svg></button>
      </form>
    <?php endif; ?>
  </section>
</div>
<script src="<?php echo $assetPrefix ?? ''; ?>assets/js/main.js?v=<?php echo $jsVersion; ?>"></script>
</body>
</html>
