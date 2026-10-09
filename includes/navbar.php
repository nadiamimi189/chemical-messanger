<?php
/**
 * Top navigation bar. Expects $assetPrefix ('' or '../').
 */
$assetPrefix = $assetPrefix ?? '';
$homeHref = isAdmin() ? $assetPrefix . 'admin/dashboard.php' : $assetPrefix . 'index.php';
$currentPage = basename($_SERVER['PHP_SELF']);
$profileAvatarPath = null;
if (isLoggedIn()) {
    $profileAvatarStmt = $pdo->prepare('SELECT avatar FROM users WHERE id = ?');
    $profileAvatarStmt->execute([currentUserId()]);
    $profileAvatarPath = $profileAvatarStmt->fetchColumn() ?: null;
}
?>
<div class="topnav">
  <div class="header-left">
    <a href="<?php echo $homeHref; ?>" class="brand" aria-label="Chemical Connect home">
      <span class="brand-mark" aria-hidden="true"><svg viewBox="0 0 32 32"><path d="M16 3.5 26.8 9.7v12.6L16 28.5 5.2 22.3V9.7z"/><path d="M11 20V12h2.2l2.8 4.2 2.8-4.2H21v8h-2v-4.8l-3 4.1-3-4.1V20z"/></svg></span>
    </a>
    <form class="header-search" role="search" method="get" action="<?php echo $assetPrefix; ?>search.php">
      <button class="search-submit" type="submit" aria-label="Search" title="Search">
        <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="10.8" cy="10.8" r="6.8"/><path d="m16 16 4.5 4.5"/></svg>
      </button>
      <input type="search" name="q" value="<?php echo e($_GET['q'] ?? ''); ?>" placeholder="Search Chemical Connect" aria-label="Search community">
    </form>
  </div>

  <nav class="nav-links" aria-label="Main navigation">
    <a href="<?php echo $homeHref; ?>" class="<?php echo in_array($currentPage, ['dashboard.php', 'index.php'], true) ? 'active' : ''; ?>" aria-label="Home" title="Home">
      <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m3.5 10 8.5-7 8.5 7v10a1 1 0 0 1-1 1h-5.3v-6.2h-4.4V21H4.5a1 1 0 0 1-1-1z"/></svg>
    </a>
    <?php if (isAdmin()): ?>
      <a href="<?php echo $assetPrefix; ?>admin/users.php" class="<?php echo in_array($currentPage, ['users.php', 'user_detail.php'], true) ? 'active' : ''; ?>" aria-label="Members" title="Members">
        <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="9" cy="8" r="3.5"/><path d="M2.8 20v-1.2a6.2 6.2 0 0 1 12.4 0V20zM16 5a3.5 3.5 0 0 1 0 6.8M18 14a4.8 4.8 0 0 1 3.2 4.6V20h-3"/></svg>
      </a>
      <a href="<?php echo $assetPrefix; ?>admin/messages.php" class="<?php echo $currentPage === 'messages.php' ? 'active' : ''; ?>" aria-label="Messages" title="Messages">
        <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m4 7 8 6 8-6"/></svg>
      </a>
    <?php else: ?>
      <a href="<?php echo $assetPrefix; ?>my_posts.php" class="<?php echo $currentPage === 'my_posts.php' ? 'active' : ''; ?>" aria-label="My Posts" title="My Posts">
        <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="5" y="3.5" width="14" height="17" rx="1.5"/><path d="M8.5 8h7M8.5 12h7M8.5 16h4"/></svg>
      </a>
      <a href="#chat" aria-label="Chat with Admin" title="Chat with Admin">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 11.5a7.5 7.5 0 0 1-7.5 7.5H6l-3 2v-6.5A7.5 7.5 0 1 1 20 11.5Z"/><path d="M8 11.5h.01M12 11.5h.01M16 11.5h.01"/></svg>
      </a>
    <?php endif; ?>
  </nav>

  <div class="header-tools">
    <div class="dropdown header-dropdown">
      <button type="button" class="header-action" aria-label="Open site menu" title="Site menu" data-dropdown-toggle>
        <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="3" width="5" height="5" rx="1"/><rect x="9.5" y="3" width="5" height="5" rx="1"/><rect x="16" y="3" width="5" height="5" rx="1"/><rect x="3" y="9.5" width="5" height="5" rx="1"/><rect x="9.5" y="9.5" width="5" height="5" rx="1"/><rect x="16" y="9.5" width="5" height="5" rx="1"/><rect x="3" y="16" width="5" height="5" rx="1"/><rect x="9.5" y="16" width="5" height="5" rx="1"/><rect x="16" y="16" width="5" height="5" rx="1"/></svg>
      </button>
      <div class="dropdown-content">
        <a href="<?php echo $homeHref; ?>"><?php echo isAdmin() ? 'Newsfeed' : 'Home'; ?></a>
        <?php if (isAdmin()): ?>
          <a href="<?php echo $assetPrefix; ?>admin/users.php">Members</a>
          <a href="<?php echo $assetPrefix; ?>admin/messages.php">Messages</a>
          <a href="<?php echo $assetPrefix; ?>admin/products.php">Products</a>
          <a href="<?php echo $assetPrefix; ?>admin/settings.php">Settings</a>
          <a href="<?php echo $assetPrefix; ?>admin/bulk_email.php">Bulk Email</a>
        <?php else: ?>
          <a href="<?php echo $assetPrefix; ?>my_posts.php">My Posts</a>
          <a href="#chat">Chat with Admin</a>
        <?php endif; ?>
      </div>
    </div>
    <?php if (!isAdmin()): ?>
      <a class="header-action" href="#chat" aria-label="Open chat" title="Open chat">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 11.5a7.5 7.5 0 0 1-7.5 7.5H6l-3 2v-6.5A7.5 7.5 0 1 1 20 11.5Z"/><path d="M8 11.5h.01M12 11.5h.01M16 11.5h.01"/></svg>
      </a>
    <?php endif; ?>
    <div class="dropdown header-profile-dropdown">
      <button type="button" class="header-profile" aria-label="Open profile menu" title="Profile" data-dropdown-toggle>
        <?php if ($profileAvatarPath): ?>
          <img src="<?php echo $assetPrefix; ?>profile_media.php?id=<?php echo (int)currentUserId(); ?>&amp;type=avatar&amp;v=<?php echo urlencode($profileAvatarPath); ?>" alt="">
        <?php else: ?>
          <?php echo e(strtoupper(substr(currentUserName(), 0, 1))); ?>
        <?php endif; ?>
      </button>
      <div class="dropdown-content">
        <?php if (isAdmin()): ?>
          <a href="<?php echo $assetPrefix; ?>admin/dashboard.php">Admin Newsfeed</a>
          <a href="<?php echo $assetPrefix; ?>admin/settings.php">Account Settings</a>
        <?php else: ?>
          <a href="<?php echo $assetPrefix; ?>my_posts.php">My Posts</a>
        <?php endif; ?>
        <a href="<?php echo $assetPrefix; ?>logout.php">Logout</a>
      </div>
    </div>
  </div>
</div>
