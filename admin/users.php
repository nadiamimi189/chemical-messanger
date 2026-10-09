<?php
require_once __DIR__ . '/../includes/init.php';
requireAdminRoot();

$stmt = $pdo->query("
    SELECT u.*,
           (SELECT COUNT(*) FROM user_posts up WHERE up.user_id = u.id)
           + (SELECT COUNT(*) FROM admin_posts ap WHERE ap.admin_id = u.id) AS post_count
    FROM users u
    WHERE u.role = 'user'
    ORDER BY u.created_at DESC
");
$members = $stmt->fetchAll();

$pageTitle = 'Members - Chemical Connect';
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
        <h1>Members</h1>
        <p>Community members and their private activity overview.</p>
      </div>
      <div class="header-badges">
        <span class="soft-pill success">● <?php echo count($members); ?> active</span>
      </div>
    </div>

    <section class="panel members-panel">
      <div class="panel-header">
        <h3>Community members</h3>
        <span class="panel-tag">Total: <?php echo count($members); ?></span>
      </div>

      <?php if (empty($members)): ?>
        <div class="empty-state"><div class="icon">👥</div>No members have signed up yet.</div>
      <?php else: ?>
        <div class="member-list">
          <?php foreach ($members as $m): ?>
            <div class="user-list-item">
              <img class="avatar" src="https://api.dicebear.com/7.x/initials/svg?seed=<?php echo urlencode($m['name']); ?>&backgroundColor=1d5aa8" alt="">
              <div class="info">
                <div class="uname"><?php echo e($m['name']); ?>
                  <?php if ($m['status'] === 'blocked'): ?><span class="badge-admin blocked">BLOCKED</span><?php endif; ?>
                </div>
                <div class="uemail"><?php echo e($m['email']); ?> &bull; Joined <?php echo timeAgo($m['created_at']); ?> &bull; <?php echo (int)$m['post_count']; ?> post(s)</div>
              </div>
              <div class="member-actions">
                <a class="btn btn-sm btn-outline" href="user_detail.php?id=<?php echo $m['id']; ?>">View Posts</a>
                <a class="btn btn-sm btn-primary" href="messages.php?with=<?php echo $m['id']; ?>">Message</a>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>
  </main>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
