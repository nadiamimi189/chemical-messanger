<?php
require_once __DIR__ . '/includes/init.php';
requireLogin();

if (isAdmin()) {
    header('Location: admin/dashboard.php');
    exit;
}

$userId  = currentUserId();

/* ---------- Save member posts privately ---------- */
$uploadError = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_user_post') {
    if (!csrfCheck()) {
        $uploadError = 'Invalid request, please try again.';
    } else {
        $content = trim($_POST['content'] ?? '');
      try {
        $media = handleMediaUpload('media', 'uploads/user_posts');
        if ($content === '' && !$media) {
          $uploadError = 'Please write something or attach a photo/video.';
        } else {
          $stmt = $pdo->prepare('INSERT INTO user_posts (user_id, content, media_path, media_type) VALUES (?, ?, ?, ?)');
          $stmt->execute([$userId, $content, $media['path'] ?? null, $media['type'] ?? null]);
          header('Location: my_posts.php?uploaded=1');
          exit;
        }
      } catch (RuntimeException $e) {
        $uploadError = $e->getMessage();
        }
    }
}

  /* ---------- Only Admin posts are shared with all members ---------- */
$stmt = $pdo->prepare("
    SELECT p.*, u.name AS author_name, u.role AS author_role
    FROM admin_posts p
    JOIN users u ON u.id = p.admin_id
    WHERE u.role = 'admin'
    ORDER BY p.created_at DESC
");
$stmt->execute();
$posts = $stmt->fetchAll();

$commentStmt = $pdo->prepare("
    SELECT c.*, u.name AS author_name, u.role AS author_role
    FROM post_comments c
    JOIN users u ON u.id = c.author_id
    WHERE c.post_id = ? AND c.owner_user_id = ?
    ORDER BY c.created_at ASC
");

$likeCountStmt = $pdo->prepare('SELECT COUNT(*) AS c FROM post_likes WHERE post_id = ?');
$likedStmt = $pdo->prepare('SELECT 1 FROM post_likes WHERE post_id = ? AND user_id = ?');

$pageTitle = 'Home - Chemical Connect';
$assetPrefix = '';
$bodyClass = 'member-app';
require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/navbar.php';
?>
<div class="layout community-layout">

  <!-- LEFT SIDEBAR -->
  <div class="sidebar-left">
    <div class="card member-panel">
      <div class="member-panel-profile">
        <img class="avatar" src="https://api.dicebear.com/7.x/initials/svg?seed=<?php echo urlencode(currentUserName()); ?>&backgroundColor=1d5aa8" alt="">
        <div><strong><?php echo e(currentUserName()); ?></strong><span>Community member</span></div>
      </div>
      <nav class="side-menu" aria-label="Member navigation">
        <a href="index.php" class="active"><span class="icon">🏠</span> Home</a>
        <a href="my_posts.php"><span class="icon">👤</span> My Posts</a>
        <a href="#chat"><span class="icon">💬</span> Chat with Admin</a>
      </nav>
    </div>
    <div class="card notice-card">
      <h4>Privacy &amp; visibility</h4>
      <p>Admin posts are shared with every member. Your posts and comment conversations stay between you and the Admin.</p>
    </div>
  </div>

  <!-- CENTER FEED -->
  <div class="community-feed">
    <div class="hero-banner">
      <img src="https://images.unsplash.com/photo-1518709268805-4e9042af2176?q=80&w=1200&auto=format&fit=crop" alt="Chemical industry">
      <div class="hero-text">
        <h1>Community for Chemical Industry Professionals</h1>
        <div class="tags">Share knowledge <span>•</span> Ask questions <span>•</span> Build your network</div>
      </div>
    </div>

    <!-- Public posts are shared with all members; private uploads are owner-only. -->
    <div class="card composer member-composer" id="feed">
      <?php if ($uploadError): ?>
        <div class="alert alert-error"><?php echo e($uploadError); ?></div>
      <?php endif; ?>
      <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>">
        <input type="hidden" name="action" value="create_user_post">
        <div class="row1">
          <img class="avatar" src="https://api.dicebear.com/7.x/initials/svg?seed=<?php echo urlencode(currentUserName()); ?>&backgroundColor=1d5aa8" alt="">
          <textarea name="content" rows="1" placeholder="Write a post visible only to you and the Admin..."></textarea>
        </div>
        <div class="row2">
          <label class="attach-label">
            📷 Photo/Video
            <input type="file" name="media" class="media-input" accept="image/*,video/*" style="display:none;">
          </label>
          <span class="audience-control">🔒 Only you &amp; Admin</span>
          <button type="submit" class="btn btn-primary btn-sm">Post</button>
        </div>
        <div class="file-preview"></div>
      </form>
      <div class="private-note">Only you and the Admin can see this post. Admin announcements are visible to all members.</div>
    </div>

    <!-- Shared public feed -->
    <?php if (empty($posts)): ?>
      <div class="card empty-state">
        <div class="icon">📭</div>
        No announcements yet. Check back soon!
      </div>
    <?php endif; ?>

    <?php foreach ($posts as $post): ?>
      <?php
        $commentStmt->execute([$post['id'], $userId]);
        $comments = $commentStmt->fetchAll();

        $likeCountStmt->execute([$post['id']]);
        $likeCount = (int)$likeCountStmt->fetch()['c'];

        $likedStmt->execute([$post['id'], $userId]);
        $iLiked = (bool)$likedStmt->fetch();
      ?>
      <div class="card post-card">
        <div class="post-header">
          <img class="avatar" src="https://api.dicebear.com/7.x/initials/svg?seed=<?php echo urlencode($post['author_name']); ?>&backgroundColor=<?php echo $post['author_role'] === 'admin' ? '14549e' : '1d5aa8'; ?>" alt="">
          <div class="who">
            <div class="name"><?php echo e($post['author_name']); ?><span class="badge-admin"><?php echo strtoupper($post['author_role']); ?></span></div>
            <div class="meta"><?php echo timeAgo($post['created_at']); ?> &bull; 🌐 Public</div>
          </div>
        </div>
        <?php if ($post['content']): ?>
          <div class="post-content"><?php echo nl2br(e($post['content'])); ?></div>
        <?php endif; ?>
        <?php if ($post['media_path']): ?>
          <div class="post-media">
            <?php if ($post['media_type'] === 'video'): ?>
              <video controls src="post_media.php?id=<?php echo (int)$post['id']; ?>"></video>
            <?php else: ?>
              <img src="post_media.php?id=<?php echo (int)$post['id']; ?>" alt="">
            <?php endif; ?>
          </div>
        <?php endif; ?>

        <div class="post-stats">
          <span>👍❤️ <span class="like-count-<?php echo $post['id']; ?>"><?php echo $likeCount; ?></span></span>
        </div>
        <div class="post-actions">
          <button class="like-btn <?php echo $iLiked ? 'liked' : ''; ?>" data-post-id="<?php echo $post['id']; ?>">👍 Like</button>
          <button onclick="document.getElementById('comment-input-<?php echo $post['id']; ?>').focus(); return false;">💬 Comment</button>
          <button onclick="return false;">↗️ Share</button>
        </div>

        <div class="comment-section">
          <div class="private-note">🔒 Only you and the Admin can see this comment thread.</div>
          <div class="comment-list">
            <?php foreach ($comments as $c): ?>
              <?php echo renderCommentHtml($c); ?>
            <?php endforeach; ?>
          </div>
          <form class="comment-form">
            <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
            <input type="text" id="comment-input-<?php echo $post['id']; ?>" name="comment" placeholder="Write a comment...">
            <button type="submit" class="btn btn-primary btn-sm">Send</button>
          </form>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <!-- RIGHT SIDEBAR: community info -->
  <div class="sidebar-right">
    <div class="card community-info">
      <h4 class="panel-title">Community feed</h4>
      <div class="community-info-count"><?php echo count($posts); ?><span>public posts</span></div>
      <p>Admin announcements appear here. Member posts are private and visible only to their owner and the Admin.</p>
    </div>
  </div>
</div>
<footer class="site-footer">Chemical Connect &mdash; A private community platform</footer>
<?php require __DIR__ . '/includes/footer.php'; ?>
