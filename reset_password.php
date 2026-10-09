<?php
require_once __DIR__ . '/includes/init.php';

header('Referrer-Policy: no-referrer');
header('Cache-Control: no-store');

$token = trim($_POST['token'] ?? $_GET['token'] ?? '');
$errors = [];
$success = false;
$tokenHash = $token !== '' ? hash('sha256', $token) : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (!csrfCheck()) {
        $errors[] = 'Your session has expired. Refresh the page and try again.';
    } elseif (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirmPassword) {
        $errors[] = 'Passwords do not match.';
    } elseif ($tokenHash === '') {
        $errors[] = 'This reset link is invalid or has expired. Request a new link.';
    } else {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare('SELECT user_id FROM password_resets WHERE token_hash = ? AND expires_at > NOW() FOR UPDATE');
        $stmt->execute([$tokenHash]);
        $reset = $stmt->fetch();

        if (!$reset) {
            $pdo->rollBack();
            $errors[] = 'This reset link is invalid or has expired. Request a new link.';
        } else {
            $update = $pdo->prepare('UPDATE users SET password = ? WHERE id = ?');
            $update->execute([password_hash($password, PASSWORD_DEFAULT), $reset['user_id']]);
            $delete = $pdo->prepare('DELETE FROM password_resets WHERE user_id = ?');
            $delete->execute([$reset['user_id']]);
            $pdo->commit();
            $success = true;
        }
    }
}

$tokenValid = false;
if (!$success && $tokenHash !== '') {
  $stmt = $pdo->prepare('SELECT token_hash FROM password_resets WHERE token_hash = ? AND expires_at > NOW()');
    $stmt->execute([$tokenHash]);
    $tokenValid = (bool)$stmt->fetch();
}

$pageTitle = 'Reset Password - Chemical Connect';
$bodyClass = 'login-page';
$csrfToken = csrfToken();
require __DIR__ . '/includes/header.php';
?>
<main class="auth-wrap password-wrap">
  <section class="auth-card login-card" aria-labelledby="reset-title">
    <div class="brand-row">
      <p class="login-eyebrow">ACCOUNT RECOVERY</p>
      <h2 id="reset-title">Set a new password</h2>
      <p class="login-subtitle">Choose a password with at least 6 characters.</p>
    </div>

    <?php if ($success): ?>
      <div class="alert alert-success" role="status">Your password has been updated.</div>
      <div class="auth-switch"><a href="login.php">Continue to log in</a></div>
    <?php elseif (!$tokenValid): ?>
      <?php foreach ($errors as $error): ?>
        <div class="alert alert-error" role="alert"><?php echo e($error); ?></div>
      <?php endforeach; ?>
      <?php if (!$errors): ?>
        <div class="alert alert-error" role="alert">This reset link is invalid or has expired. Request a new link.</div>
      <?php endif; ?>
      <div class="auth-switch"><a href="forgot_password.php">Request a new reset link</a></div>
    <?php else: ?>
      <?php foreach ($errors as $error): ?>
        <div class="alert alert-error" role="alert"><?php echo e($error); ?></div>
      <?php endforeach; ?>
      <form method="POST" action="reset_password.php" class="login-form">
        <input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">
        <input type="hidden" name="token" value="<?php echo e($token); ?>">
        <div class="form-group">
          <label for="password">New password</label>
          <input type="password" id="password" name="password" required minlength="6" autocomplete="new-password" autofocus>
        </div>
        <div class="form-group">
          <label for="confirm_password">Confirm new password</label>
          <input type="password" id="confirm_password" name="confirm_password" required minlength="6" autocomplete="new-password">
        </div>
        <button type="submit" class="btn btn-primary btn-block login-submit">Update password</button>
      </form>
    <?php endif; ?>
  </section>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>