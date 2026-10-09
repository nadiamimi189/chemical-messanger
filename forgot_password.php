<?php
require_once __DIR__ . '/includes/init.php';

if (isLoggedIn()) {
    header('Location: ' . (isAdmin() ? 'admin/dashboard.php' : 'index.php'));
    exit;
}

$errors = [];
$notice = $_SESSION['password_reset_notice'] ?? '';
unset($_SESSION['password_reset_notice']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (!csrfCheck()) {
        $errors[] = 'Your session has expired. Refresh the page and try again.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Enter a valid email address.';
    } else {
        $stmt = $pdo->prepare("SELECT id, name, email FROM users WHERE email = ? AND status = 'active'");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            $cooldown = $pdo->prepare(
                'SELECT 1 FROM password_resets WHERE user_id = ? AND created_at > DATE_SUB(NOW(), INTERVAL 60 SECOND) LIMIT 1'
            );
            $cooldown->execute([$user['id']]);

            if (!$cooldown->fetch()) {
                $token = bin2hex(random_bytes(32));
                $tokenHash = hash('sha256', $token);

                $pdo->beginTransaction();
                $delete = $pdo->prepare('DELETE FROM password_resets WHERE user_id = ?');
                $delete->execute([$user['id']]);
                $insert = $pdo->prepare(
                    'INSERT INTO password_resets (token_hash, user_id, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 1 HOUR))'
                );
                $insert->execute([$tokenHash, $user['id']]);
                $pdo->commit();

                try {
                    require_once __DIR__ . '/vendor/autoload.php';
                    $mailConfig = require __DIR__ . '/config/mail.php';
                    foreach (['host', 'username', 'password', 'from_email'] as $requiredSetting) {
                        if ($mailConfig[$requiredSetting] === '') {
                            throw new RuntimeException('Missing SMTP setting: ' . $requiredSetting);
                        }
                    }

                    $mailer = new PHPMailer\PHPMailer\PHPMailer(true);
                    $mailer->isSMTP();
                    $mailer->Host = $mailConfig['host'];
                    $mailer->SMTPAuth = true;
                    $mailer->Username = $mailConfig['username'];
                    $mailer->Password = $mailConfig['password'];
                    $mailer->Port = $mailConfig['port'];
                    $mailer->SMTPSecure = $mailConfig['encryption'] === 'ssl'
                        ? PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS
                        : PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                    $mailer->CharSet = 'UTF-8';
                    $mailer->setFrom($mailConfig['from_email'], $mailConfig['from_name']);
                    $mailer->addAddress($user['email'], $user['name']);
                    $mailer->isHTML(true);
                    $mailer->Subject = 'Reset your Chemical Connect password';

                    $resetUrl = $mailConfig['app_url'] . '/reset_password.php?token=' . urlencode($token);
                    $safeName = htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8');
                    $safeUrl = htmlspecialchars($resetUrl, ENT_QUOTES, 'UTF-8');
                    $mailer->Body = '<p>Hello ' . $safeName . ',</p>'
                        . '<p>Use the link below to choose a new Chemical Connect password. '
                        . 'This link expires in one hour and can only be used once.</p>'
                        . '<p><a href="' . $safeUrl . '">Reset your password</a></p>'
                        . '<p>If you did not request this, you can ignore this email.</p>';
                    $mailer->AltBody = "Hello " . $user['name'] . ",\n\n"
                        . "Reset your Chemical Connect password using this link (expires in one hour):\n"
                        . $resetUrl . "\n\nIf you did not request this, ignore this email.";
                    $mailer->send();
                } catch (Throwable $exception) {
                    error_log('Password reset email failed: ' . $exception->getMessage());
                    $delete = $pdo->prepare('DELETE FROM password_resets WHERE token_hash = ?');
                    $delete->execute([$tokenHash]);
                }
            }
        }

        $_SESSION['password_reset_notice'] = 'If an active account matches that email, a password reset link will be sent shortly.';
        header('Location: forgot_password.php');
        exit;
    }
}

$pageTitle = 'Forgot Password - Chemical Connect';
$bodyClass = 'login-page';
$csrfToken = csrfToken();
require __DIR__ . '/includes/header.php';
?>
<main class="auth-wrap password-wrap">
  <section class="auth-card login-card" aria-labelledby="forgot-title">
    <div class="brand-row">
      <p class="login-eyebrow">ACCOUNT RECOVERY</p>
      <h2 id="forgot-title">Forgot your password?</h2>
      <p class="login-subtitle">Enter your account email and we will send you a reset link.</p>
    </div>

    <?php if ($notice !== ''): ?>
      <div class="alert alert-success" role="status"><?php echo e($notice); ?></div>
    <?php endif; ?>
    <?php foreach ($errors as $error): ?>
      <div class="alert alert-error" role="alert"><?php echo e($error); ?></div>
    <?php endforeach; ?>

    <form method="POST" action="forgot_password.php" class="login-form">
      <input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">
      <div class="form-group">
        <label for="email">Email</label>
        <input type="email" id="email" name="email" required maxlength="150" autocomplete="email" autofocus>
      </div>
      <button type="submit" class="btn btn-primary btn-block login-submit">Send reset link</button>
    </form>
    <div class="auth-switch"><a href="login.php">Back to log in</a></div>
  </section>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>