<?php
require_once __DIR__ . '/../includes/init.php';
requireAdminRoot();

$adminId = currentUserId();

/* ---------- Handle new public post ---------- */
$postError = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create_admin_post') {
    if (!csrfCheck()) {
        $postError = 'Invalid request, please try again.';
    } else {
        $content = trim($_POST['content'] ?? '');
        try {
            $media = handleMediaUpload('media', 'uploads/posts');
            if ($content === '' && !$media) {
                $postError = 'Please write something or attach a photo/video.';
            } else {
                $stmt = $pdo->prepare('INSERT INTO admin_posts (admin_id, content, media_path, media_type) VALUES (?, ?, ?, ?)');
                $stmt->execute([$adminId, $content, $media['path'] ?? null, $media['type'] ?? null]);
                header('Location: dashboard.php');
                exit;
            }
        } catch (RuntimeException $e) {
            $postError = $e->getMessage();
        }
    }
}

/* ---------- Load posts + per-user comment threads ---------- */
$stmt = $pdo->query("
    SELECT p.id, p.content, p.media_path, p.media_type, p.created_at,
           u.name AS author_name, u.role AS author_role, 'admin' AS post_type
    FROM admin_posts p
    JOIN users u ON u.id = p.admin_id
    WHERE u.role = 'admin'
    UNION ALL
    SELECT p.id, p.content, p.media_path, p.media_type, p.created_at,
           u.name AS author_name, u.role AS author_role, 'member' AS post_type
    FROM user_posts p
    JOIN users u ON u.id = p.user_id
    UNION ALL
    SELECT p.id, p.content, p.media_path, p.media_type, p.created_at,
           u.name AS author_name, u.role AS author_role, 'legacy_member' AS post_type
    FROM admin_posts p
    JOIN users u ON u.id = p.admin_id
    WHERE u.role = 'user'
    ORDER BY created_at DESC
");
$posts = $stmt->fetchAll();

$threadUsersStmt = $pdo->prepare("
    SELECT DISTINCT c.owner_user_id, u.name
    FROM post_comments c
    JOIN users u ON u.id = c.owner_user_id
    WHERE c.post_id = ?
    ORDER BY u.name ASC
");
$threadCommentsStmt = $pdo->prepare("
    SELECT c.*, u.name AS author_name, u.role AS author_role
    FROM post_comments c
    JOIN users u ON u.id = c.author_id
    WHERE c.post_id = ? AND c.owner_user_id = ?
    ORDER BY c.created_at ASC
");
$likeCountStmt = $pdo->prepare('SELECT COUNT(*) AS c FROM post_likes WHERE post_id = ?');

$pageTitle = 'Community Newsfeed - Chemical Connect';
$assetPrefix = '../';
$bodyClass = 'admin-newsfeed';
$activeAdminPage = 'newsfeed';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/navbar.php';
?>
<div class="admin-shell">
  <?php require __DIR__ . '/../includes/admin_sidebar.php'; ?>

  <main class="admin-main">
    <div class="dashboard-header">
      <button class="admin-sidebar-toggle" type="button" aria-controls="adminSidebar" aria-expanded="true" aria-label="Hide navigation" title="Hide navigation">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
      </button>
      <div>
        <h1>Community Newsfeed</h1>
        <p>Latest posts from members and Chemical Connect admins.</p>
      </div>
    </div>

    <section class="panel feed-panel" id="composer">
      <div class="panel-header">
          <h3>Latest posts</h3>
          <span class="panel-tag">Members + Admin</span>
      </div>

      <?php if ($postError): ?>
        <div class="alert alert-error"><?php echo e($postError); ?></div>
      <?php endif; ?>

      <div class="composer" style="margin-bottom:20px;">
        <form method="POST" enctype="multipart/form-data">
          <input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>">
          <input type="hidden" name="action" value="create_admin_post">
          <div class="row1">
            <img class="avatar" src="https://api.dicebear.com/7.x/initials/svg?seed=Admin&backgroundColor=14549e" alt="">
            <textarea name="content" rows="1" placeholder="Announce something to all members..."></textarea>
          </div>
          <div class="row2">
            <label class="attach-label">
              📷 Photo/Video
              <input type="file" name="media" class="media-input" accept="image/*,video/*" style="display:none;">
            </label>
            <button type="submit" class="btn btn-primary btn-sm">Publish to everyone</button>
          </div>
          <div class="file-preview"></div>
        </form>
        <div class="private-note">🌐 This announcement is visible to the entire member community.</div>
      </div>

      <?php if (empty($posts)): ?>
        <div class="card empty-state">
          <div class="icon">📢</div>
          No posts yet. Member posts and Admin announcements will appear here.
        </div>
      <?php endif; ?>

      <div class="posts-wrap">
        <?php foreach ($posts as $post): ?>
          <?php
            $isPublicPost = $post['post_type'] === 'admin';
            $threadUsers = [];
            $likeCount = 0;
            if ($isPublicPost) {
              $threadUsersStmt->execute([$post['id']]);
              $threadUsers = $threadUsersStmt->fetchAll();

              $likeCountStmt->execute([$post['id']]);
              $likeCount = (int)$likeCountStmt->fetch()['c'];
            }
            $mediaUrl = $post['post_type'] === 'member'
              ? '../private_media.php?id=' . (int)$post['id']
              : '../post_media.php?id=' . (int)$post['id'];
          ?>
          <div class="card post-card" id="post-<?php echo (int)$post['id']; ?>">
            <div class="post-header">
              <img class="avatar" src="https://api.dicebear.com/7.x/initials/svg?seed=<?php echo urlencode($post['author_name']); ?>&backgroundColor=<?php echo $isPublicPost ? '14549e' : '1d5aa8'; ?>" alt="">
              <div class="who">
                <div class="name"><?php echo e($post['author_name']); ?><span class="badge-admin"><?php echo strtoupper($post['author_role']); ?></span></div>
                <div class="meta"><?php echo timeAgo($post['created_at']); ?> &bull; <?php echo $isPublicPost ? '🌐 Public to all members' : '🔒 Private member post'; ?></div>
              </div>
              <?php if ($isPublicPost): ?>
                <button class="btn btn-sm btn-outline" onclick="deletePost(<?php echo $post['id']; ?>, this)">Delete</button>
              <?php endif; ?>
            </div>
            <?php if ($post['content']): ?>
              <div class="post-content"><?php echo nl2br(e($post['content'])); ?></div>
            <?php endif; ?>
            <?php if ($post['media_path']): ?>
              <div class="post-media">
                <?php if ($post['media_type'] === 'video'): ?>
                  <video controls src="<?php echo e($mediaUrl); ?>"></video>
                <?php else: ?>
                  <img src="<?php echo e($mediaUrl); ?>" alt="">
                <?php endif; ?>
              </div>
            <?php endif; ?>
            <?php if ($isPublicPost): ?>
              <div class="post-stats">
                <span>👍❤️ <?php echo $likeCount; ?> likes</span>
                <span><?php echo count($threadUsers); ?> member<?php echo count($threadUsers) === 1 ? '' : 's'; ?> commented</span>
              </div>

              <div class="comment-section">
                <div class="private-note">🔒 Each member's comment thread below is private &mdash; members cannot see each other's comments.</div>

                <?php if (empty($threadUsers)): ?>
                  <p style="font-size:13px; color:#66788c;">No comments yet on this post.</p>
                <?php else: ?>
                  <div class="thread-group-<?php echo $post['id']; ?>">
                    <?php foreach ($threadUsers as $i => $tu): ?>
                      <span class="thread-user-pill <?php echo $i === 0 ? 'active' : ''; ?>"
                            data-post-id="<?php echo $post['id']; ?>"
                            data-owner-id="<?php echo $tu['owner_user_id']; ?>">
                        <?php echo e($tu['name']); ?>
                      </span>
                    <?php endforeach; ?>

                    <?php foreach ($threadUsers as $i => $tu): ?>
                      <?php
                        $threadCommentsStmt->execute([$post['id'], $tu['owner_user_id']]);
                        $threadComments = $threadCommentsStmt->fetchAll();
                      ?>
                      <div class="thread-panel thread-panel-<?php echo $tu['owner_user_id']; ?>" style="<?php echo $i === 0 ? '' : 'display:none;'; ?> margin-top:10px;">
                        <div class="comment-list">
                          <?php foreach ($threadComments as $c): ?>
                            <?php echo renderCommentHtml($c); ?>
                          <?php endforeach; ?>
                        </div>
                        <form class="comment-form">
                          <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                          <input type="hidden" name="owner_user_id" value="<?php echo $tu['owner_user_id']; ?>">
                          <input type="text" name="comment" placeholder="Reply to <?php echo e($tu['name']); ?> privately...">
                          <button type="submit" class="btn btn-primary btn-sm">Reply</button>
                        </form>
                      </div>
                    <?php endforeach; ?>
                  </div>
                <?php endif; ?>
              </div>
            <?php else: ?>
              <div class="private-note">🔒 This post is private to its author and Admin.</div>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    </section>
  </main>
</div>

<script>
  function deletePost(id, btn) {
    if (!confirm('Delete this post for everyone?')) return;
    fetch('../api/delete_post.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'post_id=' + encodeURIComponent(id)
    }).then(function (r) { return r.json(); }).then(function (data) {
      if (data.success) {
        btn.closest('.post-card').remove();
      }
    });
  }
</script>
<?php require __DIR__ . '/../includes/footer.php'; ?>
