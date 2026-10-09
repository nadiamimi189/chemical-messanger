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

$recentProductsStmt = $pdo->query('SELECT id, name, description, media_type, created_at FROM products ORDER BY created_at DESC LIMIT 4');
$recentProducts = $recentProductsStmt->fetchAll();

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
        <a href="index.php" class="active"><span class="icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="m3.5 10 8.5-7 8.5 7v10a1 1 0 0 1-1 1h-5.3v-6.2h-4.4V21H4.5a1 1 0 0 1-1-1z"/></svg></span> Home</a>
        <a href="my_posts.php"><span class="icon" aria-hidden="true"><svg viewBox="0 0 24 24"><rect x="5" y="3.5" width="14" height="17" rx="1.5"/><path d="M8.5 8h7M8.5 12h7M8.5 16h4"/></svg></span> My Posts</a>
        <a href="#chat"><span class="icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M20 11.5a7.5 7.5 0 0 1-7.5 7.5H6l-3 2v-6.5A7.5 7.5 0 1 1 20 11.5Z"/><path d="M8 11.5h.01M12 11.5h.01M16 11.5h.01"/></svg></span> Chat with Admin</a>
      </nav>
    </div>
  </div>

  <!-- CENTER FEED -->
  <div class="community-feed">
    <div class="hero-banner">
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
      <div class="card post-card" id="post-<?php echo (int)$post['id']; ?>">
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

  <aside class="sidebar-right product-rail" aria-labelledby="recent-products-title">
    <div class="product-rail-heading">
      <div>
        <span class="product-rail-kicker">JUST ADDED</span>
        <h2 id="recent-products-title">New products</h2>
      </div>
      <span class="product-rail-count"><?php echo count($recentProducts); ?></span>
    </div>
    <?php if (empty($recentProducts)): ?>
      <p class="product-rail-empty">No product uploads yet.</p>
    <?php else: ?>
      <div class="product-card-list">
        <?php foreach ($recentProducts as $product): ?>
          <article class="product-card">
            <div class="product-card-media">
              <?php if ($product['media_type'] === 'video'): ?>
                <video controls muted playsinline preload="metadata" src="product_media.php?id=<?php echo (int)$product['id']; ?>"></video>
                <span class="product-media-type">VIDEO</span>
              <?php else: ?>
                <img src="product_media.php?id=<?php echo (int)$product['id']; ?>" alt="<?php echo e($product['name']); ?>">
                <span class="product-media-type">PRODUCT</span>
              <?php endif; ?>
            </div>
            <div class="product-card-copy">
              <span class="product-card-date"><?php echo e(timeAgo($product['created_at'])); ?></span>
              <strong><?php echo e($product['name']); ?></strong>
              <?php if (trim($product['description']) !== ''): ?>
                <p><?php echo e($product['description']); ?></p>
              <?php endif; ?>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </aside>

</div>
<footer class="site-footer">Chemical Connect &mdash; A private community platform</footer>
<?php require __DIR__ . '/includes/footer.php'; ?>
