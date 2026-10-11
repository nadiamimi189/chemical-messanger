<?php
require_once __DIR__ . '/../includes/init.php';
requireAdminRoot();

$targetId = (int)($_GET['id'] ?? 0);

$userStmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role = 'user'");
$userStmt->execute([$targetId]);
$member = $userStmt->fetch();

if (!$member) {
    header('Location: users.php');
    exit;
}

$postsStmt = $pdo->prepare("
    SELECT id, content, media_path, media_type, created_at, 'private' AS post_type
    FROM user_posts
    WHERE user_id = ?
    UNION ALL
    SELECT id, content, media_path, media_type, created_at, 'legacy_private' AS post_type
    FROM admin_posts
    WHERE admin_id = ?
    ORDER BY created_at DESC
");
$postsStmt->execute([$targetId, $targetId]);
$posts = $postsStmt->fetchAll();

$pageTitle = e($member['name']) . ' - AR Tech messanger';
$assetPrefix = '../';
$activeAdminPage = 'members';
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
        <h1><?php echo e($member['name']); ?>'s Private Posts</h1>
        <p>Admin-only view · <?php echo e($member['email']); ?> · Joined <?php echo timeAgo($member['created_at']); ?></p>
      </div>
      <a class="btn btn-sm btn-primary" href="messages.php?with=<?php echo $member['id']; ?>">Message <?php echo e($member['name']); ?></a>
    </div>
    <a href="users.php" style="font-size:13px;">&larr; Back to Members</a>
    <div class="card notice-card" style="margin-bottom:16px;">
      <h4>Admin-only view</h4>
      <p><?php echo e($member['email']); ?> &bull; Joined <?php echo timeAgo($member['created_at']); ?>. These posts are visible only to you and <?php echo e($member['name']); ?> &mdash; no other member can see them.</p>
    </div>

    <?php if (empty($posts)): ?>
      <div class="card empty-state">This member hasn't posted anything yet.</div>
    <?php else: ?>
      <?php foreach ($posts as $post): ?>
        <?php $mediaUrl = ($post['post_type'] === 'legacy_private' ? '../post_media.php?id=' : '../private_media.php?id=') . (int)$post['id']; ?>
        <div class="card post-card" id="post-<?php echo (int)$post['id']; ?>">
          <div class="post-header">
            <img class="avatar" src="https://api.dicebear.com/7.x/initials/svg?seed=<?php echo urlencode($member['name']); ?>&backgroundColor=1d5aa8" alt="">
            <div class="who">
              <div class="name"><?php echo e($member['name']); ?></div>
              <div class="meta"><?php echo timeAgo($post['created_at']); ?></div>
            </div>
            <button type="button" class="btn btn-sm btn-outline" onclick="deletePost(<?php echo (int)$post['id']; ?>, '<?php echo e($post['post_type']); ?>', this)">Delete</button>
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
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</main>
</div>
  <script>
    const deletePostCsrfToken = <?php echo json_encode(csrfToken()); ?>;

    function deletePost(id, postType, btn) {
      if (!confirm('Delete this post? This cannot be undone.')) return;
      btn.disabled = true;
      fetch('../api/delete_post.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
          post_id: String(id),
          post_type: postType,
          csrf_token: deletePostCsrfToken
        })
      }).then(function (response) {
        return response.json().then(function (data) {
          if (!response.ok || !data.success) throw new Error(data.message || 'Unable to delete this post.');
          return data;
        });
      }).then(function () {
        const postCard = btn.closest('.post-card');
        if (!postCard) throw new Error('Could not find the post to remove from this page.');
        postCard.remove();
      }).catch(function (error) {
        window.alert(error.message || 'Unable to delete this post. Please try again.');
        btn.disabled = false;
      });
    }
  </script>
  <?php require __DIR__ . '/../includes/footer.php'; ?>
