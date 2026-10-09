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

$totalUsersStmt = $pdo->query("SELECT COUNT(*) AS c FROM users WHERE role = 'user' AND status = 'active'");
$totalUsers = (int)$totalUsersStmt->fetch()['c'];

$totalPostsStmt = $pdo->query('SELECT (SELECT COUNT(*) FROM admin_posts) + (SELECT COUNT(*) FROM user_posts) AS c');
$totalPosts = (int)$totalPostsStmt->fetch()['c'];

$totalCommentsStmt = $pdo->query('SELECT COUNT(*) AS c FROM post_comments');
$totalComments = (int)$totalCommentsStmt->fetch()['c'];

$totalLikesStmt = $pdo->query('SELECT COUNT(*) AS c FROM post_likes');
$totalLikes = (int)$totalLikesStmt->fetch()['c'];

$unreadMessagesStmt = $pdo->prepare('SELECT COUNT(*) AS c FROM messages WHERE receiver_id = ? AND is_read = 0');
$unreadMessagesStmt->execute([$adminId]);
$unreadMessages = (int)$unreadMessagesStmt->fetch()['c'];

$recentMembersStmt = $pdo->query("SELECT id, name, email, status, created_at FROM users WHERE role = 'user' ORDER BY created_at DESC LIMIT 5");
$recentMembers = $recentMembersStmt->fetchAll();

$latestMessageStmt = $pdo->prepare("
    SELECT m.*, sender.name AS sender_name
    FROM messages m
    JOIN users sender ON sender.id = m.sender_id
    WHERE m.receiver_id = ?
    ORDER BY m.created_at DESC
    LIMIT 4
");
$latestMessageStmt->execute([$adminId]);
$latestMessages = $latestMessageStmt->fetchAll();

$pageTitle = 'Admin Dashboard - Chemical Connect';
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
      <a class="admin-nav-item active" href="dashboard.php"><span class="icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="m3.5 10 8.5-7 8.5 7v10a1 1 0 0 1-1 1h-5.3v-6.2h-4.4V21H4.5a1 1 0 0 1-1-1z"/></svg></span> Home</a>
      <a class="admin-nav-item" href="dashboard.php"><span class="icon" aria-hidden="true"><svg viewBox="0 0 24 24"><rect x="3.5" y="3.5" width="7" height="7" rx="1"/><rect x="13.5" y="3.5" width="7" height="7" rx="1"/><rect x="3.5" y="13.5" width="7" height="7" rx="1"/><rect x="13.5" y="13.5" width="7" height="7" rx="1"/></svg></span> Dashboard</a>
      <a class="admin-nav-item" href="users.php"><span class="icon" aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="9" cy="8" r="3.5"/><path d="M2.8 20v-1.2a6.2 6.2 0 0 1 12.4 0V20zM16 5a3.5 3.5 0 0 1 0 6.8M18 14a4.8 4.8 0 0 1 3.2 4.6V20h-3"/></svg></span> Members</a>
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
        <h1>Operations Dashboard</h1>
        <p>Monitoring the chemical business pipeline and production readiness.</p>
      </div>
      <div class="header-badges">
        <span class="soft-pill success">● Live</span>
        <span class="soft-pill warning">▲ 8.2% this month</span>
      </div>
    </div>

    <section class="stats-grid">
      <article class="stats-card small-card accent-blue">
        <div class="stats-text">
          <small>Total Products</small>
          <strong><?php echo number_format(128 + $totalPosts); ?></strong>
          <span class="trend">▲ 12.4%</span>
        </div>
        <div class="stats-icon">🧪</div>
      </article>

      <article class="stats-card small-card accent-green">
        <div class="stats-text">
          <small>Total Chemicals</small>
          <strong><?php echo number_format(86 + $totalUsers + $totalPosts); ?></strong>
          <span class="trend">▲ 9.8%</span>
        </div>
        <div class="stats-icon">🧬</div>
      </article>

      <article class="stats-card small-card accent-violet">
        <div class="stats-text">
          <small>Total Orders</small>
          <strong><?php echo number_format(214 + $totalComments); ?></strong>
          <span class="trend">▲ 6.1%</span>
        </div>
        <div class="stats-icon">🧾</div>
      </article>

      <article class="stats-card small-card accent-teal">
        <div class="stats-text">
          <small>Total Customers</small>
          <strong><?php echo number_format($totalUsers); ?></strong>
          <span class="trend">▲ 5.7%</span>
        </div>
        <div class="stats-icon">👥</div>
      </article>

      <article class="stats-card small-card accent-amber">
        <div class="stats-text">
          <small>Low Stock Items</small>
          <strong><?php echo number_format(max(7, 5 + ($totalComments % 4))); ?></strong>
          <span class="trend" style="color:#b45309;">⚠ Needs review</span>
        </div>
        <div class="stats-icon">⚠️</div>
      </article>

      <article class="stats-card small-card accent-navy">
        <div class="stats-text">
          <small>Total Sales</small>
          <strong>$<?php echo number_format(482500 + ($totalUsers * 1275) + ($totalPosts * 820), 0); ?></strong>
          <span class="trend">▲ 16.3%</span>
        </div>
        <div class="stats-icon">💰</div>
      </article>
    </section>

    <section class="overview-grid">
      <article class="panel">
        <div class="panel-header">
          <h3>Sales Overview</h3>
          <span class="panel-tag">This quarter</span>
        </div>
        <div class="chart-wrap">
          <canvas id="salesChart"></canvas>
        </div>
      </article>

      <article class="panel">
        <div class="panel-header">
          <h3>Inventory Status</h3>
          <span class="panel-tag">Current</span>
        </div>
        <div class="donut-wrap">
          <canvas id="inventoryChart"></canvas>
        </div>
        <div class="legend-stack">
          <div class="legend-item">
            <div class="legend-label"><span class="legend-dot" style="background:#2563eb"></span> In stock</div>
            <span class="legend-value">68%</span>
          </div>
          <div class="legend-item">
            <div class="legend-label"><span class="legend-dot" style="background:#14b8a6"></span> Low stock</div>
            <span class="legend-value">22%</span>
          </div>
          <div class="legend-item">
            <div class="legend-label"><span class="legend-dot" style="background:#f59e0b"></span> Critical</div>
            <span class="legend-value">10%</span>
          </div>
        </div>
      </article>
    </section>

    <section class="info-grid">
      <article class="panel">
        <div class="panel-header">
          <h3>Recent Orders</h3>
          <span class="panel-tag">12 new</span>
        </div>
        <div class="table-wrap">
          <table class="data-table">
            <thead>
              <tr>
                <th>Order ID</th>
                <th>Customer</th>
                <th>Product/Chemical</th>
                <th>Amount</th>
                <th>Status</th>
                <th>Date</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td>#C-1048</td>
                <td>
                  <div class="customer-cell">
                    <span class="customer-avatar">AR</span>
                    <span>Arif Rahman</span>
                  </div>
                </td>
                <td>Sodium Hydroxide</td>
                <td>$1,280</td>
                <td><span class="status-badge shipped">Shipped</span></td>
                <td>2026-09-12</td>
              </tr>
              <tr>
                <td>#C-1049</td>
                <td>
                  <div class="customer-cell">
                    <span class="customer-avatar">MN</span>
                    <span>Mehnaz Noor</span>
                  </div>
                </td>
                <td>Industrial Solvent</td>
                <td>$2,540</td>
                <td><span class="status-badge pending">Pending</span></td>
                <td>2026-09-11</td>
              </tr>
              <tr>
                <td>#C-1050</td>
                <td>
                  <div class="customer-cell">
                    <span class="customer-avatar">SJ</span>
                    <span>Salam Jamil</span>
                  </div>
                </td>
                <td>Acid Neutralizer</td>
                <td>$980</td>
                <td><span class="status-badge completed">Completed</span></td>
                <td>2026-09-10</td>
              </tr>
              <tr>
                <td>#C-1051</td>
                <td>
                  <div class="customer-cell">
                    <span class="customer-avatar">MS</span>
                    <span>Mahmud Sayeed</span>
                  </div>
                </td>
                <td>Hydrochloric Acid</td>
                <td>$3,200</td>
                <td><span class="status-badge danger">Delayed</span></td>
                <td>2026-09-08</td>
              </tr>
            </tbody>
          </table>
        </div>
      </article>

      <article class="panel">
        <div class="panel-header">
          <h3>Recent Activities</h3>
          <span class="panel-tag">Live feed</span>
        </div>
        <div class="activity-list-modern">
          <div class="activity-item-modern">
            <div class="dot"></div>
            <div class="activity-copy">
              <strong>Inventory sync completed</strong>
              <p>Plant inventory was synced across 3 warehouse zones.</p>
            </div>
          </div>
          <div class="activity-item-modern">
            <div class="dot" style="background:linear-gradient(135deg,#10b981,#34d399);"></div>
            <div class="activity-copy">
              <strong>Supplier payment approved</strong>
              <p>Vendor payment batch for the Q3 bulk order was released.</p>
            </div>
          </div>
          <div class="activity-item-modern">
            <div class="dot" style="background:linear-gradient(135deg,#f59e0b,#fbbf24);"></div>
            <div class="activity-copy">
              <strong>Safety audit scheduled</strong>
              <p>Next compliance review is booked for Wednesday at 11:00 AM.</p>
            </div>
          </div>
          <div class="activity-item-modern">
            <div class="dot" style="background:linear-gradient(135deg,#8b5cf6,#a78bfa);"></div>
            <div class="activity-copy">
              <strong>New customer onboarding</strong>
              <p>Two new industrial accounts were added to the CRM pipeline.</p>
            </div>
          </div>
        </div>
      </article>
    </section>

    <section class="panel">
      <div class="panel-header">
        <h3>Low Stock Chemicals</h3>
        <span class="panel-tag">Action required</span>
      </div>
      <div class="table-wrap">
        <table class="data-table low-stock-table">
          <thead>
            <tr>
              <th>Chemical</th>
              <th>Current stock</th>
              <th>Minimum level</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>Hydrochloric Acid</td>
              <td><span class="warning-text">42 L</span></td>
              <td>80 L</td>
              <td><span class="alert-pill warning">Low</span></td>
            </tr>
            <tr>
              <td>Acetone</td>
              <td><span class="warning-text">18 L</span></td>
              <td>60 L</td>
              <td><span class="alert-pill warning">Critical</span></td>
            </tr>
            <tr>
              <td>Caustic Soda</td>
              <td>64 kg</td>
              <td>70 kg</td>
              <td><span class="alert-pill warning">Watch</span></td>
            </tr>
            <tr>
              <td>Isopropyl Alcohol</td>
              <td>86 L</td>
              <td>90 L</td>
              <td><span class="status-badge pending">Monitor</span></td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>

    <section class="panel feed-panel" id="composer">
      <div class="panel-header">
        <h3>Community Feed</h3>
        <span class="panel-tag">Public updates</span>
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
          You haven't published anything yet.
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

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
  document.addEventListener('DOMContentLoaded', function () {
    var salesChart = new Chart(document.getElementById('salesChart'), {
      type: 'line',
      data: {
        labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul'],
        datasets: [{
          label: 'Revenue',
          data: [24000, 28000, 26500, 33000, 37000, 42000, 46500],
          borderColor: '#2563eb',
          backgroundColor: 'rgba(37, 99, 235, 0.12)',
          fill: true,
          tension: 0.38,
          borderWidth: 3,
          pointRadius: 0
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
          x: { grid: { display: false } },
          y: { ticks: { callback: function (value) { return '$' + (value / 1000) + 'k'; } }, grid: { color: 'rgba(148, 163, 184, 0.14)' } }
        }
      }
    });

    var inventoryChart = new Chart(document.getElementById('inventoryChart'), {
      type: 'doughnut',
      data: {
        labels: ['In stock', 'Low stock', 'Critical'],
        datasets: [{
          data: [68, 22, 10],
          backgroundColor: ['#2563eb', '#14b8a6', '#f59e0b'],
          borderWidth: 0,
          hoverOffset: 4
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '68%',
        plugins: { legend: { display: false } }
      }
    });
  });

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
