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

$pageTitle = e($member['name']) . ' - Chemical Connect';
$assetPrefix = '../';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/navbar.php';
?>
<div class="layout no-right">
  <div class="sidebar-left">
    <div class="card side-menu">
      <a href="dashboard.php"><span class="icon">🏠</span> Dashboard</a>
      <a href="users.php" class="active"><span class="icon">👥</span> Members</a>
      <a href="messages.php"><span class="icon">✉️</span> Messages</a>
    </div>
  </div>
  <div>
    <a href="users.php" style="font-size:13px;">&larr; Back to Members</a>
    <div class="page-title" style="display:flex; align-items:center; gap:10px;">
      <img class="avatar" style="width:44px;height:44px;" src="https://api.dicebear.com/7.x/initials/svg?seed=<?php echo urlencode($member['name']); ?>&backgroundColor=1d5aa8" alt="">
      <?php echo e($member['name']); ?>'s Private Posts
      <a class="btn btn-sm btn-primary" style="margin-left:auto;" href="messages.php?with=<?php echo $member['id']; ?>">Message <?php echo e($member['name']); ?></a>
    </div>
    <div class="card notice-card" style="margin-bottom:16px;">
      <h4>🔒 Admin-only view</h4>
      <p><?php echo e($member['email']); ?> &bull; Joined <?php echo timeAgo($member['created_at']); ?>. These posts are visible only to you and <?php echo e($member['name']); ?> &mdash; no other member can see them.</p>
    </div>

    <?php if (empty($posts)): ?>
      <div class="card empty-state"><div class="icon">🖼️</div>This member hasn't posted anything yet.</div>
    <?php else: ?>
      <?php foreach ($posts as $post): ?>
        <?php $mediaUrl = ($post['post_type'] === 'legacy_private' ? '../post_media.php?id=' : '../private_media.php?id=') . (int)$post['id']; ?>
        <div class="card post-card">
          <div class="post-header">
            <img class="avatar" src="https://api.dicebear.com/7.x/initials/svg?seed=<?php echo urlencode($member['name']); ?>&backgroundColor=1d5aa8" alt="">
            <div class="who">
              <div class="name"><?php echo e($member['name']); ?></div>
              <div class="meta"><?php echo timeAgo($post['created_at']); ?></div>
            </div>
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
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
