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
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/navbar.php';
?>
<div class="admin-shell">
  <aside class="admin-sidebar">
    <div class="admin-brand">
      <div class="admin-brand-icon">🧪</div>
      <div>
        <strong>Chemical Ops</strong>
        <span>Control center</span>
      </div>
    </div>

    <nav class="admin-nav">
      <a class="admin-nav-item" href="dashboard.php"><span class="icon">🏠</span> Home</a>
      <a class="admin-nav-item" href="dashboard.php"><span class="icon">📊</span> Dashboard</a>
      <a class="admin-nav-item active" href="users.php"><span class="icon">👥</span> Members</a>
      <a class="admin-nav-item" href="messages.php"><span class="icon">✉️</span> Messages</a>
      <a class="admin-nav-item" href="../index.php"><span class="icon">🌐</span> Community</a>
      <a class="admin-nav-item" href="../my_posts.php"><span class="icon">🖼️</span> My Posts</a>
      <a class="admin-nav-item" href="users.php"><span class="icon">📦</span> Products</a>
      <a class="admin-nav-item" href="users.php"><span class="icon">📈</span> Reports</a>
      <a class="admin-nav-item" href="users.php"><span class="icon">🧾</span> Orders</a>
      <a class="admin-nav-item" href="users.php"><span class="icon">⚙️</span> Settings</a>
      <a class="admin-nav-item" href="../logout.php"><span class="icon">⎋</span> Logout</a>
    </nav>

    <div class="admin-sidebar-footer">
      <div class="system-badge">
        <span>System status</span>
        <span class="indicator"></span>
      </div>
    </div>
  </aside>

  <main class="admin-main">
    <div class="dashboard-header">
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
