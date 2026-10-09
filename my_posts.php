<?php
require_once __DIR__ . '/includes/init.php';
requireUserOnly();

$userId = currentUserId();

$legacyPostsStmt = $pdo->prepare('SELECT id, admin_id AS owner_id, content, media_path, media_type, created_at, "legacy_private" AS post_type FROM admin_posts WHERE admin_id = ? ORDER BY created_at DESC');
$privatePostsStmt = $pdo->prepare('SELECT id, user_id AS owner_id, content, media_path, media_type, created_at, "private" AS post_type FROM user_posts WHERE user_id = ? ORDER BY created_at DESC');

$legacyPostsStmt->execute([$userId]);
$privatePostsStmt->execute([$userId]);

$myLegacyPosts = $legacyPostsStmt->fetchAll();
$myPrivatePosts = $privatePostsStmt->fetchAll();
$myPosts = array_merge($myLegacyPosts, $myPrivatePosts);
usort($myPosts, function ($a, $b) {
    return strtotime($b['created_at']) <=> strtotime($a['created_at']);
});
$justUploaded = isset($_GET['uploaded']);

$pageTitle = 'My Posts - Chemical Connect';
$assetPrefix = '';
$bodyClass = 'member-app';
require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/navbar.php';
?>
<div class="social-shell">
  <aside class="social-sidebar">
    <div class="profile-card-modern">
      <div class="profile-cover"></div>
      <div class="profile-body">
        <img class="avatar-xl" src="https://api.dicebear.com/7.x/initials/svg?seed=<?php echo urlencode(currentUserName()); ?>&backgroundColor=1d5aa8" alt="<?php echo e(currentUserName()); ?>">
        <h2><?php echo e(currentUserName()); ?></h2>
        <span class="profile-role">Member</span>
      </div>
    </div>

    <div class="menu-card-modern">
      <a href="index.php" class="nav-link-modern"><span class="icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="m3.5 10 8.5-7 8.5 7v10a1 1 0 0 1-1 1h-5.3v-6.2h-4.4V21H4.5a1 1 0 0 1-1-1z"/></svg></span> Home</a>
      <a href="my_posts.php" class="nav-link-modern active"><span class="icon" aria-hidden="true"><svg viewBox="0 0 24 24"><rect x="5" y="3.5" width="14" height="17" rx="1.5"/><path d="M8.5 8h7M8.5 12h7M8.5 16h4"/></svg></span> My Posts</a>
      <a href="index.php#chat" class="nav-link-modern"><span class="icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M20 11.5a7.5 7.5 0 0 1-7.5 7.5H6l-3 2v-6.5A7.5 7.5 0 1 1 20 11.5Z"/><path d="M8 11.5h.01M12 11.5h.01M16 11.5h.01"/></svg></span> Chat with Admin</a>
    </div>
  </aside>

  <main class="social-feed">
    <div class="feed-hero-modern">
      <div>
        <span class="hero-chip">Private to you &amp; Admin</span>
        <h1>My Posts</h1>
      </div>
      <div class="privacy-tag">🔒 Only you &amp; Admin</div>
    </div>

    <?php if ($justUploaded): ?>
      <div class="alert alert-success">Private upload saved. Only you and the Admin can see it.</div>
    <?php endif; ?>

    <div class="card notice-card notice-modern">
      <h4>🔒 Privacy & Visibility</h4>
      <p>Your posts are visible only to you and the Admin. Admin announcements are shared with the whole community.</p>
    </div>

    <?php if (empty($myPosts)): ?>
      <div class="card empty-state empty-feed-state">
        <div class="icon">🖼️</div>
        <div class="empty-title">You haven’t posted anything yet.</div>
        <p>Share a photo or short update from <a href="index.php">Home</a> to keep in touch with the community.</p>
      </div>
    <?php else: ?>
      <?php foreach ($myPosts as $post): ?>
        <?php $mediaUrl = ($post['post_type'] === 'legacy_private' ? 'post_media.php?id=' : 'private_media.php?id=') . (int)$post['id']; ?>
        <article class="post-card-modern">
          <div class="post-header-modern">
            <img class="avatar" src="https://api.dicebear.com/7.x/initials/svg?seed=<?php echo urlencode(currentUserName()); ?>&backgroundColor=1d5aa8" alt="<?php echo e(currentUserName()); ?>">
            <div class="who-modern">
              <div class="name-modern"><?php echo e(currentUserName()); ?></div>
              <div class="meta-modern"><?php echo timeAgo($post['created_at']); ?> &bull; <span><?php echo $post['post_type'] === 'legacy_private' ? 'Private post' : 'Private upload'; ?></span></div>
            </div>
            <span class="privacy-badge">🔒 Private</span>
          </div>

          <?php if ($post['content']): ?>
            <div class="post-content-modern"><?php echo nl2br(e($post['content'])); ?></div>
          <?php endif; ?>

          <?php if ($post['media_path']): ?>
            <div class="post-media-modern">
              <?php if ($post['media_type'] === 'video'): ?>
                <video controls src="<?php echo e($mediaUrl); ?>"></video>
              <?php else: ?>
                <img src="<?php echo e($mediaUrl); ?>" alt="<?php echo e(currentUserName()); ?> post">
              <?php endif; ?>
            </div>
          <?php endif; ?>
        </article>
      <?php endforeach; ?>
    <?php endif; ?>
  </main>

  <aside class="social-rail">
    <div class="mini-card-modern">
      <h3>Profile</h3>
      <div class="stat-row">
        <span>Total posts</span>
        <strong><?php echo count($myPosts); ?></strong>
      </div>
      <div class="stat-row">
        <span>Visibility</span>
        <strong>Private</strong>
      </div>
    </div>

    <div class="mini-card-modern">
      <h3>Privacy</h3>
      <p>Only you and the Admin can view your posts on this page. Admin announcements appear on Home.</p>
    </div>
  </aside>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
