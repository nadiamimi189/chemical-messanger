<?php
require_once __DIR__ . '/../includes/init.php';
requireAdminRoot();

$adminId = (int)currentUserId();
$adminStmt = $pdo->prepare("SELECT id, name, email, password, avatar FROM users WHERE id = ? AND role = 'admin'");
$adminStmt->execute([$adminId]);
$admin = $adminStmt->fetch();

if (!$admin) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$saved = isset($_GET['saved']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $avatarPath = $admin['avatar'];

    if (!csrfCheck()) {
        $error = 'Your session has expired. Refresh the page and try again.';
    } elseif ($name === '' || strlen($name) > 100) {
        $error = 'Enter a name no longer than 100 characters.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 150) {
        $error = 'Enter a valid email address no longer than 150 characters.';
    } elseif ($newPassword !== '' && strlen($newPassword) < 8) {
        $error = 'New passwords must be at least 8 characters.';
    } elseif ($newPassword !== '' && $newPassword !== $confirmPassword) {
        $error = 'New passwords do not match.';
    } elseif ($newPassword !== '' && !password_verify($currentPassword, $admin['password'])) {
        $error = 'Enter your current password to change it.';
    } else {
        $emailCheck = $pdo->prepare('SELECT id FROM users WHERE email = ? AND id <> ?');
        $emailCheck->execute([$email, $adminId]);
        if ($emailCheck->fetch()) {
            $error = 'That email address is already used by another account.';
        }
    }

    if ($error === '') {
        try {
            if (!empty($_FILES['avatar']) && $_FILES['avatar']['error'] !== UPLOAD_ERR_NO_FILE) {
                $avatarPath = handleProfilePhotoUpload('avatar');
            }

            if ($newPassword !== '') {
                $update = $pdo->prepare('UPDATE users SET name = ?, email = ?, avatar = ?, password = ? WHERE id = ?');
                $update->execute([$name, $email, $avatarPath, password_hash($newPassword, PASSWORD_DEFAULT), $adminId]);
            } else {
                $update = $pdo->prepare('UPDATE users SET name = ?, email = ?, avatar = ? WHERE id = ?');
                $update->execute([$name, $email, $avatarPath, $adminId]);
            }

            $_SESSION['name'] = $name;
            header('Location: settings.php?saved=1');
            exit;
        } catch (RuntimeException $exception) {
            $error = $exception->getMessage();
        }
    }
}

$pageTitle = 'Admin Settings - Chemical Connect';
$assetPrefix = '../';
$activeAdminPage = 'settings';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/navbar.php';
?>
<div class="admin-shell">
  <?php require __DIR__ . '/../includes/admin_sidebar.php'; ?>
  <main class="admin-main admin-settings-main">
    <div class="dashboard-header">
      <button class="admin-sidebar-toggle" type="button" aria-controls="adminSidebar" aria-expanded="true" aria-label="Hide navigation" title="Hide navigation">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
      </button>
      <div>
        <h1>Admin settings</h1>
        <p>Manage your profile and account security.</p>
      </div>
    </div>

    <?php if ($saved): ?>
      <div class="alert alert-success" role="status">Your settings have been saved.</div>
    <?php endif; ?>
    <?php if ($error !== ''): ?>
      <div class="alert alert-error" role="alert"><?php echo e($error); ?></div>
    <?php endif; ?>

    <section class="panel admin-settings-panel" aria-labelledby="profile-settings-title">
      <div class="panel-header">
        <h2 id="profile-settings-title">Profile and security</h2>
      </div>
      <form class="admin-settings-form" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?php echo e(csrfToken()); ?>">
        <div class="admin-profile-photo-field">
          <?php if (!empty($admin['avatar'])): ?>
            <img class="admin-settings-avatar" src="../profile_media.php?id=<?php echo $adminId; ?>&amp;type=avatar&amp;v=<?php echo urlencode($admin['avatar']); ?>" alt="Current profile photo">
          <?php else: ?>
            <img class="admin-settings-avatar" src="https://api.dicebear.com/7.x/initials/svg?seed=<?php echo urlencode($admin['name']); ?>&backgroundColor=1d5aa8" alt="">
          <?php endif; ?>
          <label class="admin-settings-file">
            <span>Profile photo</span>
            <input type="file" name="avatar" accept="image/jpeg,image/png,image/gif,image/webp">
            <small>JPEG, PNG, GIF, or WebP. Maximum 5 MB.</small>
          </label>
        </div>

        <div class="admin-settings-fields">
          <label>
            <span>Display name</span>
            <input type="text" name="name" value="<?php echo e($_POST['name'] ?? $admin['name']); ?>" maxlength="100" required>
          </label>
          <label>
            <span>Email address</span>
            <input type="email" name="email" value="<?php echo e($_POST['email'] ?? $admin['email']); ?>" maxlength="150" autocomplete="email" required>
          </label>
        </div>

        <div class="admin-password-fields">
          <h3>Change password</h3>
          <p>Leave these fields empty to keep your current password.</p>
          <label>
            <span>Current password</span>
            <input type="password" name="current_password" autocomplete="current-password">
          </label>
          <label>
            <span>New password</span>
            <input type="password" name="new_password" minlength="8" autocomplete="new-password">
          </label>
          <label>
            <span>Confirm new password</span>
            <input type="password" name="confirm_password" minlength="8" autocomplete="new-password">
          </label>
        </div>

        <button class="btn btn-primary" type="submit">Save settings</button>
      </form>
    </section>
  </main>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>