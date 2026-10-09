<?php
require_once __DIR__ . '/includes/init.php';
requireUserOnly();

$userId = currentUserId();
$profilePhotoError = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_profile_photo') {
    if (!csrfCheck()) {
        $profilePhotoError = 'Invalid request, please try again.';
    } else {
        $photoType = $_POST['photo_type'] ?? '';
        $photoColumn = $photoType === 'avatar' ? 'avatar' : ($photoType === 'cover' ? 'cover_photo' : null);
        if ($photoColumn === null) {
            $profilePhotoError = 'Choose a valid profile photo option.';
        } else {
            try {
                $photoPath = handleProfilePhotoUpload('photo');
                $updatePhotoStmt = $pdo->prepare("UPDATE users SET {$photoColumn} = ? WHERE id = ?");
                $updatePhotoStmt->execute([$photoPath, $userId]);
                header('Location: my_posts.php?photo_updated=' . urlencode($photoType));
                exit;
            } catch (RuntimeException $e) {
                $profilePhotoError = $e->getMessage();
            }
        }
    }
}

$profileStmt = $pdo->prepare('SELECT avatar, cover_photo FROM users WHERE id = ?');
$profileStmt->execute([$userId]);
$profile = $profileStmt->fetch();
$avatarUrl = !empty($profile['avatar'])
    ? 'profile_media.php?id=' . (int)$userId . '&type=avatar'
    : 'https://api.dicebear.com/7.x/initials/svg?seed=' . urlencode(currentUserName()) . '&backgroundColor=1d5aa8';
$coverUrl = !empty($profile['cover_photo'])
    ? 'profile_media.php?id=' . (int)$userId . '&type=cover'
    : null;

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
    <?php if ($profilePhotoError): ?>
      <div class="alert alert-error"><?php echo e($profilePhotoError); ?></div>
    <?php elseif (isset($_GET['photo_updated'])): ?>
      <div class="alert alert-success"><?php echo $_GET['photo_updated'] === 'cover' ? 'Cover photo' : 'Profile photo'; ?> updated.</div>
    <?php endif; ?>
    <div class="profile-card-modern">
      <div class="profile-cover"<?php if ($coverUrl): ?> style="background-image:url('<?php echo e($coverUrl); ?>')"<?php endif; ?>></div>
      <form method="POST" enctype="multipart/form-data" class="profile-photo-form profile-cover-form">
        <input type="hidden" name="csrf_token" value="<?php echo e(csrfToken()); ?>">
        <input type="hidden" name="action" value="update_profile_photo">
        <input type="hidden" name="photo_type" value="cover">
        <label for="cover-photo-input">Change cover</label>
        <input type="file" id="cover-photo-input" name="photo" accept="image/jpeg,image/png,image/gif,image/webp" required>
        <button type="button" class="photo-adjust-open" hidden>Adjust</button>
        <button type="submit">Save</button>
      </form>
      <div class="profile-body">
        <img class="avatar-xl" src="<?php echo e($avatarUrl); ?>" alt="<?php echo e(currentUserName()); ?>">
        <form method="POST" enctype="multipart/form-data" class="profile-photo-form profile-avatar-form">
          <input type="hidden" name="csrf_token" value="<?php echo e(csrfToken()); ?>">
          <input type="hidden" name="action" value="update_profile_photo">
          <input type="hidden" name="photo_type" value="avatar">
          <label for="profile-photo-input">Change photo</label>
          <input type="file" id="profile-photo-input" name="photo" accept="image/jpeg,image/png,image/gif,image/webp" required>
          <button type="button" class="photo-adjust-open" hidden>Adjust</button>
          <button type="submit">Save</button>
        </form>
        <h2><?php echo e(currentUserName()); ?></h2>
        <span class="profile-role">Member</span>
      </div>
    </div>
    <dialog class="photo-adjust-dialog" id="photo-adjust-dialog" aria-labelledby="photo-adjust-title">
      <h2 id="photo-adjust-title">Adjust photo</h2>
      <p>Use the controls to position and zoom your photo before saving.</p>
      <canvas id="photo-adjust-preview" aria-label="Adjusted photo preview"></canvas>
      <label class="photo-adjust-control">Zoom
        <input type="range" id="photo-adjust-zoom" min="1" max="3" step="0.01" value="1">
      </label>
      <label class="photo-adjust-control">Horizontal position
        <input type="range" id="photo-adjust-x" min="-100" max="100" step="1" value="0">
      </label>
      <label class="photo-adjust-control">Vertical position
        <input type="range" id="photo-adjust-y" min="-100" max="100" step="1" value="0">
      </label>
      <p class="photo-adjust-error" id="photo-adjust-error" role="status" hidden></p>
      <div class="photo-adjust-actions">
        <button type="button" class="photo-adjust-cancel">Cancel</button>
        <button type="button" class="photo-adjust-apply">Use adjusted photo</button>
      </div>
    </dialog>

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
            <img class="avatar" src="<?php echo e($avatarUrl); ?>" alt="<?php echo e(currentUserName()); ?>">
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
