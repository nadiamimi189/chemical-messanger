<?php
/**
 * Top navigation bar. Expects $assetPrefix ('' or '../').
 */
$assetPrefix = $assetPrefix ?? '';
$homeHref = $assetPrefix . 'index.php';
$msgHref  = isAdmin() ? $assetPrefix . 'admin/messages.php' : $assetPrefix . 'index.php#chat';
?>
<div class="topnav">
  <a href="<?php echo $homeHref; ?>" class="brand" style="text-decoration:none;">
    <div class="brand-icon" aria-hidden="true">CC</div>
    <div>
      Chemical Connect
      <small>Plant operations</small>
    </div>
  </a>

  <div class="search">
    <input type="text" placeholder="Search community, people, products, topics...">
  </div>

  <div class="nav-links">
    <a href="<?php echo $homeHref; ?>" class="<?php echo (basename($_SERVER['PHP_SELF']) === 'dashboard.php' || basename($_SERVER['PHP_SELF']) === 'index.php') ? 'active' : ''; ?>">
      <span class="nav-icon" aria-hidden="true">⌂</span> Home
    </a>
    <?php if (isAdmin()): ?>
      <a href="<?php echo $assetPrefix; ?>admin/users.php">Members</a>
      <a href="<?php echo $assetPrefix; ?>admin/messages.php">Messages</a>
    <?php else: ?>
      <a href="<?php echo $assetPrefix; ?>my_posts.php"><span class="nav-icon" aria-hidden="true">▤</span> My Posts</a>
      <a href="#chat"><span class="nav-icon" aria-hidden="true">◉</span> Chat</a>
    <?php endif; ?>
  </div>

  <div class="topbar-meta">
    <div class="notification">
      <span class="bell">🔔</span>
      <span class="badge">3</span>
    </div>

    <div class="datetime-pill">
      <time datetime="<?php echo date(DATE_ATOM); ?>"><?php echo date('D, M j, g:i A'); ?></time>
    </div>

    <div class="user-menu">
      <div class="dropdown">
        <div class="profile-box" data-dropdown-toggle style="display:flex; align-items:center; gap:8px; cursor:pointer;">
          <img class="avatar small" src="https://api.dicebear.com/7.x/initials/svg?seed=<?php echo urlencode(currentUserName()); ?>&backgroundColor=1d5aa8" alt="avatar">
          <div>
            <span style="display:block; font-size:12px; font-weight:700; line-height:1.2; color:#fff;"><?php echo e(currentUserName()); ?></span>
            <span style="display:block; font-size:11px; opacity:0.8;"><?php echo isAdmin() ? 'Administrator' : 'Member'; ?></span>
          </div>
          <span style="font-size:11px;">▾</span>
        </div>
        <div class="dropdown-content">
          <?php if (isAdmin()): ?>
            <a href="<?php echo $assetPrefix; ?>admin/dashboard.php">Admin Dashboard</a>
          <?php else: ?>
            <a href="<?php echo $assetPrefix; ?>my_posts.php">My Posts</a>
          <?php endif; ?>
          <a href="<?php echo $assetPrefix; ?>logout.php">Logout</a>
        </div>
      </div>
    </div>
  </div>
</div>
