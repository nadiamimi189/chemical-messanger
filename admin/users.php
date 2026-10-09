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
      <div>
        <strong>Chemical Ops</strong>
        <span>Control center</span>
      </div>
    </div>

    <nav class="admin-nav">
      <a class="admin-nav-item" href="dashboard.php"><span class="icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="m3.5 10 8.5-7 8.5 7v10a1 1 0 0 1-1 1h-5.3v-6.2h-4.4V21H4.5a1 1 0 0 1-1-1z"/></svg></span> Home</a>
      <a class="admin-nav-item" href="dashboard.php"><span class="icon" aria-hidden="true"><svg viewBox="0 0 24 24"><rect x="3.5" y="3.5" width="7" height="7" rx="1"/><rect x="13.5" y="3.5" width="7" height="7" rx="1"/><rect x="3.5" y="13.5" width="7" height="7" rx="1"/><rect x="13.5" y="13.5" width="7" height="7" rx="1"/></svg></span> Dashboard</a>
      <a class="admin-nav-item active" href="users.php"><span class="icon" aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="9" cy="8" r="3.5"/><path d="M2.8 20v-1.2a6.2 6.2 0 0 1 12.4 0V20zM16 5a3.5 3.5 0 0 1 0 6.8M18 14a4.8 4.8 0 0 1 3.2 4.6V20h-3"/></svg></span> Members</a>
      <a class="admin-nav-item" href="messages.php"><span class="icon" aria-hidden="true"><svg viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m4 7 8 6 8-6"/></svg></span> Messages</a>
      <a class="admin-nav-item" href="../index.php"><span class="icon" aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M3.5 12h17M12 3a14 14 0 0 1 0 18M12 3a14 14 0 0 0 0 18"/></svg></span> Community</a>
      <a class="admin-nav-item" href="../my_posts.php"><span class="icon" aria-hidden="true"><svg viewBox="0 0 24 24"><rect x="5" y="3.5" width="14" height="17" rx="1.5"/><path d="M8.5 8h7M8.5 12h7M8.5 16h4"/></svg></span> My Posts</a>
      <a class="admin-nav-item" href="products.php"><span class="icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="m12 3 8.5 4.5v9L12 21l-8.5-4.5v-9zM3.8 7.7 12 12l8.2-4.3M12 12v9"/></svg></span> Products</a>
      <a class="admin-nav-item" href="users.php"><span class="icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M4 20V11M10 20V5M16 20v-8M22 20H2"/></svg></span> Reports</a>
      <a class="admin-nav-item" href="users.php"><span class="icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M3 7h18l-1.5 13h-15zM7 7V4h10v3M9 11v5M15 11v5"/></svg></span> Orders</a>
      <a class="admin-nav-item" href="users.php"><span class="icon" aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="m19.4 15 .1.1 1.4 1.1-1.4 2.4-1.7-.7a8 8 0 0 1-1.8 1l-.3 1.8h-2.8l-.3-1.8a8 8 0 0 1-1.8-1l-1.7.7-1.4-2.4 1.4-1.1a7 7 0 0 1 0-2l-1.4-1.1 1.4-2.4 1.7.7a8 8 0 0 1 1.8-1l.3-1.8h2.8l.3 1.8a8 8 0 0 1 1.8 1l1.7-.7 1.4 2.4-1.4 1.1a7 7 0 0 1 0 2z"/></svg></span> Settings</a>
      <a class="admin-nav-item" href="../logout.php"><span class="icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M10 4H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h5M14 16l4-4-4-4M18 12H9"/></svg></span> Logout</a>
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
